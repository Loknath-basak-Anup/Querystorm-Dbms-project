<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');

$buyerId = get_user_id() ?? 0;
$successMessage = '';
$errorMessage = '';

// Fetch all conversations for this buyer
$conversations = db_fetch_all(
    "SELECT
        c.conversation_id,
        c.created_at,
        u.user_id   AS seller_id,
        COALESCE(sp.shop_name, u.full_name) AS seller_name,
        (
            SELECT m.message_text
            FROM messages m
            WHERE m.conversation_id = c.conversation_id
            ORDER BY m.created_at DESC
            LIMIT 1
        ) AS last_message,
        (
            SELECT m.created_at
            FROM messages m
            WHERE m.conversation_id = c.conversation_id
            ORDER BY m.created_at DESC
            LIMIT 1
        ) AS last_message_at
     FROM conversations c
     INNER JOIN users u ON u.user_id = c.seller_id
     LEFT JOIN seller_profiles sp ON sp.seller_id = u.user_id
     WHERE c.buyer_id = ?
     ORDER BY COALESCE(last_message_at, c.created_at) DESC",
    [$buyerId]
);

$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
if ($conversationId === 0 && !empty($conversations)) {
    $conversationId = (int)$conversations[0]['conversation_id'];
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'send_message') {
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $messageText    = trim($_POST['message_text'] ?? '');

        if ($conversationId <= 0 || $messageText === '') {
            $errorMessage = 'Please select a conversation and type a message.';
        } else {
            // Ensure conversation belongs to this buyer
            $conv = db_fetch(
                'SELECT conversation_id FROM conversations WHERE conversation_id = ? AND buyer_id = ? LIMIT 1',
                [$conversationId, $buyerId]
            );
            if (!$conv) {
                $errorMessage = 'Invalid conversation selected.';
            } else {
                try {
                    db_execute(
                        'INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)',
                        [$conversationId, $buyerId, $messageText]
                    );
                    $successMessage = 'Message sent.';
                    header('Location: buyer_chat_to_seller.php?conversation_id=' . $conversationId);
                    exit;
                } catch (Exception $e) {
                    $errorMessage = 'Could not send message. Please try again.';
                }
            }
        }
    }
}

$messages = [];
$currentSellerName = '';
if ($conversationId > 0) {
    $headerRow = db_fetch(
        'SELECT COALESCE(sp.shop_name, u.full_name) AS seller_name
         FROM conversations c
         INNER JOIN users u ON u.user_id = c.seller_id
         LEFT JOIN seller_profiles sp ON sp.seller_id = u.user_id
         WHERE c.conversation_id = ? AND c.buyer_id = ?
         LIMIT 1',
        [$conversationId, $buyerId]
    );
    if ($headerRow) {
        $currentSellerName = $headerRow['seller_name'] ?? '';
        $messages = db_fetch_all(
            'SELECT m.message_id, m.sender_id, m.message_text, m.created_at, u.full_name AS sender_name
             FROM messages m
             INNER JOIN users u ON u.user_id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC',
            [$conversationId]
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; flex-direction:row; min-height:100vh; margin:0; }
        main.main-content { margin-left:280px; width:calc(100% - 280px); transition:margin-left 0.3s ease, width 0.3s ease; display:flex; flex-direction:column; min-height:100vh; }
        body:has(.sidebar.collapsed) main.main-content { margin-left:80px; width:calc(100% - 80px); }
        .page-content { flex:1; }
        .chat-layout { display:grid; grid-template-columns:280px 1fr; gap:1rem; height:calc(100vh - 140px); }
        .chat-sidebar { background:rgba(15,23,42,0.9); border-radius:16px; padding:0.75rem; overflow-y:auto; }
        .chat-conversation { border-radius:16px; padding:1rem; background:linear-gradient(135deg, rgba(15,23,42,0.95), rgba(30,64,175,0.5)); display:flex; flex-direction:column; height:100%; }
        .conv-item { padding:0.5rem 0.75rem; border-radius:10px; cursor:pointer; margin-bottom:0.25rem; display:flex; flex-direction:column; gap:0.15rem; }
        .conv-item.active { background:rgba(59,130,246,0.4); }
        .conv-name { font-weight:500; font-size:0.9rem; }
        .conv-preview { font-size:0.75rem; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .messages-list { flex:1; overflow-y:auto; padding-right:0.25rem; margin-bottom:0.5rem; }
        .message-row { margin-bottom:0.4rem; display:flex; }
        .message-bubble { max-width:70%; padding:0.45rem 0.7rem; border-radius:14px; font-size:0.85rem; line-height:1.3; }
        .message-self { margin-left:auto; justify-content:flex-end; }
        .message-self .message-bubble { background:rgba(59,130,246,0.9); color:#e5e7eb; border-bottom-right-radius:4px; }
        .message-other .message-bubble { background:rgba(15,23,42,0.9); color:#e5e7eb; border-bottom-left-radius:4px; border:1px solid rgba(148,163,184,0.4); }
        .message-meta { font-size:0.7rem; color:var(--text-secondary); margin-top:0.1rem; }
        .chat-input-bar { display:flex; gap:0.5rem; margin-top:0.25rem; }
        .chat-input-bar textarea { flex:1; resize:none; min-height:40px; max-height:80px; border-radius:999px; padding:0.5rem 0.75rem; border:none; outline:none; font-size:0.85rem; background:rgba(15,23,42,0.9); color:#e5e7eb; }
        .chat-input-bar button { border-radius:999px; padding:0.5rem 1rem; border:none; cursor:pointer; display:flex; align-items:center; gap:0.25rem; background:var(--primary-color); color:white; font-size:0.85rem; }
        .chat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }
        .chat-header h2 { font-size:1rem; margin:0; display:flex; align-items:center; gap:0.4rem; }
        .chat-empty { flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-size:0.9rem; text-align:center; padding:1rem; }
    </style>
</head>
<body class="dark-mode">
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'buyer');
    </script>
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadSidebar() {
                const response = await fetch('../html/leftsidebar.php');
                const html = await response.text();
                document.getElementById('sidebarContainer').innerHTML = html;
                const scripts = document.getElementById('sidebarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
            }
            loadSidebar();
            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-comments"></i> Messages';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                },50);
            }
            loadNavbar();
        </script>
        <div class="container page-content">
            <?php if ($successMessage): ?>
                <div style="margin-bottom:0.75rem;padding:0.6rem 0.9rem;border-radius:8px;background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.5);font-size:0.85rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php elseif ($errorMessage): ?>
                <div style="margin-bottom:0.75rem;padding:0.6rem 0.9rem;border-radius:8px;background:rgba(239,68,68,0.12);color:#fecaca;border:1px solid rgba(239,68,68,0.5);font-size:0.85rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            <div class="chat-layout">
                <aside class="chat-sidebar">
                    <h3 style="font-size:0.9rem;margin:0 0 0.5rem 0;color:#e5e7eb;display:flex;align-items:center;gap:0.3rem;"><i class="fas fa-store"></i> Sellers</h3>
                    <?php if (empty($conversations)): ?>
                        <p style="font-size:0.8rem;color:var(--text-secondary);margin:0.3rem 0;">No conversations yet. Here is a preview of how your chats will look when you message sellers.</p>
                        <div class="conv-item active">
                            <div class="conv-name">UrbanStyle Fashion</div>
                            <div class="conv-preview">"Hi! Your order is packed and will be shipped today."</div>
                        </div>
                        <div class="conv-item">
                            <div class="conv-name">TechHub Gadgets</div>
                            <div class="conv-preview">"Let us know if the headphones are working well for you."</div>
                        </div>
                        <div class="conv-item">
                            <div class="conv-name">HomeLiving Store</div>
                            <div class="conv-preview">"We have applied an extra protective wrap for safe delivery."</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <?php $isActive = ((int)$conv['conversation_id'] === $conversationId); ?>
                            <a href="buyer_chat_to_seller.php?conversation_id=<?php echo (int)$conv['conversation_id']; ?>" style="text-decoration:none;color:inherit;">
                                <div class="conv-item <?php echo $isActive ? 'active' : ''; ?>">
                                    <div class="conv-name"><?php echo htmlspecialchars($conv['seller_name']); ?></div>
                                    <?php if (!empty($conv['last_message'])): ?>
                                        <div class="conv-preview"><?php echo htmlspecialchars($conv['last_message']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </aside>
                <section class="chat-conversation">
                    <?php if (empty($conversations)): ?>
                        <div class="chat-header">
                            <h2><i class="fas fa-store"></i> UrbanStyle Fashion</h2>
                        </div>
                        <div class="messages-list">
                            <div class="message-row message-other">
                                <div class="message-bubble">
                                    <div>Hi! Thank you for your order. Weve just packed your items and they will be handed over to the courier shortly.</div>
                                    <div class="message-meta">Today, 10:18</div>
                                </div>
                            </div>
                            <div class="message-row message-self">
                                <div class="message-bubble">
                                    <div>Great, thanks for the update! Will I get a tracking ID?</div>
                                    <div class="message-meta">Today, 10:20</div>
                                </div>
                            </div>
                            <div class="message-row message-other">
                                <div class="message-bubble">
                                    <div>Yes, of course. As soon as the parcel is picked up, well send your tracking number here.</div>
                                    <div class="message-meta">Today, 10:22</div>
                                </div>
                            </div>
                            <div class="message-row message-self">
                                <div class="message-bubble">
                                    <div>Perfect, Im really excited to receive it.</div>
                                    <div class="message-meta">Today, 10:23</div>
                                </div>
                            </div>
                        </div>
                        <div class="chat-empty" style="padding-top:0.5rem;align-items:flex-start;">
                            <div>
                                <p style="margin:0 0 0.25rem 0;">These are sample messages to show how your chats with sellers will look.</p>
                                <p style="margin:0;font-size:0.8rem;color:var(--text-secondary);">Once you contact a seller from a product page, your real conversation will appear here.</p>
                            </div>
                        </div>
                    <?php elseif ($conversationId <= 0 || !$currentSellerName): ?>
                        <div class="chat-empty">
                            <div>
                                <i class="fas fa-comments" style="font-size:1.8rem;margin-bottom:0.5rem;"></i>
                                <p>Select a seller from the left to view and send messages.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="chat-header">
                            <h2><i class="fas fa-store"></i> <?php echo htmlspecialchars($currentSellerName); ?></h2>
                        </div>
                        <div class="messages-list">
                            <?php if (empty($messages)): ?>
                                <p class="chat-empty" style="margin:0;">No messages yet. Start the conversation below.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <?php $isSelf = ((int)$msg['sender_id'] === $buyerId); ?>
                                    <div class="message-row <?php echo $isSelf ? 'message-self' : 'message-other'; ?>">
                                        <div class="message-bubble">
                                            <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                            <div class="message-meta"><?php echo htmlspecialchars(date('M d, H:i', strtotime($msg['created_at']))); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="buyer_chat_to_seller.php?conversation_id=<?php echo (int)$conversationId; ?>">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="conversation_id" value="<?php echo (int)$conversationId; ?>">
                            <div class="chat-input-bar">
                                <textarea name="message_text" placeholder="Type your message..." required></textarea>
                                <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script>
        async function loadFooter(){
            try {
                const r = await fetch('../html/footer.php');
                const h = await r.text();
                document.getElementById('footerContainer').innerHTML = h;
            } catch(e) {
                console.error('Error loading footer:', e);
            }
        }
        loadFooter();
    </script>
</body>
</html>

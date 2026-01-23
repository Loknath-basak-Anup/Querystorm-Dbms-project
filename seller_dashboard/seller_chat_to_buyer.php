<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('seller');

$sellerId = get_user_id() ?? 0;
$successMessage = '';
$errorMessage = '';

// Fetch all conversations for this seller
$conversations = db_fetch_all(
    "SELECT
        c.conversation_id,
        c.created_at,
        u.user_id   AS buyer_id,
        u.full_name AS buyer_name,
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
     INNER JOIN users u ON u.user_id = c.buyer_id
     WHERE c.seller_id = ?
     ORDER BY COALESCE(last_message_at, c.created_at) DESC",
    [$sellerId]
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
            // Ensure conversation belongs to this seller
            $conv = db_fetch(
                'SELECT conversation_id FROM conversations WHERE conversation_id = ? AND seller_id = ? LIMIT 1',
                [$conversationId, $sellerId]
            );
            if (!$conv) {
                $errorMessage = 'Invalid conversation selected.';
            } else {
                try {
                    db_execute(
                        'INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)',
                        [$conversationId, $sellerId, $messageText]
                    );
                    $successMessage = 'Message sent.';
                    header('Location: seller_chat_to_buyer.php?conversation_id=' . $conversationId);
                    exit;
                } catch (Exception $e) {
                    $errorMessage = 'Could not send message. Please try again.';
                }
            }
        }
    }
}

$messages = [];
$currentBuyerName = '';
if ($conversationId > 0) {
    $headerRow = db_fetch(
        'SELECT u.full_name AS buyer_name
         FROM conversations c
         INNER JOIN users u ON u.user_id = c.buyer_id
         WHERE c.conversation_id = ? AND c.seller_id = ?
         LIMIT 1',
        [$conversationId, $sellerId]
    );
    if ($headerRow) {
        $currentBuyerName = $headerRow['buyer_name'] ?? '';
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
    <title>Seller Messages | QuickMart</title>
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
        localStorage.setItem('userRole', 'seller');
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
                    <h3 style="font-size:0.9rem;margin:0 0 0.5rem 0;color:#e5e7eb;display:flex;align-items:center;gap:0.3rem;"><i class="fas fa-users"></i> Buyers</h3>
                    <?php if (empty($conversations)): ?>
                        <div class="conv-item active">
                            <div class="conv-name">Ayesha Rahman</div>
                            <div class="conv-preview">Hi, is the 128GB variant still available?</div>
                        </div>
                        <div class="conv-item">
                            <div class="conv-name">Imran Hossain</div>
                            <div class="conv-preview">Can you deliver to Dhanmondi by Friday evening?</div>
                        </div>
                        <div class="conv-item">
                            <div class="conv-name">Riya Akter</div>
                            <div class="conv-preview">Thanks, just received the headphones. Quality is great!</div>
                        </div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);margin:0.5rem 0 0;">Real buyer chats will appear here automatically once customers message your products.</p>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <?php $isActive = ((int)$conv['conversation_id'] === $conversationId); ?>
                            <a href="seller_chat_to_buyer.php?conversation_id=<?php echo (int)$conv['conversation_id']; ?>" style="text-decoration:none;color:inherit;">
                                <div class="conv-item <?php echo $isActive ? 'active' : ''; ?>">
                                    <div class="conv-name"><?php echo htmlspecialchars($conv['buyer_name']); ?></div>
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
                            <h2><i class="fas fa-user"></i> Ayesha Rahman</h2>
                        </div>
                        <div class="messages-list">
                            <div class="message-row message-other">
                                <div class="message-bubble">
                                    <div>Hi, is the 128GB variant of the Redmi Note 12 still available?</div>
                                    <div class="message-meta">Today, 10:24</div>
                                </div>
                            </div>
                            <div class="message-row message-self">
                                <div class="message-bubble">
                                    <div>Yes, it is available in both blue and black.</div>
                                    <div class="message-meta">Today, 10:26</div>
                                </div>
                            </div>
                            <div class="message-row message-other">
                                <div class="message-bubble">
                                    <div>Great! How long does delivery to Mirpur usually take?</div>
                                    <div class="message-meta">Today, 10:27</div>
                                </div>
                            </div>
                            <div class="message-row message-self">
                                <div class="message-bubble">
                                    <div>Inside Dhaka it usually takes 1–2 working days.</div>
                                    <div class="message-meta">Today, 10:29</div>
                                </div>
                            </div>
                            <div class="message-row message-other">
                                <div class="message-bubble">
                                    <div>Okay, I will place the order tonight. Thanks!</div>
                                    <div class="message-meta">Today, 10:31</div>
                                </div>
                            </div>
                        </div>
                        <div class="chat-empty" style="justify-content:flex-start;align-items:flex-start;padding-top:0.25rem;">
                            <p style="margin:0;font-size:0.8rem;color:var(--text-secondary);">This is a sample conversation so you can see how chats will look. Once buyers contact you, real messages will be shown here.</p>
                        </div>
                    <?php elseif ($conversationId <= 0 || !$currentBuyerName): ?>
                        <div class="chat-empty">
                            <div>
                                <i class="fas fa-comments" style="font-size:1.8rem;margin-bottom:0.5rem;"></i>
                                <p>Select a buyer from the left to view and reply to messages.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="chat-header">
                            <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($currentBuyerName); ?></h2>
                        </div>
                        <div class="messages-list">
                            <?php if (empty($messages)): ?>
                                <p class="chat-empty" style="margin:0;">No messages yet. Start the conversation below.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <?php $isSelf = ((int)$msg['sender_id'] === $sellerId); ?>
                                    <div class="message-row <?php echo $isSelf ? 'message-self' : 'message-other'; ?>">
                                        <div class="message-bubble">
                                            <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                            <div class="message-meta"><?php echo htmlspecialchars(date('M d, H:i', strtotime($msg['created_at']))); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="seller_chat_to_buyer.php?conversation_id=<?php echo (int)$conversationId; ?>">
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

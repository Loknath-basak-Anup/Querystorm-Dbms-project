<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('seller');

$sellerId = get_user_id() ?? 0;
$walletBalance = 0;
$totalReceived = 0;
$totalSpent = 0;
$transactions = [];
$flashMessage = '';
$flashType = 'success';

// Ensure wallet_transactions table exists so the wallet works even on fresh databases
try {
    db_query(
        "CREATE TABLE IF NOT EXISTS wallet_transactions (
            txn_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            txn_type VARCHAR(30) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            note VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (txn_id),
            KEY idx_wallet_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
} catch (Exception $e) {
    error_log('Failed to ensure wallet_transactions table exists: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_money') {
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? '');
        if ($amount > 0) {
            $note = 'Add Money via ' . ($method ?: 'unspecified');
            try {
                db_execute(
                    "INSERT INTO wallet_transactions (user_id, txn_type, amount, note) VALUES (?, 'deposit', ?, ?)",
                    [$sellerId, $amount, $note]
                );
                $flashMessage = 'Money added successfully.';
                $flashType = 'success';
            } catch (Exception $e) {
                error_log('Seller add money failed: ' . $e->getMessage());
                $flashMessage = 'Could not add money right now. Please try again.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'Please enter a valid amount greater than zero.';
            $flashType = 'error';
        }
    } elseif ($action === 'withdraw') {
        $amount = (float)($_POST['amount'] ?? 0);
        $bankAccount = trim($_POST['bank_account'] ?? '');

        $currentBalanceRow = db_fetch(
            "SELECT COALESCE(SUM(
                CASE
                    WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
                    WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
                    ELSE amount
                END
            ), 0) AS balance
             FROM wallet_transactions
             WHERE user_id = ?",
            [$sellerId]
        );
        $currentBalance = (float)($currentBalanceRow['balance'] ?? 0);

        if ($amount > 0 && $amount <= $currentBalance) {
            $note = 'Withdrawal to account: ' . ($bankAccount ?: 'unspecified');
            try {
                db_execute(
                    "INSERT INTO wallet_transactions (user_id, txn_type, amount, note) VALUES (?, 'withdraw', ?, ?)",
                    [$sellerId, $amount, $note]
                );
                $flashMessage = 'Withdrawal request submitted successfully.';
                $flashType = 'success';
            } catch (Exception $e) {
                error_log('Seller withdrawal failed: ' . $e->getMessage());
                $flashMessage = 'Could not process withdrawal right now. Please try again.';
                $flashType = 'error';
            }
        } elseif ($amount > $currentBalance) {
            $flashMessage = 'Insufficient balance for this withdrawal.';
            $flashType = 'error';
        } else {
            $flashMessage = 'Please enter a valid amount greater than zero.';
            $flashType = 'error';
        }
    }
}

try {
    $walletRow = db_fetch(
        "SELECT COALESCE(SUM(
            CASE
                WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
                WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
                ELSE amount
            END
        ), 0) AS balance
         FROM wallet_transactions
         WHERE user_id = ?",
        [$sellerId]
    );
    $walletBalance = (float)($walletRow['balance'] ?? 0);
    if ($walletBalance < 0) {
        $walletBalance = 0;
    }

    $transactions = db_fetch_all(
        "SELECT
            txn_id AS transaction_id,
            txn_type,
            amount,
            created_at,
            note AS description
         FROM wallet_transactions
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 20",
        [$sellerId]
    );
    if (!is_array($transactions)) {
        $transactions = [];
    }

    $receivedRow = db_fetch(
        "SELECT COALESCE(SUM(amount), 0) AS total_received
         FROM wallet_transactions
         WHERE user_id = ? AND txn_type IN ('credit','deposit','topup','refund')",
        [$sellerId]
    );
    $totalReceived = (float)($receivedRow['total_received'] ?? 0);

    $spentRow = db_fetch(
        "SELECT COALESCE(SUM(amount), 0) AS total_spent
         FROM wallet_transactions
         WHERE user_id = ? AND txn_type IN ('debit','purchase','withdraw')",
        [$sellerId]
    );
    $totalSpent = (float)($spentRow['total_spent'] ?? 0);
    $netChange = $totalReceived - $totalSpent;
} catch (Exception $e) {
    error_log("Seller wallet error: " . $e->getMessage());
    $walletBalance = 0;
    $totalReceived = 0;
    $totalSpent = 0;
    $transactions = [];
}

function get_txn_type_label(string $type): string {
    $labels = [
        'credit' => 'Credit',
        'debit' => 'Debit',
        'deposit' => 'Deposit',
        'withdraw' => 'Withdrawal',
        'purchase' => 'Purchase',
        'refund' => 'Refund',
        'topup' => 'Top Up'
    ];
    return $labels[strtolower($type)] ?? ucfirst($type);
}

function get_txn_color(string $type): string {
    $colors = [
        'credit' => '#10b981',
        'deposit' => '#10b981',
        'topup' => '#10b981',
        'refund' => '#10b981',
        'debit' => '#ef4444',
        'withdraw' => '#ef4444',
        'purchase' => '#f59e0b'
    ];
    return $colors[strtolower($type)] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seller Wallet | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/wallet.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
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
            async function loadNavbar(){ const r=await fetch('../html/navbar.php'); const h=await r.text(); document.getElementById('navbarContainer').innerHTML=h; const scripts=document.getElementById('navbarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); const pageTitle=document.querySelector('.page-title-navbar'); if(pageTitle) pageTitle.innerHTML = '<i class="fas fa-wallet"></i> Seller Wallet'; setTimeout(()=>{ if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal(); },50);} loadNavbar();
        </script>
        <script>
            async function loadSidebar(){ const r=await fetch('../html/leftsidebar.php'); const h=await r.text(); document.getElementById('sidebarContainer').innerHTML=h; const scripts=document.getElementById('sidebarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); } loadSidebar();
        </script>
        <div class="page-content" style="padding: 1.5rem;">
            <div style="margin-bottom: 1.5rem;">
                <a href="./seller_dashboard.php" style="color: #3b82f6; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <?php if (!empty($flashMessage)): ?>
                <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid <?php echo $flashType === 'success' ? '#10b981' : '#ef4444'; ?>; background: <?php echo $flashType === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color: <?php echo $flashType === 'success' ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>
            <div class="wallet-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="wallet-balance-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; padding: 2rem; color: white; box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);" data-aos="fade-up">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem;">
                        <div>
                            <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Available Balance</p>
                            <h1 style="margin: 0.5rem 0 0 0; font-size: 2.5rem; font-weight: 700;">BDT <?php echo number_format($walletBalance, 2); ?></h1>
                        </div>
                        <i class="fas fa-wallet" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button id="addMoneyBtn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; font-weight: 600;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fas fa-plus"></i> Add Money
                        </button>
                        <button id="withdrawBtn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; font-weight: 600;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fas fa-arrow-up"></i> Withdraw
                        </button>
                    </div>
                </div>
                <div style="background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155;" data-aos="fade-up">
                    <h3 style="margin: 0 0 1.5rem 0; color: #e2e8f0;"><i class="fas fa-info-circle"></i> Wallet Info</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid #334155;">
                            <span style="color: #94a3b8;">Total Received</span>
                            <span style="color: #10b981; font-weight: 600;">+BDT <?php echo number_format($totalReceived, 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid #334155;">
                            <span style="color: #94a3b8;">Total Withdrawn/Spent</span>
                            <span style="color: #ef4444; font-weight: 600;">-BDT <?php echo number_format($totalSpent, 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid #334155;">
                            <span style="color: #94a3b8;">Net Change</span>
                            <?php $netPositive = $netChange >= 0; ?>
                            <span style="font-weight: 600; color: <?php echo $netPositive ? '#10b981' : '#ef4444'; ?>;">
                                <?php echo $netPositive ? '+' : '-'; ?>BDT <?php echo number_format(abs($netChange), 2); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #94a3b8;">Account Status</span>
                            <span style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155;" data-aos="fade-up">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #334155; padding-bottom: 1rem;">
                    <h3 style="margin: 0; color: #e2e8f0;"><i class="fas fa-history"></i> Transaction History</h3>
                    <span style="color: #94a3b8; font-size: 0.9rem;">&nbsp;<?php echo count($transactions); ?> transactions</span>
                </div>

                <?php if (empty($transactions)): ?>
                    <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>
                        <p>No transactions yet</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-y: auto; max-height: 400px;">
                        <?php foreach ($transactions as $txn): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #0f172a; border-radius: 8px; margin-bottom: 0.75rem; border-left: 4px solid <?php echo get_txn_color($txn['txn_type']); ?>">
                                <div style="flex: 1;">
                                    <p style="margin: 0; color: #e2e8f0; font-weight: 600;">&nbsp;<?php echo htmlspecialchars(get_txn_type_label($txn['txn_type'])); ?></p>
                                    <p style="margin: 0.25rem 0 0 0; color: #94a3b8; font-size: 0.85rem;">&nbsp;<?php echo htmlspecialchars($txn['description'] ?? 'No description'); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="margin: 0; color: <?php echo get_txn_color($txn['txn_type']); ?>; font-weight: 700; font-size: 1.1rem;">
                                        <?php echo (in_array(strtolower($txn['txn_type']), ['credit', 'deposit', 'topup', 'refund']) ? '+' : '-'); ?>BDT <?php echo number_format(abs($txn['amount']), 2); ?>
                                    </p>
                                    <p style="margin: 0.25rem 0 0 0; color: #94a3b8; font-size: 0.85rem;">&nbsp;<?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($txn['created_at']))); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>

    <!-- Add Money Modal -->
    <div id="addMoneyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%; border: 1px solid #334155;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; color: #e2e8f0;"><i class="fas fa-plus"></i> Add Money</h2>
                <button onclick="document.getElementById('addMoneyModal').style.display='none'" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">×</button>
            </div>
            <form method="post" action="wallet.php">
                <input type="hidden" name="action" value="add_money">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #94a3b8; margin-bottom: 0.5rem; font-weight: 500;">Amount (BDT)</label>
                    <input type="number" name="amount" id="addAmount" placeholder="Enter amount" min="1" step="0.01" required style="width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; font-size: 1rem;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; color: #94a3b8; margin-bottom: 0.5rem; font-weight: 500;">Payment Method</label>
                    <select name="payment_method" id="paymentMethod" style="width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; font-size: 1rem;">
                        <option value="card">Credit/Debit Card</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="mobile">Mobile Banking</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="document.getElementById('addMoneyModal').style.display='none'" style="flex: 1; background: #334155; color: #e2e8f0; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" style="flex: 1; background: #10b981; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Add Money</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdrawModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%; border: 1px solid #334155;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; color: #e2e8f0;"><i class="fas fa-arrow-up"></i> Withdraw</h2>
                <button onclick="document.getElementById('withdrawModal').style.display='none'" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">×</button>
            </div>
            <form method="post" action="wallet.php">
                <input type="hidden" name="action" value="withdraw">
                <div style="margin-bottom: 1rem; padding: 1rem; background: #0f172a; border-radius: 6px; border-left: 4px solid #4facfe;">
                    <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">Available Balance</p>
                    <p style="margin: 0.5rem 0 0 0; color: #10b981; font-weight: 700; font-size: 1.25rem;">BDT <?php echo number_format($walletBalance, 2); ?></p>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: #94a3b8; margin-bottom: 0.5rem; font-weight: 500;">Withdrawal Amount (BDT)</label>
                    <input type="number" name="amount" id="withdrawAmount" placeholder="Enter amount" min="1" max="<?php echo $walletBalance; ?>" step="0.01" required style="width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; font-size: 1rem;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; color: #94a3b8; margin-bottom: 0.5rem; font-weight: 500;">Bank Account</label>
                    <select name="bank_account" id="bankAccount" style="width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; font-size: 1rem;" required>
                        <option value="">Select bank account</option>
                        <option value="bank_01">Main Savings Account (...4521)</option>
                        <option value="bank_02">Business Account (...8874)</option>
                        <option value="new">Add New Bank Account</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="document.getElementById('withdrawModal').style.display='none'" style="flex: 1; background: #334155; color: #e2e8f0; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" style="flex: 1; background: #ef4444; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Withdraw</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        if (typeof AOS !== 'undefined') { AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 }); }
        async function loadFooter(){ try{ const r=await fetch('../html/footer.php'); const h=await r.text(); document.getElementById('footerContainer').innerHTML=h; }catch(e){ console.error('Error loading footer:', e); } }
        loadFooter();

        // Add Money Button
        document.getElementById('addMoneyBtn').addEventListener('click', function() {
            document.getElementById('addMoneyModal').style.display = 'flex';
        });

        // Withdraw Button
        document.getElementById('withdrawBtn').addEventListener('click', function() {
            document.getElementById('withdrawModal').style.display = 'flex';
        });

        // Close modals when clicking outside
        document.getElementById('addMoneyModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });

        document.getElementById('withdrawModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
</body>
</html>

<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amountNpr = floatval($_POST['amount'] ?? 0);
    if ($amountNpr < 10) {
        $error = 'Minimum topup is Rs 10.';
    } else {
        $amountPaisa = nprToPaisa($amountNpr);
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT wallet_balance_paisa, service_status FROM users WHERE id = ? FOR UPDATE");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            $newBalance = (int) ($user['wallet_balance_paisa'] ?? 0) + $amountPaisa;
            $newStatus = $newBalance >= WALLET_SUSPEND_THRESHOLD_PAISA ? 'active' : ($user['service_status'] ?? 'active');

            $stmt = $conn->prepare("
                UPDATE users
                SET wallet_balance_paisa = ?, last_topup_at = NOW(), last_wallet_warning_level = 'none', service_status = ?
                WHERE id = ?
            ");
            $stmt->bind_param('isi', $newBalance, $newStatus, $user_id);
            $stmt->execute();

            $desc = 'Wallet topup (manual)';
            $stmt = $conn->prepare("
                INSERT INTO wallet_ledger (user_id, type, amount_paisa, description, ref_type, ref_id, balance_after_paisa)
                VALUES (?, 'topup', ?, ?, 'manual', NULL, ?)
            ");
            $stmt->bind_param('iisi', $user_id, $amountPaisa, $desc, $newBalance);
            $stmt->execute();

            $conn->commit();
            $success = 'Topup successful.';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Topup failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topup Wallet - Astha</title>
    <link rel="stylesheet" href="Astha-theme.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">💧 Astha</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="history.php">History</a></li>
                <li><a href="billing.php">Billing</a></li>
                <li><a href="topup.php">Topup</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-card">
            <div class="icon-drop"></div>
            <h2 class="text-center" style="color: var(--teal-primary); margin-bottom: 1.5rem;">Topup Wallet</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="topup.php">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_name) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_email) ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" placeholder="98XXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (NPR)</label>
                    <input type="number" id="amount" name="amount" min="10" step="1" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Topup Wallet</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
require_once 'config.php';
requireLogin();

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
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

            <form id="topupForm">
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

                <button type="submit" class="btn btn-primary" style="width: 100%;">Pay with Khalti</button>
                <p class="text-center" style="margin-top: 0.75rem; color: var(--text-secondary); font-size: 0.9rem;">
                    Payments are processed via Khalti API. Callback verification runs automatically.
                </p>
            </form>
            <div id="errorBox" class="alert alert-error hidden" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <script>
        const form = document.getElementById('topupForm');
        const errorBox = document.getElementById('errorBox');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            const payload = {
                name: document.getElementById('name').value.trim(),
                email: document.getElementById('email').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                amount_npr: Number(document.getElementById('amount').value)
            };

            try {
                const res = await fetch('khalti-initiate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) {
                    const detail = data.detail ? ` (${data.detail})` : '';
                    errorBox.textContent = (data.error || 'Payment initiation failed.') + detail;
                    errorBox.classList.remove('hidden');
                    return;
                }
                window.location.href = data.payment_url;
            } catch (err) {
                errorBox.textContent = 'Network error. Please try again.';
                errorBox.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>

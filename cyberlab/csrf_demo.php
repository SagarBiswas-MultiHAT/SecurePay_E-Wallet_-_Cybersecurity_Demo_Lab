<?php
// csrf_demo.php
// CSRF Demo: Simulated money transfer form with vulnerable and secure modes
// Uses global secure_toggle.php for mode
include 'secure_toggle.php';

$message = '';
$success = false;

// Generate CSRF token for secure mode
if (isSecureMode()) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once '../includes/db_connect.php';
    $to = $_POST['to'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $sender_username = $_SESSION['username'] ?? '';
    $recipient_username = $to;
    $amount = floatval($amount);
    if (trim($recipient_username) === '' || trim($amount) === '' || !is_numeric($amount) || $amount <= 0 || trim($sender_username) === '') {
        $message = 'Please enter a valid recipient, amount, and make sure you are logged in.';
    } else if (isSecureMode()) {
        // --- Secure Version: Check CSRF token ---
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $message = 'CSRF token invalid! Possible CSRF attack blocked.';
        } else {
            $success = true;
            $realTransfer = true;
        }
    } else {
        // --- Vulnerable Version: No CSRF protection ---
        $success = true;
        $realTransfer = true;
    }

    // Perform real transfer if $realTransfer is set
    if (!empty($realTransfer)) {
        // Get sender and receiver user_id from users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $sender_username);
        $stmt->execute();
        $stmt->bind_result($sender_id);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $recipient_username);
        $stmt->execute();
        $stmt->bind_result($receiver_id);
        $stmt->fetch();
        $stmt->close();

        if (empty($sender_id) || empty($receiver_id)) {
            $message = 'Sender or receiver not found.';
            $success = false;
        } else {
            // Check sender balance
            $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $sender_id);
            $stmt->execute();
            $stmt->bind_result($sender_balance);
            $stmt->fetch();
            $stmt->close();

            if ($sender_balance < $amount) {
                $message = 'Insufficient balance.';
                $success = false;
            } else {
                // Deduct from sender
                $stmt = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $stmt->bind_param("di", $amount, $sender_id);
                $stmt->execute();
                $stmt->close();

                // Add to receiver
                $stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $stmt->bind_param("di", $amount, $receiver_id);
                $stmt->execute();
                $stmt->close();

                // Log transaction in transactions table
                $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, transaction_type) VALUES (?, ?, ?, 'transfer')");
                $stmt->bind_param("iid", $sender_id, $receiver_id, $amount);
                $stmt->execute();
                $stmt->close();

                $message = 'Transfer successful! Real money transferred.';
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CSRF Demo - Cyber Lab</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --color-bg: #10151a;
            --color-accent: #00ff41;
            --color-accent2: #00bfff;
            --color-card: rgba(20, 30, 30, 0.98);
            --color-text: #e0ffe6;
            --color-placeholder: #00ff41a0;
            --color-border: #00ff41cc;
            --color-btn-bg: #181f1b;
            --color-btn-hover: #00ff4160;
            --color-shadow: 0 0 24px #00ff41cc, 0 0 4px #00bfff99;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Fira Mono', 'Consolas', 'Menlo', monospace;
            color: var(--color-text);
            background: var(--color-bg);
            overflow-x: hidden;
        }

        /* Matrix rain animation */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            pointer-events: none;
            background: repeating-linear-gradient(180deg, #00ff4160 0 2px, transparent 2px 20px);
            opacity: 0.13;
            animation: matrix-fall 2s linear infinite;
        }

        @keyframes matrix-fall {
            0% {
                background-position-y: 0;
            }

            100% {
                background-position-y: 20px;
            }
        }
        html {
            zoom: 80%;
        }

        .container {
            width: 940px;
            max-width: 96vw;
            margin: 80px auto;
            padding: 36px 32px 32px 32px;
            position: relative;
            border-radius: 12px;
            background: var(--color-card);
            box-shadow: var(--color-shadow);
            border: 2px solid var(--color-border);
            z-index: 1;
        }

        .toggle-btns {
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .toggle-btns a {
            margin-right: 10px;
            color: var(--color-accent2);
            background: var(--color-btn-bg);
            border: 1px solid var(--color-accent2);
            border-radius: 4px;
            padding: 4px 14px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 0 8px #00bfff80;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }

        .toggle-btns a:hover {
            background: var(--color-btn-hover);
            color: var(--color-accent);
            box-shadow: 0 0 16px #00ff41cc;
        }

        .toggle-btns span {
            margin-right: 10px;
        }

        .csrf-note {
            background: #1a1f1a;
            border: 1.5px solid var(--color-accent2);
            color: var(--color-accent2);
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 4px;
            font-size: 1.08em;
            box-shadow: 0 0 12px #00bfff40;
        }

        .csrf-note code,
        .csrf-note pre {
            color: var(--color-accent);
            background: #181f1b;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 1em;
        }

        .demo-box {
            margin-bottom: 18px;
        }

        form {
            margin-bottom: 0;
        }

        label {
            font-size: 1.08em;
            color: var(--color-accent2);
            font-family: inherit;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            background: #181f1b;
            color: var(--color-accent);
            border: 1.5px solid var(--color-accent2);
            border-radius: 6px;
            font-family: inherit;
            font-size: 1.08em;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 0 8px #00bfff40;
        }

        input[type="text"]::placeholder,
        input[type="number"]::placeholder {
            color: var(--color-placeholder);
        }

        button[type="submit"] {
            background: var(--color-btn-bg);
            color: var(--color-accent);
            border: 1.5px solid var(--color-accent);
            font-family: inherit;
            font-size: 1.1em;
            font-weight: bold;
            padding: 10px 32px;
            border-radius: 6px;
            letter-spacing: 1px;
            box-shadow: 0 0 12px #00ff4140;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background: var(--color-accent);
            color: #10151a;
            box-shadow: 0 0 24px #00ff41cc;
        }

        h2 {
            color: var(--color-accent);
            font-family: inherit;
            font-size: 2em;
            text-shadow: 0 0 8px #00ff41cc, 0 0 2px #00bfff99;
            margin-bottom: 18px;
            letter-spacing: 2px;
            text-align: center;
            animation: flicker 2.5s infinite alternate;
        }

        @keyframes flicker {

            0%,
            100% {
                opacity: 1;
                text-shadow: 0 0 8px #00ff41cc, 0 0 2px #00bfff99;
            }

            10% {
                opacity: 0.85;
            }

            20% {
                opacity: 0.95;
            }

            30% {
                opacity: 0.7;
                text-shadow: 0 0 16px #00ff41cc, 0 0 8px #00bfff99;
            }

            40% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }

            60% {
                opacity: 1;
            }

            70% {
                opacity: 0.9;
            }

            80% {
                opacity: 1;
            }

            90% {
                opacity: 0.95;
            }
        }

        .alert {
            margin: 12px 0 18px 0;
            padding: 10px 16px;
            border-radius: 4px;
            font-weight: bold;
            font-family: inherit;
            box-shadow: 0 0 8px #00ff4140;
        }

        .alert.success {
            background: #0f2c1a;
            color: var(--color-accent);
            border: 1.5px solid var(--color-accent);
        }

        .alert.error {
            background: #2c0f1a;
            color: #ff4f4f;
            border: 1.5px solid #ff4f4f;
        }

        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            background: #222;
        }

        ::-webkit-scrollbar-thumb {
            background: #00ff41cc;
            border-radius: 4px;
        }

        /* Terminal-style footer */
        .footer-terminal {
            margin-top: 32px;
            background: #0a0f0a;
            color: #00ff41;
            font-family: inherit;
            font-size: 1.05em;
            border-radius: 0 0 12px 12px;
            border-top: 1.5px solid #00ff41cc;
            box-shadow: 0 0 12px #00ff4140;
            padding: 10px 18px 8px 18px;
            text-shadow: 0 0 4px #00ff41cc;
            letter-spacing: 1px;
            user-select: text;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>CSRF Demo</h2>
        <div class="toggle-btns">
            <div>
                <?php if (isSecureMode()): ?>
                    <span style="color:#00ff41;font-weight:bold;background:#0f2c1a;padding:2px 10px;border-radius:4px;box-shadow:0 0 8px #00ff41cc;">Secure Mode: ON</span>
                    <a href="?mode=vulnerable">Switch to Vulnerable</a>
                <?php else: ?>
                    <span style="color:#ff4f4f;font-weight:bold;background:#2c0f1a;padding:2px 10px;border-radius:4px;box-shadow:0 0 8px #ff4f4fcc;">Vulnerable Mode: ON</span>
                    <a href="?mode=secure">Switch to Secure</a>
                <?php endif; ?>
            </div>
            <a href="index.php">Back to Lab Index</a>
        </div>
        <div class="csrf-note">
            <strong>Try this attack in Vulnerable Mode:</strong><br>
            <em>Imagine a malicious site includes the following HTML:</em>
            <pre>&lt;form action="http://yourdomain/cyberlab/csrf_demo.php" method="POST"&gt;
  &lt;input type="hidden" name="to" value="attacker"&gt;
  &lt;input type="hidden" name="amount" value="10000"&gt;
  &lt;input type="submit" value="Steal Money"&gt;
&lt;/form&gt;</pre>
            <span style="color:#ff4f4f;">In Vulnerable Mode, this will work! In Secure Mode, it will fail.</span>
        </div>
        <div class="demo-box">
            <form method="post" action="" autocomplete="off">
                <label>Recipient Username</label>
                <input type="text" name="to" required placeholder="victim or attacker">
                <label>Amount</label>
                <input type="number" name="amount" min="1" step="0.01" required placeholder="10000">
                <?php if (isSecureMode()): ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <?php endif; ?>
                <button type="submit">Send Money</button>
            </form>
        </div>
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="footer-terminal">
            <span style="color:#00bfff;">user@cyberlab</span>:<span style="color:#00ff41;">~</span>$ <span style="color:#e0ffe6;"># CSRF Demo for Web Security — <b>Try to break it!</b></span>
        </div>
    </div>
</body>

</html>
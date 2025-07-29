<?php
// Admin access check
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
// Admin Transaction Logs for SecurePay
// Allows admin to view, filter, and search all transactions
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_login();

// Helper: Format amounts
function formatAmount($amount)
{
    return number_format((float)$amount, 2, '.', '');
}

// Detect columns for transaction type and date
$columns = [];
$result = $conn->query("SHOW COLUMNS FROM transactions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}
$amountCol = in_array('amount', $columns) ? 'amount' : (in_array('value', $columns) ? 'value' : '');
$typeCol = in_array('type', $columns) ? 'type' : (in_array('transaction_type', $columns) ? 'transaction_type' : '');
$dateCol = in_array('timestamp', $columns) ? 'timestamp' : (in_array('created_at', $columns) ? 'created_at' : (in_array('date', $columns) ? 'date' : ''));

// Detect user name column
$userNameCol = 'full_name';
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
if ($result && $result->num_rows === 0) {
    $userNameCol = 'name';
    $result2 = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
    if ($result2 && $result2->num_rows === 0) {
        $userNameCol = 'username';
    }
}

// pages
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$where = [];
$params = [];
$types = '';

// Date range filter
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(t.$dateCol) BETWEEN ? AND ?";
    $params[] = $_GET['from'];
    $params[] = $_GET['to'];
    $types .= 'ss';
}
// Transaction type filter
if (!empty($_GET['type']) && $typeCol) {
    $where[] = "t.$typeCol = ?";
    $params[] = $_GET['type'];
    $types .= 's';
}
// User name search
if (!empty($_GET['user'])) {
    $where[] = "(u1.$userNameCol LIKE ? OR u2.$userNameCol LIKE ?)";
    $params[] = '%' . $_GET['user'] . '%';
    $params[] = '%' . $_GET['user'] . '%';
    $types .= 'ss';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total for pages
$countSql = "SELECT COUNT(*) FROM transactions t
    LEFT JOIN users u1 ON t.sender_id = u1.id
    LEFT JOIN users u2 ON t.receiver_id = u2.id
    $whereSql";
$stmt = $conn->prepare($countSql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($totalRows);
$stmt->fetch();
$stmt->close();
$totalPages = ceil($totalRows / $limit);

// Fetch transactions
$sql = "SELECT t.id"
    . ($amountCol ? ", t.$amountCol" : "")
    . ($typeCol ? ", t.$typeCol" : "")
    . ($dateCol ? ", t.$dateCol" : "")
    . ", u1.$userNameCol AS sender, u2.$userNameCol AS receiver
    FROM transactions t
    LEFT JOIN users u1 ON t.sender_id = u1.id
    LEFT JOIN users u2 ON t.receiver_id = u2.id
    $whereSql
    ORDER BY t." . ($dateCol ? $dateCol : 'id') . " DESC
    LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Get available transaction types for filter dropdown
$typeOptions = [];
if ($typeCol) {
    $res = $conn->query("SELECT DISTINCT $typeCol FROM transactions");
    while ($row = $res->fetch_assoc()) {
        $typeOptions[] = $row[$typeCol];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transaction Logs | SecurePay Admin</title>
    <!-- <link rel="stylesheet" href="../assets/css/adminStyle.css"> -->

    <style>
        :root {
            --color-bg: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            --color-card: linear-gradient(135deg, #f8fafc 0%, #e9f0f7 100%);
            --color-primary: #1e3c72;
            --color-accent: #ff6e7f;
            --color-accent2: #bfe9ff;
            --color-table-header: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
            --color-table-row: #ffffffcc;
            --color-table-row-alt: #f3f8ff;
            --color-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
            --color-border: #e3e8ee;
            --color-link: #1e3c72;
            --color-link-hover: #ff6e7f;
        }

        html {
            zoom: 97%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            color: #222;
            background: var(--color-bg);
            background-attachment: fixed;
            letter-spacing: 0.01em;
        }

        #header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--color-card);
            box-shadow: var(--color-shadow);
            border-radius: 0 0 18px 18px;
            padding: 18px 32px 14px 32px;
            margin-bottom: 18px;
        }

        #header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #ff6e7f 80%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            /* text-fill-color: transparent; */
            letter-spacing: 0.04em;
        }

        #header nav a {
            margin-right: 1.2rem;
            color: var(--color-link);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
            border-bottom: 2px solid transparent;
            padding-bottom: 2px;
        }

        #header nav a:last-child {
            margin-right: 0;
        }

        #header nav a:hover {
            color: var(--color-link-hover);
            border-bottom: 2px solid var(--color-link-hover);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--color-card);
            border-radius: 18px;
            box-shadow: var(--color-shadow);
            padding: 32px 36px 32px 36px;
            margin-bottom: 32px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 2.2rem;
            background: #f3f8ff;
            border-radius: 10px;
            padding: 18px 20px 10px 20px;
            box-shadow: 0 2px 8px 0 rgba(31, 38, 135, 0.06);
        }

        .filter-form label {
            font-weight: 500;
            color: #1e3c72;
            margin-right: 0.3rem;
        }

        .filter-form input,
        .filter-form select {
            margin-right: 0.5rem;
            padding: 0.38rem 0.8rem;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 1rem;
            background: #fff;
            transition: border 0.2s, box-shadow 0.2s;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border: 1.5px solid var(--color-link-hover);
            outline: none;
            box-shadow: 0 0 0 2px #ff6e7f22;
        }

        .filter-form button {
            background: linear-gradient(90deg, #1e3c72 0%, #ff6e7f 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.45rem 1.2rem;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px 0 rgba(31, 38, 135, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }

        .filter-form button:hover {
            background: linear-gradient(90deg, #ff6e7f 0%, #1e3c72 100%);
            box-shadow: 0 4px 16px 0 rgba(31, 38, 135, 0.13);
        }

        .trans-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.2rem;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px 0 rgba(31, 38, 135, 0.07);
        }

        .trans-table th {
            background: var(--color-table-header);
            color: #fff;
            font-weight: 700;
            font-size: 1.08rem;
            padding: 0.7rem 1.2rem;
            border-bottom: 2.5px solid #bfe9ff;
            letter-spacing: 0.03em;
        }

        .trans-table td {
            padding: 0.6rem 1.2rem;
            border-bottom: 1px solid #e3e8ee;
            background: var(--color-table-row);
            font-size: 1.01rem;
            transition: background 0.2s;
        }

        .trans-table tr:nth-child(even) td {
            background: var(--color-table-row-alt);
        }

        .trans-table tr:hover td {
            background: #e9f0f7;
        }

        .trans-table td:first-child,
        .trans-table th:first-child {
            border-top-left-radius: 12px;
        }

        .trans-table td:last-child,
        .trans-table th:last-child {
            border-top-right-radius: 12px;
        }

        .pages {
            margin-top: 2.2rem;
            text-align: center;
        }

        .pages a {
            margin: 0 4px;
            padding: 0.38rem 1.1rem;
            background: #e9f0f7;
            color: var(--color-link);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05rem;
            box-shadow: 0 1px 4px 0 rgba(31, 38, 135, 0.06);
            transition: background 0.2s, color 0.2s;
        }

        .pages a.active,
        .pages a:hover {
            background: linear-gradient(90deg, #1e3c72 0%, #ff6e7f 100%);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 2px 8px 0 rgba(31, 38, 135, 0.13);
        }

        @media (max-width: 900px) {
            .container {
                padding: 18px 6px 18px 6px;
            }

            #header {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 8px 10px 8px;
            }

            .filter-form {
                flex-direction: column;
                gap: 0.7rem;
                padding: 12px 8px 8px 8px;
            }

            .trans-table th,
            .trans-table td {
                padding: 0.4rem 0.5rem;
                font-size: 0.98rem;
            }
        }
    </style>
</head>

<body>
    <div id="header">
        <h1>Transaction Logs</h1>
        <nav style="padding-right: 12px; font-size: 1.2rem;">
            <a href="index.php" style="margin-right:1rem; color:#1e3c72; font-weight:bold; text-decoration:none;">Dashboard</a>
            <a href="users.php" style="margin-right:1rem; color:#02565c; font-weight:bold; text-decoration:none;">Users</a>
            <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;">Logout</a>
        </nav>
    </div>
    <br>
    <div class="container">
        <!-- Filter Form -->
        <form class="filter-form" method="get">
            <label>Date Range:</label>
            <input type="date" name="from" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>">
            <input type="date" name="to" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>">
            <label>Type:</label>
            <select name="type">
                <option value="">All</option>
                <?php foreach ($typeOptions as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php if (!empty($_GET['type']) && $_GET['type'] == $opt) echo 'selected'; ?>><?php echo htmlspecialchars($opt); ?></option>
                <?php endforeach; ?>
            </select>
            <label>User:</label>
            <input type="text" name="user" placeholder="Sender or Receiver" value="<?php echo htmlspecialchars($_GET['user'] ?? ''); ?>">
            <button type="submit">Filter</button>
        </form>
        <!-- Transactions Table -->
        <table class="trans-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Amount ($)</th>
                    <th>Type</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo isset($tx['id']) ? $tx['id'] : '-'; ?></td>
                        <td><?php echo isset($tx['amount']) ? formatAmount($tx['amount']) : (isset($tx['value']) ? formatAmount($tx['value']) : '-'); ?></td>
                        <td><?php
                            if (isset($tx['type']) && $tx['type'] !== '') {
                                echo htmlspecialchars($tx['type']);
                            } elseif (isset($tx['transaction_type']) && $tx['transaction_type'] !== '') {
                                echo htmlspecialchars($tx['transaction_type']);
                            } elseif ((isset($tx['receiver']) && ($tx['receiver'] === '-' || $tx['sender'] === $tx['receiver'])) || (!isset($tx['receiver']) && isset($tx['sender']))) {
                                echo 'withdraw';
                            } else {
                                echo '-';
                            }
                            ?></td>
                        <td><?php echo htmlspecialchars($tx['sender'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tx['receiver'] ?? '-'); ?></td>
                        <td><?php
                            if (isset($tx['timestamp'])) echo date('Y-m-d H:i', strtotime($tx['timestamp']));
                            elseif (isset($tx['created_at'])) echo date('Y-m-d H:i', strtotime($tx['created_at']));
                            elseif (isset($tx['date'])) echo date('Y-m-d H:i', strtotime($tx['date']));
                            else echo '-';
                            ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- pages -->
        <div class="pages">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?php
                            $q = $_GET;
                            $q['page'] = $i;
                            echo http_build_query($q);
                            ?>" class="<?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>

</html>
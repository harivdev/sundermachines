<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Ensure table exists with close_type column
$tableSql = "
CREATE TABLE IF NOT EXISTS `daily_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `entered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closing_balance` decimal(10,2) DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_type` varchar(20) DEFAULT 'Self',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($conn, $tableSql);
mysqli_query($conn, "ALTER TABLE `daily_opening_balance` ADD COLUMN `close_type` varchar(20) DEFAULT 'Self'");

// Filters
$today_only  = isset($_GET['today']) && $_GET['today'] == '1';
$filter_from = $today_only ? date('Y-m-d') : (isset($_GET['from']) ? $_GET['from'] : date('Y-m-d'));
$filter_to   = $today_only ? date('Y-m-d') : (isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d'));
$filter_role = isset($_GET['action_by']) ? trim($_GET['action_by']) : '';

$safe_from = mysqli_real_escape_string($conn, $filter_from);
$safe_to   = mysqli_real_escape_string($conn, $filter_to);
$safe_role = mysqli_real_escape_string($conn, $filter_role);

// Determine if any filter is active (to show panel open by default)
$any_filter_active = ($today_only || !empty($filter_role) || (isset($_GET['from']) && $_GET['from'] !== date('Y-m-d')) || (isset($_GET['to']) && $_GET['to'] !== date('Y-m-d')));

// Build WHERE
$where_parts = ["`date` BETWEEN '$safe_from' AND '$safe_to'"];
if (!empty($safe_role)) {
    if (strtolower($safe_role) === 'auto') {
        $where_parts[] = "LOWER(close_type) = 'auto'";
    } else {
        $where_parts[] = "LOWER(entered_by) = LOWER('$safe_role')";
    }
}
$where_sql = implode(' AND ', $where_parts);

$records = [];
$res = mysqli_query($conn, "
    SELECT id, `date`, amount AS opening_balance, closing_balance, is_closed, closed_at, close_type, entered_by
    FROM daily_opening_balance
    WHERE $where_sql
    ORDER BY `date` DESC
");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $records[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closing Income Report - Sunder ERP</title>
    <style>
        .page-content {
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
            margin: auto;
        }
        .report-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 0 !important;
            flex-wrap: wrap;
            gap: 10px;
            background: #fff !important;
            box-shadow: none !important;
            border-radius: 8px 8px 0 0 !important;
        }
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: #24231F;
        }
        .page-header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        /* Filter Toggle Button */
        .btn-filter-toggle {
            background: #d97706;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(217,119,6,0.2);
        }
        .btn-filter-toggle:hover { background: #b45309; }

        /* Filter Panel */
        .filter-panel {
            background: #f8fafc;
            border-bottom: 1px solid #eee;
            position: relative;
            padding: 16px 20px;
            display: none;
        }
        .filter-panel.open {
            display: block;
        }
        .filter-close-btn {
            position: absolute;
            top: 10px;
            right: 14px;
            background: none;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            padding: 2px 6px;
            border-radius: 4px;
            transition: color 0.2s, background 0.2s;
        }
        .filter-close-btn:hover { color: #dc2626; background: #fee2e2; }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .filter-group input[type="date"],
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font-size: 14px;
            background: #fff;
            height: 38px;
            box-sizing: border-box;
            min-width: 140px;
        }
        .btn-today {
            height: 38px;
            padding: 0 14px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-today:hover { background: #0284c7; }
        .btn-apply {
            background: #2563eb;
            color: #fff;
            padding: 8px 18px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            height: 38px;
        }
        .btn-apply:hover { background: #1d4ed8; }
        .btn-clear {
            background: #f59e0b;
            color: #fff;
            padding: 8px 18px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            height: 38px;
            display: inline-flex;
            align-items: center;
        }
        .btn-clear:hover { background: #d97706; }

        /* Active filter chip */
        .active-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
            align-items: center;
        }
        .filter-chip {
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            min-width: 800px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
        }
        .data-table tr:hover td { background-color: #f9f9f9; }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .badge-self   { background: #e0f2fe; color: #0369a1; }
        .badge-auto   { background: #f0fdf4; color: #15803d; }
        .badge-open   { background: #fff7ed; color: #c2410c; }
        .badge-closed { background: #f0fdf4; color: #15803d; }
        .amount-positive { color: #16a34a; font-weight: 700; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 15px;
        }
        @media (max-width: 768px) {
            .data-table { font-size: 13px; }
            .data-table th, .data-table td { padding: 8px 10px; }
            .filter-row { gap: 10px; }
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>

    <div class="page-content">
        <div class="report-card">
            <!-- Page Header -->
            <div class="page-header">
            <div class="page-title">Closing Income Report</div>
            <div class="page-header-actions">
                <button class="btn-filter-toggle" id="filterToggleBtn" onclick="toggleFilterPanel()">
                    <i class="fa-solid fa-sliders"></i> Filter
                </button>
                <a href="today_income.php" style="background:#C9A227; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:600; font-size:14px; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-arrow-left"></i> Today's Income
                </a>
            </div>
        </div>

        <!-- Filter Panel (collapsible) -->
        <div class="filter-panel <?= $any_filter_active ? 'open' : '' ?>" id="filterPanel">
            <button type="button" class="filter-close-btn" onclick="toggleFilterPanel()" title="Close filters">&times;</button>
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="from" id="fromDate" value="<?= htmlspecialchars($filter_from) ?>">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="to" id="toDate" value="<?= htmlspecialchars($filter_to) ?>">
                    </div>
                    <div class="filter-group" style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn-today" onclick="setToday()" title="Show only today's records">
                            <i class="fa-solid fa-calendar-day"></i> Today
                        </button>
                    </div>
                    <div class="filter-group" style="display: flex; flex-direction: row; align-items: center; gap: 10px;">
                        <label style="margin-bottom: 0;">Action By</label>
                        <select name="action_by" style="flex: 1;">
                            <option value="">-- All --</option>
                            <option value="admin"    <?= strtolower($filter_role) === 'admin'    ? 'selected' : '' ?>>Admin</option>
                            <option value="employee" <?= strtolower($filter_role) === 'employee' ? 'selected' : '' ?>>Employee</option>
                            <option value="cashier"  <?= strtolower($filter_role) === 'cashier'  ? 'selected' : '' ?>>Cashier</option>
                            <option value="auto"     <?= strtolower($filter_role) === 'auto'     ? 'selected' : '' ?>>Auto</option>
                        </select>
                    </div>
                    <div class="filter-group" style="display:flex; gap:8px; align-items:flex-end;">
                        <button type="submit" class="btn-apply">Apply</button>
                        <a href="closing_income_report.php" class="btn-clear">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Closed By</th>
                    <th>Opening Balance</th>
                    <th>Closing Income</th>
                    <th>Status</th>
                    <th>Action Type</th>
                    <th>Closed At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">No closing income records found for the selected filters.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($records as $r): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
                            <td><?= htmlspecialchars($r['entered_by'] ?: '—') ?></td>
                            <td>₹ <?= number_format((float)$r['opening_balance'], 2) ?></td>
                            <td>
                                <?php if ($r['is_closed'] && $r['closing_balance'] !== null): ?>
                                    <span class="amount-positive">₹ <?= number_format((float)$r['closing_balance'], 2) ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Not closed yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['is_closed']): ?>
                                    <span class="badge badge-closed">Closed</span>
                                <?php else: ?>
                                    <span class="badge badge-open">Open</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $close_type = strtolower($r['close_type'] ?? 'self');
                                    if (!$r['is_closed']) {
                                        echo '<span style="color:#94a3b8;">—</span>';
                                    } elseif ($close_type === 'auto') {
                                        echo '<span class="badge badge-auto">Auto</span>';
                                    } else {
                                        echo '<span class="badge badge-self">Self</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if ($r['is_closed'] && $r['closed_at']): ?>
                                    <?= date('d/m/Y h:i A', strtotime($r['closed_at'])) ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div> <!-- End table-responsive -->
        </div> <!-- End report-card -->
    </div>

    <script>
        function toggleFilterPanel() {
            var panel = document.getElementById('filterPanel');
            panel.classList.toggle('open');
        }

        function setToday() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('fromDate').value = today;
            document.getElementById('toDate').value   = today;
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>

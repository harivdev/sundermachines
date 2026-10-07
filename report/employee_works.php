<?php
require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../includes/auth.php");
requireAdmin();

// Handle Export to CSV / Excel
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'])) {
    $export_filename = 'Employee_Works_Report_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $export_filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, ['S.No', 'Jobcard Number', 'Name', 'Role', 'Machine', 'Allocated Date', 'Completed Date', 'Labour Charge (INR)']);
}

// Fetch all employees for filter dropdown
$empList = [];
$empQ = mysqli_query($conn, "SELECT id, name, designation FROM employee WHERE active = 1 ORDER BY name ASC");
if ($empQ) {
    while ($empRow = mysqli_fetch_assoc($empQ)) {
        $empList[] = $empRow;
    }
}

// Filter inputs
$today_only = isset($_GET['today']) && $_GET['today'] == '1';
$all_dates = isset($_GET['all_dates']) && $_GET['all_dates'] == '1';

if ($today_only) {
    $filter_from = date('Y-m-d');
    $filter_to = date('Y-m-d');
} elseif ($all_dates) {
    $filter_from = '';
    $filter_to = '';
} else {
    // Default to first day of current month to current date
    $filter_from = isset($_GET['from']) && $_GET['from'] !== '' ? trim($_GET['from']) : date('Y-m-01');
    $filter_to = isset($_GET['to']) && $_GET['to'] !== '' ? trim($_GET['to']) : date('Y-m-d');
}

$filter_employee = isset($_GET['employee']) ? trim($_GET['employee']) : '';
$filter_jobcard = isset($_GET['jobcard']) ? trim($_GET['jobcard']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE conditions
$where_clauses = [];

if (!$all_dates && !empty($filter_from) && !empty($filter_to)) {
    $safe_from = mysqli_real_escape_string($conn, $filter_from);
    $safe_to = mysqli_real_escape_string($conn, $filter_to);
    $where_clauses[] = "(
        (j.givenDate BETWEEN '$safe_from' AND '$safe_to')
        OR (j.completedDate BETWEEN '$safe_from' AND '$safe_to')
        OR (j.givenDate IS NULL AND DATE(j.createdOn) BETWEEN '$safe_from' AND '$safe_to')
    )";
}

if (!empty($filter_employee)) {
    $safe_emp = mysqli_real_escape_string($conn, $filter_employee);
    if (is_numeric($filter_employee)) {
        $where_clauses[] = "j.employee = '$safe_emp'";
    } else {
        $where_clauses[] = "emp.name LIKE '%$safe_emp%'";
    }
}

if (!empty($filter_jobcard)) {
    $safe_jc = mysqli_real_escape_string($conn, $filter_jobcard);
    $where_clauses[] = "(j.cardNo LIKE '%$safe_jc%' OR j.id = '$safe_jc')";
}

if (!empty($filter_status)) {
    $safe_st = mysqli_real_escape_string($conn, $filter_status);
    if ($safe_st === 'completed') {
        $where_clauses[] = "(j.completed = 1 OR LOWER(j.jobStatus) = 'completed')";
    } elseif ($safe_st === 'pending') {
        $where_clauses[] = "(j.completed = 0 AND (j.jobStatus IS NULL OR LOWER(j.jobStatus) != 'completed'))";
    } else {
        $where_clauses[] = "LOWER(j.jobStatus) = LOWER('$safe_st')";
    }
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Main Query
$query = "
    SELECT
        j.id,
        j.cardNo,
        j.givenDate,
        j.completedDate,
        (j.completed + 0) AS is_completed,
        (j.delivered + 0) AS is_delivered,
        j.deliveryDate,
        j.jobStatus,
        j.laborCharge,
        j.actualAmountSum,
        j.receivedAmountSum,
        j.createdOn,
        emp.id AS employee_id,
        emp.name AS employee_name,
        emp.designation AS employee_role,
        c.name AS customer_name,
        COALESCE(NULLIF(MAX(m.machineName), ''), NULLIF(MAX(ji.machineName), ''), '-') AS machine_name,
        MAX(ji.serialNo) AS serial_no
    FROM jobcard j
    LEFT JOIN employee emp ON j.employee = emp.id
    LEFT JOIN customer c ON j.customer = c.id
    LEFT JOIN jobcarditems ji ON j.id = ji.jobCard
    LEFT JOIN machine m ON ji.machine = m.id
    $where_sql
    GROUP BY j.id, j.cardNo, j.givenDate, j.completedDate, j.completed, j.delivered, j.deliveryDate, j.jobStatus, j.laborCharge, j.actualAmountSum, j.receivedAmountSum, j.createdOn, emp.id, emp.name, emp.designation, c.name
    ORDER BY COALESCE(j.givenDate, DATE(j.createdOn)) DESC, j.id DESC
";

$result = mysqli_query($conn, $query);
$records = [];
$total_labor_charge = 0.0;
$total_completed = 0;
$total_pending = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $labor = (float)($row['laborCharge'] ?? 0);
        $total_labor_charge += $labor;

        $is_comp = ($row['is_completed'] == 1 || strtolower($row['jobStatus'] ?? '') === 'completed');
        if ($is_comp) {
            $total_completed++;
        } else {
            $total_pending++;
        }

        $records[] = $row;
    }
}

// Handle CSV export exit
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv']) && isset($output)) {
    $idx = 1;
    foreach ($records as $r) {
        $cNo = !empty($r['cardNo']) ? $r['cardNo'] : ('JC-' . $r['id']);
        $eName = !empty($r['employee_name']) ? $r['employee_name'] : 'Unassigned';
        $eRole = !empty($r['employee_role']) ? $r['employee_role'] : 'Worker';
        $mName = $r['machine_name'];
        $allocD = !empty($r['givenDate']) ? date('d-m-Y', strtotime($r['givenDate'])) : (!empty($r['createdOn']) ? date('d-m-Y', strtotime($r['createdOn'])) : '-');
        $compD = !empty($r['completedDate']) ? date('d-m-Y', strtotime($r['completedDate'])) : 'Pending';
        $lab = number_format((float)$r['laborCharge'], 2, '.', '');

        fputcsv($output, [$idx++, $cNo, $eName, $eRole, $mName, $allocD, $compD, $lab]);
    }
    // Footer row
    fputcsv($output, ['', 'TOTAL', '', '', '', '', count($records) . ' Works', number_format($total_labor_charge, 2, '.', '')]);
    fclose($output);
    exit();
}

// Active filters count for panel state
$any_filter_active = ($today_only || $all_dates || !empty($filter_employee) || !empty($filter_jobcard) || !empty($filter_status) || (isset($_GET['from']) && $_GET['from'] !== date('Y-m-01')) || (isset($_GET['to']) && $_GET['to'] !== date('Y-m-d')));

include(__DIR__ . "/../includes/header.php");
?>

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
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
        flex-wrap: wrap;
        gap: 15px;
        background: #fff;
    }

    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .page-subtitle {
        font-size: 13px;
        color: #64748b;
        margin-top: 3px;
        font-weight: 500;
    }

    .page-header-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    /* Right-side corner total box */
    .corner-total-box {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f0fdf4;
        border: 1.5px solid #86efac;
        padding: 8px 16px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(22, 163, 74, 0.1);
    }

    .corner-total-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #15803d;
        line-height: 1.2;
    }

    .corner-total-value {
        font-size: 19px;
        font-weight: 800;
        color: #166534;
        line-height: 1.1;
    }

    .corner-total-count {
        font-size: 11.5px;
        font-weight: 600;
        color: #15803d;
        background: #dcfce7;
        padding: 2px 8px;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
    }

    /* Action Buttons */
    .btn-filter-toggle {
        background: #d97706;
        color: #fff;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(217,119,6,0.2);
        transition: all 0.15s ease;
    }
    .btn-filter-toggle:hover { background: #b45309; }

    .btn-export-csv {
        background: #16a34a;
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(22,163,74,0.2);
        transition: all 0.15s ease;
    }
    .btn-export-csv:hover { background: #15803d; color: #fff; }

    .btn-print {
        background: #475569;
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(71,85,105,0.2);
        transition: all 0.15s ease;
    }
    .btn-print:hover { background: #334155; }

    /* Filter Panel */
    .filter-panel {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
        padding: 16px 20px;
        display: <?= ($any_filter_active) ? 'block' : 'none' ?>;
    }

    .filter-panel.open {
        display: block;
    }

    .filter-row {
        display: flex;
        gap: 14px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .filter-group input[type="date"],
    .filter-group input[type="text"],
    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        background: #fff;
        height: 38px;
        box-sizing: border-box;
        min-width: 150px;
        color: #1e293b;
        outline: none;
        transition: border-color 0.15s;
    }

    .filter-group input[type="date"]:focus,
    .filter-group input[type="text"]:focus,
    .filter-group select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
    }

    .btn-today {
        height: 38px;
        padding: 0 14px;
        background: #0ea5e9;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background 0.15s;
    }
    .btn-today:hover { background: #0284c7; }

    .btn-apply {
        background: #2563eb;
        color: #fff;
        padding: 8px 18px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        cursor: pointer;
        height: 38px;
        transition: background 0.15s;
        box-shadow: 0 2px 4px rgba(37,99,235,0.25);
    }
    .btn-apply:hover { background: #1d4ed8; }

    .btn-clear {
        background: #f59e0b;
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        text-decoration: none;
        height: 38px;
        display: inline-flex;
        align-items: center;
        transition: background 0.15s;
    }
    .btn-clear:hover { background: #d97706; color: #fff; }

    /* Active filter chips */
    .active-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
        align-items: center;
    }

    .filter-chip {
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #bae6fd;
    }

    /* Data Table Styling */
    .table-responsive {
        overflow-x: auto;
        width: 100%;
        background: #fff;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        min-width: 900px;
    }

    .data-table th,
    .data-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
    }

    .data-table th {
        background-color: #f8fafc;
        font-weight: 700;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-top: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .data-table tbody tr:hover td {
        background-color: #f8fafc;
    }

    /* Links */
    .jobcard-link {
        color: #0d6efd;
        text-decoration: underline;
        font-weight: 700;
        font-size: 13.5px;
        transition: color 0.15s ease-in-out;
    }

    .jobcard-link:hover {
        color: #0b5ed7;
        text-decoration: underline;
    }

    .jobcard-link .link-arrow {
        font-size: 11px;
        opacity: 0.8;
    }

    /* Badges */
    .badge-role {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .badge-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .badge-status.completed { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .badge-status.delivered { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .badge-status.pending   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

    /* Date display block */
    .date-cell-wrap {
        display: flex;
        flex-direction: column;
        gap: 3px;
        font-size: 12.5px;
    }

    .date-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .date-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        min-width: 62px;
    }

    .date-value {
        color: #0f172a;
        font-weight: 600;
    }

    .date-value.pending {
        color: #d97706;
        font-style: italic;
    }

    /* Labour amount */
    .labour-col-header {
        text-align: right !important;
    }

    .labour-amount-cell {
        text-align: right !important;
    }

    .labour-amount {
        font-weight: 800;
        font-size: 14px;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }

    /* Table Footer */
    .data-table tfoot td {
        background: #f8fafc;
        font-weight: 700;
        border-top: 2px solid #cbd5e1;
        border-bottom: none;
        padding: 14px 16px;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #94a3b8;
    }

    .empty-state-icon {
        font-size: 36px;
        margin-bottom: 10px;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    .empty-state-desc {
        font-size: 13px;
        color: #64748b;
    }

    .print-report-header {
        display: none;
    }

    /* Print View */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 8mm;
        }

        .topbar,
        .site-header,
        .menu-container,
        .navbar,
        nav,
        footer,
        .no-print,
        .filter-panel,
        .filter-panel *,
        .filter-panel.open,
        #filterPanel,
        #filterPanel *,
        form#filterForm,
        form#filterForm *,
        .page-header,
        .page-header *,
        .page-header-actions,
        .page-header-actions *,
        .btn-filter-toggle,
        .btn-print,
        .btn-apply,
        .btn-clear,
        .btn-today,
        input,
        select,
        button {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            max-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            overflow: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        body {
            padding: 0 !important;
            margin: 0 !important;
            background: #fff !important;
            font-size: 11px !important;
        }

        .page-content,
        .erp-container,
        .report-card {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .print-report-header {
            display: block !important;
            text-align: center !important;
            margin-bottom: 14px !important;
            border-bottom: 2px solid #000 !important;
            padding-bottom: 8px !important;
        }

        .print-company-name {
            margin: 0 !important;
            font-size: 20px !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px !important;
            color: #000 !important;
            text-transform: uppercase !important;
        }

        .print-report-title {
            margin: 4px 0 0 0 !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #000 !important;
            text-transform: uppercase !important;
        }

        .print-report-meta {
            margin: 4px 0 0 0 !important;
            font-size: 11px !important;
            color: #333 !important;
            font-weight: 500 !important;
        }

        .table-responsive {
            overflow: visible !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .data-table {
            width: 100% !important;
            min-width: 100% !important;
            border-collapse: collapse !important;
            border: 1.5px solid #000 !important;
            page-break-inside: auto;
        }

        .data-table thead {
            display: table-header-group !important;
        }

        .data-table thead tr {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .data-table th {
            border: 1px solid #000 !important;
            padding: 6px 6px !important;
            font-size: 10px !important;
            color: #000 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }

        .data-table td {
            border: 1px solid #000 !important;
            padding: 5px 6px !important;
            font-size: 10px !important;
            color: #000 !important;
        }

        .data-table tr {
            page-break-inside: avoid !important;
            page-break-after: auto;
        }

        .jobcard-link {
            color: #000 !important;
            text-decoration: underline !important;
            font-weight: 700 !important;
        }

        .data-table tfoot {
            display: table-footer-group !important;
        }

        .data-table tfoot tr {
            background: #f8fafc !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .data-table tfoot td {
            border: 1.5px solid #000 !important;
            padding: 7px 8px !important;
            font-size: 11px !important;
            color: #000 !important;
        }

        .labour-amount-cell {
            text-align: right !important;
        }
    }
</style>

<div class="page-content erp-container">

    <div class="report-card">

        <!-- Report Header Bar -->
        <div class="page-header no-print">
            <div>
                <h1 class="page-title">
                    <i class="fa-solid fa-user-gear" style="color: #2563eb; font-size: 20px;"></i>
                    Employee Works Report
                </h1>
            </div>

            <div class="page-header-actions">
                <!-- Action buttons (no icons) -->
                <button type="button" class="btn-filter-toggle" onclick="toggleFilterPanel()">
                    Filter
                </button>

                <a href="print_employee_works.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn-print" title="Print Clean A4 Report">
                    Print
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-panel no-print <?= ($any_filter_active) ? 'open' : '' ?>" id="filterPanel">
            <form method="GET" action="employee_works.php" id="filterForm">
                <div class="filter-row">

                    <!-- From Date -->
                    <div class="filter-group">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from" value="<?= htmlspecialchars($filter_from) ?>">
                    </div>

                    <!-- To Date -->
                    <div class="filter-group">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to" value="<?= htmlspecialchars($filter_to) ?>">
                    </div>

                    <!-- Today Quick Button -->
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn-today" onclick="setTodayDates()" title="Set dates to Today">
                            📅 Today
                        </button>
                    </div>

                    <!-- Employee Filter -->
                    <div class="filter-group">
                        <label for="filter_employee">Employee Name</label>
                        <select id="filter_employee" name="employee">
                            <option value="">-- All Employees --</option>
                            <?php foreach ($empList as $e): ?>
                                <option value="<?= htmlspecialchars($e['id']) ?>" <?= ($filter_employee == $e['id'] || $filter_employee == $e['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['name']) ?> <?= !empty($e['designation']) ? '(' . htmlspecialchars($e['designation']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jobcard Filter -->
                    <div class="filter-group">
                        <label for="filter_jobcard">Jobcard</label>
                        <input type="text" id="filter_jobcard" name="jobcard" placeholder="Search Card No..." value="<?= htmlspecialchars($filter_jobcard) ?>">
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label for="filter_status">Status</label>
                        <select id="filter_status" name="status">
                            <option value="">-- All Status --</option>
                            <option value="completed" <?= ($filter_status === 'completed') ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>In Progress / Pending</option>
                            <option value="delivered" <?= ($filter_status === 'delivered') ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="filter-group" style="display:flex; flex-direction:row; gap:8px;">
                        <div>
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-apply">Apply</button>
                        </div>
                        <div>
                            <label>&nbsp;</label>
                            <a href="employee_works.php" class="btn-clear" title="Reset all filters">Clear</a>
                        </div>
                    </div>

                </div>

            </form>
        </div>

        <!-- Print Header Only (Standardized for Sunder Machines Reports) -->
        <div class="print-report-header">
            <h1 class="print-company-name">SUNDER MACHINES</h1>
            <div class="print-report-title">Employee Works Report</div>
            <div class="print-report-meta">
                Date: <?= date('d/m/Y', strtotime($filter_from)) ?> to <?= date('d/m/Y', strtotime($filter_to)) ?><?php if (!empty($filter_employee)): 
                    $matchedEmp = array_filter($empList, fn($x) => $x['id'] == $filter_employee || $x['name'] == $filter_employee);
                    $empLabel = !empty($matchedEmp) ? reset($matchedEmp)['name'] : $filter_employee;
                ?> | Employee: <?= htmlspecialchars($empLabel) ?><?php endif; ?><?php if (!empty($filter_jobcard)): ?> | Jobcard: <?= htmlspecialchars($filter_jobcard) ?><?php endif; ?><?php if (!empty($filter_status)): ?> | Status: <?= ucfirst($filter_status) ?><?php endif; ?>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th style="width: 150px;">Jobcard Number</th>
                        <th style="width: 180px;">Name</th>
                        <th style="width: 130px;">Role</th>
                        <th style="width: 170px;">Machine</th>
                        <th style="width: 130px;">Allocated Date</th>
                        <th style="width: 130px;">Completed Date</th>
                        <th class="labour-col-header" style="width: 130px;">Labour Charge</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📋</div>
                                    <div class="empty-state-title">No employee work records found</div>
                                    <div class="empty-state-desc">Try clearing or expanding the date filter range to view works.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $sno = 1; foreach ($records as $row): 
                            $jc_card = !empty($row['cardNo']) ? $row['cardNo'] : ('JC-' . $row['id']);
                            $emp_name = !empty($row['employee_name']) ? $row['employee_name'] : 'Unassigned';
                            $emp_role = !empty($row['employee_role']) ? $row['employee_role'] : 'Worker';
                            
                            $alloc_raw = !empty($row['givenDate']) ? $row['givenDate'] : (!empty($row['createdOn']) ? substr($row['createdOn'], 0, 10) : null);
                            $alloc_str = $alloc_raw ? date('d/m/Y', strtotime($alloc_raw)) : '-';
                            
                            $comp_raw = !empty($row['completedDate']) ? $row['completedDate'] : null;
                            $comp_str = $comp_raw ? date('d/m/Y', strtotime($comp_raw)) : null;
                        ?>
                            <tr>
                                <!-- S.No (pure number series) -->
                                <td style="text-align: center; color: #64748b; font-weight: 600;">
                                    <?= $sno++ ?>
                                </td>

                                <!-- Jobcard Number (direct link as in jobcard list, underlined) -->
                                <td>
                                    <a href="../jobcard/edit.php?id=<?= urlencode($row['id']) ?>" 
                                       class="jobcard-link" 
                                       target="_blank" 
                                       title="Click to edit jobcard <?= htmlspecialchars($jc_card) ?>">
                                        <?= htmlspecialchars($jc_card) ?>
                                    </a>
                                </td>

                                <!-- Name (only employee name, no customer name) -->
                                <td>
                                    <?php if (!empty($row['employee_name'])): ?>
                                        <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($row['employee_name']) ?></strong>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">Unassigned</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Role (word only, no outline / background color) -->
                                <td style="color: #334155; font-size: 13px;">
                                    <?= htmlspecialchars($emp_role) ?>
                                </td>

                                <!-- Machine (machine name only, no S/N) -->
                                <td>
                                    <strong style="color: #1e293b; font-size: 13px;"><?= htmlspecialchars($row['machine_name']) ?></strong>
                                </td>

                                <!-- Allocated Date -->
                                <td style="color: #1e293b; font-size: 13px; font-weight: 500;">
                                    <?= $alloc_str ?>
                                </td>

                                <!-- Completed Date -->
                                <td style="font-size: 13px;">
                                    <?php if ($comp_str): ?>
                                        <span style="color: #15803d; font-weight: 600;"><?= $comp_str ?></span>
                                    <?php else: ?>
                                        <span style="color: #d97706; font-style: italic;">Pending</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Labour Charge -->
                                <td class="labour-amount-cell">
                                    <span class="labour-amount">₹ <?= number_format((float)$row['laborCharge'], 2) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <!-- Table Footer with Rightside Corner Total -->
                <?php if (!empty($records)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="color: #475569;">
                                <strong>TOTAL RECORDS: <?= count($records) ?> Works</strong>
                                &nbsp;(<span style="color: #15803d;"><?= $total_completed ?> Completed</span>, 
                                <span style="color: #c2410c;"><?= $total_pending ?> Pending</span>)
                            </td>
                            <td colspan="2" style="text-align: right; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                                <strong>Total Labour Charge:</strong>
                            </td>
                            <td class="labour-amount-cell" style="background: #f0fdf4;">
                                <span style="font-size: 16px; font-weight: 800; color: #166534;">
                                    ₹ <?= number_format($total_labor_charge, 2) ?>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

    </div>

</div>

<script>
    function toggleFilterPanel() {
        const panel = document.getElementById('filterPanel');
        if (panel) {
            panel.classList.toggle('open');
        }
    }

    function setTodayDates() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('from_date').value = today;
        document.getElementById('to_date').value = today;
        document.getElementById('filterForm').submit();
    }
</script>

<style media="print">
    #filterPanel, .filter-panel, .filter-panel *, .page-header, .page-header *, .no-print, form#filterForm {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }
</style>

<?php include(__DIR__ . "/../includes/footer.php"); ?>

<?php
require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../includes/auth.php");
requireAdmin();

// Filter inputs
$today_only = isset($_GET['today']) && $_GET['today'] == '1';
$all_dates   = isset($_GET['all_dates']) && $_GET['all_dates'] == '1';

if ($today_only) {
    $filter_from = date('Y-m-d');
    $filter_to   = date('Y-m-d');
} elseif ($all_dates) {
    $filter_from = '';
    $filter_to   = '';
} else {
    $filter_from = isset($_GET['from']) && $_GET['from'] !== '' ? trim($_GET['from']) : date('Y-m-01');
    $filter_to   = isset($_GET['to'])   && $_GET['to']   !== '' ? trim($_GET['to'])   : date('Y-m-d');
}

$filter_employee = isset($_GET['employee']) ? trim($_GET['employee']) : '';
$filter_jobcard  = isset($_GET['jobcard'])  ? trim($_GET['jobcard'])  : '';
$filter_status   = isset($_GET['status'])   ? trim($_GET['status'])   : '';

// Build WHERE conditions
$where_clauses = [];

if (!$all_dates && !empty($filter_from) && !empty($filter_to)) {
    $safe_from = mysqli_real_escape_string($conn, $filter_from);
    $safe_to   = mysqli_real_escape_string($conn, $filter_to);
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
$total_completed    = 0;
$total_pending      = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $records[]           = $row;
        $total_labor_charge += (float)$row['laborCharge'];
        $is_comp = ($row['is_completed'] == 1 || strtolower($row['jobStatus'] ?? '') === 'completed');
        if ($is_comp) $total_completed++;
        else           $total_pending++;
    }
}

// Resolve employee label for header meta
$filter_emp_label = '';
if (!empty($filter_employee) && is_numeric($filter_employee)) {
    $eQ = mysqli_query($conn, "SELECT name FROM employee WHERE id = " . (int)$filter_employee);
    if ($eQ && $eRow = mysqli_fetch_assoc($eQ)) {
        $filter_emp_label = $eRow['name'];
    }
} elseif (!empty($filter_employee)) {
    $filter_emp_label = $filter_employee;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Works Report</title>
<style>
    /* ── Page setup: A4 landscape for all 8 cols ── */
    @page {
        size: A4 landscape;
        margin: 8mm 10mm;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        color: #000;
        background: #fff;
        padding: 12px 15px;
    }

    /* ── On-screen toolbar (hidden in print) ── */
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        margin-bottom: 14px;
        font-size: 13px;
    }
    .toolbar .btn {
        padding: 7px 16px;
        border: none;
        border-radius: 4px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        color: #fff;
    }
    .btn-blue { background: #2563eb; }
    .btn-grey { background: #64748b; margin-left: 8px; }

    /* ── Report header ── */
    .rpt-header {
        text-align: center;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #000;
    }
    .rpt-company {
        font-size: 15px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .rpt-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 2px;
    }
    .rpt-meta {
        font-size: 9px;
        margin-top: 3px;
    }

    /* ── Data table ── */
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9.5px;
    }

    /* Column widths – totals must stay ≤ 277mm (A4 landscape − margins) */
    col.c-sno   { width: 25px;  }
    col.c-jc    { width: 82px;  }
    col.c-name  { width: 75px;  }
    col.c-role  { width: 60px;  }
    col.c-mach  { width: 80px;  }
    col.c-alloc { width: 62px;  }
    col.c-comp  { width: 62px;  }
    col.c-lc    { width: 62px;  }

    thead tr {
        background: #d1d5db;
    }

    th {
        border: 1px solid #000;
        padding: 5px 4px;
        font-size: 9px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
    }
    th.tc { text-align: center; }
    th.tr { text-align: right;  }

    td {
        border: 1px solid #000;
        padding: 4px 4px;
        vertical-align: middle;
        word-wrap: break-word;
        color: #000;
    }
    td.tc { text-align: center; }
    td.tr { text-align: right;  }

    tbody tr { page-break-inside: avoid; }

    tbody tr:nth-child(even) { background: #f9fafb; }

    tfoot tr {
        background: #e5e7eb;
    }
    tfoot td {
        border: 1.5px solid #000;
        padding: 5px 4px;
        font-weight: 700;
        font-size: 10px;
    }

    /* ── Print overrides ── */
    @media print {
        .toolbar { display: none !important; }
        body { padding: 0 !important; }
        tbody tr:nth-child(even) {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
</head>
<body onload="window.print()">

<!-- Toolbar – screen only, never prints -->
<div class="toolbar">
    <span><strong>SUNDER MACHINES</strong> — Employee Works Report Preview</span>
    <span>
        <button class="btn btn-blue" onclick="window.print()">🖨️ Print / Save PDF</button>
        <button class="btn btn-grey" onclick="window.close()">✕ Close</button>
    </span>
</div>

<!-- Report heading -->
<div class="rpt-header">
    <div class="rpt-company">Sunder Machines</div>
    <div class="rpt-title">Employee Works Report</div>
    <div class="rpt-meta">
        <?php if (!$all_dates && !empty($filter_from) && !empty($filter_to)): ?>
            Date: <?= date('d/m/Y', strtotime($filter_from)) ?> to <?= date('d/m/Y', strtotime($filter_to)) ?>
        <?php elseif ($all_dates): ?>
            Date: All Dates
        <?php endif; ?>
        <?php if (!empty($filter_emp_label)): ?> &nbsp;|&nbsp; Employee: <?= htmlspecialchars($filter_emp_label) ?><?php endif; ?>
        <?php if (!empty($filter_jobcard)):   ?> &nbsp;|&nbsp; Jobcard: <?= htmlspecialchars($filter_jobcard)     ?><?php endif; ?>
        <?php if (!empty($filter_status)):    ?> &nbsp;|&nbsp; Status: <?= ucfirst(htmlspecialchars($filter_status)) ?><?php endif; ?>
        &nbsp;|&nbsp; Printed: <?= date('d/m/Y H:i') ?>
    </div>
</div>

<!-- Data table -->
<table>
    <colgroup>
        <col class="c-sno">
        <col class="c-jc">
        <col class="c-name">
        <col class="c-role">
        <col class="c-mach">
        <col class="c-alloc">
        <col class="c-comp">
        <col class="c-lc">
    </colgroup>
    <thead>
        <tr>
            <th class="tc">#</th>
            <th>Jobcard No.</th>
            <th>Name</th>
            <th>Role</th>
            <th>Machine</th>
            <th>Allocated Dt</th>
            <th>Completed Dt</th>
            <th class="tr">Labour Charge</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr>
                <td colspan="8" class="tc" style="padding:20px;">No records found.</td>
            </tr>
        <?php else: ?>
            <?php $sno = 1; foreach ($records as $row):
                $jc_card  = !empty($row['cardNo'])        ? $row['cardNo']        : ('JC-' . $row['id']);
                $emp_name = !empty($row['employee_name']) ? $row['employee_name'] : 'Unassigned';
                $emp_role = !empty($row['employee_role']) ? $row['employee_role'] : '-';
                $mach     = !empty($row['machine_name'])  ? $row['machine_name']  : '-';

                $alloc_raw = !empty($row['givenDate'])     ? $row['givenDate']
                           : (!empty($row['createdOn'])    ? substr($row['createdOn'], 0, 10) : null);
                $alloc_str = $alloc_raw ? date('d/m/Y', strtotime($alloc_raw)) : '-';

                $comp_str  = !empty($row['completedDate']) ? date('d/m/Y', strtotime($row['completedDate'])) : 'Pending';
            ?>
            <tr>
                <td class="tc"><?= $sno++ ?></td>
                <td><?= htmlspecialchars($jc_card) ?></td>
                <td><?= htmlspecialchars($emp_name) ?></td>
                <td><?= htmlspecialchars($emp_role) ?></td>
                <td><?= htmlspecialchars($mach) ?></td>
                <td><?= $alloc_str ?></td>
                <td><?= $comp_str ?></td>
                <td class="tr">Rs. <?= number_format((float)$row['laborCharge'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <?php if (!empty($records)): ?>
    <tfoot>
        <tr>
            <td colspan="5">
                Total Records: <?= count($records) ?>
                &nbsp;(<?= $total_completed ?> Completed, <?= $total_pending ?> Pending)
            </td>
            <td colspan="2" class="tr">Total Labour Charge :</td>
            <td class="tr">Rs. <?= number_format($total_labor_charge, 2) ?></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>

</body>
</html>

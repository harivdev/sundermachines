<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Filtering parameters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$whereClauses = ["1=1"];

if (!empty($search)) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $whereClauses[] = "(name LIKE '%$searchEsc%' OR empId LIKE '%$searchEsc%' OR phoneNo1 LIKE '%$searchEsc%' OR phoneNo2 LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%' OR city LIKE '%$searchEsc%' OR designation LIKE '%$searchEsc%')";
}

if (!empty($roleFilter)) {
    $roleEsc = mysqli_real_escape_string($conn, $roleFilter);
    $whereClauses[] = "UPPER(role) = '$roleEsc'";
}

if ($statusFilter !== '') {
    $statusInt = intval($statusFilter);
    $whereClauses[] = "active = $statusInt";
}

// Fetch distinct roles for filter dropdown
$distinctRolesRes = mysqli_query($conn, "SELECT DISTINCT UPPER(role) as roleName FROM employee WHERE role IS NOT NULL AND role != '' ORDER BY roleName ASC");
$allRoles = ['ADMIN', 'TECHNICIAN', 'MANAGER', 'STAFF'];
if ($distinctRolesRes) {
    while ($rRow = mysqli_fetch_assoc($distinctRolesRes)) {
        $rName = strtoupper(trim($rRow['roleName']));
        if (!empty($rName) && !in_array($rName, $allRoles)) {
            $allRoles[] = $rName;
        }
    }
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$countRes = mysqli_query($conn, "SELECT COUNT(*) as total FROM employee WHERE $whereSql");
$totalRows = mysqli_fetch_assoc($countRes)['total'] ?? 0;
$totalPages = max(1, ceil($totalRows / $limit));

$employeesRes = mysqli_query($conn, "SELECT * FROM employee WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");

include("../includes/header.php");
?>

<div class="page-main-container erp-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    
    <!-- HEADER BAR -->
    <div class="erp-header-bar" style="background: #ffffff; padding: 16px 20px; border-radius: 8px 8px 0 0; border: 1px solid #cbd5e1; border-bottom: none; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div class="erp-header-title" style="font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>👥 Employee Management</span>
            <span style="font-size: 13px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 2px 10px; border-radius: 12px; border: 1px solid #cbd5e1;"><?= $totalRows ?> Total</span>
        </div>
        
        <!-- COLOR CODED OUTER ACTION BOXES -->
        <div class="erp-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="add.php" style="background: #2563eb; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                ➕ New Employee
            </a>
            <a href="list.php" style="background: #475569; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                🔄 Refresh
            </a>
            <a href="javascript:void(0)" onclick="document.getElementById('empFilter').style.display = document.getElementById('empFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">
                🔽 Filter
            </a>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="empFilter" class="erp-filter-panel" style="display:<?= (!empty($search) || !empty($roleFilter) || $statusFilter !== '') ? 'block' : 'none' ?>; background: #f8fafc; padding: 16px 20px; border: 1px solid #cbd5e1; border-radius: 0 0 8px 8px; margin-bottom: 20px;">
        <form method="GET" class="erp-filter-form" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label class="erp-label" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Search Name / Phone / Email / City</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search details..." class="erp-input" style="width:240px; border: 1px solid #cbd5e1; border-radius: 6px; height: 38px; padding: 0 12px;">
            </div>

            <div>
                <label class="erp-label" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Role</label>
                <select name="role" class="erp-select" style="width:140px; border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                    <option value="">-- All Roles --</option>
                    <?php foreach ($allRoles as $rOpt): ?>
                        <option value="<?= $rOpt ?>" <?= strtoupper($roleFilter) === $rOpt ? 'selected' : '' ?>><?= $rOpt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="erp-label" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Status</label>
                <select name="status" class="erp-select" style="width:130px; border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                    <option value="">-- All --</option>
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="erp-filter-actions-group" style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn-erp btn-erp-apply" style="background: #2563eb; color: #ffffff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 700; cursor: pointer;">Apply</button>
                <a href="list.php" class="btn-erp btn-erp-clear" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 18px; border-radius: 6px; font-weight: 600; text-decoration: none;">Clear</a>
            </div>
        </form>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            ✅ <?= htmlspecialchars($_SESSION['success_msg']) ?>
            <?php unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>

    <!-- MAIN TABLE -->
    <div class="erp-table-box" style="background: #ffffff; border-radius: 8px; border: 1px solid #cbd5e1; overflow-x: auto; width: 100%;">
        <table class="erp-table master-table" style="width: 100%; border-collapse: collapse; min-width: 0;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="width: 45px; padding: 12px 10px; text-align: center;">#</th>
                    <th style="padding: 12px 12px;">Emp ID</th>
                    <th style="padding: 12px 12px;">Name & Designation</th>
                    <th style="padding: 12px 12px;">Contact Info</th>
                    <th style="padding: 12px 12px;">City</th>
                    <th style="padding: 12px 12px; text-align: center;">Role</th>
                    <th style="padding: 12px 12px; text-align: center;">Status</th>
                    <th style="padding: 12px 12px; text-align: center; width: 120px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalRows > 0): ?>
                    <?php 
                    $i = $offset + 1;
                    while ($emp = mysqli_fetch_assoc($employeesRes)): 
                        $isActive = intval($emp['active']) === 1;
                        $roleName = strtoupper($emp['role'] ?? 'STAFF');
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                            <td style="padding: 10px; text-align: center; color: #64748b; font-size: 13.5px; font-weight: 600;"><?= $i++ ?></td>
                            <td style="padding: 10px; font-weight: 700; color: #1e293b; font-size: 13.5px; font-family: monospace;">
                                <?= htmlspecialchars($emp['empId'] ?? 'EMP-'.$emp['id']) ?>
                            </td>
                            <td style="padding: 10px;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?= htmlspecialchars($emp['name']) ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($emp['designation'] ?: ($emp['employmentType'] ?: 'Employee')) ?></div>
                            </td>
                            <td style="padding: 10px; font-size: 13px; color: #334155;">
                                <div>📞 <strong><?= htmlspecialchars($emp['phoneNo1']) ?></strong></div>
                                <?php if (!empty($emp['phoneNo2'])): ?>
                                    <div style="font-size: 12px; color: #16a34a;">💬 <?= htmlspecialchars($emp['phoneNo2']) ?> (WA)</div>
                                <?php endif; ?>
                                <?php if (!empty($emp['email'])): ?>
                                    <div style="font-size: 12px; color: #2563eb;">✉️ <?= htmlspecialchars($emp['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; font-size: 13.5px; color: #475569;">
                                <?= htmlspecialchars($emp['city'] ?: '-') ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: <?= $roleName === 'ADMIN' ? '#dbeafe' : ($roleName === 'TECHNICIAN' ? '#fef3c7' : '#f1f5f9') ?>; color: <?= $roleName === 'ADMIN' ? '#1e40af' : ($roleName === 'TECHNICIAN' ? '#92400e' : '#475569') ?>; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; border: 1px solid <?= $roleName === 'ADMIN' ? '#93c5fd' : ($roleName === 'TECHNICIAN' ? '#fde68a' : '#cbd5e1') ?>;">
                                    <?= htmlspecialchars($roleName) ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <?php if ($isActive): ?>
                                    <span style="background: #dcfce7; color: #166534; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 12px; border: 1px solid #86efac;">Active</span>
                                <?php else: ?>
                                    <span style="background: #fee2e2; color: #991b1b; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 12px; border: 1px solid #fca5a5;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <a href="edit.php?id=<?= $emp['id'] ?>" style="background: #475569; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                        ✏️ Edit
                                    </a>
                                    <a href="delete.php?id=<?= $emp['id'] ?>&action=toggle" onclick="return confirm('Change status for this employee?')" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; text-decoration: none; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center;">
                                        <?= $isActive ? '⏸️' : '▶️' ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px; color: #64748b; font-size: 14px;">
                            No employees found. <a href="add.php" style="color: #2563eb; font-weight: 700; text-decoration: underline;">Add a new employee</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 6px; margin-top: 20px; flex-wrap: wrap;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>" style="padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; background: <?= $p === $page ? '#2563eb' : '#ffffff' ?>; color: <?= $p === $page ? '#ffffff' : '#475569' ?>; border: 1px solid #cbd5e1;">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>

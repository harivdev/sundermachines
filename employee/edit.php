<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: list.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM employee WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($res);

if (!$employee) {
    $_SESSION['error_msg'] = "Employee not found.";
    header("Location: list.php");
    exit();
}

$empIdVal = intval($employee['id']);
$rawEmpId = trim($employee['empId'] ?? '');
$num = $empIdVal;
if (preg_match('/\d+/', $rawEmpId, $m)) {
    $num = intval($m[0]);
}
$employee['empId'] = 'EMP' . str_pad($num, 4, '0', STR_PAD_LEFT);

// Fetch allocated job cards for this employee
$empNameEsc = mysqli_real_escape_string($conn, $employee['name'] ?? '');
$empCodeEsc = mysqli_real_escape_string($conn, $employee['empId']);
$rawEmpCodeEsc = mysqli_real_escape_string($conn, $rawEmpId);

$allocatedJobs = [];
$allocatedSql = "
    SELECT 
        j.id, 
        j.cardNo, 
        j.jobStatus, 
        j.givenDate, 
        j.completedDate, 
        j.deliveryDate,
        c.name AS customerName
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    WHERE j.employee = $empIdVal
       OR (j.employee IS NOT NULL AND j.employee != '' AND j.employee = '$empNameEsc')
       " . (!empty($empCodeEsc) ? "OR (j.employee IS NOT NULL AND j.employee != '' AND j.employee = '$empCodeEsc')" : "") . "
       " . (!empty($rawEmpCodeEsc) && $rawEmpCodeEsc !== $empCodeEsc ? "OR (j.employee IS NOT NULL AND j.employee != '' AND j.employee = '$rawEmpCodeEsc')" : "") . "
    ORDER BY j.id DESC
";
$allocatedRes = @mysqli_query($conn, $allocatedSql);
if ($allocatedRes) {
    while ($jRow = mysqli_fetch_assoc($allocatedRes)) {
        $allocatedJobs[] = $jRow;
    }
}
$allocatedCount = count($allocatedJobs);
$cardNumbersList = array_map(function($j) { return $j['cardNo']; }, $allocatedJobs);
$cardNumbersDisplay = !empty($cardNumbersList) ? implode(', ', $cardNumbersList) : '';

include("../includes/header.php");
?>

<div class="page-main-container erp-container" style="width: 100%; padding: 20px;">

    <div class="erp-header-bar" style="margin-bottom: 20px;">
        <div class="erp-header-title">
            <span>✏️ Edit Employee: <?= htmlspecialchars($employee['name']) ?> (<?= htmlspecialchars($employee['empId']) ?>)</span>
        </div>
        <div class="erp-header-actions">
            <a href="list.php" class="btn-erp btn-erp-secondary" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none;">
                ⬅️ Back to Employee List
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            ⚠️ <?= htmlspecialchars($_SESSION['error_msg']) ?>
            <?php unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <form action="update.php" method="POST" autocomplete="off" style="width: 100%;">

        <input type="text" style="display:none" aria-hidden="true">
        <input type="password" style="display:none" aria-hidden="true">
        <input type="hidden" name="id" value="<?= $employee['id'] ?>">

        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>📋 Personal & Contact Details</span>
            </div>

            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Employee Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($employee['name']) ?>" required placeholder="Enter full name" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Primary Phone <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="phoneNo1" value="<?= htmlspecialchars($employee['phoneNo1']) ?>" required placeholder="Enter mobile number" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">WhatsApp Number</label>
                    <input type="text" name="phoneNo2" value="<?= htmlspecialchars($employee['phoneNo2'] ?? '') ?>" placeholder="Enter WhatsApp number" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Email ID</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" placeholder="employee@example.com" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Date of Birth</label>
                    <input type="date" name="dob" value="<?= $employee['dob'] ?? '' ?>" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Gender</label>
                    <select name="gender" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="">-- Select Gender --</option>
                        <option value="Male" <?= ($employee['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($employee['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($employee['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>📍 Address Details</span>
            </div>

            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Lane 1 / Street</label>
                    <input type="text" name="line1" value="<?= htmlspecialchars($employee['line1'] ?? '') ?>" placeholder="Address line 1" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Lane 2 / Area</label>
                    <input type="text" name="line2" value="<?= htmlspecialchars($employee['line2'] ?? '') ?>" placeholder="Address line 2" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($employee['city'] ?? '') ?>" placeholder="City name" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Zipcode / Pincode</label>
                    <input type="text" name="zipCode" value="<?= htmlspecialchars($employee['zipCode'] ?? '') ?>" placeholder="6-digit Pincode" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>
            </div>
        </div>

        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>🏢 Corporate & User Credentials</span>
            </div>

            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Employee ID</label>
                    <input type="text" name="empId" value="<?= htmlspecialchars($employee['empId']) ?>" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px; font-weight: 700;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Employment Type</label>
                    <select name="employmentType" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="Full-Time" <?= ($employee['employmentType'] ?? '') === 'Full-Time' ? 'selected' : '' ?>>Full-Time</option>
                        <option value="Part-Time" <?= ($employee['employmentType'] ?? '') === 'Part-Time' ? 'selected' : '' ?>>Part-Time</option>
                        <option value="Contract" <?= ($employee['employmentType'] ?? '') === 'Contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="Trainee" <?= ($employee['employmentType'] ?? '') === 'Trainee' ? 'selected' : '' ?>>Trainee</option>
                    </select>
                </div>

                <input type="hidden" name="designation" value="">

                <div class="form-group" style="position: relative;">
                    <label class="erp-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Allocated Jobs</span>
                        <?php if ($allocatedCount > 0): ?>
                            <span style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; border: 1px solid #fde68a;">
                                <?= $allocatedCount ?> <?= $allocatedCount === 1 ? 'Job' : 'Jobs' ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <!-- Allocated Jobs Dropdown Trigger -->
                    <div id="allocatedJobsTrigger" 
                         onclick="toggleAllocatedJobsDropdown(event)" 
                         class="erp-input" 
                         style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px; background: #ffffff; display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; box-sizing: border-box; transition: all 0.2s;"
                         title="<?= !empty($cardNumbersDisplay) ? htmlspecialchars($cardNumbersDisplay) : 'Click to see allocated job cards' ?>">
                        
                        <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex: 1; padding-right: 8px;">
                            <i class="fa-solid fa-wrench" style="color: <?= $allocatedCount > 0 ? '#d97706' : '#94a3b8' ?>; font-size: 13px; flex-shrink: 0;"></i>
                            <?php if ($allocatedCount > 0): ?>
                                <span style="font-weight: 600; color: #1e293b; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($cardNumbersDisplay) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 13px; font-style: italic;">
                                    No job cards allocated
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <i id="allocatedJobsChevron" class="fa-solid fa-chevron-down" style="color: #64748b; font-size: 11px; transition: transform 0.2s;"></i>
                        </div>
                    </div>

                    <!-- Allocated Jobs Dropdown Menu -->
                    <div id="allocatedJobsDropdown" 
                         style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); z-index: 1000; max-height: 280px; overflow-y: auto;">
                        
                        <?php if ($allocatedCount === 0): ?>
                            <div style="padding: 20px 14px; text-align: center; color: #94a3b8; font-size: 13px;">
                                <i class="fa-regular fa-folder-open" style="font-size: 24px; margin-bottom: 6px; display: block; color: #cbd5e1;"></i>
                                No job cards currently allocated to this employee.
                            </div>
                        <?php else: ?>
                            <div style="padding: 4px 0;">
                                <?php foreach ($allocatedJobs as $job): ?>
                                    <?php
                                    $status = trim($job['jobStatus'] ?? 'Pending');
                                    $statusBadgeBg = '#f1f5f9';
                                    $statusBadgeColor = '#475569';
                                    if (strcasecmp($status, 'Completed') === 0 || strcasecmp($status, 'Delivered') === 0) {
                                        $statusBadgeBg = '#dcfce7';
                                        $statusBadgeColor = '#15803d';
                                    } elseif (strcasecmp($status, 'In Progress') === 0) {
                                        $statusBadgeBg = '#dbeafe';
                                        $statusBadgeColor = '#1d4ed8';
                                    } elseif (strcasecmp($status, 'Pending') === 0) {
                                        $statusBadgeBg = '#fef3c7';
                                        $statusBadgeColor = '#b45309';
                                    } elseif (strcasecmp($status, 'Cancelled') === 0) {
                                        $statusBadgeBg = '#fee2e2';
                                        $statusBadgeColor = '#b91c1c';
                                    }
                                    $jobDate = !empty($job['givenDate']) ? date('d M Y', strtotime($job['givenDate'])) : '-';
                                    ?>
                                    <div class="allocated-job-item" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 10px; transition: background 0.15s;">
                                        <div style="min-width: 0; flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="font-weight: 700; color: #0f172a; font-size: 13px;">
                                                    #<?= htmlspecialchars($job['cardNo']) ?>
                                                </span>
                                                <span style="background: <?= $statusBadgeBg ?>; color: <?= $statusBadgeColor ?>; font-size: 11px; font-weight: 600; padding: 1px 6px; border-radius: 4px;">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </div>
                                            <div style="font-size: 11px; color: #64748b; margin-top: 3px; display: flex; gap: 10px; flex-wrap: wrap;">
                                                <span>📅 <?= $jobDate ?></span>
                                                <?php if (!empty($job['customerName'])): ?>
                                                    <span>👤 <?= htmlspecialchars($job['customerName']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="../jobcard/edit.php?id=<?= intval($job['id']) ?>" target="_blank" onclick="event.stopPropagation();" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 5px; font-size: 11px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                            View ↗
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="erp-label">Date of Joining</label>
                    <input type="date" name="joinedDate" value="<?= $employee['joinedDate'] ?? '' ?>" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">ERP Login Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($employee['username'] ?? '') ?>" placeholder="Username for system login" autocomplete="new-password" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">ERP Login Password (Leave blank to keep current)</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="editPasswordInput" name="password" placeholder="Leave blank to keep unchanged" autocomplete="new-password" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 40px 0 12px; height: 38px; width: 100%;">
                        <button type="button" onclick="togglePasswordVisibility('editPasswordInput', this)" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #64748b; font-size: 15px; padding: 0; outline: none;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <?php
                $stdRoles = ['STAFF', 'TECHNICIAN', 'MANAGER', 'ADMIN'];
                $currRole = strtoupper($employee['role'] ?? 'STAFF');
                $isCustomRole = !empty($currRole) && !in_array($currRole, $stdRoles);
                ?>
                <div class="form-group">
                    <label class="erp-label">System Role</label>
                    <select name="role" id="roleSelect" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;" onchange="toggleCustomRole(this)">
                        <option value="STAFF" <?= $currRole === 'STAFF' ? 'selected' : '' ?>>STAFF</option>
                        <option value="TECHNICIAN" <?= $currRole === 'TECHNICIAN' ? 'selected' : '' ?>>TECHNICIAN</option>
                        <option value="MANAGER" <?= $currRole === 'MANAGER' ? 'selected' : '' ?>>MANAGER</option>
                        <option value="ADMIN" <?= $currRole === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                        <option value="Other" <?= $isCustomRole ? 'selected' : '' ?>>Other</option>
                    </select>
                    <div id="customRoleBox" style="display: <?= $isCustomRole ? 'block' : 'none' ?>; margin-top: 8px;">
                        <input type="text" name="customRole" id="customRoleInput" value="<?= $isCustomRole ? htmlspecialchars($currRole) : '' ?>" placeholder="Enter custom role name..." class="erp-input" style="border: 1.5px solid #2563eb; border-radius: 6px; padding: 0 12px; height: 38px; background: #f0f9ff; font-weight: 600;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="erp-label">Account Status</label>
                    <select name="active" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="1" <?= intval($employee['active']) === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= intval($employee['active']) === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; flex-wrap: wrap;">
            <a href="list.php" style="background: #e2e8f0; color: #475569; padding: 10px 24px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                Cancel
            </a>
            <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 10px 30px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                💾 Update Employee
            </button>
        </div>

    </form>
</div>

<script>
function toggleCustomRole(selectElem) {
    const box = document.getElementById('customRoleBox');
    const input = document.getElementById('customRoleInput');
    if (selectElem.value === 'Other') {
        box.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        box.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

function toggleAllocatedJobsDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('allocatedJobsDropdown');
    const chevron = document.getElementById('allocatedJobsChevron');
    const trigger = document.getElementById('allocatedJobsTrigger');
    if (!dropdown) return;
    
    const isVisible = dropdown.style.display === 'block';
    if (isVisible) {
        dropdown.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        if (trigger) {
            trigger.style.borderColor = '#cbd5e1';
            trigger.style.boxShadow = 'none';
        }
    } else {
        dropdown.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (trigger) {
            trigger.style.borderColor = '#d97706';
            trigger.style.boxShadow = '0 0 0 2px rgba(217, 119, 6, 0.2)';
        }
    }
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('allocatedJobsDropdown');
    const trigger = document.getElementById('allocatedJobsTrigger');
    const chevron = document.getElementById('allocatedJobsChevron');
    if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        if (trigger) {
            trigger.style.borderColor = '#cbd5e1';
            trigger.style.boxShadow = 'none';
        }
    }
});
</script>

<style>
.allocated-job-item:hover {
    background-color: #f8fafc !important;
}
@media (max-width: 768px) {
    .erp-form-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>


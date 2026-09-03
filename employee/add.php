<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Auto-generate next Employee ID
$resCount = mysqli_query($conn, "SELECT MAX(id) as maxId FROM employee");
$rowC = mysqli_fetch_assoc($resCount);
$nextNum = intval($rowC['maxId'] ?? 0) + 1;
$defaultEmpId = 'EMP' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

include("../includes/header.php");
?>

<div class="page-main-container erp-container" style="max-width: 900px; margin: 0 auto; padding: 20px;">
    
    <!-- HEADER BAR -->
    <div class="erp-header-bar" style="margin-bottom: 20px;">
        <div class="erp-header-title">
            <span>👤 Add New Employee</span>
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

    <form action="insert.php" method="POST" style="width: 100%;">
        
        <!-- SECTION 1: PERSONAL & CONTACT INFO -->
        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>📋 Personal & Contact Details</span>
            </div>
            
            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Employee Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" required placeholder="Enter full name" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Primary Phone <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="phoneNo1" required placeholder="Enter mobile number" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">WhatsApp Number</label>
                    <input type="text" name="phoneNo2" placeholder="Enter WhatsApp number" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Email ID</label>
                    <input type="email" name="email" placeholder="employee@example.com" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Date of Birth</label>
                    <input type="date" name="dob" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Gender</label>
                    <select name="gender" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 2: ADDRESS DETAILS -->
        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>📍 Address Details</span>
            </div>

            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Lane 1 / Street</label>
                    <input type="text" name="line1" placeholder="Address line 1" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Lane 2 / Area</label>
                    <input type="text" name="line2" placeholder="Address line 2" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">City</label>
                    <input type="text" name="city" placeholder="City name" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Zipcode / Pincode</label>
                    <input type="text" name="zipCode" placeholder="6-digit Pincode" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>
            </div>
        </div>

        <!-- SECTION 3: CORPORATE & ERP CREDENTIALS -->
        <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div class="erp-card-header" style="font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>🏢 Corporate & User Credentials</span>
            </div>

            <div class="erp-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label class="erp-label">Employee ID</label>
                    <input type="text" name="empId" value="<?= htmlspecialchars($defaultEmpId) ?>" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px; font-weight: 700;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Employment Type</label>
                    <select name="employmentType" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="Full-Time">Full-Time</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Contract">Contract</option>
                        <option value="Trainee">Trainee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="erp-label">Designation</label>
                    <input type="text" name="designation" placeholder="e.g. Service Technician, Manager" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">Date of Joining</label>
                    <input type="date" name="joinedDate" value="<?= date('Y-m-d') ?>" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">ERP Login Username</label>
                    <input type="text" name="username" placeholder="Username for system login" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">ERP Login Password</label>
                    <input type="password" name="password" placeholder="Password for system login" class="erp-input" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px;">
                </div>

                <div class="form-group">
                    <label class="erp-label">System Role</label>
                    <select name="role" id="roleSelect" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;" onchange="toggleCustomRole(this)">
                        <option value="STAFF">STAFF</option>
                        <option value="TECHNICIAN">TECHNICIAN</option>
                        <option value="MANAGER">MANAGER</option>
                        <option value="ADMIN">ADMIN</option>
                        <option value="Other">Other</option>
                    </select>
                    <div id="customRoleBox" style="display: none; margin-top: 8px;">
                        <input type="text" name="customRole" id="customRoleInput" placeholder="Enter custom role name..." class="erp-input" style="border: 1.5px solid #2563eb; border-radius: 6px; padding: 0 12px; height: 38px; background: #f0f9ff; font-weight: 600;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="erp-label">Account Status</label>
                    <select name="active" class="erp-select" style="border: 1px solid #cbd5e1; border-radius: 6px; height: 38px;">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- BOTTOM FORM ACTIONS -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; flex-wrap: wrap;">
            <a href="list.php" style="background: #e2e8f0; color: #475569; padding: 10px 24px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                Cancel
            </a>
            <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 10px 30px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                💾 Save Employee
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
</script>

<style>
@media (max-width: 768px) {
    .erp-form-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

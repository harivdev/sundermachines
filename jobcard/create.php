<?php
require_once("../config/db.php");
include("../includes/header.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentDate = date('d/m/Y');
$currentYY = date('y');
$currentMM = date('m');
$currentYearVal = intval(date('Y'));

// Generate Card # in format YYMMJ00001 (e.g. 2608J00001)
if (empty($_SESSION['draft_jobcard_no']) || ($_SESSION['draft_jobcard_year'] ?? 0) !== $currentYearVal) {
    $lastSql = "SELECT cardNo FROM jobcard WHERE cardNo IS NOT NULL AND cardNo != '' ORDER BY id DESC LIMIT 100";
    $cardResult = mysqli_query($conn, $lastSql);
    $lastYear = null;
    $lastSeq = 0;
    if ($cardResult) {
        while ($row = mysqli_fetch_assoc($cardResult)) {
            $cNo = str_replace(['/', ' '], '', trim($row['cardNo'] ?? ''));
            if (preg_match('/^(\d{2})(\d{2})J(?:C)?(\d{5})$/', $cNo, $m)) {
                $lastYear = intval($m[1]);
                $lastSeq = intval($m[3]);
                break;
            } elseif (preg_match('/^(\d{2})\/(\d{2})\/J\/(\d+)$/', $cNo, $m)) {
                $lastYear = intval($m[1]);
                $lastSeq = intval($m[3]);
                break;
            }
        }
    }

    $targetYY = intval($currentYY);
    if ($lastYear !== null && $lastYear === $targetYY) {
        $nextSeq = $lastSeq + 1;
    } else {
        $nextSeq = 1;
    }

    $_SESSION['draft_jobcard_no'] = "{$currentYY}{$currentMM}J" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    $_SESSION['draft_jobcard_year'] = $currentYearVal;
}

$tempCardNo = $_SESSION['draft_jobcard_no'];

// Fetch Machines for dropdown
$machines = mysqli_query($conn, "SELECT id, machineName FROM machine WHERE active = 1");

// Fetch Employees / Technicians
$employees = mysqli_query($conn, "SELECT id, name FROM employee WHERE active = 1 ORDER BY name ASC");
?>

<div class="erp-container">

    <!-- HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Job Card</div>
        <div class="erp-header-actions">
            <a href="list.php" class="btn-erp btn-erp-new">
                <span>☰</span> Job Cards
            </a>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="erp-card" style="border-radius: 0 0 12px 12px; border-top: none;">

        <form id="jobCardForm" action="insert_jobcard.php" method="POST" enctype="multipart/form-data">

            <!-- CARD INFO -->
            <div class="erp-form-grid-3" style="margin-bottom: 24px;">
                <div class="erp-form-group">
                    <label class="erp-label">Card #</label>
                    <input type="text" value="<?= htmlspecialchars($tempCardNo) ?>" readonly class="erp-input" style="background: #f1f5f9; font-weight: 700; color: #1e293b;">
                    <input type="hidden" name="cardNo" value="<?= htmlspecialchars($tempCardNo) ?>">
                </div>
                <div class="erp-form-group">
                    <label class="erp-label">Date <span class="req">*</span></label>
                    <input type="text" name="givenDate" value="<?= $currentDate ?>" required class="erp-input" style="background: #f1f5f9; cursor: pointer;" onclick="(this.type='date')" onblur="(this.type='text')">
                </div>
                <div class="erp-form-group">
                    <label class="erp-label">Category</label>
                    <select name="jobCategory" class="erp-input" style="height: 42px;">
                        <option value="Offsite" selected>Offsite</option>
                        <option value="Onsite">Onsite</option>
                    </select>
                </div>
            </div>

            <!-- CUSTOMER INFO SECTION -->
            <div class="erp-card" style="margin-bottom: 24px;">
                <div class="erp-card-header">
                    <span>Customer Info:</span>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="openCustomerLookupModal()" class="btn-erp btn-erp-primary btn-erp-sm">
                            🔍 Search Customer
                        </button>
                        <button type="button" onclick="clearCustomer()" class="btn-erp btn-erp-warning btn-erp-sm">
                            Clear Customer Info
                        </button>
                    </div>
                </div>

                <input type="hidden" name="customerId" id="customerId" value="">
                <div class="erp-form-grid-3">
                    <div class="erp-form-group">
                        <label class="erp-label">Phone # Primary <span class="req">*</span></label>
                        <input type="text" name="customerPhone" id="customerPhone" class="erp-input" placeholder="Click or type to search customer..." autocomplete="off" onfocus="openCustomerLookupModal(this.value)" onclick="openCustomerLookupModal(this.value)">
                    </div>
                    <div class="erp-form-group">
                        <label class="erp-label">Name <span class="req">*</span></label>
                        <input type="text" name="customerName" id="customerName" class="erp-input" placeholder="Customer Name">
                    </div>
                    <div class="erp-form-group">
                        <label class="erp-label">City</label>
                        <input type="text" name="city" id="city" class="erp-input" placeholder="City">
                    </div>
                </div>
            </div>

            <div class="erp-form-grid jobcard-2col-grid">
                <!-- JOB DETAIL SECTION -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
                    <h4 style="margin-bottom: 25px; color: #334155; font-size: 16px; font-weight: 700;">Job Card Item #: 1</h4>

                    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 25px;">
                        <!-- Photo Upload & Preview Section -->
                        <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                            <div id="photoPreviewContainer" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%; align-items: center; justify-content: center;">
                                <div id="noPhotoPlaceholder" style="width: 100%; height: 130px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 12px; font-weight: 600; text-align: center; padding: 10px; box-sizing: border-box;">
                                    <span style="font-size: 28px; margin-bottom: 4px;">🖼️</span>
                                    <span>No Photo</span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
                                <button type="button" onclick="openErpCamera(function(dataUrl, file){ if(file){ try { let c = new DataTransfer(); c.items.add(file); const inp = document.getElementById('createCameraInput'); inp.files = c.files; previewPhotos(inp); } catch(e){} } })" style="flex: 1; min-width: 140px; background: #2563eb; color: #fff; border: none; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box;">
                                    📷 Take Photo
                                </button>
                                <button type="button" onclick="document.getElementById('createGalleryInput').click()" style="flex: 1; min-width: 140px; background: #475569; color: #fff; border: none; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box;">
                                    📁 Choose From Device
                                </button>
                            </div>

                            <input type="file" id="createCameraInput" name="jobcard_photos[]" accept="image/*" capture="environment" style="display: none;" onchange="previewPhotos(this)">
                            <input type="file" id="createGalleryInput" name="jobcard_photos[]" accept="image/*" multiple style="display: none;" onchange="previewPhotos(this)">
                        </div>

                        <div style="width: 100%; display: flex; flex-direction: column; gap: 15px;">
                            <div class="jobcard-2col-grid">
                                <div class="form-group">
                                    <label>Machine <span class="required">*</span></label>
                                    <input type="text" name="machineName" placeholder="Enter Machine Name" required style="width: 100%; height: 42px; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px;">
                                </div>
                                <div class="form-group">
                                    <label>Serial #</label>
                                    <div style="display:flex; gap:8px;">
                                        <input type="text" name="serial" id="jobcardSerialInput" placeholder="Serial Number" style="height: 42px; flex:1;">
                                    </div>
                                </div>
                            </div>
                            <div class="jobcard-2col-grid" style="align-items: end;">
                                  <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                                      <label style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">Work Details</label>
                                      <select name="workDetails" style="width: 100%; height: 42px; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px;">
                                          <option value="Service">Service</option>
                                          <option value="Repair">Repair</option>
                                          <option value="Replacement">Replacement</option>
                                      </select>
                                  </div>
                                  <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                                       <label style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;" title="Technician / Employee Allocated">Technician / Employee</label>
                                       <input type="text" name="employeeName" placeholder="Enter Technician Name" style="width: 100%; height: 42px; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px;">
                                   </div>
                             </div>          
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" placeholder="Enter service/repair remarks..." style="width: 100%; height: 100px; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 14px; color: #334155; resize: none;"></textarea>
                    </div>
                </div>

                <!-- RIGHT SIDE (LOGO PLACEHOLDER) -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 10px;">
                    <img src="../img/logo.png" alt="SUNDER MACHINES WORLD" style="max-width: 100%; height: auto; border-radius: 10px; max-height: 280px; object-fit: contain;">
                </div>
            </div>

            <div style="margin-top: 50px; display: flex; justify-content: flex-end; gap: 15px;">
                <button type="submit" style="background: #3b82f6; color: white; padding: 12px 35px; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer;">Submit</button>
                <button type="reset" style="background: #64748b; color: white; padding: 12px 35px; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer;">Reset</button>
            </div>

        </form>
    </div>
</div>

<!-- CUSTOMER LOOKUP MODAL -->
<div id="customerLookupModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1050px; width: 95%;">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 220px;">
                <h3 style="margin: 0; color: #0f172a; font-size: 17px; font-weight: 700; white-space: nowrap;">Customer Lookup</h3>
                <input type="text" id="modalSearchInput" placeholder="search..." autocomplete="off" oninput="triggerModalSearch(this.value)" style="flex: 1; min-width: 150px; max-width: 450px; height: 38px; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-size: 13px;">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" onclick="openNewCustomerModal()" style="background: #16a34a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; white-space: nowrap;">
                    + New Customer
                </button>
                <button type="button" onclick="closeCustomerLookupModal()" style="background: transparent; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
            </div>
        </div>
        <div class="modal-body" style="padding: 0; overflow-y: auto; max-height: 65vh;">
            <table class="cust-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; color: #475569; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px 14px;">Customer ID</th>
                        <th style="padding: 12px 14px;">Customer Name</th>
                        <th style="padding: 12px 14px;">Address</th>
                        <th style="padding: 12px 14px;">Contact Number</th>
                        <th style="padding: 12px 14px;">WhatsApp Number</th>
                        <th style="padding: 12px 14px; text-align: center;">Active Status</th>
                        <th style="padding: 12px 14px; text-align: center; width: 80px;">Choose</th>
                        <th style="padding: 12px 14px; text-align: center; width: 80px;">Edit</th>
                    </tr>
                </thead>
                <tbody id="modalCustTbody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px; color: #64748b;">Loading customers...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CUSTOMER EDIT / NEW MODAL -->
<div id="customerEditModal" class="modal-overlay" style="z-index: 10000; overflow-y: auto;">
    <div class="modal-content" style="max-width: 650px; width: 90%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        <div class="modal-header">
            <h3 id="editModalTitle" style="margin: 0; color: #0f172a; font-size: 18px; font-weight: 700;">Edit Customer</h3>
            <button type="button" onclick="closeCustomerEditModal()" style="background: transparent; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form id="customerEditForm" onsubmit="saveCustomerAjax(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1; margin: 0;">
            <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: calc(85vh - 120px); -webkit-overflow-scrolling: touch; flex: 1; touch-action: pan-y;">
                <input type="hidden" name="id" id="edit_id" value="0">
                <input type="hidden" name="address_id" id="edit_address_id" value="0">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size: 12px;">Customer ID</label>
                        <input type="text" name="customerId" id="edit_customerId" placeholder="Auto-generated if empty" style="height: 38px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px;">Customer Name <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_name" required placeholder="Full Name" style="height: 38px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size: 12px;">Primary Contact # <span class="required">*</span></label>
                        <input type="text" name="phoneNo1" id="edit_phoneNo1" required placeholder="Primary Phone" style="height: 38px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px;">Secondary Phone #</label>
                        <input type="text" name="phoneNo2" id="edit_phoneNo2" placeholder="Secondary Phone" style="height: 38px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size: 12px;">WhatsApp Number</label>
                        <input type="text" name="whatsAppNo" id="edit_whatsAppNo" placeholder="WhatsApp Number" style="height: 38px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px;">Email ID</label>
                        <input type="email" name="emailId" id="edit_emailId" placeholder="Email Address" style="height: 38px;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 12px;">Address Line 1</label>
                        <input type="text" name="line1" id="edit_line1" placeholder="Street Address / Line 1" style="height: 38px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 12px;">Address Line 2</label>
                        <input type="text" name="line2" id="edit_line2" placeholder="Line 2 / Area" style="height: 38px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label style="font-size: 12px;">City</label>
                            <input type="text" name="city" id="edit_city" placeholder="City" style="height: 38px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 12px;">Zip Code</label>
                            <input type="text" name="zipCode" id="edit_zipCode" placeholder="Zip Code" style="height: 38px;">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="active" id="edit_active" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="edit_active" style="margin: 0; font-size: 13px; font-weight: 600; cursor: pointer;">Active Status</label>
                </div>
            </div>
            <div style="padding: 15px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; border-radius: 0 0 12px 12px;">
                <button type="button" onclick="closeCustomerEditModal()" style="background: #94a3b8; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                <button type="submit" id="saveCustBtn" style="background: #2563eb; color: #fff; border: none; padding: 9px 24px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-group label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 0 15px;
        font-size: 14px;
        transition: 0.2s;
    }

    .form-group input {
        height: 42px;
    }

    .required {
        color: #ef4444;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* MODAL STYLES */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.15s ease-out;
    }

    .modal-content {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        max-height: 90vh;
    }

    .modal-header {
        padding: 16px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .cust-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }

    .cust-table tbody tr:hover {
        background: #f8fafc;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<script>
    let fetchedCustomers = [];
    let searchDebounceTimer = null;

    function clearCustomer() {
        ['customerId', 'customerPhone', 'customerName', 'city'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    }

    // Modal Control Functions
    function openCustomerLookupModal(initialValue = '') {
        const modal = document.getElementById('customerLookupModal');
        const input = document.getElementById('modalSearchInput');
        modal.style.display = 'flex';

        if (initialValue && initialValue.trim() !== '') {
            input.value = initialValue;
        }

        setTimeout(() => input.focus(), 100);
        fetchModalCustomers(input.value);
    }

    function closeCustomerLookupModal() {
        document.getElementById('customerLookupModal').style.display = 'none';
    }

    function triggerModalSearch(val) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            fetchModalCustomers(val);
        }, 200);
    }

    function fetchModalCustomers(query = '') {
        const tbody = document.getElementById('modalCustTbody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 25px; color: #64748b;">Searching customers...</td></tr>';

        fetch('../customers/api_search_customers.php?query=' + encodeURIComponent(query.trim()))
            .then(res => res.json())
            .then(resData => {
                if (!resData.success || !Array.isArray(resData.data) || resData.data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 35px; color: #64748b;">
                                <div style="margin-bottom: 10px; font-size: 15px;">No matching customers found</div>
                                <button type="button" onclick="openNewCustomerModal('${escapeHtml(query)}')" style="background: #16a34a; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;">
                                    + Add New Customer
                                </button>
                            </td>
                        </tr>`;
                    fetchedCustomers = [];
                    return;
                }

                fetchedCustomers = resData.data;
                tbody.innerHTML = resData.data.map((c, idx) => `
                    <tr>
                        <td style="padding: 12px 14px; font-weight: 600; color: #334155; font-size: 13px;">${escapeHtml(c.customerId || 'C-' + c.id)}</td>
                        <td style="padding: 12px 14px; font-weight: 700; color: #0f172a; font-size: 13.5px;">${escapeHtml(c.name || '-')}</td>
                        <td style="padding: 12px 14px; color: #475569; font-size: 12.5px;">${escapeHtml(c.fullAddress || '-')}</td>
                        <td style="padding: 12px 14px; font-weight: 600; color: #2563eb; font-size: 13px;">${escapeHtml(c.phoneNo1 || '-')}</td>
                        <td style="padding: 12px 14px; color: #475569; font-size: 12.5px;">${escapeHtml(c.whatsAppNo || '-')}</td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="${c.active ? 'badge-active' : 'badge-inactive'}">${c.active ? 'Active' : 'Inactive'}</span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <button type="button" onclick="chooseCustomer(${idx})" style="background: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: 0.15s;">Choose</button>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <button type="button" onclick="openEditCustomerModal(${idx})" style="background: #f59e0b; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: 0.15s;">Edit</button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 25px; color: #ef4444;">Error fetching customer data.</td></tr>';
            });
    }

    function chooseCustomer(idx) {
        const c = fetchedCustomers[idx];
        if (!c) return;

        document.getElementById('customerId').value = c.id || '';
        document.getElementById('customerPhone').value = c.phoneNo1 || '';
        document.getElementById('customerName').value = c.name || '';
        document.getElementById('city').value = c.city || '';

        closeCustomerLookupModal();
    }

    // New / Edit Customer Modal
    function openNewCustomerModal(initialPhoneOrName = '') {
        document.getElementById('editModalTitle').textContent = 'New Customer';
        document.getElementById('customerEditForm').reset();
        document.getElementById('edit_id').value = '0';
        document.getElementById('edit_address_id').value = '0';
        document.getElementById('edit_active').checked = true;

        if (initialPhoneOrName) {
            if (/^\d+$/.test(initialPhoneOrName.trim())) {
                document.getElementById('edit_phoneNo1').value = initialPhoneOrName.trim();
            } else {
                document.getElementById('edit_name').value = initialPhoneOrName.trim();
            }
        }

        document.getElementById('customerEditModal').style.display = 'flex';
    }

    function openEditCustomerModal(idx) {
        const c = fetchedCustomers[idx];
        if (!c) return;

        document.getElementById('editModalTitle').textContent = 'Edit Customer';
        document.getElementById('edit_id').value = c.id || '0';
        document.getElementById('edit_address_id').value = c.address_id || '0';
        document.getElementById('edit_customerId').value = c.customerId || '';
        document.getElementById('edit_name').value = c.name || '';
        document.getElementById('edit_phoneNo1').value = c.phoneNo1 || '';
        document.getElementById('edit_phoneNo2').value = c.phoneNo2 || '';
        document.getElementById('edit_whatsAppNo').value = c.whatsAppNo || '';
        document.getElementById('edit_emailId').value = c.emailId || '';
        document.getElementById('edit_line1').value = c.line1 || '';
        document.getElementById('edit_line2').value = c.line2 || '';
        document.getElementById('edit_city').value = c.city || '';
        document.getElementById('edit_zipCode').value = c.zipCode || '';
        document.getElementById('edit_active').checked = Boolean(c.active);

        document.getElementById('customerEditModal').style.display = 'flex';
    }

    function closeCustomerEditModal() {
        document.getElementById('customerEditModal').style.display = 'none';
    }

    function saveCustomerAjax(e) {
        e.preventDefault();
        const form = document.getElementById('customerEditForm');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveCustBtn');

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch('../customers/api_save_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Customer';

            if (!resData.success) {
                alert('Error: ' + (resData.error || 'Failed to save customer'));
                return;
            }

            const savedCust = resData.customer;
            closeCustomerEditModal();

            // Refresh search modal
            const currentSearch = document.getElementById('modalSearchInput').value;
            fetchModalCustomers(currentSearch);

            // If it was a new customer or the current selected customer, auto select into jobcard form!
            const currentFormCustId = document.getElementById('customerId').value;
            if (savedCust && (document.getElementById('edit_id').value === '0' || currentFormCustId == savedCust.id)) {
                document.getElementById('customerId').value = savedCust.id;
                document.getElementById('customerPhone').value = savedCust.phoneNo1 || '';
                document.getElementById('customerName').value = savedCust.name || '';
                document.getElementById('city').value = savedCust.city || '';
                closeCustomerLookupModal();
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Customer';
            alert('An error occurred while saving customer details.');
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    function previewPhotos(input) {
        const container = document.getElementById('photoPreviewContainer');
        if (!input.files || input.files.length === 0) return;

        // Clear empty placeholder if present
        const noPhotoDiv = container.querySelector('div');
        if (noPhotoDiv && container.textContent.includes('No Photo')) {
            container.innerHTML = '';
        }

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const maxFileSize = 10 * 1024 * 1024; // 10MB

        Array.from(input.files).forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                alert(`File "${file.name}" is not a supported image format. (Allowed: JPG, PNG, WEBP, GIF)`);
                return;
            }
            if (file.size > maxFileSize) {
                alert(`File "${file.name}" exceeds maximum allowed size of 10MB.`);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.className = 'photo-wrapper';
                wrapper.style.position = 'relative';
                wrapper.style.width = '75px';
                wrapper.style.height = '75px';
                wrapper.style.borderRadius = '8px';
                wrapper.style.overflow = 'hidden';
                wrapper.style.border = '1.5px solid #cbd5e1';
                wrapper.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                wrapper.style.background = '#f8fafc';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '&times;';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '2px';
                removeBtn.style.right = '2px';
                removeBtn.style.background = 'rgba(239, 68, 68, 0.9)';
                removeBtn.style.color = '#fff';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '20px';
                removeBtn.style.height = '20px';
                removeBtn.style.fontSize = '14px';
                removeBtn.style.fontWeight = '700';
                removeBtn.style.lineHeight = '1';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.display = 'flex';
                removeBtn.style.alignItems = 'center';
                removeBtn.style.justifyContent = 'center';
                removeBtn.onclick = function() { 
                    wrapper.remove(); 
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div style="width: 90px; height: 90px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 11px; font-weight: 600; text-align: center; padding: 4px;">
                                <span style="font-size: 24px;">🖼️</span>
                                <span>No Photo</span>
                            </div>
                        `;
                    }
                };

                wrapper.appendChild(img);
                wrapper.appendChild(removeBtn);
                container.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });
    }

    // DEDICATED CAMERA PHOTO CAPTURE LOGIC FOR JOBCARD
    let jobcardPhotoStream = null;
    let jobcardPhotoRunning = false;

    function openJobcardCameraModal() {
        const modal = document.getElementById('jobcardPhotoModal');
        if (modal) modal.style.display = 'flex';
        initJobcardPhotoCameras();
        startJobcardPhotoCamera();
    }

    function closeJobcardCameraModal() {
        stopJobcardPhotoCamera();
        const modal = document.getElementById('jobcardPhotoModal');
        if (modal) modal.style.display = 'none';
    }

    function onJobcardCameraSelectChange() {
        stopJobcardPhotoCamera();
        startJobcardPhotoCamera();
    }

    // KEYBOARD FIELD NAVIGATION (ENTER & ARROW KEYS)
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('jobCardForm');
        if (!form) return;

        form.addEventListener('keydown', function (e) {
            const target = e.target;
            if (!target || !['INPUT', 'SELECT'].includes(target.tagName)) return;
            if (target.type === 'submit' || target.type === 'button' || target.type === 'file') return;

            const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'));
            const index = inputs.indexOf(target);
            if (index === -1) return;

            // Enter Key or Down Arrow -> Move to Next Field
            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                if (target.tagName === 'SELECT' && e.key === 'ArrowDown') return;
                e.preventDefault();
                const nextInput = inputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                    if (typeof nextInput.select === 'function' && nextInput.tagName === 'INPUT' && nextInput.type === 'text') {
                        nextInput.select();
                    }
                } else {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.focus();
                }
            }
            // Up Arrow -> Move to Previous Field
            else if (e.key === 'ArrowUp') {
                if (target.tagName === 'SELECT') return;
                e.preventDefault();
                const prevInput = inputs[index - 1];
                if (prevInput) {
                    prevInput.focus();
                    if (typeof prevInput.select === 'function' && prevInput.tagName === 'INPUT' && prevInput.type === 'text') {
                        prevInput.select();
                    }
                }
            }
            // Right Arrow -> Move to Next Field (when cursor at end of input string)
            else if (e.key === 'ArrowRight') {
                if (target.tagName === 'SELECT') return;
                if (typeof target.selectionEnd === 'number' && target.selectionEnd === target.value.length) {
                    e.preventDefault();
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        if (typeof nextInput.select === 'function' && nextInput.tagName === 'INPUT' && nextInput.type === 'text') {
                            nextInput.select();
                        }
                    }
                }
            }
            // Left Arrow -> Move to Previous Field (when cursor at start of input string)
            else if (e.key === 'ArrowLeft') {
                if (target.tagName === 'SELECT') return;
                if (typeof target.selectionStart === 'number' && target.selectionStart === 0) {
                    e.preventDefault();
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                        if (typeof prevInput.select === 'function' && prevInput.tagName === 'INPUT' && prevInput.type === 'text') {
                            prevInput.select();
                        }
                    }
                }
            }
        });
    });

</script>

<?php include("../includes/footer.php"); ?>
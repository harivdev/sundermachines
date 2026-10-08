<?php
require_once("../config/db.php");
include("../includes/header.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$jcRes = mysqli_query($conn, "
    SELECT j.*, (j.completed + 0) AS completed_val, (j.delivered + 0) AS delivered_val, e.name AS employeeName, c.name AS customerName, c.phoneNo1, c.id AS cust_id, a.city, a.line1, a.line2, a.zipCode
    FROM jobcard j
    LEFT JOIN employee e ON j.employee = e.id
    LEFT JOIN customer c ON j.customer = c.id
    LEFT JOIN address a ON c.address = a.id
    WHERE j.id = $id
    LIMIT 1
");

if (!$jcRes || mysqli_num_rows($jcRes) === 0) {
    echo "<div style='padding: 30px; text-align: center; color: #ef4444; font-size: 18px;'>Job Card not found. <a href='list.php'>Back to List</a></div>";
    include("../includes/footer.php");
    exit;
}

$jobcard = mysqli_fetch_assoc($jcRes);
$cleanCardNo = str_replace(['/', ' '], '', $jobcard['cardNo']);

$existingPaymentMode = $jobcard['paymentMode'] ?? '';
if (empty($existingPaymentMode)) {
    $payChk = mysqli_query($conn, "SELECT mode FROM payment WHERE jobCard = $id ORDER BY createdOn DESC LIMIT 1");
    if ($payChk && mysqli_num_rows($payChk) > 0) {
        $existingPaymentMode = mysqli_fetch_assoc($payChk)['mode'] ?? 'Cash';
    } else {
        $existingPaymentMode = 'Cash';
    }
}
$stClean = strtolower(trim((string)($jobcard['jobStatus'] ?? '')));
$isAlreadyDelivered = (
    strpos($stClean, 'deliver') !== false ||
    (!empty($jobcard['delivered']) && ($jobcard['delivered'] == 1 || ord((string)$jobcard['delivered']) === 1)) ||
    (!empty($jobcard['delivered_val']) && $jobcard['delivered_val'] == 1) ||
    (!empty($jobcard['deliveryDate']) && $jobcard['deliveryDate'] !== '0000-00-00')
);

$itemRes = mysqli_query($conn, "SELECT * FROM jobcarditems WHERE jobCard = $id OR id = $id LIMIT 1");
$jcItem = $itemRes ? mysqli_fetch_assoc($itemRes) : [];

$last_cust = mysqli_fetch_assoc(mysqli_query($conn, "SELECT customerId FROM customer WHERE customerId LIKE 'C%' ORDER BY id DESC LIMIT 1"));
$next_cust_num = 1;
if ($last_cust && preg_match('/(\d+)$/', $last_cust['customerId'], $m)) {
    $next_cust_num = (int)$m[1] + 1;
}
$nextCustomerId = 'C' . str_pad($next_cust_num, 7, '0', STR_PAD_LEFT);

$machinesRes = mysqli_query($conn, "SELECT id, machineName FROM machine WHERE active = 1 ORDER BY machineName ASC");
$machinesList = [];
if ($machinesRes) {
    while ($mRow = mysqli_fetch_assoc($machinesRes)) {
        if (!empty(trim($mRow['machineName'] ?? ''))) {
            $machinesList[] = $mRow;
        }
    }
}
$currMachine = trim($jcItem['machineName'] ?? '');
$isKnownMachine = false;
foreach ($machinesList as $m) {
    if (strcasecmp($m['machineName'], $currMachine) === 0) {
        $isKnownMachine = true;
        break;
    }
}

$employees = mysqli_query($conn, "SELECT id, name FROM employee WHERE active = 1 ORDER BY name ASC");

$sparesQuery = "
    SELECT
        jis.*,
        s.id AS stock_id,
        s.availableQty,
        s.barCode,
        sp.partNo,
        sp.rackNumber,
        sp.picture
    FROM jobcarditemspares jis
    LEFT JOIN stock s ON jis.stock = s.id
    LEFT JOIN spares sp ON jis.spares = sp.id
    WHERE (jis.jobCardItem = {$id} OR jis.jobCardItem IN (SELECT id FROM jobcarditems WHERE jobCard = {$id}))
      AND jis.deleted = 0
";
$existingSparesRes = mysqli_query($conn, $sparesQuery);
$existingSpares = [];
if ($existingSparesRes) {
    while ($spRow = mysqli_fetch_assoc($existingSparesRes)) {
        $existingSpares[] = $spRow;
    }
}

$givenDate = !empty($jobcard['givenDate']) ? $jobcard['givenDate'] : date('Y-m-d');

$empListRes = mysqli_query($conn, "SELECT id, name, role FROM employee WHERE (active = 1 OR active IS NULL) AND name IS NOT NULL AND TRIM(name) != '' ORDER BY name ASC");
$employeesList = [];
if ($empListRes) {
    while ($empRow = mysqli_fetch_assoc($empListRes)) {
        $employeesList[] = $empRow;
    }
}
?>

<style>
  .scan-btn-blue {
    background: #0d6efd; color: #fff; border: none; border-radius: 6px;
    height: 36px; padding: 0 16px; font-weight: 600; font-size: 13px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box;
    white-space: nowrap; transition: background 0.15s ease;
  }
  .scan-btn-blue:hover { background: #0b5ed7; }

  .scan-btn-go {
    background: #16a34a; color: #fff; border: none; border-radius: 6px;
    height: 36px; padding: 0 16px; font-weight: 600; font-size: 13px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;
    white-space: nowrap; transition: background 0.15s ease;
  }
  .scan-btn-go:hover { background: #15803d; }

  .scan-barcode-input {
    height: 36px; width: 195px; padding: 0 12px; border: 1.5px solid #ced4da;
    border-radius: 6px; font-size: 13px; font-family: inherit; box-sizing: border-box;
    outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .scan-barcode-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
  }

  .btn-add-spare-row {
    background: #16a34a; color: #fff; border: none; border-radius: 6px;
    height: 36px; padding: 0 16px; font-weight: 700; font-size: 12px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box;
    white-space: nowrap; transition: background 0.15s ease;
  }
  .btn-add-spare-row:hover { background: #15803d; }

  .sales-toolbar-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .scan-tools-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .scan-input-group {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* Collapsible Section Styles */
  .section-toggle-btn {
    background: #f1f5f9;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #475569;
    font-size: 11px;
    transition: all 0.2s ease;
    padding: 0;
    flex-shrink: 0;
  }
  .section-toggle-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
    border-color: #94a3b8;
  }
  .collapsible-wrapper {
    display: grid;
    grid-template-rows: 1fr;
    transition: grid-template-rows 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease, margin-top 0.3s ease;
    opacity: 1;
    margin-top: 15px;
  }
  .collapsible-wrapper.collapsed {
    grid-template-rows: 0fr;
    opacity: 0;
    margin-top: 0;
  }
  .collapsible-inner {
    min-height: 0;
    overflow: hidden;
  }

  @media (max-width: 768px) {
    .sales-items-header {
      flex-direction: column !important;
      align-items: flex-start !important;
      gap: 10px !important;
    }
    .sales-toolbar-wrap {
      width: 100% !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 8px !important;
    }
    .scan-tools-row {
      display: flex !important;
      align-items: center !important;
      gap: 6px !important;
      width: 100% !important;
    }
    .scan-btn-blue {
      flex-shrink: 0 !important;
      width: 130px !important;
      padding: 0 8px !important;
      font-size: 12.5px !important;
      gap: 4px !important;
      box-sizing: border-box !important;
      justify-content: center !important;
    }
    .scan-input-group {
      display: flex !important;
      align-items: center !important;
      gap: 4px !important;
      flex: 1 !important;
      min-width: 0 !important;
    }
    .scan-barcode-input {
      flex: 1 !important;
      min-width: 0 !important;
      width: 100% !important;
      padding: 0 8px !important;
      font-size: 12px !important;
    }
    .scan-btn-go {
      flex-shrink: 0 !important;
      padding: 0 10px !important;
      font-size: 12px !important;
    }
    .btn-add-spare-row {
      width: 130px !important;
      align-self: flex-start !important;
      justify-content: center !important;
      box-sizing: border-box !important;
      padding: 0 8px !important;
    }
  }
</style>

<div id="scanToastContainer" style="position:fixed; top:80px; right:16px; z-index:100000; display:flex; flex-direction:column; gap:8px; pointer-events:none;"></div>

<div class="page-main-container erp-container" style="padding: 20px; background: #f8fafc;">

    <div class="jobcard-header-bar" style="background: #ffffff; display: flex; align-items: center; justify-content: space-between; border-radius: 12px 12px 0 0; padding: 16px 24px; border: 1px solid #e2e8f0; border-bottom: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 style="margin: 0; color: #0f172a; font-weight: 700; font-size: 22px;">Edit Job Card</h2>
            <span style="background: #eff6ff; color: #2563eb; padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; border: 1px solid #bfdbfe;">
                <?= htmlspecialchars($cleanCardNo) ?>
            </span>
        </div>
        <div class="jobcard-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="location.reload()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                🔄 Reload
            </button>
            <a href="list.php" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                📋 Back to List
            </a>
            <a href="print_receipt.php?id=<?= $id ?>&from=edit" target="_blank" style="background: #2563eb; color: #ffffff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                🖨️ Print
            </a>
        </div>
    </div>

    <div class="jobcard-main-card" style="background: #ffffff; border-radius: 0 0 12px 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); padding: 20px; border: 1px solid #e2e8f0; border-top: none;">

        <div id="alertContainer"></div>

        <form id="editJobCardForm" onsubmit="submitJobCardEdit(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="jobcard-section-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 15px 0; color: #334155; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Job Card Overview</h4>
                        <div class="jobcard-overview-grid">
                            <div class="form-group">
                                <label style="font-size: 13px;">Card # (Readonly)</label>
                                <input type="text" value="<?= htmlspecialchars($cleanCardNo) ?>" readonly style="background: #e2e8f0; font-weight: 700; color: #1e293b;">
                                <input type="hidden" name="cardNo" value="<?= htmlspecialchars($cleanCardNo) ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 13px;">Status <span class="required">*</span></label>
                                <select name="jobStatus" required style="height: 42px; font-weight: 600;">
                                    <?php
                                    $statuses = ['New', 'In Progress', 'Completed', 'Delivered', 'Cancelled'];
                                    $currStatus = $jobcard['jobStatus'] ?? 'New';
                                    foreach ($statuses as $st):
                                    ?>
                                        <option value="<?= $st ?>" <?= ($currStatus == $st) ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 13px;">Category</label>
                                <?php $currCat = $jobcard['jobCategory'] ?? 'Offsite'; ?>
                                <input type="text" value="<?= htmlspecialchars($currCat) ?>" readonly style="height: 42px; background: #f1f5f9; font-weight: 600; color: #334155; cursor: not-allowed;">
                                <input type="hidden" name="jobCategory" value="<?= htmlspecialchars($currCat) ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 13px;">Given Date <span class="required">*</span></label>
                                <input type="date" name="givenDate" value="<?= htmlspecialchars($givenDate) ?>" required style="height: 42px;">
                            </div>
                        </div>
                    </div>

                    <div class="jobcard-section-card" id="customerSectionCard" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleCustomerSection()" title="Click to collapse / expand Customer Information">
                                <h4 style="margin: 0; color: #334155; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Customer Information</h4>
                                <button type="button" id="customerSectionBtn" class="section-toggle-btn" title="Click to show Customer Details" onclick="event.stopPropagation(); toggleCustomerSection();">
                                    <i id="customerSectionIcon" class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="customerId" id="customerId" value="<?= htmlspecialchars($jobcard['cust_id'] ?? '') ?>">
                        <div id="customerCollapseWrapper" class="collapsible-wrapper collapsed">
                            <div class="collapsible-inner">
                                <div class="jobcard-cust-grid" style="padding-top: 2px;">
                                    <div class="form-group">
                                        <label style="font-size: 13px;">Phone # Primary <span class="required">*</span></label>
                                        <input type="text" name="customerPhone" id="customerPhone" value="<?= htmlspecialchars($jobcard['phoneNo1'] ?? '') ?>" required placeholder="Phone Number" readonly style="background: #f1f5f9; cursor: not-allowed; pointer-events: none;">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size: 13px;">Customer Name <span class="required">*</span></label>
                                        <input type="text" name="customerName" id="customerName" value="<?= htmlspecialchars($jobcard['customerName'] ?? '') ?>" required placeholder="Customer Name" readonly style="background: #f1f5f9; cursor: not-allowed; pointer-events: none;">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size: 13px;">City</label>
                                        <input type="text" name="city" id="city" value="<?= htmlspecialchars($jobcard['city'] ?? '') ?>" placeholder="City" readonly style="background: #f1f5f9; cursor: not-allowed; pointer-events: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="jobcard-section-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                        <div class="sales-items-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 8px;">
                            <h4 style="margin: 0; color: #334155; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Taken Spares</h4>
                            <div class="sales-toolbar-wrap">
                                <div class="scan-tools-row">
                                    <button type="button" class="scan-btn-blue" onclick="openSpareBarcodeScanner()">
                                        📷 Scan Barcode
                                    </button>
                                    <div class="scan-input-group">
                                        <input type="text" id="spareUsbBarcodeInput" class="scan-barcode-input" placeholder="Scan / Enter Barcode" autocomplete="off" onkeydown="if(event.key==='Enter'){handleSpareUsbBarcodeSubmit();event.preventDefault();}">
                                        <button type="button" class="scan-btn-go" onclick="handleSpareUsbBarcodeSubmit()">Go</button>
                                    </div>
                                </div>
                                <button type="button" class="btn-add-spare-row" onclick="openSpareSearchModal()">
                                    + Add Spare Row
                                </button>
                            </div>
                        </div>

                        <div style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                            <table class="spares-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                                <thead>
                                    <tr style="background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 10px; width: 50px;">Img</th>
                                        <th style="padding: 10px;">Spare Name</th>
                                        <th style="padding: 10px; width: 100px;">Barcode</th>
                                        <th style="padding: 10px; width: 90px;">Rack No</th>
                                        <th style="padding: 10px; width: 100px;">Part No</th>
                                        <th style="padding: 10px; width: 80px;">Qty</th>
                                        <th style="padding: 10px; width: 100px;">Price (₹)</th>
                                        <th style="padding: 10px; width: 80px;">GST %</th>
                                        <th style="padding: 10px; width: 110px;">Total (₹)</th>
                                        <th style="padding: 10px; width: 50px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="sparesTbody">
                                    <?php if (!empty($existingSpares)): ?>
                                        <?php foreach ($existingSpares as $sIdx => $sp):
                                            $sub = (float)$sp['quantity'] * (float)$sp['pricePerQty'];
                                            $gstVal = $sub * ((float)$sp['gstPercentage'] / 100);
                                            $rowTotal = $sub + $gstVal;
                                            $imgSrc = !empty($sp['picture']) && file_exists("../uploads/" . $sp['picture']) ? "../uploads/" . $sp['picture'] : "../img/no-image.png";
                                        ?>
                                            <tr class="spare-row">
                                                <td style="padding: 8px;">
                                                    <div style="width: 38px; height: 38px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px;">⚙️</div>
                                                </td>
                                                <td style="padding: 8px;">
                                                    <input type="text" name="spare_name[]" value="<?= htmlspecialchars($sp['itemName']) ?>" class="form-control spare-name" required style="height: 36px;" readonly>
                                                    <input type="hidden" name="spare_stock_id[]" value="<?= htmlspecialchars($sp['stock_id'] ?? $sp['stock'] ?? '') ?>" class="spare-stock-id">
                                                    <input type="hidden" name="spare_id[]" value="<?= htmlspecialchars($sp['spares'] ?? '') ?>" class="spare-id">
                                                    <input type="hidden" name="spare_created_on[]" value="<?= htmlspecialchars($sp['createdOn'] ?? '') ?>" class="spare-created-on">
                                                    <?php
                                                    $currAvail = intval($sp['availableQty'] ?? 0);
                                                    $itemAlloc = intval($sp['quantity'] ?? 0);
                                                    $totalAllowed = $currAvail + $itemAlloc;
                                                    ?>
                                                    <input type="hidden" class="spare-avail-qty" value="<?= $totalAllowed ?>">
                                                </td>
                                                <td style="padding: 8px;"><input type="text" name="spare_barcode[]" value="<?= htmlspecialchars($sp['barCode'] ?? '-') ?>" class="form-control spare-barcode" style="height: 36px;" readonly></td>
                                                <td style="padding: 8px;"><input type="text" name="spare_rack[]" value="<?= htmlspecialchars($sp['rackNumber'] ?? '-') ?>" class="form-control spare-rack" style="height: 36px;" readonly></td>
                                                <td style="padding: 8px;"><input type="text" name="spare_partno[]" value="<?= htmlspecialchars($sp['partNo'] ?? '-') ?>" class="form-control spare-partno" style="height: 36px;" readonly></td>
                                                <td style="padding: 8px;"><input type="number" name="spare_qty[]" value="<?= (int)$sp['quantity'] ?>" min="1" class="form-control spare-qty" oninput="calculateSparesTotal()" style="height: 36px; text-align: center;"></td>
                                                <td style="padding: 8px;"><input type="number" step="0.01" name="spare_price[]" value="<?= (float)$sp['pricePerQty'] ?>" min="0" class="form-control spare-price" oninput="calculateSparesTotal()" style="height: 36px;"></td>
                                                <td style="padding: 8px;"><input type="number" step="0.01" name="spare_gst[]" value="<?= (float)$sp['gstPercentage'] ?>" min="0" class="form-control spare-gst" oninput="calculateSparesTotal()" style="height: 36px; text-align: center;"></td>
                                                <td style="padding: 8px; font-weight: 700; color: #0f172a;" class="spare-row-total"><?= number_format(round($rowTotal), 0) ?></td>
                                                <td style="padding: 8px; text-align: center;">
                                                    <button type="button" onclick="removeSpareRow(this)" style="background: transparent; border: none; padding: 6px; cursor: pointer; font-size: 17px;" title="Remove Spare">🗑️</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="jobcard-section-card jobcard-summary-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);">
                        <h4 style="margin: 0 0 14px 0; color: #0f172a; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Summary</h4>

                        <div class="jobcard-summary-grid">

                            <!-- 1. Spare Total / Spares Subtotal (Top Left) -->
                            <div class="jobcard-summary-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px;">Spares Subtotal</div>
                                <div class="summary-val" style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px;" id="summarySparesSub">₹0.00</div>
                            </div>

                            <!-- 2. Total Amount (Top Right) -->
                            <div class="jobcard-summary-box" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.4px;">Total Amount</div>
                                <div class="summary-val" style="font-size: 22px; font-weight: 800; color: #1e3a8a; margin-top: 4px;" id="summaryTotal">₹0.00</div>
                            </div>

                            <!-- 3. Labour Charge (Middle Left) -->
                            <div class="jobcard-summary-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; width: 100%; min-height: 22px;">
                                    <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.4px;">Labour Charge (₹)</div>
                                </div>
                                <div style="width: 100%;">
                                    <input type="number" step="1" name="laborCharge" id="laborCharge" value="<?= floatval($jobcard['laborCharge'] ?? 0) > 0 ? number_format(round((float)$jobcard['laborCharge']), 0, '.', '') : '' ?>" placeholder="0" oninput="calculateSparesTotal()" style="height: 38px; font-size: 18px; font-weight: 800; color: #2563eb; background: #ffffff; max-width: 180px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 10px; box-sizing: border-box; text-align: left;">
                                </div>
                            </div>

                            <!-- 4. Paid Amount (Middle Right) -->
                            <div class="jobcard-summary-box" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="paid-amount-header" style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 6px; width: 100%; min-height: 22px;">
                                    <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.4px;">Paid Amount</div>
                                    <button type="button" onclick="autoFillPayment()" class="btn-add-payment" title="Auto-fill total amount as paid" style="background: #16a34a; color: #ffffff; border: none; padding: 3px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.08); transition: all 0.2s;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                                        <span>➕</span> <span class="add-pay-text">Add Payment</span>
                                    </button>
                                </div>
                                <div style="width: 100%;">
                                    <input type="number" step="1" name="paidAmount" id="paidAmount" value="<?= floatval($jobcard['receivedAmountSum'] ?? 0) > 0 ? number_format(round((float)$jobcard['receivedAmountSum']), 0, '.', '') : '' ?>" placeholder="0" oninput="calculateSparesTotal()" style="height: 38px; font-size: 18px; font-weight: 800; color: #15803d; background: #ffffff; max-width: 180px; width: 100%; border: 1.5px solid #86efac; border-radius: 6px; padding: 0 10px; box-sizing: border-box;">
                                </div>
                            </div>

                            <!-- 5. Mode of Payment (Bottom Left, directly under Labour Charge) -->
                            <div class="jobcard-summary-box" style="grid-column: 1; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">
                                    Payment Mode
                                </div>
                                <div style="width: 100%;">
                                    <?php if ($isAlreadyDelivered): 
                                        $dispExisting = (strcasecmp($existingPaymentMode, 'NetBanking') === 0) ? 'Net Banking' : $existingPaymentMode;
                                    ?>
                                        <div style="height: 38px; font-size: 14px; font-weight: 700; color: #334155; background: #f1f5f9; max-width: 180px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 10px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;" title="Delivered - Payment Mode Locked">
                                            <span><?= htmlspecialchars($dispExisting) ?></span>
                                            <span style="font-size: 10px; font-weight: 800; background: #e2e8f0; color: #64748b; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">Locked</span>
                                        </div>
                                        <input type="hidden" name="paymentMode" value="<?= htmlspecialchars($existingPaymentMode) ?>">
                                    <?php else: ?>
                                        <select name="paymentMode" id="paymentMode" style="height: 38px; font-size: 15px; font-weight: 700; color: #1e293b; background: #ffffff; max-width: 180px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 10px; box-sizing: border-box; cursor: pointer;">
                                            <option value="Cash" <?= ($existingPaymentMode === 'Cash') ? 'selected' : '' ?>>Cash</option>
                                            <option value="Card" <?= ($existingPaymentMode === 'Card') ? 'selected' : '' ?>>Card</option>
                                            <option value="UPI" <?= ($existingPaymentMode === 'UPI') ? 'selected' : '' ?>>UPI</option>
                                            <option value="NetBanking" <?= ($existingPaymentMode === 'NetBanking' || $existingPaymentMode === 'Net Banking') ? 'selected' : '' ?>>Net Banking</option>
                                            <option value="Cheque" <?= ($existingPaymentMode === 'Cheque') ? 'selected' : '' ?>>Cheque</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 6. Balance Amount (Bottom Right, directly under Paid Amount in same width) -->
                            <div class="jobcard-summary-box" style="grid-column: 2; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="summary-box-label" style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.4px;">Balance Amount</div>
                                <div class="summary-val" style="font-size: 22px; font-weight: 800; color: #991b1b; margin-top: 4px;" id="summaryBalance">₹0.00</div>
                            </div>

                        </div>
                    </div>

                    <div class="jobcard-section-card" id="machineSectionCard" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleMachineSection()" title="Click to collapse / expand Machine Details">
                                <h4 style="margin: 0; color: #334155; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Job Card Item Details</h4>
                                <button type="button" id="machineSectionBtn" class="section-toggle-btn" title="Click to show Machine Details" onclick="event.stopPropagation(); toggleMachineSection();">
                                    <i id="machineSectionIcon" class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>

                        <div id="machineCollapseWrapper" class="collapsible-wrapper collapsed">
                            <div class="collapsible-inner">
                                <div class="jobcard-2col-grid" style="margin-bottom: 15px; padding-top: 2px;">
                            <div class="form-group">
                                <label style="font-size: 13px;">Machine <span class="required">*</span></label>
                                <input type="text" name="machineName" value="<?= htmlspecialchars($currMachine) ?>" readonly style="width: 100%; height: 42px; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px; font-size: 14px; color: #1e293b; box-sizing: border-box; background: #f1f5f9; cursor: not-allowed; pointer-events: none;">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 13px;">Serial #</label>
                                <input type="text" name="serial" value="<?= htmlspecialchars($jcItem['serialNo'] ?? '') ?>" placeholder="Serial Number" readonly style="height: 42px; width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px; font-size: 14px; color: #1e293b; box-sizing: border-box; background: #f1f5f9; cursor: not-allowed; pointer-events: none;">
                            </div>
                        </div>

                        <div class="jobcard-2col-grid" style="margin-bottom: 15px; align-items: end;">
                            <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                                <label style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">Work Details</label>
                                <input type="text" name="workDetails" value="<?= htmlspecialchars($jcItem['issueDetails'] ?? 'Total Checkup') ?>" readonly style="height: 42px; background: #f1f5f9; cursor: not-allowed; pointer-events: none; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px; font-size: 14px; color: #1e293b; box-sizing: border-box;">
                            </div>
                            <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                                <label style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;" title="Technician / Employee Allocated">Technician / Employee</label>
                                <input type="text" name="employeeName" value="<?= htmlspecialchars($jobcard['employeeName'] ?? '') ?>" readonly style="height: 42px; background: #f1f5f9; cursor: not-allowed; pointer-events: none; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0 12px; font-size: 14px; color: #1e293b; box-sizing: border-box;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size: 13px;">Service / Repair Remarks</label>
                            <textarea name="remarks" placeholder="Enter detailed service/repair remarks..." readonly style="height: 80px; width: 100%; resize: none; background: #f1f5f9; cursor: not-allowed; pointer-events: none; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 14px; color: #1e293b; box-sizing: border-box;"><?= htmlspecialchars($jcItem['remark'] ?? '') ?></textarea>
                        </div>

                        <?php
                        $existingPhotos = [];
                        $rawPic = $jcItem['picture'] ?? '';
                        if (!empty($rawPic)) {
                            $decoded = json_decode($rawPic, true);
                            if (is_array($decoded)) {
                                $existingPhotos = $decoded;
                            } else {
                                $existingPhotos = array_filter(array_map('trim', explode(',', $rawPic)));
                            }
                        }
                        ?>
                        <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                            <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 12px; text-align: center;">
                                Job Card Item Photos
                            </label>

                            <div id="photoPreviewContainer" style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; align-items: center; justify-content: center; width: 100%;">
                                <?php if (!empty($existingPhotos)): ?>
                                    <?php foreach ($existingPhotos as $imgFile):
                                        $imgFileTrim = trim($imgFile);
                                        if (empty($imgFileTrim)) continue;

                                        $cleanPath = preg_replace('/^(\.\.\/)+/', '', $imgFileTrim);
                                        $cleanPath = ltrim($cleanPath, '/\\');

                                        $candidates = [
                                            "../uploads/jobcards/" . basename($cleanPath),
                                            "../" . $cleanPath,
                                            "../uploads/" . $cleanPath
                                        ];

                                        $imgPath = "../uploads/jobcards/" . basename($cleanPath);
                                        foreach ($candidates as $cand) {
                                            if (file_exists(__DIR__ . '/' . $cand)) {
                                                $imgPath = $cand;
                                                break;
                                            }
                                        }
                                    ?>
                                        <div class="photo-wrapper" style="position: relative; width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 1.5px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.08); background: #f8fafc;">
                                            <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($imgFileTrim) ?>">
                                            <img src="<?= htmlspecialchars($imgPath) ?>" onclick="openLightbox('<?= htmlspecialchars($imgPath) ?>')" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" title="Click to view full image">
                                            <button type="button" onclick="this.parentElement.remove()" style="position: absolute; top: 3px; right: 3px; background: rgba(239, 68, 68, 0.9); color: #fff; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 14px; font-weight: 700; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Remove Photo">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div id="noPhotoPlaceholder" style="max-width: 340px; width: 100%; height: 120px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 12px; font-weight: 600; text-align: center; padding: 10px; box-sizing: border-box; margin: 0 auto;">
                                        <span style="font-size: 28px; margin-bottom: 4px;">🖼️</span>
                                        <span>No Photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="photo-btn-row" style="display: flex; gap: 10px; width: 100%; max-width: 340px; justify-content: center; align-items: center; margin: 4px auto 0 auto; box-sizing: border-box;">
                                <button type="button" onclick="openErpCamera(function(dataUrl, file){ if(file){ try { let c = new DataTransfer(); c.items.add(file); const inp = document.getElementById('editCameraInput'); inp.files = c.files; previewPhotos(inp); } catch(e){} } })" style="flex: 1 1 0; max-width: 160px; min-width: 0; background: #2563eb; color: #ffffff; border: none; padding: 9px 12px; border-radius: 8px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15); transition: all 0.2s; white-space: nowrap; text-align: center;">
                                    📷 Take Photo
                                </button>
                                <button type="button" onclick="document.getElementById('editGalleryInput').click()" style="flex: 1 1 0; max-width: 160px; min-width: 0; background: #475569; color: #ffffff; border: none; padding: 9px 12px; border-radius: 8px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.15); transition: all 0.2s; white-space: nowrap; text-align: center;">
                                    📁 Choose File
                                </button>
                            </div>

                            <input type="file" id="editCameraInput" name="jobcard_photos[]" accept="image/*" capture="environment" style="display: none;" onchange="previewPhotos(this)">
                            <input type="file" id="editGalleryInput" name="jobcard_photos[]" accept="image/*" multiple style="display: none;" onchange="previewPhotos(this)">
                        </div>
                    </div>
                </div>
            </div>

            <div class="jobcard-bottom-actions" style="margin-top: 30px; border-top: 1.5px solid #e2e8f0; padding-top: 20px; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; width: 100%; box-sizing: border-box;">
                <a href="print_receipt.php?id=<?= $id ?>&from=edit" target="_blank" class="btn-bottom-act" style="background: #475569; color: #fff; padding: 11px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 13.5px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">🖨️ Print</a>
                <button type="reset" onclick="setTimeout(calculateSparesTotal, 100)" class="btn-bottom-act" style="background: #94a3b8; color: #fff; border: none; padding: 11px 20px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">🔄 Reset</button>
                <a href="list.php" class="btn-bottom-act" style="background: #e2e8f0; color: #334155; padding: 11px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 13.5px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">❌ Cancel</a>
                <button type="submit" id="saveJobCardBtn" class="btn-bottom-act" style="background: #2563eb; color: #fff; border: none; padding: 11px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">💾 Save Changes</button>
            </div>

        </form>
    </div>
</div>

<div id="spareSearchModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1050px; width: 95%;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                <h3 style="margin: 0; color: #0f172a; font-size: 18px; font-weight: 700;">Select Spare Part</h3>
                <input type="text" id="spareSearchInput" placeholder="Search by Spare Name, Code, Barcode, or Part No..." autocomplete="off" oninput="triggerSpareModalSearch(this.value)" style="flex: 1; max-width: 450px; height: 38px; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-size: 14px;">
            </div>
            <button type="button" onclick="closeSpareSearchModal()" style="background: transparent; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0; overflow-y: auto; overflow-x: auto; max-height: 65vh; -webkit-overflow-scrolling: touch; touch-action: pan-x pan-y; overscroll-behavior-x: contain;">
            <table class="cust-table" style="width: 100%; border-collapse: collapse; min-width: 820px;">
                <thead>
                    <tr style="background: #f8fafc; color: #475569; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px;">Spare Name</th>
                        <th style="padding: 12px;">Spare Code / Stock ID</th>
                        <th style="padding: 12px;">Barcode</th>
                        <th style="padding: 12px;">Rack No</th>
                        <th style="padding: 12px;">Part No</th>
                        <th style="padding: 12px; text-align: center;">Available Stock</th>
                        <th style="padding: 12px; text-align: right;">Selling Price (₹)</th>
                        <th style="padding: 12px; text-align: center;">GST %</th>
                        <th style="padding: 12px; text-align: center; width: 90px;">Action</th>
                    </tr>
                </thead>
                <tbody id="spareModalTbody">
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 30px; color: #64748b;">Loading available spares...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Spare Details Modal -->
<div id="spareDetailsConfirmModal" class="modal-overlay" style="z-index: 100005;">
    <div class="modal-content" style="max-width: 400px; width: 90%;">
        <div class="modal-header">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;">Spare Details</h3>
            <button type="button" onclick="closeSpareDetailsConfirmModal()" style="background: transparent; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <input type="hidden" id="confirmSpareIdx" value="">
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: #334155;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Spare Name:</span>
                    <span id="confirmSpareName" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Barcode Number:</span>
                    <span id="confirmSpareBarcode" style="font-weight: 700; color: #2563eb; font-family: monospace; font-size: 14px;"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Available Stock:</span>
                    <span id="confirmSpareAvail" style="font-weight: 700; color: #166534;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Wanted Stocks:</span>
                    <input type="number" id="confirmSpareWanted" value="1" min="1" oninput="updateConfirmSpareRemaining()" style="width: 80px; height: 32px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 700; padding: 0 8px;">
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Remaining Stock:</span>
                    <span id="confirmSpareRemaining" style="font-weight: 700;"></span>
                </div>
            </div>
            
            <div id="confirmSpareWarning" style="margin-top: 15px; padding: 10px; border-radius: 6px; font-size: 13px; font-weight: 600; display: none;"></div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeSpareDetailsConfirmModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                <button type="button" id="confirmSpareAddBtn" onclick="confirmAddSpareFromModal()" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">Confirm & Add</button>
            </div>
        </div>
    </div>
</div>

<div id="customerLookupModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1050px; width: 95%;">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 220px;">
                <h3 style="margin: 0; color: #0f172a; font-size: 17px; font-weight: 700; white-space: nowrap;">Customer Lookup</h3>
                <input type="text" id="modalSearchInput" placeholder="search..." autocomplete="off" oninput="triggerCustomerModalSearch(this.value)" style="flex: 1; min-width: 150px; max-width: 450px; height: 38px; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-size: 13px;">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-left: auto;">
                <button type="button" onclick="openNewCustomerModal()" style="background: #16a34a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; white-space: nowrap;">
                    + New Customer
                </button>
                <button type="button" onclick="closeCustomerLookupModal()" style="background: transparent; border: none; font-size: 14px; font-weight: 700; color: #dc2626; cursor: pointer; padding: 6px 12px; line-height: 1; white-space: nowrap; margin-left: auto;">Close</button>
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

<div id="customerEditModal" class="modal-overlay" style="z-index: 10000; overflow-y: auto;">
    <div class="modal-content" style="max-width: 650px; width: 90%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        <div class="modal-header">
            <h3 id="editModalTitle" style="margin: 0; color: #0f172a; font-size: 18px; font-weight: 700;">Edit Customer</h3>
            <button type="button" onclick="closeCustomerEditModal()" style="background: transparent; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form id="customerEditForm" onsubmit="saveCustomerAjax(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1; margin: 0;">
            <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: calc(85vh - 120px); -webkit-overflow-scrolling: touch; flex: 1; touch-action: pan-x pan-y;">
                <input type="hidden" name="id" id="edit_id" value="0">
                <input type="hidden" name="address_id" id="edit_address_id" value="0">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size: 12px;">Customer ID</label>
                        <input type="text" name="customerId" id="edit_customerId" readonly style="height: 38px; background: #eef5f1; cursor: not-allowed;">
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
        margin-bottom: 6px;
        font-size: 13px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea,
    .form-control {
        width: 100%;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 14px;
        transition: 0.15s ease-in-out;
    }

    input[readonly], 
    input[readonly].form-control {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }

    .required {
        color: #ef4444;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

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
    let fetchedSpareItems = [];
    let spareSearchDebounce = null;
    let fetchedCustomers = [];
    let custSearchDebounce = null;

    document.addEventListener('DOMContentLoaded', () => {
        calculateSparesTotal();
    });

    function calculateSparesTotal() {
        let sparesSubtotal = 0;
        const rows = document.querySelectorAll('#sparesTbody tr.spare-row');

        rows.forEach(row => {
            const qtyInput = row.querySelector('.spare-qty');
            const priceInput = row.querySelector('.spare-price');
            const gstInput = row.querySelector('.spare-gst');
            const totalCell = row.querySelector('.spare-row-total');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
            const gst = parseFloat(gstInput ? gstInput.value : 0) || 0;

            const sub = Math.round(qty * price);
            const gstVal = Math.round(sub * (gst / 100));
            const rowTotal = Math.round(sub + gstVal);

            if (totalCell) {
                totalCell.textContent = '₹' + rowTotal;
            }

            sparesSubtotal += rowTotal;
        });

        const laborChargeInput = document.getElementById('laborCharge');
        const paidAmountInput = document.getElementById('paidAmount');

        const laborCharge = Math.round(parseFloat(laborChargeInput ? laborChargeInput.value : 0) || 0);
        const paidAmount = Math.round(parseFloat(paidAmountInput ? paidAmountInput.value : 0) || 0);

        const grandTotal = Math.round(sparesSubtotal + laborCharge);
        const balanceAmount = Math.round(grandTotal - paidAmount);

        if (document.getElementById('summarySparesSub')) document.getElementById('summarySparesSub').textContent = '₹' + Math.round(sparesSubtotal);
        if (document.getElementById('summaryLabour')) document.getElementById('summaryLabour').textContent = '₹' + laborCharge;
        if (document.getElementById('summaryTotal')) document.getElementById('summaryTotal').textContent = '₹' + grandTotal;
        if (document.getElementById('summaryBalance')) document.getElementById('summaryBalance').textContent = '₹' + balanceAmount;

        const statusSelect = document.querySelector('select[name="jobStatus"]');
        if (statusSelect) {
            if (paidAmount > 0) {
                statusSelect.value = 'Delivered';
            } else if (laborCharge > 0) {
                statusSelect.value = 'Completed';
            } else if (rows.length > 0 && (statusSelect.value === 'New' || statusSelect.value === 'New Job')) {
                statusSelect.value = 'In Progress';
            }
        }
    }

    function removeSpareRow(btn) {
        const row = btn.closest('tr');
        if (row) {
            row.remove();
            calculateSparesTotal();
        }
    }

    function autoFillPayment() {
        const totalEl = document.getElementById('summaryTotal');
        const paidInput = document.getElementById('paidAmount');
        if (totalEl && paidInput) {
            const totalVal = Math.round(parseFloat(totalEl.textContent.replace(/[^\d.-]/g, '')) || 0);
            paidInput.value = totalVal;
            calculateSparesTotal();
            paidInput.focus();
        }
    }

    function toggleCustomerSection() {
        const wrapper = document.getElementById('customerCollapseWrapper');
        const icon = document.getElementById('customerSectionIcon');
        const btn = document.getElementById('customerSectionBtn');
        if (!wrapper || !icon) return;

        const isCollapsed = wrapper.classList.contains('collapsed');
        if (isCollapsed) {
            wrapper.classList.remove('collapsed');
            icon.className = 'fa-solid fa-chevron-up';
            if (btn) btn.title = 'Click to hide Customer Details';
        } else {
            wrapper.classList.add('collapsed');
            icon.className = 'fa-solid fa-chevron-down';
            if (btn) btn.title = 'Click to show Customer Details';
        }
    }

    function toggleMachineSection() {
        const wrapper = document.getElementById('machineCollapseWrapper');
        const icon = document.getElementById('machineSectionIcon');
        const btn = document.getElementById('machineSectionBtn');
        if (!wrapper || !icon) return;

        const isCollapsed = wrapper.classList.contains('collapsed');
        if (isCollapsed) {
            wrapper.classList.remove('collapsed');
            icon.className = 'fa-solid fa-chevron-up';
            if (btn) btn.title = 'Click to hide Machine Details';
        } else {
            wrapper.classList.add('collapsed');
            icon.className = 'fa-solid fa-chevron-down';
            if (btn) btn.title = 'Click to show Machine Details';
        }
    }

    function openSpareSearchModal() {
        document.getElementById('spareSearchModal').style.display = 'flex';
        const input = document.getElementById('spareSearchInput');
        input.value = '';
        setTimeout(() => input.focus(), 100);
        fetchModalSpares('');
    }

    function closeSpareSearchModal() {
        document.getElementById('spareSearchModal').style.display = 'none';
    }

    function triggerSpareModalSearch(val) {
        clearTimeout(spareSearchDebounce);
        spareSearchDebounce = setTimeout(() => {
            fetchModalSpares(val);
        }, 200);
    }

    function fetchModalSpares(query = '') {
        const tbody = document.getElementById('spareModalTbody');
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 25px; color: #64748b;">Searching available spares...</td></tr>';

        fetch('../spares/api_search_spares.php?query=' + encodeURIComponent(query.trim()))
            .then(res => res.json())
            .then(resData => {
                if (!resData.success || !Array.isArray(resData.data) || resData.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 30px; color: #64748b;">No matching spare parts found.</td></tr>';
                    fetchedSpareItems = [];
                    return;
                }

                fetchedSpareItems = resData.data;
                tbody.innerHTML = resData.data.map((item, idx) => `
                    <tr>
                        <td style="padding: 10px 12px; font-weight: 700; color: #0f172a; font-size: 13px;">${escapeHtml(item.spareName || '-')}</td>
                        <td style="padding: 10px 12px; font-weight: 600; color: #334155; font-size: 12.5px;" title="${escapeHtml(item.stock_id || '-')}">${escapeHtml((item.stock_id || '-').length > 8 ? (item.stock_id.substring(0, 8) + '...') : (item.stock_id || '-'))}</td>
                        <td style="padding: 10px 12px; color: #475569; font-size: 12.5px;">${escapeHtml(item.barCode || '-')}</td>
                        <td style="padding: 10px 12px; color: #475569; font-size: 12.5px;">${escapeHtml(item.rackNumber || '-')}</td>
                        <td style="padding: 10px 12px; color: #475569; font-size: 12.5px;">${escapeHtml(item.partNo || '-')}</td>
                        <td style="padding: 10px 12px; text-align: center; font-weight: 700; color: ${item.availableQty > 0 ? '#166534' : '#ef4444'}; font-size: 13px;">
                            ${item.availableQty}
                        </td>
                        <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #2563eb; font-size: 13px;">₹${parseFloat(item.sellingPrice || 0).toFixed(2)}</td>
                        <td style="padding: 10px 12px; text-align: center; font-size: 12.5px;">${item.gstPercentage}%</td>
                        <td style="padding: 10px 12px; text-align: center;">
                            <button type="button" onclick="selectSpareFromModal(${idx})" style="background: #16a34a; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;">
                                Select
                            </button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 25px; color: #ef4444;">Error searching spares.</td></tr>';
            });
    }

    let currentSelectedSpareItem = null;

    function selectSpareFromModal(idx) {
        const item = fetchedSpareItems[idx];
        if (!item) return;
        
        currentSelectedSpareItem = item;
        document.getElementById('confirmSpareIdx').value = idx;
        document.getElementById('confirmSpareName').textContent = item.spareName || '-';
        document.getElementById('confirmSpareBarcode').textContent = item.barCode || item.barcode || '-';
        document.getElementById('confirmSpareAvail').textContent = item.availableQty || '0';
        document.getElementById('confirmSpareWanted').value = '1';
        
        updateConfirmSpareRemaining();
        
        document.getElementById('spareDetailsConfirmModal').style.display = 'flex';
    }
    
    function closeSpareDetailsConfirmModal() {
        document.getElementById('spareDetailsConfirmModal').style.display = 'none';
        currentSelectedSpareItem = null;
    }
    
    function updateConfirmSpareRemaining() {
        const avail = parseInt(document.getElementById('confirmSpareAvail').textContent) || 0;
        const wanted = parseInt(document.getElementById('confirmSpareWanted').value) || 0;
        const remaining = avail - wanted;
        const remainingEl = document.getElementById('confirmSpareRemaining');
        const warningEl = document.getElementById('confirmSpareWarning');
        const confirmBtn = document.getElementById('confirmSpareAddBtn');
        
        remainingEl.textContent = remaining;
        
        if (remaining < 0) {
            remainingEl.style.color = '#ef4444';
            warningEl.style.display = 'block';
            warningEl.style.background = '#fef2f2';
            warningEl.style.color = '#991b1b';
            warningEl.style.border = '1px solid #fecaca';
            warningEl.innerHTML = '⚠️ No stock to add. Please purchase it quickly.';
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.5';
            confirmBtn.style.cursor = 'not-allowed';
        } else {
            if (remaining === 0) {
                remainingEl.style.color = '#f59e0b';
            } else {
                remainingEl.style.color = '#166534';
            }
            warningEl.style.display = 'none';
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            confirmBtn.style.cursor = 'pointer';
        }
    }

    function confirmAddSpareFromModal() {
        const idx = document.getElementById('confirmSpareIdx').value;
        const wanted = parseInt(document.getElementById('confirmSpareWanted').value) || 1;
        
        let item = currentSelectedSpareItem;
        if (!item && typeof fetchedSpareItems !== 'undefined' && fetchedSpareItems[idx]) {
            item = fetchedSpareItems[idx];
        }
        if (!item) return;

        closeSpareDetailsConfirmModal();

        // Check if this spare is already in spares table (if so, increase quantity)
        const stockIdToMatch = item.stock_id || item.id || '';
        const spareIdToMatch = item.spare_id || item.spares || '';
        const existingRows = document.querySelectorAll('#sparesTbody tr.spare-row');
        for (const row of existingRows) {
            const rowStockId = row.querySelector('.spare-stock-id')?.value;
            const rowSpareId = row.querySelector('.spare-id')?.value;
            if ((stockIdToMatch && rowStockId === stockIdToMatch) || (spareIdToMatch && rowSpareId == spareIdToMatch)) {
                const qtyInput = row.querySelector('.spare-qty');
                if (qtyInput) {
                    const currentQty = parseInt(qtyInput.value) || 0;
                    qtyInput.value = currentQty + wanted;
                    calculateSparesTotal();
                    showScanToast(`✓ ${escapeHtml(item.spareName || 'Spare')} qty updated (+${wanted})`, 'success');
                    closeSpareSearchModal();
                    return;
                }
            }
        }

        const tbody = document.getElementById('sparesTbody');
        const tr = document.createElement('tr');
        tr.className = 'spare-row';

        const unitPrice = parseFloat(item.sellingPrice || item.sellingPricePerUnit || 0);
        const gstPct = parseFloat(item.gstPercentage || 0);
        const rowTotal = (unitPrice * (1 + (gstPct / 100))) * wanted;

        tr.innerHTML = `
            <td style="padding: 8px;">
                <div style="width: 38px; height: 38px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px;">⚙️</div>
            </td>
            <td style="padding: 8px;">
                <input type="text" name="spare_name[]" value="${escapeHtml(item.spareName || '')}" class="form-control spare-name" required style="height: 36px;" readonly>
                <input type="hidden" name="spare_stock_id[]" value="${escapeHtml(item.stock_id || item.id || '')}" class="spare-stock-id">
                <input type="hidden" name="spare_id[]" value="${escapeHtml(item.spare_id || item.spares || '')}" class="spare-id">
                <input type="hidden" name="spare_created_on[]" value="" class="spare-created-on">
                <input type="hidden" class="spare-avail-qty" value="${item.availableQty || 0}">
            </td>
            <td style="padding: 8px;"><input type="text" name="spare_barcode[]" value="${escapeHtml(item.barCode || '-')}" class="form-control spare-barcode" style="height: 36px;" readonly></td>
            <td style="padding: 8px;"><input type="text" name="spare_rack[]" value="${escapeHtml(item.rackNumber || '-')}" class="form-control spare-rack" style="height: 36px;" readonly></td>
            <td style="padding: 8px;"><input type="text" name="spare_partno[]" value="${escapeHtml(item.partNo || '-')}" class="form-control spare-partno" style="height: 36px;" readonly></td>
            <td style="padding: 8px;"><input type="number" name="spare_qty[]" value="${wanted}" min="1" class="form-control spare-qty" oninput="calculateSparesTotal()" style="height: 36px; text-align: center;"></td>
            <td style="padding: 8px;"><input type="number" step="0.01" name="spare_price[]" value="${unitPrice.toFixed(2)}" min="0" class="form-control spare-price" oninput="calculateSparesTotal()" style="height: 36px;"></td>
            <td style="padding: 8px;"><input type="number" step="0.01" name="spare_gst[]" value="${gstPct.toFixed(2)}" min="0" class="form-control spare-gst" oninput="calculateSparesTotal()" style="height: 36px; text-align: center;"></td>
            <td style="padding: 8px; font-weight: 700; color: #0f172a;" class="spare-row-total">₹${rowTotal.toFixed(2)}</td>
            <td style="padding: 8px; text-align: center;">
                <button type="button" onclick="removeSpareRow(this)" style="background: transparent; border: none; padding: 6px; cursor: pointer; font-size: 17px;" title="Remove Spare">🗑️</button>
            </td>
        `;

        tbody.appendChild(tr);
        calculateSparesTotal();
        closeSpareSearchModal();
        showScanToast(`✓ ${escapeHtml(item.spareName || 'Spare')} added to table`, 'success');
    }

    function openStockPopupForScannedSpare(data) {
        if (!data) return;

        const price = parseFloat(data.sellingPrice ?? data.sellingPricePerUnit ?? data.sellingPricePerQty ?? data.selledPricePerUnit ?? 0);
        const gst = parseFloat(data.gstPercentage ?? 0);

        currentSelectedSpareItem = {
            spareName: data.spareName || data.itemName || 'Spare Item',
            stock_id: data.stock_id || data.id || '',
            spare_id: data.spare_id || data.spareId || data.spare || data.spares || '',
            barCode: data.barCode || '',
            rackNumber: data.rackNumber || '-',
            partNo: data.partNo || '-',
            availableQty: parseInt(data.availableQty) || 0,
            sellingPrice: price,
            gstPercentage: gst
        };

        document.getElementById('confirmSpareIdx').value = 'SCANNED';
        document.getElementById('confirmSpareName').textContent = currentSelectedSpareItem.spareName;
        document.getElementById('confirmSpareBarcode').textContent = currentSelectedSpareItem.barCode || currentSelectedSpareItem.barcode || '-';
        document.getElementById('confirmSpareAvail').textContent = currentSelectedSpareItem.availableQty;
        document.getElementById('confirmSpareWanted').value = '1';

        updateConfirmSpareRemaining();

        document.getElementById('spareDetailsConfirmModal').style.display = 'flex';
    }

    function openSpareBarcodeScanner() {
        if (typeof window.openBarcodeScanner !== 'function') {
            alert('Barcode scanner modal is not available. Please use the manual barcode input field.');
            return;
        }

        window.openBarcodeScanner({
            title: '📷 Scan Spare Barcode',
            continuous: true,
            callback: function(barcode, itemData) {
                handleSpareBarcodeScanResult(barcode, itemData);
            }
        });
    }

    function handleSpareBarcodeScanResult(barcode, itemData) {
        if (typeof playCommonScanBeep === 'function') playCommonScanBeep();

        function showInCameraError(type, htmlContent) {
            // Show a small bubble in front of the camera — camera stays open
            if (typeof window.showScannerOverlayMessage === 'function') {
                window.showScannerOverlayMessage(type, htmlContent, 6000);
            } else {
                // Fallback if modal isn't open (e.g. USB scanner input)
                showScanToast(htmlContent, type, 6000);
            }
        }

        function processResolvedItem(item) {
            const availQty = parseInt(item.availableQty) || 0;
            if (availQty <= 0) {
                // ⚠ Out of stock — keep camera open, show overlay
                const spareName = escapeHtml(item.spareName || item.itemName || 'Item');
                showInCameraError('warning',
                    `<div style="display:flex; flex-direction:column; gap:6px;">
                        <div style="font-weight:700; font-size:13px;">⚠️ Out of Stock</div>
                        <div style="font-size:12px; color:#fde68a;"><em>${spareName}</em> — Available: <strong>0</strong></div>
                        <div style="font-size:11px; color:#fbbf24;">Scan a different barcode or close the camera.</div>
                    </div>`
                );
                return;
            }

            // ✅ Valid item found — close camera and open confirmation popup
            if (typeof closeCommonBarcodeScannerModal === 'function') {
                closeCommonBarcodeScannerModal();
            }
            openStockPopupForScannedSpare(item);
        }

        function handleItemNotFound(code) {
            // ❌ Not in stock — keep camera open, show overlay with Add button
            const addStockUrl = '../stock/add_stock.php?barcode=' + encodeURIComponent(code);
            showInCameraError('error',
                `<div style="display:flex; flex-direction:column; gap:7px;">
                    <div style="font-weight:700; font-size:13px;">❌ Stock Not Registered</div>
                    <div style="font-size:11.5px; color:#fca5a5;">Barcode <span style="font-family:monospace; background:rgba(255,255,255,0.12); padding:1px 5px; border-radius:4px;">${escapeHtml(code)}</span> not in stock list.</div>
                    <div>
                        <a href="${addStockUrl}" target="_blank"
                           style="display:inline-flex; align-items:center; gap:5px; background:#2563eb; color:#fff; padding:5px 12px; border-radius:6px; text-decoration:none; font-size:11.5px; font-weight:700;">
                           ➕ Add to Stock
                        </a>
                    </div>
                </div>`
            );
        }

        // If itemData was already resolved by the modal's API call, process directly
        if (itemData) {
            processResolvedItem(itemData);
            return;
        }

        if (!barcode) return;
        const cleanCode = barcode.trim();

        // Strict exact-match lookup (barcode/serialNo/partNo/id — no fuzzy LIKE)
        fetch('../stock/get_by_barcode.php?barcode=' + encodeURIComponent(cleanCode))
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    processResolvedItem(res.data);
                } else {
                    handleItemNotFound(cleanCode);
                }
            })
            .catch(err => {
                console.error('[SpareScan] Stock API error:', err);
                showInCameraError('error',
                    `<div style="font-weight:700; font-size:13px;">⚠️ Network / Camera Error</div>
                     <div style="font-size:11.5px; color:#fca5a5; margin-top:4px;">Could not reach stock API. Check connection and try again.</div>`
                );
            });
    }


    function handleSpareUsbBarcodeSubmit() {
        const input = document.getElementById('spareUsbBarcodeInput');
        if (!input) return;
        const barcode = input.value.trim();
        if (!barcode) {
            input.focus();
            return;
        }
        input.value = '';
        handleSpareBarcodeScanResult(barcode, null);
    }

    function showScanToast(message, type, duration = 3500) {
        let container = document.getElementById('scanToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'scanToastContainer';
            container.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
            document.body.appendChild(container);
        }
        const colors = {
            success: { bg: '#d1fae5', border: '#34d399', color: '#065f46' },
            error:   { bg: '#fee2e2', border: '#f87171', color: '#991b1b' },
            warning: { bg: '#fef3c7', border: '#fbbf24', color: '#92400e' },
            info:    { bg: '#dbeafe', border: '#60a5fa', color: '#1e40af' }
        };
        const c = colors[type] || colors.info;
        const toast = document.createElement('div');
        toast.style.cssText = `background:${c.bg}; border:1.5px solid ${c.border}; color:${c.color}; padding:12px 18px; border-radius:8px; font-size:13.5px; font-weight:600; box-shadow:0 8px 20px rgba(0,0,0,0.16); pointer-events:auto; max-width:380px; animation:fadeIn 0.15s ease-out;`;
        toast.innerHTML = message;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
    }

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

    function triggerCustomerModalSearch(val) {
        clearTimeout(custSearchDebounce);
        custSearchDebounce = setTimeout(() => {
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
                            <button type="button" onclick="chooseCustomer(${idx})" style="background: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;">Choose</button>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <button type="button" onclick="openEditCustomerModal(${idx})" style="background: #f59e0b; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;">Edit</button>
                        </td>
                    </tr>
                `).join('');
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

    function openNewCustomerModal(initialVal = '') {
        document.getElementById('editModalTitle').textContent = 'New Customer';
        document.getElementById('customerEditForm').reset();
        document.getElementById('edit_id').value = '0';
        document.getElementById('edit_address_id').value = '0';
        document.getElementById('edit_customerId').value = '<?= $nextCustomerId ?>';
        document.getElementById('edit_active').checked = true;

        if (initialVal) {
            if (/^\d+$/.test(initialVal.trim())) {
                document.getElementById('edit_phoneNo1').value = initialVal.trim();
            } else {
                document.getElementById('edit_name').value = initialVal.trim();
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

        fetch('../customers/api_save_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            if (!resData.success) {
                alert('Error: ' + (resData.error || 'Failed to save customer'));
                return;
            }

            const savedCust = resData.customer;
            closeCustomerEditModal();
            fetchModalCustomers(document.getElementById('modalSearchInput').value);

            if (savedCust) {
                document.getElementById('customerId').value = savedCust.id;
                document.getElementById('customerPhone').value = savedCust.phoneNo1 || '';
                document.getElementById('customerName').value = savedCust.name || '';
                document.getElementById('city').value = savedCust.city || '';
                closeCustomerLookupModal();
            }
        });
    }

    function submitJobCardEdit(e) {
        e.preventDefault();

        const rows = document.querySelectorAll('#sparesTbody tr.spare-row');
        let stockReqMap = {};

        rows.forEach(row => {
            const name = row.querySelector('.spare-name') ? row.querySelector('.spare-name').value.trim() : 'Spare';
            const sId = row.querySelector('.spare-stock-id') ? row.querySelector('.spare-stock-id').value.trim() : '';
            const qty = parseInt(row.querySelector('.spare-qty') ? row.querySelector('.spare-qty').value : 0) || 0;
            const avail = parseInt(row.querySelector('.spare-avail-qty') ? row.querySelector('.spare-avail-qty').value : 9999) || 0;

            const key = sId || name;
            if (!stockReqMap[key]) {
                stockReqMap[key] = { name: name, requested: 0, avail: avail };
            }
            stockReqMap[key].requested += qty;
        });



        const btn = document.getElementById('saveJobCardBtn');
        btn.disabled = true;
        btn.textContent = 'Saving Changes...';

        const form = document.getElementById('editJobCardForm');
        const formData = new FormData(form);

        fetch('update_jobcard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';

            if (!resData.success) {
                alertBox.innerHTML = `
                    <div style="background: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
                        <span>⚠️ ${escapeHtml(resData.error || 'Failed to save changes')}</span>
                        <button type="button" onclick="this.parentElement.remove()" style="background: transparent; border: none; font-size: 18px; color: #991b1b; cursor: pointer;">&times;</button>
                    </div>`;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            alert('Job Card updated successfully!');
            
            const balanceEl = document.getElementById('summaryBalance');
            const balanceText = balanceEl ? balanceEl.textContent.replace(/[^\d.-]/g, '') : '0';
            const balanceAmount = parseFloat(balanceText) || 0;

            const paidInput = document.getElementById('paidAmount');
            const paidAmount = parseFloat(paidInput ? paidInput.value : 0) || 0;

            if (paidAmount > 0 && balanceAmount <= 0) {
                window.location.href = 'print_receipt.php?id=<?= $id ?>&autoprint=1';
            } else {
                window.location.reload();
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
            alertBox.innerHTML = `
                <div style="background: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                    An unexpected error occurred while saving the Job Card.
                </div>`;
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

        const placeholder = document.getElementById('noPhotoPlaceholder');
        if (placeholder) {
            placeholder.remove();
        }

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const maxFileSize = 10 * 1024 * 1024;

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
                wrapper.style.width = '100px';
                wrapper.style.height = '100px';
                wrapper.style.borderRadius = '8px';
                wrapper.style.overflow = 'hidden';
                wrapper.style.border = '1.5px solid #cbd5e1';
                wrapper.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';
                wrapper.style.background = '#f8fafc';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.cursor = 'pointer';
                img.onclick = function() { openLightbox(e.target.result); };

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '&times;';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '3px';
                removeBtn.style.right = '3px';
                removeBtn.style.background = 'rgba(239, 68, 68, 0.9)';
                removeBtn.style.color = '#fff';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '22px';
                removeBtn.style.height = '22px';
                removeBtn.style.fontSize = '14px';
                removeBtn.style.fontWeight = '700';
                removeBtn.style.lineHeight = '1';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.display = 'flex';
                removeBtn.style.alignItems = 'center';
                removeBtn.style.justifyContent = 'center';
                removeBtn.onclick = function() { wrapper.remove(); };

                wrapper.appendChild(img);
                wrapper.appendChild(removeBtn);
                container.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });
    }

    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxModal').style.display = 'flex';
    }

    function closeLightbox() {
        document.getElementById('lightboxModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="update_jobcard.php"]');
        if (!form) return;

        form.addEventListener('keydown', function (e) {
            const target = e.target;
            if (!target || !['INPUT', 'SELECT'].includes(target.tagName)) return;
            if (target.type === 'submit' || target.type === 'button' || target.type === 'file') return;

            const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'));
            const index = inputs.indexOf(target);
            if (index === -1) return;

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

    function toggleMachineMode() {
        const selectBox = document.getElementById('machineSelectBox');
        const textBox = document.getElementById('machineTextBox');
        const selectEl = document.getElementById('machineSelect');
        const textEl = document.getElementById('machineTextInput');
        const toggleIcon = document.getElementById('machineToggleIcon');
        const toggleText = document.getElementById('machineToggleText');

        if (!selectBox || !textBox) return;

        if (selectBox.style.display !== 'none') {
            // Switch to text input mode
            selectBox.style.display = 'none';
            textBox.style.display = 'block';
            selectEl.removeAttribute('name');
            selectEl.removeAttribute('required');
            textEl.setAttribute('name', 'machineName');
            textEl.setAttribute('required', 'required');
            if (selectEl.value && selectEl.value !== '__NEW__') {
                textEl.value = selectEl.value;
            }
            if (toggleIcon) toggleIcon.innerText = '📋';
            if (toggleText) toggleText.innerText = 'Select from List';
            textEl.focus();
        } else {
            // Switch to select dropdown mode
            textBox.style.display = 'none';
            selectBox.style.display = 'block';
            textEl.removeAttribute('name');
            textEl.removeAttribute('required');
            selectEl.setAttribute('name', 'machineName');
            selectEl.setAttribute('required', 'required');
            if (toggleIcon) toggleIcon.innerText = '✏️';
            if (toggleText) toggleText.innerText = '+ Type New';
            selectEl.focus();
        }
    }

    function checkMachineSelect(sel) {
        if (sel.value === '__NEW__') {
            toggleMachineMode();
            const textEl = document.getElementById('machineTextInput');
            if (textEl) {
                textEl.value = '';
                textEl.focus();
            }
        }
    }
</script>

<div id="lightboxModal" class="modal-overlay" onclick="closeLightbox()" style="z-index: 10005;">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; background: #000; border-radius: 8px; padding: 6px;" onclick="event.stopPropagation()">
        <button type="button" onclick="closeLightbox()" style="position: absolute; top: -12px; right: -12px; background: #ef4444; color: #fff; border: none; border-radius: 50%; width: 30px; height: 30px; font-size: 18px; font-weight: 700; cursor: pointer; z-index: 10006; display: flex; align-items: center; justify-content: center;">&times;</button>
        <img id="lightboxImg" src="" style="max-width: 100%; max-height: 85vh; border-radius: 4px; display: block; margin: 0 auto;">
    </div>
</div>

<script src="../customers/customer_phone_check.js?v=<?= time() ?>"></script>
<script>
    setupCustomerPhoneCheck({
        input: '#edit_phoneNo1',
        apiPath: '../customers/api_check_customer_phone.php',
        getExcludeId: function () {
            return document.getElementById('edit_id').value;
        },
        onUseExisting: function (c) {
            closeCustomerEditModal();
            document.getElementById('customerId').value = c.id || '';
            document.getElementById('customerPhone').value = c.phoneNo1 || '';
            document.getElementById('customerName').value = c.name || '';
            document.getElementById('city').value = c.city || '';
            closeCustomerLookupModal();
        },
        onTryAnother: function () {
            const input = document.getElementById('edit_phoneNo1');
            if (input) {
                input.value = '';
                input.focus();
            }
        }
    });
</script>

<?php include("../includes/barcode_scanner_modal.php"); ?>

<?php if (($jobcard['jobStatus'] ?? '') === 'Delivered'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formFields = document.querySelectorAll('#editJobCardForm input, #editJobCardForm select, #editJobCardForm textarea');
    formFields.forEach(el => {
        el.disabled = true;
        el.style.backgroundColor = '#f1f5f9';
        el.style.cursor = 'not-allowed';
    });

    const saveBtn = document.getElementById('saveJobCardBtn');
    if (saveBtn) saveBtn.style.display = 'none';

    const addBtns = document.querySelectorAll('button[onclick*="openSpareModal"], button[onclick*="autoFillPayment"]');
    addBtns.forEach(btn => btn.style.display = 'none');

    const removeBtns = document.querySelectorAll('button[onclick*="removeSpareRow"]');
    removeBtns.forEach(btn => btn.style.display = 'none');
});
</script>
<?php endif; ?>

<?php include("../includes/footer.php"); ?>


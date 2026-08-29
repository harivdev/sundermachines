<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Generate Order # in format YYYY00001 (e.g. 202600001)
$year = date('Y');
$maxRes = mysqli_query($conn, "SELECT orderNo FROM sales WHERE orderNo LIKE '$year%' ORDER BY id DESC LIMIT 1");
$nextNum = 1;
if ($maxRes && $row = mysqli_fetch_assoc($maxRes)) {
    if (preg_match('/(\d{5})$/', trim($row['orderNo'] ?? ''), $m)) {
        $nextNum = intval($m[1]) + 1;
    }
}
$tempOrderNo = $year . sprintf("%05d", $nextNum);
$currentDate = date('Y-m-d');

// Generate Next Customer ID sample (e.g. C0003216)
$cRes = mysqli_query($conn, "SELECT customerId FROM customer WHERE customerId LIKE 'C%' ORDER BY id DESC LIMIT 1");
$nextCustNum = 1;
if ($cRes && $cRow = mysqli_fetch_assoc($cRes)) {
    if (preg_match('/(\d+)$/', $cRow['customerId'], $m)) {
        $nextCustNum = intval($m[1]) + 1;
    }
}
$sampleCustId = 'C' . str_pad($nextCustNum, 7, '0', STR_PAD_LEFT);
?>

<?php include("../includes/header.php"); ?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --bg: #f8f9fa;
    --card: #ffffff;
    --border: #dee2e6;
    --label: #495057;
    --text: #212529;
    --blue: #0d6efd;
    --green: #198754;
    --yellow: #ffc107;
    --red: #dc3545;
    --gray-btn: #5c636a;
    --readonly-bg: #e9ecef;
    --radius: 6px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
  }

  .page-wrapper {
    max-width: 1240px;
    margin: 20px auto;
    padding: 0 16px;
    width: 100%;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 10px;
  }

  .page-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
  }

  .btn-back {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    text-decoration: none;
    padding: 6px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: #fff;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-back:hover { background: #f1f3f5; }

  .card-section {
    background: var(--card);
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e9ecef;
    padding: 20px;
    margin-bottom: 16px;
  }

  label {
    font-size: 12.5px;
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 4px;
  }

  .req { color: var(--red); }

  input[type=text],
  input[type=number],
  input[type=date],
  select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: var(--radius);
    font-size: 13.5px;
    font-family: inherit;
    color: var(--text);
    background: #ffffff;
  }

  select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7'%3E%3Cpath d='M0 0l6 7 6-7z' fill='%23495057'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
  }

  input[readonly], input[disabled] {
    background: var(--readonly-bg);
    color: #495057;
  }

  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; }

  .cust-addr-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
  }

  /* ── Items Table ── */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-top: 10px;
    border-radius: 6px;
  }
  .items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 780px;
  }
  .items-table th {
    background: #f8f9fa; font-weight: 700; color: #495057;
    padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left;
    white-space: nowrap;
  }
  .items-table td {
    padding: 8px; border-bottom: 1px solid #e9ecef; vertical-align: middle;
  }
  .td-input {
    width: 100%;
    padding: 6px 8px; font-size: 13px; border: 1px solid #ced4da; border-radius: 4px;
  }
  .img-cell {
    width: 36px; height: 36px; background: #e9ecef; border-radius: 4px;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
  }
  .img-cell img { width: 100%; height: 100%; object-fit: cover; }

  /* ── Totals Box ── */
  .totals-container {
    display: flex; justify-content: flex-end; margin-top: 14px;
  }
  .totals-table {
    width: 280px; font-size: 13.5px; border-collapse: collapse;
  }
  .totals-table td {
    padding: 6px 10px; text-align: right;
  }
  .totals-table tr.total-row td {
    font-weight: 700; font-size: 14.5px; border-top: 1px solid #dee2e6;
  }
  .totals-table tr.total-row td:last-child {
    background: #f8f9fa;
  }

  /* ── Action Bar ── */
  .action-bar {
    display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;
  }
  .btn-sub {
    background: var(--blue); color: #fff; border: none; border-radius: 6px;
    padding: 9px 24px; font-weight: 600; font-size: 14px; cursor: pointer;
  }
  .btn-sub:hover { background: #0b5ed7; }
  .btn-res {
    background: var(--gray-btn); color: #fff; border: none; border-radius: 6px;
    padding: 9px 18px; font-weight: 600; font-size: 14px; cursor: pointer;
  }
  .btn-res:hover { background: #4b5257; }
  .btn-green {
    background: var(--green); color: #fff; border: none; border-radius: 6px;
    padding: 7px 16px; font-weight: 600; font-size: 13px; cursor: pointer;
  }
  .btn-green:hover { background: #157347; }
  .btn-green:disabled { background: #6c757d; cursor: not-allowed; }

  /* ── Modals / Overlays ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    z-index: 2000; padding: 20px; overflow-y: auto;
  }
  .modal-box {
    background: #fff; width: 100%; max-width: 680px; margin: 60px auto;
    padding: 24px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }

  /* Customer Search Drawer */
  .cust-drawer-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    z-index: 2000; align-items: flex-start; justify-content: center; padding-top: 50px;
  }
  .cust-drawer {
    background: #fff; width: 96%; max-width: 1100px; max-height: 85vh;
    border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    display: flex; flex-direction: column; overflow: hidden;
  }
  .cd-header-bar {
    padding: 12px 16px; background: #fff; border-bottom: 1px solid #dee2e6;
    display: flex; justify-content: space-between; align-items: center;
  }
  .cd-actions { display: flex; gap: 16px; font-weight: 600; font-size: 13px; }
  .cd-actions a { color: #212529; text-decoration: none; cursor: pointer; }
  .cd-actions a:hover { color: var(--blue); }

  .cd-search-inputs {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; padding: 12px 16px; background: #fafafa; border-bottom: 1px solid #dee2e6;
  }

  .cd-table-wrap { overflow-y: auto; overflow-x: auto; flex: 1; }
  .cd-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 760px; }
  .cd-table th {
    padding: 10px; background: #f8f9fa; font-weight: 700; border-bottom: 2px solid #dee2e6;
    text-align: left; position: sticky; top: 0; background: #fff; z-index: 5;
  }
  .cd-table td { padding: 10px; border-bottom: 1px solid #e9ecef; }
  .cd-table tr:hover td { background: #e7f5ff; }

  .cd-pagination-bar {
    padding: 10px 16px; border-top: 1px solid #dee2e6; background: #fff;
    display: flex; justify-content: space-between; align-items: center;
    gap: 12px; font-size: 12.5px; font-weight: 600; color: #495057;
    flex-wrap: wrap;
  }
  .cd-pag-controls {
    display: flex; gap: 5px; align-items: center; flex-wrap: wrap;
  }
  .cd-pag-btn {
    padding: 5px 10px; border-radius: 4px; border: 1px solid #ced4da;
    background: #fff; color: #212529; font-size: 12px; font-weight: 600;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
  }
  .cd-pag-btn:hover:not(.disabled) { background: #e9ecef; color: var(--blue); border-color: var(--blue); }
  .cd-pag-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); }
  .cd-pag-btn.disabled { opacity: 0.45; cursor: not-allowed; }

  /* Stock Search Drawer */
  .stock-drawer-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    z-index: 2000; align-items: flex-end; justify-content: center;
  }
  .stock-drawer {
    background: #fff; width: 100%; max-width: 1200px; max-height: 80vh;
    border-radius: 12px 12px 0 0; box-shadow: 0 -10px 30px rgba(0,0,0,0.2);
    display: flex; flex-direction: column; overflow: hidden;
  }
  .sd-header {
    padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  }
  .sd-header span { font-weight: 700; font-size: 14px; }
  .sd-header input {
    flex: 1; min-width: 200px; padding: 8px 12px; border: 1.5px solid var(--blue); border-radius: 6px;
    font-size: 13.5px;
  }
  .sd-body { overflow-y: auto; overflow-x: auto; flex: 1; }
  .sd-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 800px; }
  .sd-table th {
    padding: 10px; background: #f8f9fa; font-weight: 700; border-bottom: 2px solid #dee2e6;
    text-align: left; position: sticky; top: 0; background: #fff; z-index: 10;
  }
  .sd-table td { padding: 9px 10px; border-bottom: 1px solid #e9ecef; }
  .sd-table tr:hover td { background: #e7f5ff; }
  .sd-footer { padding: 10px; text-align: center; border-top: 1px solid #dee2e6; background: #f8f9fa; }
  .sd-footer button { background: none; border: none; color: #dc3545; font-weight: 700; cursor: pointer; }

  /* Responsive Media Queries */
  @media (max-width: 992px) {
    .grid-3 { grid-template-columns: 1fr 1fr; }
    .grid-4 { grid-template-columns: 1fr 1fr; }
    .cust-addr-grid { grid-template-columns: 1fr; gap: 16px; }
    .cd-search-inputs { grid-template-columns: 1fr 1fr; }
  }

  @media (max-width: 768px) {
    .page-wrapper { margin: 10px auto; padding: 0 10px; }
    .grid-3, .grid-2, .grid-4 { grid-template-columns: 1fr; gap: 12px; }
    .cust-addr-grid { grid-template-columns: 1fr; gap: 16px; }
    .cd-search-inputs { grid-template-columns: 1fr; }
    .totals-container { justify-content: stretch; }
    .totals-table { width: 100%; }
    .action-bar { flex-direction: column; }
    .action-bar button { width: 100%; }
    .cd-pagination-bar { flex-direction: column; align-items: center; text-align: center; }
    .cd-pag-controls { justify-content: center; }
    .modal-box { margin: 20px auto; padding: 16px; width: 95%; }
  }

  @media (max-width: 480px) {
    .page-header h2 { font-size: 18px; }
    .btn-back { width: 100%; justify-content: center; }
    .card-section { padding: 14px; }
    .cd-pag-btn { padding: 4px 7px; font-size: 11px; }
  }
</style>

<div class="page-wrapper">

  <div class="page-header">
    <h2>Create Sales Order</h2>
    <a href="list.php" class="btn-back">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
      Sales Orders
    </a>
  </div>

  <form method="POST" action="insert_sales.php" id="salesForm" onsubmit="return validateSalesForm(event)">

    <!-- Hidden payment tracking fields -->
    <input type="hidden" name="isPaymentAdded" id="isPaymentAdded" value="0">
    <input type="hidden" name="paymentAmountSubmitted" id="paymentAmountSubmitted" value="0">

    <!-- SECTION 1: ORDER HEADER -->
    <div class="card-section">
      <div class="grid-3">
        <div>
          <label>Order # Generated</label>
          <input type="text" name="orderNo" value="<?= htmlspecialchars($tempOrderNo) ?>" readonly>
        </div>
        <div>
          <label>Order Status</label>
          <select name="orderStatus" id="orderStatusSelect">
            <option value="New" selected>New</option>
            <option value="Invoiced">Invoiced</option>
          </select>
        </div>
        <div>
          <label>Order Date <span class="req">*</span></label>
          <input type="date" name="orderDate" id="orderDate" value="<?= $currentDate ?>" max="<?= $currentDate ?>" required>
          <small style="color:#6c757d; font-size:11px; display:block; margin-top:2px;">Today or past dates only</small>
        </div>
      </div>
    </div>

    <!-- SECTION 2: CUSTOMER INFO & ADDRESS -->
    <div class="card-section">
      <div class="cust-addr-grid">
        
        <!-- Left: Customer Info -->
        <div>
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <h3 style="margin:0; font-size:14px; font-weight:700; color:#212529;">Customer Info:</h3>
            <div style="display:flex; gap:6px;">
              <button type="button" onclick="openCustDrawer()" style="background:#0d6efd; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-weight:600; font-size:12px; cursor:pointer;">Search / Choose</button>
              <button type="button" onclick="clearCust()" style="background:#ffc107; color:#000; border:none; padding:4px 12px; border-radius:4px; font-weight:600; font-size:12px; cursor:pointer;">Clear</button>
            </div>
          </div>

          <input type="hidden" name="customerId" id="customerId">

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
            <div>
              <label>Phone # Primary<span class="req">*</span></label>
              <input type="text" name="customerPhone" id="customerPhone" placeholder="Click or type to search" onclick="openCustDrawer()" autocomplete="off" required style="cursor:pointer;">
            </div>
            <div>
              <label>Phone # WhatsApp</label>
              <input type="text" name="customerWhatsApp" id="customerWhatsApp" placeholder="">
            </div>
          </div>

          <div>
            <label>Name <span class="req">*</span></label>
            <input type="text" name="customerName" id="customerName" placeholder="" onclick="openCustDrawer()" autocomplete="off" required style="cursor:pointer;">
          </div>
        </div>

        <!-- Right: Address -->
        <div>
          <h3 style="margin:0 0 12px 0; font-size:14px; font-weight:700; color:#212529;">Address:</h3>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
            <div>
              <label>Line 1</label>
              <input type="text" name="addressLine1" id="addressLine1" placeholder="">
            </div>
            <div>
              <label>Line 2</label>
              <input type="text" name="line2" id="line2" placeholder="">
            </div>
          </div>
          <div>
            <label>City</label>
            <input type="text" name="city" id="city" placeholder="">
          </div>
        </div>

      </div>
    </div>

    <!-- SECTION 3: ITEMS TABLE -->
    <div class="card-section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <h3 style="margin:0; font-size:14px; font-weight:700; color:#212529;">Sales Items <span class="req">*</span></h3>
        <button type="button" class="btn-green" onclick="addItemRow()">+ Add Row</button>
      </div>

      <div class="table-responsive">
        <table class="items-table" id="itemsTable">
          <thead>
            <tr>
              <th style="width:30px;">#</th>
              <th style="width:45px;">Image</th>
              <th>Item</th>
              <th style="width:120px;">Barcode</th>
              <th style="width:120px;">Part/Serial #</th>
              <th style="width:80px;">Rack #</th>
              <th style="width:65px;">Qty</th>
              <th style="width:100px;">Price/Qty</th>
              <th style="width:65px;">GST %</th>
              <th style="width:110px;">Total Price</th>
              <th style="width:65px; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
          </tbody>
        </table>
      </div>

      <!-- Totals Box at Bottom Right of Items Table -->
      <div class="totals-container">
        <table class="totals-table">
          <tr>
            <td style="font-weight:700; color:#212529;">Total Rs:</td>
            <td style="font-weight:700; color:#212529;" id="lblGrandTotal">0.00</td>
          </tr>
          <tr>
            <td style="font-weight:700; color:#495057;">Paid Rs:</td>
            <td style="font-weight:700; color:#495057;" id="lblTotalPaid">0.00</td>
          </tr>
          <tr class="total-row">
            <td style="font-weight:700; color:#dc3545;">Balance Rs:</td>
            <td style="font-weight:700; color:#dc3545;" id="lblBalanceAmt">0.00</td>
          </tr>
        </table>
      </div>

      <input type="hidden" name="grandTotal" id="hGrandTotal" value="0">
      <input type="hidden" name="paidTotal" id="hPaidTotal" value="0">
    </div>

    <!-- SECTION 4: PAYMENT SECTION & ACTIONS -->
    <div class="card-section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <h3 style="margin:0; font-size:14px; font-weight:700; color:#212529;">Payment Information:</h3>
        <div id="paymentBadge" style="display:none; background:#dcfce7; color:#166534; padding:4px 12px; border-radius:12px; font-weight:700; font-size:12px;">Payment Added ✓</div>
      </div>

      <div class="grid-4" style="align-items:end; margin-bottom:12px;">
        <div>
          <label>Paid On</label>
          <input type="date" name="paymentDate" id="payDate" value="<?= $currentDate ?>" max="<?= $currentDate ?>">
        </div>
        <div>
          <label>Mode</label>
          <select name="paymentMode" id="payMode">
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="UPI">UPI</option>
            <option value="NetBanking">NetBanking</option>
            <option value="Cheque">Cheque</option>
          </select>
        </div>
        <div>
          <label>Ref No</label>
          <input type="text" name="paymentRef" id="payRef" placeholder="Txn / Ref #">
        </div>
        <div>
          <label>Amount</label>
          <input type="number" id="payAmount" value="0" min="0" step="0.01">
        </div>
      </div>

      <div style="margin-top:8px; margin-bottom:16px;">
        <button type="button" id="btnAddPayment" class="btn-green" onclick="handleAddPaymentClick()">Add Payment</button>
      </div>

      <!-- Action Buttons -->
      <div class="action-bar">
        <button type="submit" class="btn-sub">Submit Sales Order</button>
        <button type="button" class="btn-res" onclick="resetSalesForm()">Reset</button>
      </div>
    </div>

  </form>

</div>

<!-- CUSTOMER SEARCH DRAWER OVERLAY -->
<div class="cust-drawer-overlay" id="custDrawerOverlay">
  <div class="cust-drawer">
    
    <div class="cd-header-bar">
      <div style="font-weight:700; font-size:15px; color:#212529;">Select Customer</div>
      <div class="cd-actions">
        <a onclick="openCreateCustModal()" style="color:var(--green); font-weight:700;">+ New Customer</a>
        <a onclick="closeCustDrawer()" style="color:#dc3545;">✕ Close</a>
      </div>
    </div>

    <div class="cd-search-inputs">
      <div>
        <label>Phone # Primary</label>
        <input type="text" id="cd_phone" placeholder="Search phone..." oninput="onCustSearchInput()">
      </div>
      <div>
        <label>Phone # WhatsApp</label>
        <input type="text" id="cd_wa" placeholder="Search WhatsApp..." oninput="onCustSearchInput()">
      </div>
      <div>
        <label>Customer Name</label>
        <input type="text" id="cd_name" placeholder="Search name..." oninput="onCustSearchInput()">
      </div>
    </div>

    <div class="cd-table-wrap">
      <table class="cd-table">
        <thead>
          <tr>
            <th style="width:35px;">#</th>
            <th style="width:110px;">Customer ID</th>
            <th>Name</th>
            <th>Address</th>
            <th style="width:110px;">Contact #</th>
            <th style="width:110px;">WhatsApp #</th>
            <th style="width:70px;">Role</th>
            <th style="width:65px;">Active</th>
            <th style="width:130px; text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody id="cdTbody">
          <tr><td colspan="9" style="text-align:center; padding:30px; color:#6c757d;">Loading customers...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Interactive Working Customer Pagination Bar -->
    <div class="cd-pagination-bar" id="cdPaginationBar">
      <div id="cdPagInfo" style="color:#64748b; font-size:12.5px;">Loading...</div>
      <div class="cd-pag-controls" id="cdPagControls"></div>
    </div>

  </div>
</div>

<!-- CREATE / EDIT CUSTOMER MODAL -->
<div id="createCustModal" class="modal-overlay">
  <div class="modal-box">
    <form id="createCustForm" onsubmit="saveCustomerAjax(event)">
      <input type="hidden" id="cc_id" name="id" value="0">
      <input type="hidden" id="cc_address_id" name="address_id" value="0">

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 id="ccModalTitle" style="margin:0; font-size:18px; font-weight:700; color:#212529;">Create Customer</h3>
        <label style="margin:0; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
          <input type="checkbox" name="active" value="1" checked style="width:auto;">
          <span style="background:#8b5cf6; color:#fff; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700;">✓ Active</span>
        </label>
      </div>

      <div class="grid-2 form-group" style="margin-bottom:12px;">
        <div>
          <label>Name <span class="req">*</span></label>
          <input type="text" id="cc_name" name="name" required placeholder="">
        </div>
        <div>
          <label>Customer ID System Generated</label>
          <input type="text" id="cc_customerId" name="customerId" value="<?= htmlspecialchars($sampleCustId) ?>" readonly>
        </div>
      </div>

      <div class="grid-3 form-group" style="margin-bottom:12px;">
        <div>
          <label>Phone # Primary<span class="req">*</span></label>
          <input type="text" id="cc_phoneNo1" name="phoneNo1" required placeholder="">
        </div>
        <div>
          <label>Phone # WhatsApp</label>
          <input type="text" id="cc_whatsAppNo" name="whatsAppNo" placeholder="">
        </div>
        <div>
          <label>Email ID</label>
          <input type="text" id="cc_emailId" name="emailId" placeholder="">
        </div>
      </div>

      <div style="font-weight:700; font-size:14px; margin-top:14px; margin-bottom:8px;">Address</div>

      <div class="grid-2 form-group" style="margin-bottom:12px;">
        <div>
          <label>Line 1</label>
          <input type="text" id="cc_line1" name="line1" placeholder="">
        </div>
        <div>
          <label>Line 2</label>
          <input type="text" id="cc_line2" name="line2" placeholder="">
        </div>
      </div>

      <div class="grid-2 form-group" style="margin-bottom:12px;">
        <div>
          <label>City</label>
          <input type="text" id="cc_city" name="city" placeholder="">
        </div>
        <div>
          <label>Zip Code</label>
          <input type="text" id="cc_zipCode" name="zipCode" placeholder="">
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
        <button type="submit" class="btn-sub">Submit</button>
        <button type="button" class="btn-res" onclick="closeCreateCustModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- STOCKS SEARCH DRAWER -->
<div class="stock-drawer-overlay" id="stockDrawerOverlay">
  <div class="stock-drawer">
    <div class="sd-header" style="display:flex; align-items:center; gap:8px;">
      <span style="white-space:nowrap;">Stocks:</span>
      <input type="text" id="sdSearchInput" placeholder="Item Name / Barcode / Serial No / Part No / Rack No" oninput="searchStocksDrawer()" autocomplete="off" style="flex:1;">
    </div>
    <div class="sd-body">
      <table class="sd-table">
        <thead>
          <tr>
            <th style="width:60px;">Category</th>
            <th>Name</th>
            <th>Serial #</th>
            <th>Barcode</th>
            <th>Rack No</th>
            <th>Part No</th>
            <th style="text-align:right;">Avil Qty</th>
            <th style="text-align:right;">Per Unit</th>
            <th style="text-align:right;">Per Qty</th>
            <th style="text-align:center; width:80px;">Action</th>
          </tr>
        </thead>
        <tbody id="sdTbody">
          <tr><td colspan="10" style="text-align:center; padding:30px; color:#6c757d;">Type to search stocks...</td></tr>
        </tbody>
      </table>
    </div>
    <div class="sd-footer">
      <button type="button" onclick="closeStockDrawer()">Cancel (Esc)</button>
    </div>
  </div>
</div>

<script>
  let activeItemRow = null;
  let cdTimer = null;
  let stockSearchTimer = null;
  let custDataStore = [];
  let isPaymentCommitted = false;
  let isPayAmtUserEdited = false;

  // Customer Drawer Pagination State
  let cdPage = 1;
  let cdLimit = 10;
  let cdTotalRecords = 0;
  let cdTotalPages = 1;

  window.onload = function() {
    addItemRow();
    calcAllTotals();

    document.getElementById("payAmount").addEventListener("input", function() {
      isPayAmtUserEdited = true;
    });
  };

  /* ── Customer Drawer JS with interactive pagination ── */
  function openCustDrawer() {
    document.getElementById("custDrawerOverlay").style.display = "flex";
    fetchCustDrawer(1);
  }

  function closeCustDrawer() {
    document.getElementById("custDrawerOverlay").style.display = "none";
  }

  function onCustSearchInput() {
    clearTimeout(cdTimer);
    cdTimer = setTimeout(() => {
      fetchCustDrawer(1);
    }, 250);
  }

  function fetchCustDrawer(targetPage) {
    if (targetPage !== undefined) {
      cdPage = targetPage;
    }

    let p = document.getElementById("cd_phone").value.trim();
    let wa = document.getElementById("cd_wa").value.trim();
    let nm = document.getElementById("cd_name").value.trim();

    let url = `../customers/api_search_customers.php?phone=${encodeURIComponent(p)}&name=${encodeURIComponent(nm)}&whatsApp=${encodeURIComponent(wa)}&page=${cdPage}&limit=${cdLimit}`;

    fetch(url)
      .then(r => r.json())
      .then(res => {
        let tbody = document.getElementById("cdTbody");
        if (res.success && res.data && res.data.length > 0) {
          custDataStore = res.data;
          cdTotalRecords = res.pagination.totalRecords;
          cdTotalPages = res.pagination.totalPages;
          cdPage = res.pagination.page;

          let startIdx = res.pagination.offset;
          tbody.innerHTML = res.data.map((c, idx) => `
            <tr>
              <td>${startIdx + idx + 1}</td>
              <td style="font-weight:600; font-family:monospace;">${c.customerId || ('C' + String(c.id).padStart(7,'0'))}</td>
              <td style="font-weight:600;">${c.name || ''}</td>
              <td>${c.fullAddress || '-'}</td>
              <td>${c.phoneNo1 || '-'}</td>
              <td>${c.whatsAppNo || '-'}</td>
              <td>Customer</td>
              <td><span style="background:${c.active ? '#dcfce7; color:#166534' : '#fee2e2; color:#991b1b'}; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700;">${c.active ? 'Yes' : 'No'}</span></td>
              <td style="text-align:center; white-space:nowrap;">
                <button type="button" onclick="chooseCustomer(${idx})" style="background:#198754; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-weight:600; font-size:12px; cursor:pointer; margin-right:4px;">Choose</button>
                <button type="button" onclick="editCustomer(${idx})" style="background:#0d6efd; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-weight:600; font-size:12px; cursor:pointer;">Edit</button>
              </td>
            </tr>
          `).join('');

          renderCustPagination(res.pagination);
        } else {
          custDataStore = [];
          cdTotalRecords = 0;
          cdTotalPages = 1;
          tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:25px; color:#6c757d;">No matching customers found</td></tr>';
          renderCustPagination({ page: 1, totalPages: 1, totalRecords: 0, offset: 0, count: 0 });
        }
      })
      .catch(() => {
        document.getElementById("cdTbody").innerHTML = '<tr><td colspan="9" style="text-align:center; padding:25px; color:#dc3545;">Error loading customers</td></tr>';
      });
  }

  function renderCustPagination(pag) {
    let info = document.getElementById("cdPagInfo");
    let controls = document.getElementById("cdPagControls");

    if (!pag || pag.totalRecords === 0) {
      info.innerText = "0 customers";
      controls.innerHTML = "";
      return;
    }

    let start = pag.offset + 1;
    let end = Math.min(pag.offset + pag.limit, pag.totalRecords);
    info.innerText = `Showing ${start}–${end} of ${pag.totalRecords} customers | Page ${pag.page} of ${pag.totalPages}`;

    let html = "";

    // First
    let isFirstDisabled = pag.page <= 1;
    html += `<button type="button" class="cd-pag-btn ${isFirstDisabled ? 'disabled' : ''}" ${isFirstDisabled ? 'disabled' : `onclick="fetchCustDrawer(1)"`}>|&lt; First</button>`;

    // Previous
    let isPrevDisabled = pag.page <= 1;
    html += `<button type="button" class="cd-pag-btn ${isPrevDisabled ? 'disabled' : ''}" ${isPrevDisabled ? 'disabled' : `onclick="fetchCustDrawer(${pag.page - 1})"`}>&larr; Previous</button>`;

    // Numeric Pages (show up to 5 surrounding pages)
    let startPage = Math.max(1, pag.page - 2);
    let endPage = Math.min(pag.totalPages, startPage + 4);
    if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
    }

    for (let p = startPage; p <= endPage; p++) {
      let isActive = p === pag.page;
      html += `<button type="button" class="cd-pag-btn ${isActive ? 'active' : ''}" onclick="fetchCustDrawer(${p})">${p}</button>`;
    }

    // Next
    let isNextDisabled = pag.page >= pag.totalPages;
    html += `<button type="button" class="cd-pag-btn ${isNextDisabled ? 'disabled' : ''}" ${isNextDisabled ? 'disabled' : `onclick="fetchCustDrawer(${pag.page + 1})"`}>Next &rarr;</button>`;

    // Last
    let isLastDisabled = pag.page >= pag.totalPages;
    html += `<button type="button" class="cd-pag-btn ${isLastDisabled ? 'disabled' : ''}" ${isLastDisabled ? 'disabled' : `onclick="fetchCustDrawer(${pag.totalPages})"`}>&gt;| Last</button>`;

    controls.innerHTML = html;
  }

  function chooseCustomer(idx) {
    let c = custDataStore[idx];
    if (!c) return;

    document.getElementById("customerId").value = c.id || '';
    document.getElementById("customerPhone").value = c.phoneNo1 || '';
    document.getElementById("customerWhatsApp").value = c.whatsAppNo || c.phoneNo2 || '';
    document.getElementById("customerName").value = c.name || '';
    document.getElementById("addressLine1").value = c.line1 || '';
    document.getElementById("line2").value = c.line2 || '';
    document.getElementById("city").value = c.city || '';

    closeCustDrawer();
  }

  function clearCust() {
    document.getElementById("customerId").value = "";
    document.getElementById("customerPhone").value = "";
    document.getElementById("customerWhatsApp").value = "";
    document.getElementById("customerName").value = "";
    document.getElementById("addressLine1").value = "";
    document.getElementById("line2").value = "";
    document.getElementById("city").value = "";
  }

  /* ── Create / Edit Customer Modal ── */
  function openCreateCustModal() {
    document.getElementById("createCustForm").reset();
    document.getElementById("cc_id").value = "0";
    document.getElementById("cc_address_id").value = "0";
    document.getElementById("ccModalTitle").innerText = "Create Customer";
    document.getElementById("createCustModal").style.display = "block";
  }

  function editCustomer(idx) {
    let c = custDataStore[idx];
    if (!c) return;

    document.getElementById("cc_id").value = c.id || "0";
    document.getElementById("cc_address_id").value = c.address_id || "0";
    document.getElementById("ccModalTitle").innerText = "Edit Customer";
    document.getElementById("cc_name").value = c.name || "";
    document.getElementById("cc_customerId").value = c.customerId || "";
    document.getElementById("cc_phoneNo1").value = c.phoneNo1 || "";
    document.getElementById("cc_whatsAppNo").value = c.whatsAppNo || "";
    document.getElementById("cc_emailId").value = c.emailId || "";
    document.getElementById("cc_line1").value = c.line1 || "";
    document.getElementById("cc_line2").value = c.line2 || "";
    document.getElementById("cc_city").value = c.city || "";
    document.getElementById("cc_zipCode").value = c.zipCode || "";

    document.getElementById("createCustModal").style.display = "block";
  }

  function closeCreateCustModal() {
    document.getElementById("createCustModal").style.display = "none";
  }

  function saveCustomerAjax(e) {
    e.preventDefault();
    let form = document.getElementById("createCustForm");
    let formData = new FormData(form);

    fetch("../customers/api_save_customer.php", {
      method: "POST",
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.customer) {
        closeCreateCustModal();
        let c = data.customer;
        document.getElementById("customerId").value = c.id || '';
        document.getElementById("customerPhone").value = c.phoneNo1 || '';
        document.getElementById("customerWhatsApp").value = c.whatsAppNo || '';
        document.getElementById("customerName").value = c.name || '';
        document.getElementById("addressLine1").value = c.line1 || '';
        document.getElementById("line2").value = c.line2 || '';
        document.getElementById("city").value = c.city || '';
        closeCustDrawer();
      } else {
        alert(data.error || "Failed to save customer.");
      }
    })
    .catch(() => alert("Error saving customer."));
  }

  /* ── Add Item Row ── */
  function addItemRow() {
    let tbody = document.getElementById("itemsBody");
    let rowIdx = tbody.children.length + 1;
    let tr = document.createElement("tr");

    tr.innerHTML = `
      <td class="row-num" style="text-align:center; font-weight:600; color:#6c757d;">${rowIdx}</td>
      <td>
        <div class="img-cell" id="imgBox_${rowIdx}">
          <svg style="width:20px; height:20px; fill:#adb5bd;" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/></svg>
        </div>
      </td>
      <td>
        <input type="text" name="item[]" class="td-input item-name-inp" placeholder="Click to choose item" readonly onclick="openStockDrawer(this)" style="cursor:pointer; background:#fff;" required>
        <input type="hidden" name="stockId[]" class="h-stock-id">
        <input type="hidden" name="spareId[]" class="h-spare-id">
        <input type="hidden" name="sparePicture[]" class="h-picture">
      </td>
      <td><input type="text" name="barcode[]" class="td-input h-barcode" readonly placeholder=""></td>
      <td><input type="text" name="serial[]" class="td-input h-serial" readonly placeholder=""></td>
      <td><input type="text" name="rack[]" class="td-input h-rack" readonly placeholder=""></td>
      <td><input type="number" name="qty[]" value="1" min="1" class="td-input inp-qty" oninput="calcRow(this)"></td>
      <td><input type="number" name="price[]" value="0" step="0.01" min="0" class="td-input inp-price" oninput="calcRow(this)"></td>
      <td><input type="number" name="gst[]" value="0" step="0.01" min="0" class="td-input inp-gst" oninput="calcRow(this)"></td>
      <td><input type="text" name="total[]" value="0.00" readonly class="td-input inp-total" style="font-weight:700; background:#f8f9fa;"></td>
      <td style="text-align:center; white-space:nowrap;">
        <button type="button" onclick="removeRow(this)" style="color:#dc3545; background:none; border:none; font-size:16px; cursor:pointer; margin-right:4px;" title="Remove row">🗑️</button>
        <button type="button" onclick="addItemRow()" style="color:#212529; background:none; border:none; font-size:16px; cursor:pointer;" title="Add row">➕</button>
      </td>
    `;
    tbody.appendChild(tr);
    renumberRows();
  }

  function removeRow(btn) {
    let tbody = document.getElementById("itemsBody");
    if (tbody.children.length <= 1) return;
    btn.closest("tr").remove();
    renumberRows();
    calcAllTotals();
  }

  function renumberRows() {
    let rows = document.querySelectorAll("#itemsBody tr");
    rows.forEach((r, idx) => {
      r.querySelector(".row-num").innerText = idx + 1;
    });
  }

  function calcRow(inputEl) {
    let tr = inputEl.closest("tr");
    let qty = +tr.querySelector(".inp-qty").value || 0;
    let price = +tr.querySelector(".inp-price").value || 0;
    let gst = +tr.querySelector(".inp-gst").value || 0;

    let subtotal = Math.round(qty * price);
    let total = Math.round(subtotal + (subtotal * gst / 100));
    tr.querySelector(".inp-total").value = (qty > 0 && price > 0) ? total : "";
    calcAllTotals();
  }

  /* ── Calc All Totals ── */
  function calcAllTotals() {
    let grandTotal = 0;
    document.querySelectorAll(".inp-total").forEach(el => {
      grandTotal += (+el.value || 0);
    });

    grandTotal = Math.round(grandTotal);
    let paidTotal = isPaymentCommitted ? Math.round(parseFloat(document.getElementById("paymentAmountSubmitted").value) || 0) : 0;
    let balance = Math.max(0, Math.round(grandTotal - paidTotal));

    document.getElementById("lblGrandTotal").innerText = grandTotal;
    document.getElementById("lblTotalPaid").innerText = paidTotal;
    document.getElementById("lblBalanceAmt").innerText = balance;

    document.getElementById("hGrandTotal").value = grandTotal;
    document.getElementById("hPaidTotal").value = paidTotal;

    // Auto-fill payment amount if not user edited or committed
    if (!isPaymentCommitted && !isPayAmtUserEdited) {
      document.getElementById("payAmount").value = grandTotal > 0 ? grandTotal : "";
    }
  }

  /* ── Handle Add Payment Button Click ── */
  function handleAddPaymentClick() {
    if (isPaymentCommitted) return;

    let grandTotal = Math.round(parseFloat(document.getElementById("hGrandTotal").value) || 0);
    let amtInput = document.getElementById("payAmount");
    let payAmt = Math.round(parseFloat(amtInput.value) || 0);

    if (payAmt <= 0) {
      alert("Payment amount must be greater than 0.");
      amtInput.focus();
      return;
    }

    if (payAmt > grandTotal) {
      alert("Payment amount (₹" + payAmt + ") cannot exceed total sales order amount (₹" + grandTotal + ").");
      return;
    }

    let pDate = document.getElementById("payDate").value;
    let today = "<?= $currentDate ?>";
    if (pDate > today) {
      alert("Payment date cannot be in the future.");
      return;
    }

    // Commit payment state in form
    isPaymentCommitted = true;
    document.getElementById("isPaymentAdded").value = "1";
    document.getElementById("paymentAmountSubmitted").value = payAmt;

    // Update UI totals and status
    let paidTotal = payAmt;
    let balance = Math.max(0, grandTotal - paidTotal);

    document.getElementById("lblTotalPaid").innerText = paidTotal;
    document.getElementById("lblBalanceAmt").innerText = balance;
    document.getElementById("hPaidTotal").value = paidTotal;

    // Automatically set status to Invoiced
    document.getElementById("orderStatusSelect").value = "Invoiced";

    // Disable payment input controls
    document.getElementById("payDate").disabled = true;
    document.getElementById("payMode").disabled = true;
    document.getElementById("payRef").disabled = true;
    document.getElementById("payAmount").disabled = true;

    // Update Add Payment button
    let btn = document.getElementById("btnAddPayment");
    btn.innerText = "Payment Added ✓";
    btn.disabled = true;
    btn.style.background = "#6c757d";

    document.getElementById("paymentBadge").style.display = "inline-block";
  }

  /* ── Stock Search Drawer ── */
  function openStockDrawer(inputEl) {
    activeItemRow = inputEl.closest("tr");
    document.getElementById("stockDrawerOverlay").style.display = "flex";
    let input = document.getElementById("sdSearchInput");
    input.value = "";
    document.getElementById("sdTbody").innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:#6c757d;">Type to search stocks...</td></tr>';
    setTimeout(() => input.focus(), 80);
    searchStocksDrawer();
  }

  function closeStockDrawer() {
    document.getElementById("stockDrawerOverlay").style.display = "none";
    activeItemRow = null;
  }

  function searchStocksDrawer() {
    clearTimeout(stockSearchTimer);
    let term = document.getElementById("sdSearchInput").value.trim();
    let tbody = document.getElementById("sdTbody");

    stockSearchTimer = setTimeout(() => {
      fetch("search_stock.php?term=" + encodeURIComponent(term))
        .then(r => r.json())
        .then(data => {
          if (!data || !data.length) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:#6c757d;">No matching stocks found</td></tr>';
            return;
          }

          tbody.dataset.items = JSON.stringify(data);
          tbody.innerHTML = data.map((item, idx) => `
            <tr>
              <td><span style="font-weight:700; font-size:11px; color:#6c757d;">${item.category || 'SPR'}</span></td>
              <td style="font-weight:600; color:#212529;">${item.spareName || ''}</td>
              <td>${item.serialNo || '—'}</td>
              <td style="font-family:monospace; font-weight:600;">${item.barCode || '—'}</td>
              <td>${item.rackNumber || '—'}</td>
              <td>${item.partNo || '—'}</td>
              <td style="font-weight:700; color:#084298; text-align:right;">${item.availableQty}</td>
              <td style="text-align:right;">${parseFloat(item.sellingPricePerUnit||0).toFixed(2)}</td>
              <td style="text-align:right;">${parseFloat(item.selledPricePerUnit||0).toFixed(2)}</td>
              <td style="text-align:center;">
                <button type="button" onclick="pickStock(${idx})" style="background:#198754; color:#fff; border:none; padding:4px 12px; border-radius:4px; font-weight:600; font-size:12px; cursor:pointer;">Select</button>
              </td>
            </tr>
          `).join('');
        })
        .catch(() => {
          tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:#dc3545;">Error loading stocks</td></tr>';
        });
    }, 200);
  }

  function pickStock(idx) {
    let tbody = document.getElementById("sdTbody");
    let items = JSON.parse(tbody.dataset.items || "[]");
    let item = items[idx];
    if (!item || !activeItemRow) return;

    activeItemRow.querySelector(".item-name-inp").value = item.spareName || '';
    activeItemRow.querySelector(".h-stock-id").value = item.stockId || '';
    activeItemRow.querySelector(".h-spare-id").value = item.spareId || '';
    activeItemRow.querySelector(".h-picture").value = item.picture || '';
    activeItemRow.querySelector(".h-barcode").value = item.barCode || '';
    activeItemRow.querySelector(".h-serial").value = item.serialNo || item.partNo || '';
    activeItemRow.querySelector(".h-rack").value = item.rackNumber || '';

    let price = parseFloat(item.selledPricePerUnit) || parseFloat(item.sellingPricePerUnit) || 0;
    activeItemRow.querySelector(".inp-price").value = price;
    activeItemRow.querySelector(".inp-gst").value = parseFloat(item.gstPercentage) || 0;

    // Load picture thumbnail
    let imgCell = activeItemRow.querySelector(".img-cell");
    if (item.picture && item.picture !== 'no-image.png') {
      let src = item.picture.startsWith('uploads/') || item.picture.startsWith('Spare/') ? "../" + item.picture.replace(/^\.\.\//,'') : "../uploads/spares/" + item.picture;
      imgCell.innerHTML = `<img src="${src}" onerror="this.parentNode.innerHTML='📦'">`;
    } else {
      imgCell.innerHTML = '📦';
    }

    calcRow(activeItemRow.querySelector(".inp-price"));
    closeStockDrawer();
  }

  function validateSalesForm(e) {
    // 1. Validate Order Date (Today or past dates only)
    let orderDate = document.getElementById("orderDate").value;
    let today = "<?= $currentDate ?>";
    if (!orderDate) {
      alert("Please select an Order Date.");
      e.preventDefault(); return false;
    }
    if (orderDate > today) {
      alert("Order date cannot be in the future. Allowed: Today (" + today + ") or previous dates only.");
      e.preventDefault(); return false;
    }

    // 2. Validate Customer Info
    let custPhone = document.getElementById("customerPhone").value.trim();
    let custName = document.getElementById("customerName").value.trim();
    if (!custPhone || !custName) {
      alert("Please enter or select customer phone and name.");
      e.preventDefault(); return false;
    }

    // 3. Validate at least one item
    let hasItem = false;
    document.querySelectorAll(".item-name-inp").forEach(el => {
      if (el.value.trim()) hasItem = true;
    });
    if (!hasItem) {
      alert("Please select at least one item before submitting.");
      e.preventDefault(); return false;
    }

    return true;
  }

  function resetSalesForm() {
    document.getElementById("salesForm").reset();
    clearCust();
    document.getElementById("itemsBody").innerHTML = "";
    isPaymentCommitted = false;
    isPayAmtUserEdited = false;
    document.getElementById("isPaymentAdded").value = "0";
    document.getElementById("paymentAmountSubmitted").value = "0";
    document.getElementById("payDate").disabled = false;
    document.getElementById("payMode").disabled = false;
    document.getElementById("payRef").disabled = false;
    document.getElementById("payAmount").disabled = false;

    let btn = document.getElementById("btnAddPayment");
    btn.innerText = "Add Payment";
    btn.disabled = false;
    btn.style.background = "var(--green)";
    document.getElementById("paymentBadge").style.display = "none";

    addItemRow();
    calcAllTotals();
  }

  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
      closeStockDrawer();
      closeCustDrawer();
      closeCreateCustModal();
    }
  });

  document.getElementById("custDrawerOverlay").addEventListener("click", function(e) {
    if (e.target === this) closeCustDrawer();
  });

  document.getElementById("stockDrawerOverlay").addEventListener("click", function(e) {
    if (e.target === this) closeStockDrawer();
  });

  document.getElementById("createCustModal").addEventListener("click", function(e) {
    if (e.target === this) closeCreateCustModal();
  });

</script>

<?php include("../includes/footer.php"); ?>
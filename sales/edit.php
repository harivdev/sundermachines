<?php
require_once("../config/db.php");
include("../includes/header.php");

$salesId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($salesId <= 0) {
    echo "<script>alert('Invalid Sales ID'); window.location.href='list.php';</script>";
    exit;
}

// Fetch sales & customer
$query = "
    SELECT s.*, c.name as customer_name, c.phoneNo1, c.whatsAppNo 
    FROM sales s 
    LEFT JOIN customer c ON s.customer = c.id 
    WHERE s.id = $salesId
";
$res = mysqli_query($conn, $query);
if (!$res || mysqli_num_rows($res) == 0) {
    echo "<script>alert('Sales Order not found'); window.location.href='list.php';</script>";
    exit;
}
$sale = mysqli_fetch_assoc($res);

// Fetch items
$iQuery = "SELECT * FROM salesitems WHERE sales = $salesId AND deleted = 0";
$iRes = mysqli_query($conn, $iQuery);
$items = [];
while ($row = mysqli_fetch_assoc($iRes)) {
    $items[] = $row;
}

// Fetch single payment from payment table
$hasPayment = false;
$paymentRecord = null;
$pQuery = "SELECT * FROM payment WHERE sales = $salesId LIMIT 1";
$pRes = mysqli_query($conn, $pQuery);
if ($pRes && $pRow = mysqli_fetch_assoc($pRes)) {
    $hasPayment = true;
    $paymentRecord = $pRow;
}

$paidSum = $hasPayment ? floatval($paymentRecord['amount']) : floatval($sale['paidAmountSum']);
$orderStatus = $hasPayment ? 'Invoiced' : ($sale['orderStatus'] ?: 'New');
$actualSum = floatval($sale['actualAmountSum']);
$balance = max(0.0, round($actualSum - $paidSum, 2));
$today = date('Y-m-d');
?>

<style>
  :root {
    --border: #e2e8f0; --label: #334155; --text: #1e293b;
    --muted: #64748b; --accent: #2563eb; --green: #16a34a;
    --red: #ef4444; --bg: #f1f5f9; --card: #fff;
    --header-blue: #1e40af; --radius: 6px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); font-size: 14px; color: var(--text); overflow-x: hidden; }

  .top-bar { background: var(--header-blue); height: 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; flex-wrap: wrap; }
  .top-bar .title { color: #fff; font-weight: 700; font-size: 17px; }
  .top-bar a { color: #fff; text-decoration: none; font-size: 13px; font-weight: 600; }

  .form-wrap { background: var(--card); border: 1px solid var(--border); padding: 18px 18px 0; max-width: 1240px; margin: 0 auto; }
  .section { border: 1px solid var(--border); border-radius: 6px; margin-bottom: 14px; overflow: hidden; background: #fff; }

  label { font-size: 12px; font-weight: 600; color: var(--muted); display: block; margin-bottom: 4px; }
  .req { color: var(--red); }

  input[type=text], input[type=number], input[type=date], select {
    width: 100%; height: 38px; padding: 0 10px;
    border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 13px; font-family: inherit; color: var(--text);
    background: #fff; transition: border-color 0.12s;
    appearance: none; -webkit-appearance: none;
  }
  input:focus, select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }
  input:disabled { background: #f8fafc; color: var(--muted); cursor: default; }
  input[readonly] { background: #f8fafc; color: var(--muted); }
  select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 9px center; padding-right: 26px;
  }
  .form-group { margin-bottom: 12px; }
  .g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
  .g4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; }

  .cust-action-bar { display: flex; justify-content: flex-end; gap: 16px; padding: 8px 14px; border-bottom: 1px solid var(--border); background: #fafafa; }
  .cust-action-bar button { background: none; border: none; cursor: pointer; font-family: inherit; font-size: 13px; font-weight: 700; color: var(--text); padding: 2px 0; }
  .cust-fields { padding: 14px; }

  .items-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .items-tbl { width: 100%; border-collapse: collapse; min-width: 860px; }
  .items-tbl th { padding: 9px 8px; background: #f8fafc; font-size: 12px; font-weight: 700; color: var(--muted); text-align: left; border-bottom: 2px solid var(--border); white-space: nowrap; }
  .items-tbl td { padding: 5px 6px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
  .td-in { width: 100%; height: 34px; padding: 0 8px; border: 1px solid var(--border); border-radius: 5px; font-size: 13px; font-family: inherit; color: var(--text); background: #fff; }
  .td-in:focus { outline: none; border-color: var(--accent); }
  .td-in[readonly], .td-in:disabled { background: #f8fafc; color: var(--muted); }

  .row-img { width: 36px; height: 36px; border-radius: 5px; border: 1px solid var(--border); background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 15px; overflow: hidden; }
  
  .totals-wrap { display: flex; justify-content: flex-end; padding: 10px 14px 14px; }
  .totals-box { width: 280px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; }
  .tot-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 7px; }
  .tot-row.final { border-top: 1px solid #cbd5e1; padding-top: 8px; margin-top: 4px; color: var(--text); font-size: 14px; border-bottom: none !important;}
  .tot-row span:last-child { color: var(--text); }
  .tot-row.final span:last-child { color: #e11d48; }

  .pay-section { border: 1px solid var(--border); border-radius: 6px; margin-bottom: 14px; }
  .pay-hdr { padding: 9px 14px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 13px; background: #fafafa; display: flex; justify-content: space-between; align-items: center; }

  .action-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 0 18px; border-top: 1px solid var(--border); }
  .btn { padding: 9px 22px; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; font-family: inherit; transition: all 0.12s; }
  .btn-blue { background: var(--accent); color: #fff; } .btn-blue:hover { background: #1d4ed8; }
  .btn-green { background: var(--green); color: #fff; } .btn-green:hover { background: #15803d; }
  .btn-green:disabled { background: #6c757d; cursor: not-allowed; }

  /* Stock Modal */
  .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.38); z-index: 4000; }
  .modal-overlay.open { display: flex; align-items: flex-start; justify-content: center; padding-top: 55px; }
  .stock-modal { background: #fff; border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,0.22); width: 96%; max-width: 1050px; max-height: 78vh; display: flex; flex-direction: column; overflow: hidden; }
  .sm-header { padding: 10px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; background: #fafafa; }
  .sm-header input { flex: 1; height: 38px; padding: 0 12px; border: 1.5px solid var(--accent); border-radius: 6px; font-size: 13px; }
  .sm-header input:focus { outline: none; }
  .sm-body { overflow-y: auto; flex: 1; }
  .sm-footer { padding: 9px; text-align: center; border-top: 1px solid var(--border); background: #fafafa; }
  .sm-footer button { background: none; border: none; cursor: pointer; color: #e11d48; font-weight: 700; font-size: 13px; }

  .stk-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
  .stk-tbl th { padding: 8px 10px; background: #f8fafc; font-weight: 700; color: var(--muted); text-align: left; border-bottom: 2px solid var(--border); white-space: nowrap; position: sticky; top: 0; z-index: 1;}
  .stk-tbl td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; }
  .stk-tbl tbody tr:hover td { background: #f0f7ff; }

  @media (max-width: 768px) {
    .g3, .g4 { grid-template-columns: 1fr; gap: 10px; }
    .totals-wrap { justify-content: stretch; }
    .totals-box { width: 100%; }
    .action-bar { flex-direction: column; }
    .action-bar button { width: 100%; }
  }
  .scan-tools-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 0 14px; flex-wrap: wrap; }
  .scan-btn-blue { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
  .scan-btn-blue:hover { background: #1d4ed8; }
  .scan-input-group { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 200px; max-width: 350px; }
  .scan-barcode-input { flex: 1; height: 34px; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 0 10px; font-size: 13px; outline: none; }
  .scan-barcode-input:focus { border-color: #2563eb; }
  .scan-btn-go { background: #16a34a; color: #fff; border: none; border-radius: 6px; padding: 0 16px; height: 34px; font-weight: 600; font-size: 13px; cursor: pointer; }
  .scan-btn-go:hover { background: #15803d; }
</style>

<div class="top-bar">
  <span class="title">Edit Sales Order #<?= htmlspecialchars($sale['orderNo']) ?></span>
  <a href="list.php">☰ Back to List</a>
</div>

<div class="form-wrap">
  <form id="salesForm" method="POST" action="update_sales.php" onsubmit="return validateEditForm(event)">
    <input type="hidden" name="salesId" id="salesId" value="<?= $salesId ?>">
    <input type="hidden" name="orderNo" value="<?= htmlspecialchars($sale['orderNo']) ?>">
    <input type="hidden" name="customerId" id="customerId" value="<?= htmlspecialchars($sale['customer']) ?>">

    <!-- ORDER HEADER -->
    <div class="section" style="margin-top:14px;">
      <div style="padding:14px;">
        <div class="g3">
          <div class="form-group">
            <label>Order #</label>
            <input type="text" value="<?= htmlspecialchars($sale['orderNo']) ?>" disabled>
          </div>
          <div class="form-group">
            <label>Order Status</label>
            <input type="text" id="statusBadgeDisplay" value="<?= htmlspecialchars($orderStatus) ?>" disabled style="font-weight:700; color:<?= $orderStatus==='Invoiced'?'#16a34a':'#2563eb' ?>;">
            <input type="hidden" name="orderStatus" id="orderStatusVal" value="<?= htmlspecialchars($orderStatus) ?>">
          </div>
          <div class="form-group">
            <label>Order Date <span class="req">*</span></label>
            <input type="date" name="orderDate" id="orderDate" value="<?= htmlspecialchars(date('Y-m-d', strtotime($sale['orderDate']))) ?>" max="<?= $today ?>" required>
          </div>
        </div>
      </div>
    </div>

    <!-- CUSTOMER SECTION -->
    <div class="section">
      <div class="cust-fields">
        <div class="g3">
          <div class="form-group" style="margin:0;">
            <label>Phone # Primary <span class="req">*</span></label>
            <input type="text" name="customerPhone" id="customerPhone" autocomplete="off" required value="<?= htmlspecialchars($sale['phoneNo1'] ?? '') ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Phone # WhatsApp</label>
            <input type="text" name="customerWhatsApp" id="customerWhatsApp" value="<?= htmlspecialchars($sale['whatsAppNo'] ?? '') ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Name <span class="req">*</span></label>
            <input type="text" name="customerName" id="customerName" required value="<?= htmlspecialchars($sale['customer_name'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="section">
      <div class="scan-tools-row" style="margin-top: 14px;">
        <button type="button" class="scan-btn-blue" onclick="openScanToBill()">
          📷 Scan Barcode
        </button>
        <div class="scan-input-group">
          <input type="text" id="usbBarcodeInput" class="scan-barcode-input" placeholder="Scan / Enter Barcode" autocomplete="off" onkeydown="if(event.key==='Enter'){handleUsbBarcodeSubmit();event.preventDefault();}">
          <button type="button" class="scan-btn-go" onclick="handleUsbBarcodeSubmit()">Go</button>
        </div>
      </div>
      <div class="items-wrap">
        <table class="items-tbl">
          <thead>
            <tr>
              <th style="width:32px;">#</th>
              <th style="width:44px;">Image</th>
              <th style="min-width:220px; width:220px;">Item</th>
              <th style="width:120px;">Barcode</th>
              <th style="width:110px;">Part/Serial #</th>
              <th style="width:72px;">Rack #</th>
              <th style="width:58px;">Qty</th>
              <th style="width:95px;">Price/Qty</th>
              <th style="width:62px;">GST %</th>
              <th style="width:100px;">Total Price</th>
              <th style="width:60px;">Action</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            <?php foreach ($items as $idx => $item): ?>
            <tr>
              <td class="row-num" style="font-size:12px; color:#94a3b8; font-weight:700; text-align:center;"><?= $idx + 1 ?></td>
              <td><div class="row-img">📷</div></td>
              <td style="min-width:220px;">
                <input type="text" name="item[]" class="td-in item-inp" value="<?= htmlspecialchars($item['itemName']) ?>" readonly onclick="openModal(this)" style="cursor:pointer; min-width:200px;">
                <input type="hidden" name="itemId[]" value="<?= $item['id'] ?>">
                <input type="hidden" name="stockId[]" class="h-stock" value="<?= $item['stock'] ?>">
                <input type="hidden" name="spareId[]" class="h-spare" value="<?= $item['spare'] ?>">
              </td>
              <td><input type="text"   name="barcode[]" class="td-in" readonly value="<?= htmlspecialchars($item['barCode'] ?? '') ?>"></td>
              <td><input type="text"   name="serial[]"  class="td-in" value="<?= htmlspecialchars($item['serialNo'] ?? '') ?>" readonly></td>
              <td><input type="text"   name="rack[]"    class="td-in" readonly></td>
              <td><input type="number" name="qty[]"   value="<?= intval($item['quantity']) ?>" min="1" class="td-in" oninput="calcRow(this)"></td>
              <td><input type="number" name="price[]" value="<?= floatval($item['pricePerQty']) ?>" step="0.01" class="td-in" oninput="calcRow(this)"></td>
              <td><input type="number" name="gst[]"   value="<?= floatval($item['gstPercentage']) ?>" step="0.01" class="td-in" oninput="calcRow(this)"></td>
              <td><input type="text"   name="total[]" value="<?= floatval($item['totalPrice']) ?>" readonly class="td-in" style="font-weight:700; color:#475569; background:#f8fafc;"></td>
              <td style="text-align:center; white-space:nowrap;">
                <button type="button" class="icon-btn" onclick="delRow(this)" style="background:none; border:none; cursor:pointer;">🗑️</button>
                <button type="button" class="icon-btn add-btn" onclick="addRow()" style="background:none; border:none; cursor:pointer;">➕</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="totals-wrap">
        <div class="totals-box">
          <div class="tot-row"><span>Total Rs:</span><span id="grandTotal"><?= number_format($actualSum, 2) ?></span></div>
          <div class="tot-row"><span>Paid Rs:</span><span id="totalPaid"><?= number_format($paidSum, 2) ?></span></div>
          <div class="tot-row final"><span>Balance Rs:</span><span id="balanceAmt"><?= number_format($balance, 2) ?></span></div>
        </div>
      </div>
    </div>

    <!-- PAYMENT -->
    <div class="pay-section">
      <div class="pay-hdr">
        <span>Payment Information:</span>
        <?php if ($hasPayment): ?>
          <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:12px; font-weight:700; font-size:12px;">Payment Added ✓</span>
        <?php endif; ?>
      </div>
      <div style="padding:14px;">
        
        <div class="g4 pay-row" style="align-items:end; margin-bottom:10px;">
          <div>
            <label>Paid On</label>
            <input type="date" id="pmtDate" value="<?= $hasPayment ? date('Y-m-d', strtotime($paymentRecord['transactionDate'])) : $today ?>" max="<?= $today ?>" <?= $hasPayment ? 'disabled' : '' ?>>
          </div>
          <div>
            <label>Mode</label>
            <select id="pmtMode" <?= $hasPayment ? 'disabled' : '' ?>>
              <option value="Cash" <?= ($hasPayment && $paymentRecord['mode']=='Cash')?'selected':'' ?>>Cash</option>
              <option value="Card" <?= ($hasPayment && $paymentRecord['mode']=='Card')?'selected':'' ?>>Card</option>
              <option value="UPI" <?= ($hasPayment && $paymentRecord['mode']=='UPI')?'selected':'' ?>>UPI</option>
              <option value="NetBanking" <?= ($hasPayment && $paymentRecord['mode']=='NetBanking')?'selected':'' ?>>NetBanking</option>
              <option value="Cheque" <?= ($hasPayment && $paymentRecord['mode']=='Cheque')?'selected':'' ?>>Cheque</option>
            </select>
          </div>
          <div>
            <label>Ref No</label>
            <input type="text" id="pmtRef" value="<?= $hasPayment ? htmlspecialchars($paymentRecord['refNo'] ?? '') : '' ?>" placeholder="Txn / Ref #" <?= $hasPayment ? 'disabled' : '' ?>>
          </div>
          <div>
            <label>Amount</label>
            <input type="number" id="pmtAmt" value="<?= $hasPayment ? floatval($paymentRecord['amount']) : floatval($actualSum) ?>" min="0" step="0.01" <?= $hasPayment ? 'disabled' : '' ?>>
          </div>
        </div>

        <div style="margin-top:10px;">
          <?php if ($hasPayment): ?>
            <button type="button" class="btn btn-green" disabled style="background:#6c757d; cursor:not-allowed;">Payment Added ✓</button>
          <?php else: ?>
            <button type="button" id="btnAddPaymentEdit" class="btn btn-green" onclick="submitPaymentEditAjax()">Add Payment</button>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <input type="hidden" name="grandTotal" id="hGT" value="<?= floatval($actualSum) ?>">
    <input type="hidden" name="paidTotal"  id="hPT" value="<?= floatval($paidSum) ?>">

    <div class="action-bar">
      <button type="submit" class="btn btn-blue">Update Sales Order</button>
    </div>
  </form>
</div>

<!-- STOCK MODAL -->
<div class="modal-overlay" id="stockModal">
  <div class="stock-modal">
    <div class="sm-header">
      <span>Stocks:</span>
      <input type="text" id="stkInput" placeholder="Type to Search Stock..." oninput="fetchStocks()" autocomplete="off">
    </div>
    <div class="sm-body">
      <table class="stk-tbl">
        <thead>
          <tr>
            <th>Name</th>
            <th style="text-align:right;">Avil Qty</th>
            <th style="text-align:right;">Per Unit</th>
            <th style="text-align:center; width:80px;">Action</th>
          </tr>
        </thead>
        <tbody id="stkBody">
          <tr><td colspan="4" class="no-res">Type to search...</td></tr>
        </tbody>
      </table>
    </div>
    <div class="sm-footer">
      <button type="button" onclick="closeModal()">Close Details</button>
    </div>
  </div>
</div>

<template id="rowTpl">
  <tr>
    <td class="row-num" style="font-size:12px; color:#94a3b8; font-weight:700; text-align:center;"></td>
    <td><div class="row-img">📷</div></td>
    <td style="min-width:140px;">
      <input type="text" name="item[]" class="td-in item-inp" placeholder="Click to set item..." readonly onclick="openModal(this)" style="cursor:pointer;">
      <input type="hidden" name="itemId[]" value="0">
      <input type="hidden" name="stockId[]" class="h-stock">
      <input type="hidden" name="spareId[]" class="h-spare">
    </td>
    <td><input type="text"   name="barcode[]" class="td-in" readonly></td>
    <td><input type="text"   name="serial[]"  class="td-in" readonly></td>
    <td><input type="text"   name="rack[]"    class="td-in" readonly></td>
    <td><input type="number" name="qty[]"   value="1" min="1" class="td-in" oninput="calcRow(this)"></td>
    <td><input type="number" name="price[]" value="0" step="0.01" class="td-in" oninput="calcRow(this)"></td>
    <td><input type="number" name="gst[]"   value="0" step="0.01" class="td-in" oninput="calcRow(this)"></td>
    <td><input type="text"   name="total[]" value="0.00" readonly class="td-in" style="font-weight:700; color:#475569; background:#f8fafc;"></td>
    <td style="text-align:center; white-space:nowrap;">
      <button type="button" class="icon-btn" onclick="delRow(this)" style="background:none; border:none; cursor:pointer;">🗑️</button>
      <button type="button" class="icon-btn add-btn" onclick="addRow()" style="background:none; border:none; cursor:pointer;">➕</button>
    </td>
  </tr>
</template>

<script>
const TODAY = '<?= $today ?>';
let isPaymentSaved = <?= $hasPayment ? 'true' : 'false' ?>;

function addRow() {
  const t = document.getElementById('rowTpl');
  document.getElementById('itemsBody').appendChild(t.content.cloneNode(true));
  renum();
}
function delRow(btn) {
  if (document.querySelectorAll('#itemsBody tr').length <= 1) return;
  btn.closest('tr').remove(); renum(); calcTotals();
}
function renum() {
  document.querySelectorAll('#itemsBody tr').forEach((r,i) => r.querySelector('.row-num').textContent = i+1);
}
function calcRow(el) {
  const r = el.closest('tr');
  const q = parseFloat(r.querySelector('[name="qty[]"]').value) || 0;
  const p = parseFloat(r.querySelector('[name="price[]"]').value) || 0;
  const g = parseFloat(r.querySelector('[name="gst[]"]').value) || 0;
  const s = q * p;
  r.querySelector('[name="total[]"]').value = (s + s*g/100).toFixed(2);
  calcTotals();
}

function calcTotals() {
  let gt = 0;
  document.querySelectorAll('[name="total[]"]').forEach(i => gt += parseFloat(i.value)||0);
  
  let pd = isPaymentSaved ? parseFloat(document.getElementById('pmtAmt').value)||0 : 0;
  let bal = Math.max(0, gt - pd);

  document.getElementById('grandTotal').textContent = gt.toFixed(2);
  document.getElementById('totalPaid').textContent  = pd.toFixed(2);
  document.getElementById('balanceAmt').textContent = bal.toFixed(2);
  document.getElementById('hGT').value = gt.toFixed(2);
  document.getElementById('hPT').value = pd.toFixed(2);

  if (!isPaymentSaved) {
    document.getElementById('pmtAmt').value = gt.toFixed(2);
  }
}

function submitPaymentEditAjax() {
  if (isPaymentSaved) return;

  let salesId = document.getElementById('salesId').value;
  let amount = parseFloat(document.getElementById('pmtAmt').value) || 0;
  let paymentDate = document.getElementById('pmtDate').value;
  let paymentMode = document.getElementById('pmtMode').value;
  let refNo = document.getElementById('pmtRef').value.trim();
  let gt = parseFloat(document.getElementById('hGT').value) || 0;

  if (amount <= 0) {
    alert("Payment amount must be greater than 0.");
    return;
  }

  if (amount > gt) {
    alert("Payment amount cannot exceed total order amount (₹" + gt.toFixed(2) + ").");
    return;
  }

  if (paymentDate > TODAY) {
    alert("Payment date cannot be in the future.");
    return;
  }

  let btn = document.getElementById('btnAddPaymentEdit');
  btn.disabled = true;
  btn.innerText = "Saving...";

  fetch('api_add_payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      salesId: salesId,
      amount: amount,
      paymentDate: paymentDate,
      paymentMode: paymentMode,
      refNo: refNo
    })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      isPaymentSaved = true;
      alert("✅ Payment saved successfully! Sales Order status updated to Invoiced.");

      document.getElementById('statusBadgeDisplay').value = "Invoiced";
      document.getElementById('statusBadgeDisplay').style.color = "#16a34a";
      document.getElementById('orderStatusVal').value = "Invoiced";

      document.getElementById('pmtDate').disabled = true;
      document.getElementById('pmtMode').disabled = true;
      document.getElementById('pmtRef').disabled = true;
      document.getElementById('pmtAmt').disabled = true;

      btn.innerText = "Payment Added ✓";
      btn.style.background = "#6c757d";

      calcTotals();
    } else {
      alert("❌ " + (res.error || "Failed to save payment"));
      btn.disabled = false;
      btn.innerText = "Add Payment";
    }
  })
  .catch(() => {
    alert("Error executing payment submission.");
    btn.disabled = false;
    btn.innerText = "Add Payment";
  });
}

function validateEditForm(e) {
  let orderDate = document.getElementById('orderDate').value;
  if (!orderDate || orderDate > TODAY) {
    alert("Order date cannot be in the future.");
    e.preventDefault(); return false;
  }
  let p = document.getElementById('customerPhone').value.trim();
  let n = document.getElementById('customerName').value.trim();
  if (!p || !n) {
    alert("Please enter customer phone and name.");
    e.preventDefault(); return false;
  }
  return true;
}

let activeInput = null, stkTimer = null;
function openModal(inp) {
  activeInput = inp;
  document.getElementById('stockModal').classList.add('open');
  const si = document.getElementById('stkInput'); si.value = '';
  document.getElementById('stkBody').innerHTML = '<tr><td colspan="4" class="no-res">Type to search stocks...</td></tr>';
  setTimeout(() => si.focus(), 60);
}
function closeModal() { document.getElementById('stockModal').classList.remove('open'); activeInput = null; }

function fetchStocks() {
  clearTimeout(stkTimer);
  const q = document.getElementById('stkInput').value.trim();
  const tb = document.getElementById('stkBody');
  if (!q) { tb.innerHTML='<tr><td colspan="4" class="no-res">Type to search...</td></tr>'; return; }
  stkTimer = setTimeout(() => {
    fetch('search_stock.php?term=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        if (!Array.isArray(data) || !data.length) {
          tb.innerHTML = '<tr><td colspan="4" class="no-res">No results</td></tr>'; return;
        }
        tb.dataset.items = JSON.stringify(data);
        tb.innerHTML = data.map((item, idx) => `
          <tr onclick="pickStock(${idx})" style="cursor:pointer;">
            <td style="font-weight:600;">${escH(item.spareName)}</td>
            <td style="text-align:right;"><strong>${item.availableQty}</strong></td>
            <td style="text-align:right;">${parseFloat(item.sellingPricePerUnit||0).toFixed(2)}</td>
            <td style="text-align:center;"><button type="button" class="btn-green" style="padding:3px 10px; font-size:11px;">Select</button></td>
          </tr>`).join('');
      }).catch(()=>{});
  }, 260);
}

function pickStock(idx) {
  const tb = document.getElementById('stkBody');
  const item = JSON.parse(tb.dataset.items||'[]')[idx];
  if (!item || !activeInput) return;
  const row = activeInput.closest('tr');
  activeInput.value = item.spareName;
  row.querySelector('.h-stock').value = item.stockId || '';
  row.querySelector('.h-spare').value = item.spareId || '';
  row.querySelector('[name="barcode[]"]').value = item.barCode || '';
  row.querySelector('[name="serial[]"]').value = item.serialNo || item.partNo || '';
  row.querySelector('[name="rack[]"]').value = item.rackNumber || '';
  row.querySelector('[name="price[]"]').value = parseFloat(item.selledPricePerUnit) || parseFloat(item.sellingPricePerUnit) || 0;
  row.querySelector('[name="gst[]"]').value = parseFloat(item.gstPercentage) || 0;
  calcRow(row.querySelector('[name="price[]"]'));
  closeModal();
}

function escH(s) {
  if (s===null||s===undefined) return '';
  return String(s).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
}

window.addEventListener('DOMContentLoaded', () => { 
  if (document.querySelectorAll('#itemsBody tr').length === 0) { addRow(); }
  calcTotals();
});
</script>

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
            
            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeSpareDetailsConfirmModal()" style="padding: 8px 16px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="button" id="confirmSpareAddBtn" onclick="confirmAddSpareFromModal()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Confirm & Add
                </button>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/barcode_scanner_modal.php"); ?>

<!-- Scan-to-Bill Toast Container -->
<div id="scanToastContainer" style="position:fixed; top:80px; right:16px; z-index:100000; display:flex; flex-direction:column; gap:8px; pointer-events:none;"></div>

<script>
(function() {
  /* -- Scan-to-Bill: Continuous Scanning + Cart Integration -- */

  let lastScanBarcode = '';
  let lastScanTime = 0;
  const SCAN_COOLDOWN_MS = 1200;

  // -- Toast notification --
  window.showScanToast = function(message, type) {
    const container = document.getElementById('scanToastContainer');
    if (!container) return;
    const colors = {
      success: { bg: '#d1fae5', border: '#34d399', color: '#065f46' },
      error:   { bg: '#fee2e2', border: '#f87171', color: '#991b1b' },
      warning: { bg: '#fef3c7', border: '#fbbf24', color: '#92400e' },
      info:    { bg: '#dbeafe', border: '#60a5fa', color: '#1e40af' }
    };
    const c = colors[type] || colors.info;
    const toast = document.createElement('div');
    toast.style.cssText = "background:" + c.bg + "; border:1px solid " + c.border + "; color:" + c.color + "; padding:10px 16px; border-radius:8px; font-size:13px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.12); pointer-events:auto; max-width:340px; animation:fadeIn 0.15s ease-out;";
    toast.innerHTML = message;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 2500);
  }

  // -- Open scanner modal in continuous scan-to-bill mode --
  window.openScanToBill = function() {
    if (typeof window.openBarcodeScanner !== 'function') {
      alert('Barcode scanner is not available. Please use the manual barcode input field.');
      return;
    }

    window.openBarcodeScanner({
      title: '?? Scan to Bill � Continuous Mode',
      continuous: true,
      callback: function(barcode) {
        handleScannedBarcode(barcode);
      }
    });
  };

  // -- Central barcode handler � used by camera, USB, and manual input --
  window.handleScannedBarcode = function(barcode) {
    if (!barcode || barcode.trim() === '') return;
    barcode = barcode.trim();

    // Duplicate scan protection (same barcode within cooldown = ignore)
    const now = Date.now();
    if (barcode === lastScanBarcode && (now - lastScanTime) < SCAN_COOLDOWN_MS) {
      console.log('[ScanToBill] Duplicate scan ignored (cooldown):', barcode);
      return;
    }
    lastScanBarcode = barcode;
    lastScanTime = now;

    // Play beep
    if (typeof playCommonScanBeep === 'function') playCommonScanBeep();

    // Call existing barcode API
    fetch('../stock/get_by_barcode.php?barcode=' + encodeURIComponent(barcode))
      .then(r => r.json())
      .then(res => {
        if (res.success && res.data) {
          openStockPopupForScannedSpare(res.data);
        } else {
          showScanToast('? Product not found: <span style="font-family:monospace;">' + escapeHtml(barcode) + '</span><br><a href="../stock/add_stock.php" target="_blank" style="color:inherit; text-decoration:underline;">Add New Product</a>', 'error');
        }
      })
      .catch(err => {
        console.error('[ScanToBill] API error:', err);
        showScanToast('? Unable to contact server. Check your connection.', 'error');
      });
  };

  // -- Add scanned product to the existing billing cart --
  window.addScannedProductToCart = function(data, initialQty = 1) {
    const stockId = data.stockId || data.id || '';
    const spareName = data.spareName || data.itemName || 'Item';
    const availableQty = parseInt(data.availableQty) || 0;

    // Check if this product is already in the cart (duplicate scan ? increase qty)
    const existingRows = document.querySelectorAll('#itemsBody tr');
    for (const row of existingRows) {
      const rowStockId = row.querySelector('.h-stock-id');
      if (rowStockId && rowStockId.value == stockId && stockId !== '') {
        // Found existing row � increase quantity
        const qtyInput = row.querySelector('.inp-qty');
        const currentQty = parseInt(qtyInput.value) || 0;
        const newQty = currentQty + initialQty;

        // Stock validation
        if (newQty > availableQty && availableQty > 0) {
          showScanToast('? Only ' + availableQty + ' units available for ' + spareName, 'warning');
          return;
        }

        qtyInput.value = newQty;
        calcRow(qtyInput);
        showScanToast('? ' + spareName + ' � ' + newQty, 'success');
        return;
      }
    }

    // Stock validation for new item
    if (availableQty < initialQty) {
      showScanToast('? ' + spareName + ' does not have enough stock (' + availableQty + ' available)', 'warning');
      return;
    }

    // Check if there's an empty row we can fill (first row often starts empty)
    let targetRow = null;
    for (const row of existingRows) {
      const nameInput = row.querySelector('.item-name-inp');
      if (nameInput && nameInput.value.trim() === '') {
        targetRow = row;
        break;
      }
    }

    // No empty row found � add a new one
    if (!targetRow) {
      addItemRow();
      const allRows = document.querySelectorAll('#itemsBody tr');
      targetRow = allRows[allRows.length - 1];
    }

    // Populate the row � same logic as existing pickStock()
    targetRow.querySelector('.item-name-inp').value = spareName;
    targetRow.querySelector('.h-stock-id').value = stockId;
    const availInp = targetRow.querySelector('.h-avail-qty');
    if (availInp) availInp.value = availableQty;
    const qtyInp = targetRow.querySelector('.inp-qty');
    if (qtyInp && availableQty > 0) qtyInp.max = availableQty;
    targetRow.querySelector('.h-spare-id').value = data.spareId || '';
    targetRow.querySelector('.h-picture').value = data.picture || '';
    targetRow.querySelector('.h-barcode').value = data.barCode || '';
    targetRow.querySelector('.h-serial').value = data.serialNo || data.partNo || '';
    targetRow.querySelector('.h-rack').value = data.rackNumber || '';

    const price = parseFloat(data.selledPricePerUnit) || parseFloat(data.sellingPricePerUnit) || 0;
    targetRow.querySelector('.inp-price').value = price;
    targetRow.querySelector('.inp-gst').value = parseFloat(data.gstPercentage) || 0;
    targetRow.querySelector('.inp-qty').value = initialQty;

    // Load picture thumbnail
    const imgCell = targetRow.querySelector('.img-cell');
    if (imgCell) {
      if (data.picture && data.picture !== 'no-image.png') {
        const src = (data.picture.startsWith('uploads/') || data.picture.startsWith('Spare/')) ? '../' + data.picture.replace(/^\.\.\//, '') : '../uploads/spares/' + data.picture;
        imgCell.innerHTML = '<img src="' + src + '" onerror="this.parentNode.innerHTML=\'??\'">';
      } else {
        imgCell.innerHTML = '??';
      }
    }

    calcRow(targetRow.querySelector('.inp-price'));
    showScanToast('? ' + spareName + ' � ' + initialQty + ' &nbsp;(Stock: ' + availableQty + ')', 'success');
  }

  // -- USB / Manual barcode input handler --
  window.handleUsbBarcodeSubmit = function() {
    const input = document.getElementById('usbBarcodeInput');
    if (!input) return;
    const barcode = input.value.trim();
    if (!barcode) { input.focus(); return; }
    handleScannedBarcode(barcode);
    input.value = '';
    input.focus();
  };

  // Handle Enter key on USB barcode input
  document.addEventListener('DOMContentLoaded', function() {
    const usbInput = document.getElementById('usbBarcodeInput');
    if (usbInput) {
      usbInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          e.stopPropagation();
          handleUsbBarcodeSubmit();
        }
      });
    }
  });

})();

let currentSelectedSpareItem = null;

window.selectSpareFromModal = function(idx) {
    const tbody = document.getElementById('sdTbody');
    if(!tbody || !tbody.dataset.items) return;
    const items = JSON.parse(tbody.dataset.items || "[]");
    const item = items[idx];
    if (!item) return;
    
    currentSelectedSpareItem = item;
    document.getElementById('confirmSpareIdx').value = idx;
    document.getElementById('confirmSpareName').textContent = item.spareName || item.itemName || '-';
    document.getElementById('confirmSpareBarcode').textContent = item.barCode || item.barcode || '-';
    document.getElementById('confirmSpareAvail').textContent = item.availableQty || '0';
    document.getElementById('confirmSpareWanted').value = '1';
    
    updateConfirmSpareRemaining();
    
    document.getElementById('spareDetailsConfirmModal').style.display = 'flex';
}

window.closeSpareDetailsConfirmModal = function() {
    document.getElementById('spareDetailsConfirmModal').style.display = 'none';
    currentSelectedSpareItem = null;
}

window.updateConfirmSpareRemaining = function() {
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
        warningEl.innerHTML = '?? No stock to add. Please purchase it quickly.';
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

window.confirmAddSpareFromModal = function() {
    const wanted = parseInt(document.getElementById('confirmSpareWanted').value) || 1;
    
    if (!currentSelectedSpareItem) return;
    
    closeSpareDetailsConfirmModal();
    if(typeof addScannedProductToCart === 'function') {
        addScannedProductToCart(currentSelectedSpareItem, wanted);
    }
    
    const drawer = document.getElementById('stockDrawerOverlay');
    if (drawer && drawer.style.display !== 'none') {
        closeStockDrawer();
    }
}

window.openStockPopupForScannedSpare = function(data) {
    if (!data) return;

    currentSelectedSpareItem = data;

    document.getElementById('confirmSpareIdx').value = 'SCANNED';
    document.getElementById('confirmSpareName').textContent = currentSelectedSpareItem.spareName || currentSelectedSpareItem.itemName || 'Spare Item';
    document.getElementById('confirmSpareBarcode').textContent = currentSelectedSpareItem.barCode || currentSelectedSpareItem.barcode || '-';
    document.getElementById('confirmSpareAvail').textContent = currentSelectedSpareItem.availableQty || '0';
    document.getElementById('confirmSpareWanted').value = '1';

    updateConfirmSpareRemaining();

    document.getElementById('spareDetailsConfirmModal').style.display = 'flex';
}

// Function to escape HTML
function escapeHtml(unsafe) {
    return (unsafe||'').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
</script>
<?php include("../includes/footer.php"); ?>


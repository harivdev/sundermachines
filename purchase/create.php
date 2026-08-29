<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

// Generate auto PO number e.g. PO202600001
$currentYear = date('Y');
$poPrefix = "PO" . $currentYear;

// Find max sequence number for current year
$poQuery = "SELECT orderNo FROM purchase WHERE orderNo LIKE '{$poPrefix}%' ORDER BY id DESC LIMIT 1";
$poRes = mysqli_query($conn, $poQuery);
$poSeq = 1;

if ($poRes && $poRow = mysqli_fetch_assoc($poRes)) {
    $lastNo = $poRow['orderNo'];
    $numPart = substr($lastNo, strlen($poPrefix));
    if (is_numeric($numPart)) {
        $poSeq = intval($numPart) + 1;
    } else {
        $cntRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM purchase WHERE orderNo LIKE '{$poPrefix}%'");
        if ($cntRow = mysqli_fetch_assoc($cntRes)) {
            $poSeq = intval($cntRow['cnt']) + 1;
        }
    }
} else {
    $cntRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM purchase WHERE orderNo LIKE '{$poPrefix}%'");
    if ($cntRes && $cntRow = mysqli_fetch_assoc($cntRes)) {
        $poSeq = intval($cntRow['cnt']) + 1;
    }
}

$autoOrderNo = $poPrefix . sprintf("%05d", $poSeq);

// Fetch suppliers for dropdown
$suppliersRes = mysqli_query($conn, "
    SELECT s.id, s.name, s.phoneNo1, s.emailId, a.line1, a.line2, a.city, a.zipCode 
    FROM supplier s 
    LEFT JOIN address a ON s.address = a.id 
    WHERE s.active = 1 
    ORDER BY s.name ASC
");
$suppliersList = [];
while ($s = mysqli_fetch_assoc($suppliersRes)) {
    $suppliersList[] = $s;
}

// Fetch spares/items for dropdown
$sparesRes = mysqli_query($conn, "
    SELECT 
        sp.id AS spare_id,
        sp.spareName,
        COALESCE(sp.partNo, '-') AS partNo,
        (SELECT b.id FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brand_id,
        (SELECT b.brandName FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brandName,
        (SELECT m.id FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS model_id,
        (SELECT m.model FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS modelName,
        COALESCE((SELECT st.sellingPricePerUnit FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS sellingPrice,
        COALESCE((SELECT st.gstPercentage FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS gstPercentage
    FROM spares sp
    WHERE sp.active = 1
    ORDER BY sp.spareName ASC
");
$sparesList = [];
while ($sp = mysqli_fetch_assoc($sparesRes)) {
    $sparesList[] = $sp;
}

// Fetch brands & models for dropdown fallback
$brandsRes = mysqli_query($conn, "SELECT id, brandName FROM brand ORDER BY brandName ASC");
$brandsList = [];
while ($b = mysqli_fetch_assoc($brandsRes)) { $brandsList[] = $b; }

$modelsRes = mysqli_query($conn, "SELECT id, model FROM model ORDER BY model ASC");
$modelsList = [];
while ($m = mysqli_fetch_assoc($modelsRes)) { $modelsList[] = $m; }

?>
<?php include("../includes/header.php"); ?>

<style>
  :root {
    --blue: #0d6efd;
    --gray-btn: #6c757d;
    --bg: #f8f9fa;
    --card: #ffffff;
    --text: #212529;
    --border: #dee2e6;
    --radius: 6px;
    --readonly-bg: #e9ecef;
    --input-bg: #ffffff;
  }

  body {
    background: var(--bg);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 13.5px;
    color: var(--text);
  }

  /* ── Page Wrapper & Layout ── */
  .page-wrapper {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 16px;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .page-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
  }

  .header-actions {
    display: flex;
    gap: 8px;
  }

  .btn-new-top {
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    background: #212529;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: var(--radius);
    display: inline-flex;
    align-items: center;
    gap: 4px;
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

  .btn-back:hover {
    background: #f1f3f5;
  }

  /* ── Card Containers ── */
  .card {
    background: var(--card);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #e9ecef;
    padding: 24px;
    margin-bottom: 20px;
  }

  .section-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f3f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  label {
    font-size: 12.5px;
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 4px;
  }

  label span.req {
    color: #dc3545;
  }

  label small {
    font-weight: 400;
    color: #6c757d;
  }

  input[type=text],
  input[type=number],
  input[type=date],
  input[type=email],
  select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: var(--radius);
    font-size: 13.5px;
    font-family: inherit;
    color: var(--text);
    background: var(--input-bg);
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
  }

  select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7'%3E%3Cpath d='M0 0l6 7 6-7z' fill='%23495057'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
  }

  input:focus, select:focus {
    outline: none;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  }

  input[readonly] {
    background: var(--readonly-bg);
    color: #495057;
  }

  .form-group {
    margin-bottom: 12px;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
  }

  /* ── Purchase Table ── */
  .po-table-container {
    overflow-x: auto;
    margin-top: 10px;
    margin-bottom: 12px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
  }

  .po-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
  }

  .po-table th, .po-table td {
    padding: 8px 10px;
    border: 1px solid #e9ecef;
    vertical-align: middle;
  }

  .po-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #334155;
    text-align: center;
    font-size: 12px;
  }

  .po-table th.th-grp {
    background: #f1f5f9;
  }

  .po-table td {
    background: #ffffff;
  }

  .po-table input.tbl-input, .po-table select.tbl-input {
    height: 32px;
    padding: 4px 8px;
    font-size: 12px;
  }

  .btn-row-add {
    background: #2563eb;
    color: #ffffff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .btn-row-add:hover {
    background: #1d4ed8;
  }

  .btn-row-del {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-row-del:hover {
    background: #fca5a5;
  }

  /* ── Summary Cards Grid ── */
  .summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
  }

  .summary-card {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 14px 16px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
  }

  .summary-card .s-title {
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .summary-card .s-val {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
  }

  .summary-card.total-val .s-val { color: #2563eb; }
  .summary-card.paid-val .s-val { color: #16a34a; }
  .summary-card.bal-val { background: #fef2f2; border-color: #fecaca; }
  .summary-card.bal-val .s-val { color: #dc2626; }

  /* ── Action Bar ── */
  .action-bar {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
  }

  .btn-sub {
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 24px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
  }

  .btn-res {
    background: var(--gray-btn);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 20px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
  }

  .btn-sub:hover { background: #0b5ed7; }
  .btn-res:hover { background: #4c535a; }

  /* ── Responsive Viewports ── */
  @media (max-width: 992px) {
    .grid-3 {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 768px) {
    .page-wrapper {
      margin: 10px auto;
      padding: 0 12px;
    }
    .card {
      padding: 16px;
      border-radius: 8px;
    }
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
    .header-actions {
      width: 100%;
      justify-content: space-between;
    }
    .summary-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 425px) {
    .grid-2, .grid-3, .summary-grid {
      grid-template-columns: 1fr !important;
      gap: 10px;
    }
    .action-bar {
      flex-direction: column;
      width: 100%;
    }
    .btn-sub, .btn-res {
      width: 100%;
      text-align: center;
    }
  }

  @media (max-width: 320px) {
    .page-wrapper {
      padding: 0 6px;
    }
    .page-header h2 {
      font-size: 17px;
    }
  }
</style>

<div class="page-wrapper">

  <div class="page-header">
    <h2>Purchase Order</h2>
    <div class="header-actions">
      <a href="purchase_list.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
        Purchase Orders
      </a>
    </div>
  </div>

  <form action="insert.php" method="POST" id="poForm" onsubmit="return validatePOForm()">

    <!-- 1. ORDER INFORMATION CARD -->
    <div class="card">
      <div class="section-title">Order Information</div>
      <div class="grid-3">
        <div class="form-group">
          <label>Order # <small>(System generated)</small></label>
          <input type="text" name="orderNo" id="orderNo" value="<?= htmlspecialchars($autoOrderNo) ?>" required>
        </div>
        <div class="form-group">
          <label>Order Status <span class="req">*</span></label>
          <select name="orderStatus" id="orderStatus" required>
            <option value="New" selected>New</option>
            <option value="Ordered">Ordered</option>
            <option value="Received">Received</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label>Order Date <span class="req">*</span></label>
          <input type="date" name="orderDate" id="orderDate" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
    </div>

    <!-- 2. SUPPLIER CARD -->
    <div class="card">
      <div class="section-title">
        <span>Supplier Information</span>
        <button type="button" onclick="clearSupplierInfo()" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer;">Clear Info</button>
      </div>
      <div class="grid-2">
        <!-- Left Supplier Info -->
        <div>
          <div class="form-group">
            <label>Supplier Name <span class="req">*</span></label>
            <select name="supplier" id="supplierSelect" required onchange="onSupplierChange(this.value)">
              <option value="">-- Select Supplier --</option>
              <?php foreach ($suppliersList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Phone # Primary <span class="req">*</span></label>
            <input type="text" name="supplierPhone" id="supplierPhone" required placeholder="e.g. 9843361326">
          </div>
          <div class="form-group">
            <label>Email ID</label>
            <input type="email" name="supplierEmail" id="supplierEmail" placeholder="supplier@example.com">
          </div>
        </div>

        <!-- Right Address Info -->
        <div>
          <div class="form-group">
            <label>Address Line 1 <span class="req">*</span></label>
            <input type="text" name="addressLine1" id="addressLine1" required placeholder="Line 1">
          </div>
          <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" name="addressLine2" id="addressLine2" placeholder="Line 2">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>City <span class="req">*</span></label>
              <input type="text" name="city" id="supplierCity" required placeholder="City">
            </div>
            <div class="form-group">
              <label>Zip Code <span class="req">*</span></label>
              <input type="text" name="zipCode" id="supplierZip" required placeholder="Zip Code">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. PURCHASE ITEMS TABLE CARD -->
    <div class="card">
      <div class="section-title">
        <span>Purchase Items</span>
        <div style="display:flex; gap:8px;">
          <button type="button" onclick="addPurchaseRow()" class="btn-row-add">+ Add Row</button>
        </div>
      </div>

      <div class="po-table-container">
        <table class="po-table" id="itemsTable">
          <thead>
            <tr>
              <th rowspan="2" style="width:35px;">#</th>
              <th rowspan="2" style="min-width:200px;">Item *</th>
              <th rowspan="2" style="width:120px;">Brand</th>
              <th rowspan="2" style="width:120px;">Model</th>
              <th colspan="2" class="th-grp">Quantity</th>
              <th colspan="3" class="th-grp">Purchase Price</th>
              <th class="th-grp">Selling Price</th>
              <th rowspan="2" style="width:50px;">Action</th>
            </tr>
            <tr>
              <th style="width:70px;">Ordered</th>
              <th style="width:70px;">Received</th>
              <th style="width:90px;">Without GST</th>
              <th style="width:65px;">GST %</th>
              <th style="width:90px;">With GST</th>
              <th style="width:90px;">Without GST</th>
            </tr>
          </thead>
          <tbody id="itemsTbody">
            <!-- Dynamic rows -->
          </tbody>
        </table>
      </div>

      <div style="margin-top:10px;">
        <button type="button" onclick="addPurchaseRow()" class="btn-row-add">+ Add Row</button>
      </div>
    </div>

    <!-- 4. FINANCIAL SUMMARY CARDS -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="s-title">Quoted Total</div>
        <div class="s-val" id="dispQuotedRs">₹0.00</div>
      </div>
      <div class="summary-card total-val">
        <div class="s-title">Total Amount</div>
        <div class="s-val" id="dispTotalRs">₹0.00</div>
      </div>
      <div class="summary-card paid-val">
        <div class="s-title">Paid Amount</div>
        <div class="s-val" id="dispPaidRs">₹0.00</div>
      </div>
      <div class="summary-card bal-val">
        <div class="s-title">Balance Due</div>
        <div class="s-val" id="dispBalanceRs">₹0.00</div>
      </div>
    </div>

    <!-- Hidden summary inputs for Form Post -->
    <input type="hidden" name="quoteAmountSum" id="quoteAmountSum" value="0">
    <input type="hidden" name="actualAmountSum" id="actualAmountSum" value="0">
    <input type="hidden" name="paidAmountSum" id="paidAmountSum" value="0">

    <!-- 5. PAYMENT CARD -->
    <div class="card">
      <div class="section-title">Payment Details</div>
      <div class="grid-3">
        <div class="form-group">
          <label>Paid On</label>
          <input type="date" name="paymentDate" id="paymentDate" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Mode</label>
          <select name="paymentMode" id="paymentMode">
            <option value="Cash" selected>Cash</option>
            <option value="Card">Card</option>
            <option value="UPI">UPI</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Ref No</label>
          <input type="text" name="paymentRefNo" id="paymentRefNo" placeholder="Transaction / Cheque #">
        </div>
      </div>
      <div class="grid-2" style="margin-top:10px; align-items:flex-end;">
        <div class="form-group">
          <label>Amount (₹)</label>
          <input type="number" step="0.01" name="paymentAmount" id="paymentAmount" placeholder="0.00" oninput="recalculateTotals()">
        </div>
        <div class="form-group">
          <button type="button" onclick="applyPaymentAmount()" class="btn-row-add" style="height:36px; padding:0 20px;">Add Payment</button>
        </div>
      </div>
    </div>

    <!-- SUBMIT ACTION BAR -->
    <div class="action-bar">
      <button type="submit" class="btn-sub">Save Purchase Order</button>
      <button type="reset" onclick="setTimeout(recalculateTotals, 50)" class="btn-res">Reset</button>
    </div>

  </form>
</div>

<!-- DATA OBJECTS FOR JS -->
<script>
    const SUPPLIERS_DATA = <?= json_encode($suppliersList) ?>;
    const SPARES_DATA = <?= json_encode($sparesList) ?>;
    const BRANDS_DATA = <?= json_encode($brandsList) ?>;
    const MODELS_DATA = <?= json_encode($modelsList) ?>;
</script>

<script>
    let rowCounter = 0;

    document.addEventListener("DOMContentLoaded", function () {
        // Add initial 2 rows
        addPurchaseRow();
        addPurchaseRow();
    });

    // ── Supplier Auto-Fill ──
    function onSupplierChange(suppId) {
        if (!suppId) {
            clearSupplierInfo();
            return;
        }
        const s = SUPPLIERS_DATA.find(item => item.id == suppId);
        if (s) {
            document.getElementById('supplierPhone').value = s.phoneNo1 || s.whatsAppNo || '';
            document.getElementById('supplierEmail').value = s.emailId || '';
            document.getElementById('addressLine1').value = s.line1 || '';
            document.getElementById('addressLine2').value = s.line2 || '';
            document.getElementById('supplierCity').value = s.city || '';
            document.getElementById('supplierZip').value = s.zipCode || '';
        }
    }

    function clearSupplierInfo() {
        document.getElementById('supplierSelect').value = '';
        document.getElementById('supplierPhone').value = '';
        document.getElementById('supplierEmail').value = '';
        document.getElementById('addressLine1').value = '';
        document.getElementById('addressLine2').value = '';
        document.getElementById('supplierCity').value = '';
        document.getElementById('supplierZip').value = '';
    }

    // ── Add Item Row ──
    function addPurchaseRow() {
        rowCounter++;
        const tbody = document.getElementById('itemsTbody');
        const tr = document.createElement('tr');
        tr.id = `row_${rowCounter}`;

        let itemOptionsHtml = `<option value="">-- Select / Search Item --</option>`;
        SPARES_DATA.forEach(sp => {
            itemOptionsHtml += `<option value="${sp.spare_id}" data-price="${sp.sellingPrice}" data-actual="${sp.actualPrice || 0}" data-gst="${sp.gstPercentage}" data-brand="${sp.brand_id || ''}" data-model="${sp.model_id || ''}">${escapeHtml(sp.spareName)} (${escapeHtml(sp.partNo)})</option>`;
        });

        let brandOptionsHtml = `<option value="">-- Brand --</option>`;
        BRANDS_DATA.forEach(b => {
            brandOptionsHtml += `<option value="${b.id}">${escapeHtml(b.brandName)}</option>`;
        });

        let modelOptionsHtml = `<option value="">-- Model --</option>`;
        MODELS_DATA.forEach(m => {
            modelOptionsHtml += `<option value="${m.id}">${escapeHtml(m.model)}</option>`;
        });

        tr.innerHTML = `
            <td style="text-align:center; font-weight:700;" class="row-seq">${tbody.children.length + 1}</td>
            <td>
                <select name="items[${rowCounter}][spare]" class="tbl-input item-select" required onchange="onItemSelect(this, ${rowCounter})">
                    ${itemOptionsHtml}
                </select>
                <input type="hidden" name="items[${rowCounter}][itemName]" class="item-name-hidden">
            </td>
            <td>
                <select name="items[${rowCounter}][brand]" class="tbl-input brand-select">
                    ${brandOptionsHtml}
                </select>
            </td>
            <td>
                <select name="items[${rowCounter}][model]" class="tbl-input model-select">
                    ${modelOptionsHtml}
                </select>
            </td>
            <td>
                <input type="number" name="items[${rowCounter}][orderedQty]" class="tbl-input qty-ordered" placeholder="1" min="0" required oninput="recalculateTotals()">
            </td>
            <td>
                <input type="number" name="items[${rowCounter}][receivedQty]" class="tbl-input qty-received" placeholder="0" min="0" oninput="recalculateTotals()">
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowCounter}][priceWOGst]" class="tbl-input price-wo-gst" placeholder="0.00" min="0" required oninput="calcRowGst(${rowCounter})">
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowCounter}][gstPct]" class="tbl-input gst-pct" placeholder="0.00" min="0" oninput="calcRowGst(${rowCounter})">
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowCounter}][priceWithGst]" class="tbl-input price-with-gst" placeholder="0.00" min="0" oninput="calcRowFromWithGst(${rowCounter})">
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowCounter}][sellingPrice]" class="tbl-input selling-price" placeholder="0.00" min="0">
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn-row-del" onclick="removePurchaseRow(${rowCounter})">Delete</button>
            </td>
        `;

        tbody.appendChild(tr);
        updateRowSequences();
    }

    function removePurchaseRow(id) {
        const tr = document.getElementById(`row_${id}`);
        if (tr) {
            tr.remove();
            updateRowSequences();
            recalculateTotals();
        }
    }

    function updateRowSequences() {
        const rows = document.querySelectorAll('#itemsTbody tr');
        rows.forEach((r, idx) => {
            const seqTd = r.querySelector('.row-seq');
            if (seqTd) seqTd.innerText = idx + 1;
        });
    }

    function onItemSelect(selectEl, rowId) {
        const tr = document.getElementById(`row_${rowId}`);
        if (!tr) return;

        const opt = selectEl.options[selectEl.selectedIndex];
        const text = opt.text;
        tr.querySelector('.item-name-hidden').value = text.split(' (')[0] || text;

        if (opt.value) {
            const actual = parseFloat(opt.getAttribute('data-actual')) || 0;
            const selling = parseFloat(opt.getAttribute('data-price')) || 0;
            const gst = parseFloat(opt.getAttribute('data-gst')) || 0;
            const brandId = opt.getAttribute('data-brand');
            const modelId = opt.getAttribute('data-model');

            if (brandId) tr.querySelector('.brand-select').value = brandId;
            if (modelId) tr.querySelector('.model-select').value = modelId;

            tr.querySelector('.price-wo-gst').value = Math.round(actual);
            tr.querySelector('.selling-price').value = Math.round(selling);
            tr.querySelector('.gst-pct').value = Math.round(gst);

            calcRowGst(rowId);
        }
    }

    function calcRowGst(rowId) {
        const tr = document.getElementById(`row_${rowId}`);
        if (!tr) return;

        let wo = parseFloat(tr.querySelector('.price-wo-gst').value) || 0;
        let gst = parseFloat(tr.querySelector('.gst-pct').value) || 0;

        let withGst = Math.round(wo + (wo * gst / 100));
        tr.querySelector('.price-with-gst').value = withGst;

        recalculateTotals();
    }

    function calcRowFromWithGst(rowId) {
        const tr = document.getElementById(`row_${rowId}`);
        if (!tr) return;

        let withGst = parseFloat(tr.querySelector('.price-with-gst').value) || 0;
        let gst = parseFloat(tr.querySelector('.gst-pct').value) || 0;

        let wo = Math.round(withGst / (1 + (gst / 100)));
        tr.querySelector('.price-wo-gst').value = wo;

        recalculateTotals();
    }

    // ── Live Calculation Engine ──
    function recalculateTotals() {
        let grandTotalWithGst = 0;
        let grandQuotedSum = 0;

        const rows = document.querySelectorAll('#itemsTbody tr');
        rows.forEach(r => {
            let qty = parseFloat(r.querySelector('.qty-ordered').value) || 0;
            let priceWithGst = parseFloat(r.querySelector('.price-with-gst').value) || 0;
            let priceWoGst = parseFloat(r.querySelector('.price-wo-gst').value) || 0;

            grandTotalWithGst += Math.round(qty * priceWithGst);
            grandQuotedSum += Math.round(qty * priceWoGst);
        });

        grandTotalWithGst = Math.round(grandTotalWithGst);
        grandQuotedSum = Math.round(grandQuotedSum);
        let paidAmount = Math.round(parseFloat(document.getElementById('paymentAmount').value) || 0);
        let balanceAmount = Math.round(grandTotalWithGst - paidAmount);

        document.getElementById('dispQuotedRs').innerText = '₹' + grandQuotedSum;
        document.getElementById('dispTotalRs').innerText = '₹' + grandTotalWithGst;
        document.getElementById('dispPaidRs').innerText = '₹' + paidAmount;
        document.getElementById('dispBalanceRs').innerText = '₹' + balanceAmount;

        document.getElementById('quoteAmountSum').value = grandQuotedSum;
        document.getElementById('actualAmountSum').value = grandTotalWithGst;
        document.getElementById('paidAmountSum').value = paidAmount;
    }

    function applyPaymentAmount() {
        recalculateTotals();
    }

    function validatePOForm() {
        const rows = document.querySelectorAll('#itemsTbody tr');
        if (rows.length === 0) {
            alert('Please add at least one purchase item!');
            return false;
        }

        let hasValidItem = false;
        rows.forEach(r => {
            const spareId = r.querySelector('.item-select').value;
            if (spareId) hasValidItem = true;
        });

        if (!hasValidItem) {
            alert('Please select an item for at least one row!');
            return false;
        }

        return true;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>

<?php include("../includes/footer.php"); ?>
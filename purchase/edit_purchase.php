<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

$purchaseId = intval($_GET['id'] ?? 0);
if ($purchaseId <= 0) {
  header("Location: purchase_list.php");
  exit();
}

$stmtP = mysqli_prepare($conn, "
    SELECT p.*, s.name as supplierName, s.phoneNo1 as supplierPhone, s.emailId as supplierEmail,
           a.line1 as addressLine1, a.line2 as addressLine2, a.city, a.zipCode
    FROM purchase p
    LEFT JOIN supplier s ON p.supplier = s.id
    LEFT JOIN address a ON s.address = a.id
    WHERE p.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmtP, "i", $purchaseId);
mysqli_stmt_execute($stmtP);
$resP = mysqli_stmt_get_result($stmtP);
$purchaseData = mysqli_fetch_assoc($resP);

if (!$purchaseData) {
  echo "<script>alert('Purchase Order not found!'); window.location.href='purchase_list.php';</script>";
  exit();
}

$purchasedBy = !empty($purchaseData['createdBy']) ? $purchaseData['createdBy'] : ($_SESSION['username'] ?? 'Admin');
$currentStatus = $purchaseData['orderStatus'] ?? 'Purchased';
$isDeliveredOrder = in_array($currentStatus, ['Delivered', 'Received', 'Completed']);
$deliveredDateVal = $isDeliveredOrder ? (!empty($purchaseData['modifiedOn']) ? date('Y-m-d', strtotime($purchaseData['modifiedOn'])) : date('Y-m-d')) : 'Pending';

$stmtItems = mysqli_prepare($conn, "
    SELECT pi.*, sp.partNo, COALESCE(sp.rackNumber, '-') AS rackNumber, COALESCE(sp.picture, '') AS picture,
           COALESCE((SELECT st.availableQty FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS availableQty,
           COALESCE((SELECT st.sellingPricePerUnit FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS defaultSellingPrice,
           COALESCE((SELECT st.gstPercentage FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS defaultGst
    FROM purchaseitems pi
    LEFT JOIN spares sp ON pi.spare = sp.id
    WHERE pi.purchase = ? AND (pi.deleted = 0 OR pi.deleted IS NULL)
    ORDER BY pi.id ASC
");
mysqli_stmt_bind_param($stmtItems, "i", $purchaseId);
mysqli_stmt_execute($stmtItems);
$resItems = mysqli_stmt_get_result($stmtItems);
$existingItems = [];
while ($row = mysqli_fetch_assoc($resItems)) {
  $existingItems[] = $row;
}

$stmtPay = mysqli_prepare($conn, "SELECT * FROM payment WHERE purchase = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmtPay, "i", $purchaseId);
mysqli_stmt_execute($stmtPay);
$resPay = mysqli_stmt_get_result($stmtPay);
$paymentData = mysqli_fetch_assoc($resPay);

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

$sparesRes = mysqli_query($conn, "
    SELECT
        sp.id AS spare_id,
        sp.spareName,
        COALESCE(sp.partNo, '-') AS partNo,
        COALESCE(sp.rackNumber, '-') AS rackNumber,
        COALESCE(sp.picture, '') AS picture,
        COALESCE((SELECT st.availableQty FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS availableQty,
        COALESCE((SELECT st.minQty FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS minQty,
        (SELECT b.id FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brand_id,
        (SELECT b.brandName FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brandName,
        (SELECT m.id FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS model_id,
        (SELECT m.model FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS modelName,
        COALESCE((SELECT st.actualPricePerUnit FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS actualPrice,
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

$brandsRes = mysqli_query($conn, "SELECT id, brandName FROM brand ORDER BY brandName ASC");
$brandsList = [];
while ($b = mysqli_fetch_assoc($brandsRes)) {
  $brandsList[] = $b;
}

$modelsRes = mysqli_query($conn, "SELECT id, model FROM model ORDER BY model ASC");
$modelsList = [];
while ($m = mysqli_fetch_assoc($modelsRes)) {
  $modelsList[] = $m;
}

// Fetch items at reorder level, critical, or out of stock for Wanted / Reorder Items modal
$reorderStockRes = mysqli_query($conn, "
    SELECT
        st.id AS stock_id,
        COALESCE(st.spare, s.id, 0) AS spare_id,
        COALESCE(st.barCode, '') AS barCode,
        COALESCE(s.spareName, st.itemName) AS spareName,
        COALESCE(st.itemName, s.spareName) AS itemName,
        COALESCE(s.partNo, '-') AS partNo,
        COALESCE(st.availableQty, 0) AS availableQty,
        COALESCE(st.minQty, 0) AS minQty,
        COALESCE(st.maxQty, 0) AS maxQty,
        COALESCE(st.reorderLevel, 0) AS reorderLevel,
        COALESCE(b.id, 0) AS brand_id,
        COALESCE(b.brandName, '') AS brandName,
        COALESCE(m.id, 0) AS model_id,
        COALESCE(m.model, '') AS modelName,
        COALESCE(st.actualPricePerUnit, 0) AS actualPrice,
        COALESCE(st.sellingPricePerUnit, 0) AS sellingPrice,
        COALESCE(st.gstPercentage, 0) AS gstPercentage
    FROM stock st
    LEFT JOIN spares s ON st.spare = s.id
    LEFT JOIN brand b ON st.brand = b.id
    LEFT JOIN model m ON st.model = m.id
    WHERE (
        st.availableQty <= 0
        OR (st.minQty > 0 AND st.availableQty <= st.minQty)
        OR (st.reorderLevel > 0 AND st.availableQty <= st.reorderLevel)
    )
    ORDER BY
        CASE
            WHEN st.availableQty <= 0 THEN 1
            WHEN st.minQty > 0 AND st.availableQty <= st.minQty THEN 2
            WHEN st.reorderLevel > 0 AND st.availableQty <= st.reorderLevel THEN 3
            ELSE 4
        END,
        st.availableQty ASC,
        st.itemName ASC
");
$reorderStockList = [];
if ($reorderStockRes) {
  while ($rstk = mysqli_fetch_assoc($reorderStockRes)) {
    $reorderStockList[] = $rstk;
  }
}

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

  .page-wrapper {
    max-width: 1700px;
    width: 98%;
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

  input:focus,
  select:focus {
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
    font-size: 13px;
    min-width: 980px;
    table-layout: fixed;
  }

  .btn-review {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 12px;
    height: 30px;
    border-radius: 6px;
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.15s ease;
  }

  .btn-review:hover {
    background: #4338ca;
    color: #ffffff;
    border-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(67, 56, 202, 0.25);
  }

  .po-table th,
  .po-table td {
    padding: 8px 10px;
    border: 1px solid #e9ecef;
    vertical-align: middle;
    white-space: nowrap;
  }

  .po-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #334155;
    text-align: center;
    font-size: 12.5px;
    border: 1px solid #e9ecef;
    white-space: nowrap;
  }

  .po-table th.th-grp {
    background: #f1f5f9;
    border: 1px solid #e9ecef;
  }

  .po-table td {
    background: #ffffff;
    border: 1px solid #e9ecef;
  }

  .po-item-hover-tip {
    position: fixed;
    display: none;
    z-index: 99999;
    background: #0f172a;
    color: #f8fafc;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-family: inherit;
    line-height: 1.35;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22);
    pointer-events: none;
    max-width: 340px;
    word-break: break-word;
    white-space: normal;
  }

  .po-item-hover-tip .tip-type {
    color: #94a3b8;
    font-weight: 700;
    margin-right: 4px;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .po-item-hover-tip .tip-name {
    color: #ffffff;
    font-weight: 600;
  }

  .po-item-hover-tip.arrow-bottom::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 16px;
    border-width: 5px;
    border-style: solid;
    border-color: #0f172a transparent transparent transparent;
  }

  .po-item-hover-tip.arrow-top::after {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 16px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent #0f172a transparent;
  }

  .po-table input.tbl-input {
    width: 100%;
    height: 36px;
    padding: 6px 10px;
    font-size: 13px;
    box-sizing: border-box;
    border: 1px solid #ced4da;
    border-radius: var(--radius);
  }

  .po-table select.tbl-input {
    width: 100%;
    height: 36px;
    padding: 6px 26px 6px 10px;
    font-size: 13px;
    box-sizing: border-box;
    border: 1px solid #ced4da;
    border-radius: var(--radius);
    background-position: right 10px center;
    background-repeat: no-repeat;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    white-space: nowrap;
  }

  .po-table select.tbl-input:disabled {
    background-color: #f8fafc !important;
    color: #000000 !important;
    -webkit-text-fill-color: #000000 !important;
    opacity: 1 !important;
    cursor: not-allowed !important;
    border-color: #cbd5e1 !important;
  }

  .item-search-input {
    color: #000000 !important;
  }

  .searchable-item-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
  }

  .item-search-input {
    width: 100% !important;
    height: 36px;
    padding: 6px 26px 6px 10px !important;
    font-size: 13px;
    box-sizing: border-box;
    border: 1px solid #ced4da;
    border-radius: var(--radius);
    background-color: #ffffff;
    cursor: text;
  }

  .item-search-input:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    outline: none;
  }

  .search-drop-icon {
    position: absolute;
    right: 9px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .po-search-dropdown-menu {
    position: fixed;
    z-index: 999999;
    background: #fffcf4;
    border: 1px solid #767676;
    border-radius: 0px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
    max-height: 250px;
    overflow-y: auto;
    display: none;
    box-sizing: border-box;
    font-family: inherit;
  }

  .po-search-dropdown-menu .drop-item-row {
    padding: 3px 8px;
    cursor: default;
    font-size: 13px;
    font-weight: 400;
    color: #000000;
    border-bottom: none;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    line-height: 1.45;
    user-select: none;
  }

  .po-search-dropdown-menu .drop-item-row.highlighted {
    background-color: #1967d2 !important;
    color: #ffffff !important;
  }

  .po-search-dropdown-menu .drop-no-match {
    padding: 6px 8px;
    color: #666666;
    font-size: 12.5px;
    text-align: left;
    font-style: italic;
  }

  .status-display-field {
    width: 100%;
    height: 38px;
    padding: 6px 14px;
    font-size: 13.5px;
    font-weight: 700;
    border-radius: var(--radius);
    box-sizing: border-box;
    cursor: not-allowed;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
  }

  .status-display-field.status-new {
    color: #334155;
    background: #f1f5f9;
    border: 1.5px solid #cbd5e1;
  }

  .status-display-field.status-ordered {
    color: #b45309;
    background: #fef3c7;
    border: 1.5px solid #fde68a;
  }

  .status-display-field.status-received {
    color: #4338ca;
    background: #e0e7ff;
    border: 1.5px solid #c7d2fe;
  }

  .status-display-field.status-completed {
    color: #047857;
    background: #d1fae5;
    border: 1.5px solid #a7f3d0;
  }

  .qty-ordered,
  .qty-received {
    width: 100%;
    text-align: center;
    box-sizing: border-box;
    padding: 6px 8px;
  }

  .gst-pct {
    width: 100%;
    text-align: center;
    box-sizing: border-box;
    padding: 6px 8px;
  }

  .price-wo-gst,
  .price-with-gst,
  .selling-price {
    width: 100%;
    text-align: right;
    box-sizing: border-box;
    padding: 6px 10px;
  }

  .po-table input[type=number].price-wo-gst::-webkit-inner-spin-button,
  .po-table input[type=number].price-wo-gst::-webkit-outer-spin-button,
  .po-table input[type=number].price-with-gst::-webkit-inner-spin-button,
  .po-table input[type=number].price-with-gst::-webkit-outer-spin-button,
  .po-table input[type=number].selling-price::-webkit-inner-spin-button,
  .po-table input[type=number].selling-price::-webkit-outer-spin-button,
  .po-table input[type=number].gst-pct::-webkit-inner-spin-button,
  .po-table input[type=number].gst-pct::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .po-table input[type=number].price-wo-gst,
  .po-table input[type=number].price-with-gst,
  .po-table input[type=number].selling-price,
  .po-table input[type=number].gst-pct {
    -moz-appearance: textfield;
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

  .btn-stock-redirect {
    background: #16a34a;
    color: #ffffff;
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    border: none;
    cursor: pointer;
    transition: background 0.15s ease;
  }

  .btn-stock-redirect:hover {
    background: #15803d;
    color: #ffffff;
    text-decoration: none;
  }

  .btn-wanted-stock {
    background: #ea580c;
    color: #ffffff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(234, 88, 12, 0.25);
    transition: all 0.2s ease;
  }

  .btn-wanted-stock:hover {
    background: #c2410c;
    box-shadow: 0 2px 6px rgba(234, 88, 12, 0.35);
  }

  .reorder-select-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    padding: 5px 12px;
    border-radius: 6px;
    transition: all 0.15s ease;
    user-select: none;
    outline: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }

  .reorder-select-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  .reorder-select-btn.is-selected {
    background: #eff6ff;
    border-color: #cbd5e1;
  }

  .reorder-tick-btn {
    width: 19px;
    height: 19px;
    min-width: 19px;
    min-height: 19px;
    border-radius: 5px;
    border: 2px solid #94a3b8;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 0.15s ease;
    flex-shrink: 0;
    pointer-events: none;
  }

  .reorder-select-btn:hover .reorder-tick-btn {
    border-color: #2563eb;
  }

  .reorder-tick-btn.is-checked {
    background: #2563eb !important;
    border-color: #2563eb !important;
  }

  .reorder-tick-btn .tick-svg {
    color: #ffffff;
    display: none;
  }

  .reorder-tick-btn.is-checked .tick-svg {
    display: block;
  }

  .reorder-select-text {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    line-height: 1;
    pointer-events: none;
  }

  .reorder-select-btn.is-selected .reorder-select-text {
    color: #1d4ed8;
    font-weight: 700;
  }

  #reorderModalTable thead th {
    position: sticky;
    top: 0;
    background: #f1f5f9;
    z-index: 5;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
  }

  #reorderModalTable tbody tr {
    background-color: #ffffff;
    transition: background-color 0.15s ease;
  }

  #reorderModalTable tbody tr:hover {
    background-color: #f8fafc;
  }

  .badge-reorder-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
  }

  .badge-status-out {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
  }

  .badge-status-critical {
    background: #ffedd5;
    color: #9a3412;
    border: 1px solid #fdba74;
  }

  .badge-status-reorder {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
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
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
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

  .summary-card.total-val .s-val {
    color: #2563eb;
  }

  .summary-card.paid-val .s-val {
    color: #16a34a;
  }

  .summary-card.bal-val {
    background: #fef2f2;
    border-color: #fecaca;
  }

  .summary-card.bal-val .s-val {
    color: #dc2626;
  }

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
    text-decoration: none;
  }

  .btn-sub:hover {
    background: #0b5ed7;
  }

  .btn-res:hover {
    background: #4c535a;
  }

  .btn-summary-edit {
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    border-radius: 5px;
    padding: 4px 12px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-summary-edit:hover {
    background: #4338ca;
    color: #ffffff;
    border-color: #4338ca;
  }

  .btn-confirm-purchase {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #ffffff;
    border: none;
    border-radius: 7px;
    padding: 9px 22px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 3px 8px rgba(22, 163, 74, 0.25);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-confirm-purchase:hover {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
    transform: translateY(-1px);
  }

  .btn-confirm-purchase:active {
    transform: translateY(0);
  }

  .badge-delivered {
    background: #dcfce7;
    color: #15803d;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #bbf7d0;
    display: inline-block;
  }

  .badge-pending {
    background: #fef3c7;
    color: #b45309;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #fde68a;
    display: inline-block;
  }

  @keyframes rowHighlightPulse {
    0% {
      background-color: #fef08a !important;
    }

    50% {
      background-color: #fef9c3 !important;
    }

    100% {
      background-color: transparent;
    }
  }

  .row-highlight-pulse {
    animation: rowHighlightPulse 1.8s ease-out;
  }

  @media (max-width: 992px) {
    .grid-3 {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 768px) {
    .page-wrapper {
      margin: 10px auto;
      padding: 0 4px !important;
    }

    .card {
      padding: 14px 12px;
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
      grid-template-columns: 1fr !important;
      gap: 10px !important;
    }

    .summary-card {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      justify-content: space-between !important;
      text-align: left !important;
      padding: 14px 16px !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }

    .summary-card .s-title {
      margin: 0 !important;
      font-size: 13px !important;
      font-weight: 700 !important;
      color: #64748b !important;
      text-align: left !important;
    }

    .summary-card .s-val {
      margin: 0 !important;
      font-size: 19px !important;
      font-weight: 800 !important;
      text-align: right !important;
    }

    /* Mobile responsive tables */
    .po-table-container {
      margin-top: 6px;
      margin-bottom: 8px;
      border-radius: 6px;
      -webkit-overflow-scrolling: touch;
    }

    .po-table th,
    .po-table td {
      white-space: nowrap;
    }

    #itemsTable {
      min-width: 800px !important;
    }

    #summaryItemsTable {
      min-width: 700px !important;
    }

    .btn-wanted-stock,
    .btn-stock-redirect,
    .btn-row-add {
      font-size: 11px !important;
      padding: 3px 8px !important;
      height: 26px !important;
      white-space: nowrap !important;
    }
  }

  @media (max-width: 425px) {

    .grid-2,
    .grid-3 {
      grid-template-columns: 1fr !important;
      gap: 10px;
    }

    .action-bar {
      display: flex !important;
      flex-direction: row !important;
      width: 100% !important;
      gap: 12px !important;
      align-items: stretch !important;
      justify-content: center !important;
      padding: 0 16px !important;
      box-sizing: border-box !important;
    }

    .btn-sub,
    .btn-res {
      flex: 1 1 0 !important;
      max-width: 180px !important;
      width: auto !important;
      text-align: center;
      height: 44px !important;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Extra compact tables for small phones */

  }
</style>

<div class="page-wrapper">

  <div class="page-header">
    <h2>Edit Purchase Order Details</h2>
    <div class="header-actions">
      <a href="purchase_list.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
          <path fill-rule="evenodd"
            d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
        </svg>
        Purchase Orders
      </a>
    </div>
  </div>

  <form action="update_purchase.php" method="POST" id="poEditForm" onsubmit="return validatePOForm()"
    onreset="resetPurchaseForm()">
    <input type="hidden" name="purchaseId" value="<?= $purchaseId ?>">
    <input type="hidden" name="redirectToPrint" id="redirectToPrint" value="0">

    <div class="card">
      <div class="section-title">
        <span>Order Information</span>
        <span
          style="background:#e0f2fe; color:#0369a1; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700;"><?= htmlspecialchars($purchaseData['orderNo']) ?></span>
      </div>
      <div class="grid-3">
        <div class="form-group">
          <label>Order # <small>(Read Only)</small></label>
          <input type="text" name="orderNo" id="orderNo" value="<?= htmlspecialchars($purchaseData['orderNo']) ?>"
            readonly required>
        </div>
        <div class="form-group">
          <label>Order Status <span class="req">*</span></label>
          <select name="orderStatus" id="orderStatus" style="font-weight: 600;" onchange="updatePurchasedSummaryTable()"
            <?= $isDeliveredOrder ? 'disabled' : '' ?>>
            <option value="Purchased" <?= in_array($purchaseData['orderStatus'] ?? '', ['Purchased', 'New', 'Ordered']) ? 'selected' : '' ?>>Purchased</option>
            <option value="Delivered" <?= in_array($purchaseData['orderStatus'] ?? '', ['Delivered', 'Received', 'Completed']) ? 'selected' : '' ?>>Delivered</option>
          </select>
          <?php if ($isDeliveredOrder): ?>
            <input type="hidden" name="orderStatus" value="<?= htmlspecialchars($currentStatus) ?>">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Order Date <span class="req">*</span></label>
          <input type="date" name="orderDate" id="orderDate"
            value="<?= htmlspecialchars($purchaseData['orderDate'] ?? date('Y-m-d')) ?>" required
            onchange="updatePurchasedSummaryTable()" <?= $isDeliveredOrder ? 'readonly' : '' ?>>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>Supplier Information</span>
        <button type="button" onclick="openAddSupplierModal()" style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;padding:8px 18px;border-radius:8px;font-weight:700;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(22,163,74,0.35);transition:opacity 0.2s;" onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">&#43; Add Supplier</button>
      </div>
      <div class="grid-2">
        <div>
          <div class="form-group">
            <label>Supplier Name <span class="req">*</span></label>
            <select name="supplier" id="supplierSelect" required onchange="onSupplierChange(this.value)"
              <?= $isDeliveredOrder ? 'disabled' : '' ?>>
              <option value="">-- Select Supplier --</option>
              <?php foreach ($suppliersList as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $purchaseData['supplier'] == $s['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($isDeliveredOrder): ?>
              <input type="hidden" name="supplier" value="<?= htmlspecialchars($purchaseData['supplier']) ?>">
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Phone # Primary <span class="req">*</span></label>
            <input type="text" name="supplierPhone" id="supplierPhone" required
              value="<?= htmlspecialchars($purchaseData['supplierPhone'] ?? '') ?>" placeholder="e.g. 9843361326"
              <?= $isDeliveredOrder ? 'readonly' : '' ?>>
          </div>
          <div class="form-group">
            <label>Email ID</label>
            <input type="email" name="supplierEmail" id="supplierEmail"
              value="<?= htmlspecialchars($purchaseData['supplierEmail'] ?? '') ?>" placeholder="supplier@example.com"
              <?= $isDeliveredOrder ? 'readonly' : '' ?>>
          </div>
        </div>

        <div>
          <div class="form-group">
            <label>Address Line 1 <span class="req">*</span></label>
            <input type="text" name="addressLine1" id="addressLine1" required
              value="<?= htmlspecialchars($purchaseData['addressLine1'] ?? '') ?>" placeholder="Line 1"
              <?= $isDeliveredOrder ? 'readonly' : '' ?>>
          </div>
          <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" name="addressLine2" id="addressLine2"
              value="<?= htmlspecialchars($purchaseData['addressLine2'] ?? '') ?>" placeholder="Line 2"
              <?= $isDeliveredOrder ? 'readonly' : '' ?>>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>City <span class="req">*</span></label>
              <input type="text" name="city" id="supplierCity" required
                value="<?= htmlspecialchars($purchaseData['city'] ?? '') ?>" placeholder="City" <?= $isDeliveredOrder ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
              <label>Zip Code <span class="req">*</span></label>
              <input type="text" name="zipCode" id="supplierZip" required
                value="<?= htmlspecialchars($purchaseData['zipCode'] ?? '') ?>" placeholder="Zip Code"
                <?= $isDeliveredOrder ? 'readonly' : '' ?>>
            </div>
          </div>
          <?php if (!$isDeliveredOrder): ?>
            <div style="display:flex; justify-content:flex-end; margin-top:10px;">
              <button type="button" onclick="clearSupplierInfo()"
                style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">Clear
                Info</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="section-title">
        <span>Purchase Items</span>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
          <?php if (!$isDeliveredOrder): ?>
            <button type="button" onclick="openReorderModal()" class="btn-wanted-stock"
              title="View & Add Reorder Level / Wanted Items">Wanted Items</button>
            <a href="../stock/add_stock.php" target="_blank" class="btn-stock-redirect">Add New Stock</a>
            <button type="button" onclick="addPurchaseRow()" class="btn-row-add">+ Add Row</button>
          <?php endif; ?>
        </div>
      </div>

      <div class="po-table-container">
        <table class="po-table" id="itemsTable">
          <colgroup>
            <col class="col-idx" style="width: 45px;"> <!-- # -->
            <col class="col-item" style="width: 280px;"> <!-- Item * -->
            <col class="col-brand" style="width: 140px;"> <!-- Brand -->
            <col class="col-model" style="width: 140px;"> <!-- Model -->
            <col class="col-qty" style="width: 110px;"> <!-- Quantity -->
            <col class="col-remarks" style="width: 290px;"> <!-- Remarks -->
            <col class="col-action" style="width: 80px;"> <!-- Action -->
          </colgroup>
          <thead>
            <tr>
              <th style="text-align:center;">#</th>
              <th title="Spare Item">Item *</th>
              <th title="Item Brand">Brand</th>
              <th title="Item Model">Model</th>
              <th style="text-align:center;">Quantity</th>
              <th>Remarks</th>
              <th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody id="itemsTbody">

          </tbody>
        </table>
      </div>
    </div>

    <!-- Purchased Items Summary Section -->
    <div class="card" id="purchasedSummaryCard" style="margin-top: 20px;">
      <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>Summary of Purchased Items</span>
        <span
          style="font-size:12px; font-weight:600; color:#475569; background:#f1f5f9; padding:3px 10px; border-radius:12px;">Live
          Overview</span>
      </div>

      <div class="po-table-container">
        <table class="po-table" id="summaryItemsTable">
          <colgroup>
            <col class="col-sum-sno" style="width: 55px;"> <!-- S.No -->
            <col class="col-sum-items" style="width: 280px;"> <!-- Items -->
            <col class="col-sum-qty" style="width: 90px;"> <!-- Qty -->
            <col class="col-sum-by" style="width: 150px;"> <!-- Purchased By -->
            <col class="col-sum-ord-date" style="width: 130px;"> <!-- Ordered Date -->
            <col class="col-sum-del-date" style="width: 140px;"> <!-- Delivered Date -->
            <col class="col-sum-act" style="width: 90px;"> <!-- Action -->
          </colgroup>
          <thead>
            <tr>
              <th style="text-align:center;">S.No</th>
              <th>Items</th>
              <th style="text-align:center;">Qty</th>
              <th>Purchased By</th>
              <th style="text-align:center;">Ordered Date</th>
              <th style="text-align:center;">Delivered Date</th>
              <th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody id="summaryItemsTbody">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>

      <!-- Below the details: Total items, Supplier, and Confirm Purchase button -->
      <div class="summary-bottom-bar"
        style="margin-top:16px; padding-top:16px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
          <div style="font-size:13.5px; color:#334155; font-weight:600;">
            Total Items: <span id="summaryTotalItemsCount"
              style="font-size:16px; font-weight:800; color:#1e293b; margin-left:4px;">0</span>
          </div>
          <div style="font-size:13.5px; color:#334155; font-weight:600;">
            Supplier: <span id="summarySupplierName"
              style="font-size:14px; font-weight:700; color:#2563eb; margin-left:4px;">-</span>
          </div>
        </div>

        <div>
          <?php if (!$isDeliveredOrder): ?>
            <button type="button" class="btn-confirm-purchase" onclick="confirmPurchaseDelivery()">
              Confirm Purchase
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <input type="hidden" name="quoteAmountSum" id="quoteAmountSum"
      value="<?= htmlspecialchars($purchaseData['quoteAmountSum'] ?? '0') ?>">
    <input type="hidden" name="actualAmountSum" id="actualAmountSum"
      value="<?= htmlspecialchars($purchaseData['actualAmountSum'] ?? '0') ?>">
    <input type="hidden" name="paidAmountSum" id="paidAmountSum"
      value="<?= htmlspecialchars($purchaseData['paidAmountSum'] ?? '0') ?>">
    <input type="hidden" name="paymentAmount" id="paymentAmount"
      value="<?= htmlspecialchars($paymentData['amount'] ?? $purchaseData['paidAmountSum'] ?? '0') ?>">
    <input type="hidden" name="paymentMode" id="paymentMode"
      value="<?= htmlspecialchars($paymentData['mode'] ?? 'Cash') ?>">
    <input type="hidden" name="paymentDate" id="paymentDate"
      value="<?= htmlspecialchars($paymentData['transactionDate'] ?? date('Y-m-d')) ?>">
    <input type="hidden" name="paymentRefNo" id="paymentRefNo"
      value="<?= htmlspecialchars($paymentData['refNo'] ?? '') ?>">

    <div class="action-bar">
      <a href="purchase_list.php" class="btn-res"><?= $isDeliveredOrder ? 'Back' : 'Cancel' ?></a>
      <?php if (!$isDeliveredOrder): ?>
        <button type="submit" class="btn-sub">Save Changes</button>
      <?php endif; ?>
    </div>

  </form>
</div>

<script>
  const SUPPLIERS_DATA = <?= json_encode($suppliersList) ?>;
  const SPARES_DATA = <?= json_encode($sparesList) ?>;
  const BRANDS_DATA = <?= json_encode($brandsList) ?>;
  const MODELS_DATA = <?= json_encode($modelsList) ?>;
  const EXISTING_ITEMS = <?= json_encode($existingItems) ?>;
  const PURCHASED_BY = <?= json_encode($purchasedBy) ?>;
  const DELIVERED_DATE_VAL = <?= json_encode($deliveredDateVal) ?>;
  const IS_DELIVERED = <?= $isDeliveredOrder ? 'true' : 'false' ?>;
  const REORDER_ITEMS_DATA = <?= json_encode($reorderStockList) ?>;
</script>

<script>
  let rowCounter = 0;

  document.addEventListener("DOMContentLoaded", function () {
    if (EXISTING_ITEMS && EXISTING_ITEMS.length > 0) {
      EXISTING_ITEMS.forEach(item => {
        addPurchaseRow(item);
      });
    } else {
      addPurchaseRow();
    }
    recalculateTotals();
    initPoItemHoverTooltips();
    updatePurchasedSummaryTable();
  });

  function autoUpdateOrderStatus() {
    // Status is user-selected ('Purchased' or 'Delivered')
  }

  function onSupplierChange(suppId) {
    if (!suppId) {
      clearSupplierInfo();
      updateSummarySupplierName();
      return;
    }
    const s = SUPPLIERS_DATA.find(item => item.id == suppId);
    if (s) {
      document.getElementById('supplierPhone').value = s.phoneNo1 || '';
      document.getElementById('supplierEmail').value = s.emailId || '';
      document.getElementById('addressLine1').value = s.line1 || '';
      document.getElementById('addressLine2').value = s.line2 || '';
      document.getElementById('supplierCity').value = s.city || '';
      document.getElementById('supplierZip').value = s.zipCode || '';
    }
    updateSummarySupplierName();
  }

  function clearSupplierInfo() {
    document.getElementById('supplierSelect').value = '';
    document.getElementById('supplierPhone').value = '';
    document.getElementById('supplierEmail').value = '';
    document.getElementById('addressLine1').value = '';
    document.getElementById('addressLine2').value = '';
    document.getElementById('supplierCity').value = '';
    document.getElementById('supplierZip').value = '';
    updateSummarySupplierName();
  }

  function addPurchaseRow(data = null) {
    rowCounter++;
    const tbody = document.getElementById('itemsTbody');
    const tr = document.createElement('tr');
    tr.id = `row_${rowCounter}`;

    let selectedSpareId = data ? data.spare : '';
    let selectedBrandId = data ? data.brand : '';
    let selectedModelId = data ? data.model : '';
    let itemNameHidden = data ? escapeHtml(data.itemName) : '';

    let selectedItemText = '';
    if (selectedSpareId) {
      const sp = SPARES_DATA.find(s => s.spare_id == selectedSpareId);
      if (sp) {
        const pno = (sp.partNo && sp.partNo !== '-' && sp.partNo.trim() !== '') ? ` (${sp.partNo.trim()})` : '';
        selectedItemText = (sp.spareName || '').replace(/\s+/g, ' ').trim() + pno;
      } else if (data && data.itemName) {
        selectedItemText = data.itemName;
      }
    }

    let brandOptionsHtml = `<option value="">-- Brand --</option>`;
    BRANDS_DATA.forEach(b => {
      const isSel = (b.id == selectedBrandId) ? 'selected' : '';
      brandOptionsHtml += `<option value="${b.id}" ${isSel}>${escapeHtml(b.brandName)}</option>`;
    });

    let modelOptionsHtml = `<option value="">-- Model --</option>`;
    MODELS_DATA.forEach(m => {
      const isSel = (m.id == selectedModelId) ? 'selected' : '';
      modelOptionsHtml += `<option value="${m.id}" ${isSel}>${escapeHtml(m.model)}</option>`;
    });

    const orderedQty = data ? (data.orderedQuantity || '') : '1';
    const receivedQty = data ? (data.receivedQuantity || data.orderedQuantity || '1') : '1';
    const priceWOGst = data ? parseFloat(data.sellingPricePerQtyWOGSt || 0).toFixed(2) : '0';
    const gstPct = data ? parseFloat(data.gstPercentage || 0).toFixed(2) : '0';
    const priceWithGst = data ? (parseFloat(data.totalPriceWithGst || 0) / (parseFloat(orderedQty) || 1)).toFixed(2) : '0';
    const sellingPrice = data ? (data.defaultSellingPrice ? parseFloat(data.defaultSellingPrice).toFixed(2) : '0') : '0';
    const remarksVal = data ? (data.remarks || '') : '';

    const searchInputAttrs = IS_DELIVERED ? 'readonly disabled' : `onfocus="openItemDropdown(${rowCounter})" oninput="filterItemDropdown(${rowCounter})" onkeydown="handleItemDropdownKey(event, ${rowCounter})"`;
    const dropIconHtml = IS_DELIVERED ? '' : `
            <span class="search-drop-icon" onclick="toggleItemDropdown(${rowCounter})">
                <svg width="10" height="10" viewBox="0 0 16 16" fill="#666"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
            </span>
        `;
    const qtyAttrs = IS_DELIVERED ? 'readonly' : `oninput="recalculateTotals(); updateRowHiddenQty(${rowCounter}); updatePurchasedSummaryTable();"`;
    const remarksAttrs = IS_DELIVERED ? 'readonly' : '';
    const delBtnHtml = IS_DELIVERED ? '-' : `<button type="button" class="btn-row-del" onclick="removePurchaseRow(${rowCounter})">Delete</button>`;

    tr.innerHTML = `
            <td style="text-align:center; font-weight:700;" class="row-seq">${tbody.children.length + 1}</td>
            <td>
                <div class="searchable-item-wrapper">
                    <input type="text"
                           class="tbl-input item-search-input"
                           id="itemSearchInput_${rowCounter}"
                           placeholder="-- Search / Type Item --"
                           value="${escapeHtml(selectedItemText)}"
                           title="${escapeHtml(selectedItemText)}"
                           autocomplete="off"
                           ${searchInputAttrs}>
                    <input type="hidden" name="items[${rowCounter}][spare]" class="item-select" id="itemSelect_${rowCounter}" value="${selectedSpareId}" required>
                    <input type="hidden" name="items[${rowCounter}][itemName]" class="item-name-hidden" id="itemNameHidden_${rowCounter}" value="${itemNameHidden}">
                    ${dropIconHtml}
                </div>
            </td>
            <td>
                <select class="tbl-input brand-select" id="brandSelect_${rowCounter}" disabled>
                    ${brandOptionsHtml}
                </select>
                <input type="hidden" name="items[${rowCounter}][brand]" id="brandHidden_${rowCounter}" class="brand-hidden" value="${selectedBrandId}">
            </td>
            <td>
                <select class="tbl-input model-select" id="modelSelect_${rowCounter}" disabled>
                    ${modelOptionsHtml}
                </select>
                <input type="hidden" name="items[${rowCounter}][model]" id="modelHidden_${rowCounter}" class="model-hidden" value="${selectedModelId}">
            </td>
            <td>
                <input type="number" name="items[${rowCounter}][orderedQty]" class="tbl-input qty-ordered" value="${orderedQty}" placeholder="1" min="1" required ${qtyAttrs}>
                <input type="hidden" name="items[${rowCounter}][receivedQty]" class="qty-received" id="qtyReceived_${rowCounter}" value="${receivedQty}">
            </td>
            <td>
                <input type="text" name="items[${rowCounter}][remarks]" id="remarks_${rowCounter}" class="tbl-input item-remarks" value="${escapeHtml(remarksVal)}" placeholder="Enter remarks..." ${remarksAttrs}>
                <input type="hidden" name="items[${rowCounter}][priceWOGst]" class="price-wo-gst" value="${priceWOGst}">
                <input type="hidden" name="items[${rowCounter}][gstPct]" class="gst-pct" value="${gstPct}">
                <input type="hidden" name="items[${rowCounter}][priceWithGst]" class="price-with-gst" value="${priceWithGst}">
                <input type="hidden" name="items[${rowCounter}][sellingPrice]" class="selling-price" value="${sellingPrice}">
            </td>
            <td style="text-align:center;">
                ${delBtnHtml}
            </td>
        `;

    tbody.appendChild(tr);

    updateRowSequences();
    updatePurchasedSummaryTable();
  }

  function removePurchaseRow(id) {
    const tbody = document.getElementById('itemsTbody');
    const tr = document.getElementById(`row_${id}`);
    if (tr) {
      tr.remove();
      if (tbody.children.length === 0) {
        addPurchaseRow();
      }
      updateRowSequences();
      recalculateTotals();
      updatePurchasedSummaryTable();
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
    const spareName = opt.getAttribute('data-sparename') || opt.text.split(' (')[0] || opt.text;
    tr.querySelector('.item-name-hidden').value = spareName;

    const brandSel = tr.querySelector('.brand-select');
    const modelSel = tr.querySelector('.model-select');

    if (opt.value) {
      const actual = parseFloat(opt.getAttribute('data-actual')) || 0;
      const selling = parseFloat(opt.getAttribute('data-price')) || 0;
      const gst = parseFloat(opt.getAttribute('data-gst')) || 0;
      const brandId = opt.getAttribute('data-brand');
      const modelId = opt.getAttribute('data-model');

      if (brandId) brandSel.value = brandId;
      if (modelId) modelSel.value = modelId;

      tr.querySelector('.price-wo-gst').value = Math.round(actual);
      tr.querySelector('.selling-price').value = Math.round(selling);
      tr.querySelector('.gst-pct').value = Math.round(gst);

      calcRowGst(rowId);
    }

    autoUpdateOrderStatus();
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

  function recalculateTotals() {
    let grandTotalWithGst = 0;
    let grandQuotedSum = 0;

    const rows = document.querySelectorAll('#itemsTbody tr');
    rows.forEach(r => {
      let qty = parseFloat(r.querySelector('.qty-ordered')?.value) || 0;
      let priceWithGst = parseFloat(r.querySelector('.price-with-gst')?.value) || 0;
      let priceWoGst = parseFloat(r.querySelector('.price-wo-gst')?.value) || 0;

      grandTotalWithGst += Math.round(qty * priceWithGst);
      grandQuotedSum += Math.round(qty * priceWoGst);
    });

    const qEl = document.getElementById('quoteAmountSum');
    if (qEl) qEl.value = Math.round(grandQuotedSum);
    const aEl = document.getElementById('actualAmountSum');
    if (aEl) aEl.value = Math.round(grandTotalWithGst);
  }

  function applyPaymentAmount() {
    // No-op
  }

  function validatePOForm() {
    const rows = document.querySelectorAll('#itemsTbody tr');
    if (rows.length === 0) {
      alert('❌ Please add at least one purchase item!');
      return false;
    }

    let validCount = 0;
    let missingOrdered = false;

    rows.forEach(r => {
      const spareId = r.querySelector('.item-select').value;
      const ordRaw = r.querySelector('.qty-ordered').value.trim();
      const ordQty = parseFloat(ordRaw) || 0;

      if (spareId) {
        validCount++;
        if (ordRaw === '' || ordQty <= 0) {
          missingOrdered = true;
        }
      }
    });

    if (validCount === 0) {
      alert('❌ No item selected! You must select at least one purchase item before saving.');
      return false;
    }

    if (missingOrdered) {
      alert('❌ Ordered Quantity is required (must be 1 or more) for all selected items!');
      return false;
    }

    return true;
  }

  // Searchable Item Dropdown Logic
  let activeDropdownRow = null;
  let highlightedIndex = -1;
  let currentFilteredSpares = [];

  function ensureSearchDropdownEl() {
    let el = document.getElementById('itemSearchDropdownMenu');
    if (!el) {
      el = document.createElement('div');
      el.id = 'itemSearchDropdownMenu';
      el.className = 'po-search-dropdown-menu';
      document.body.appendChild(el);
    }
    return el;
  }

  function positionSearchDropdown(rowId) {
    const input = document.getElementById(`itemSearchInput_${rowId}`);
    const menu = ensureSearchDropdownEl();
    if (!input || !menu || menu.style.display !== 'block') return;

    const rect = input.getBoundingClientRect();
    menu.style.left = rect.left + 'px';
    menu.style.width = Math.max(rect.width, 300) + 'px';

    const spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < 220 && rect.top > 220) {
      menu.style.top = Math.max(10, rect.top - Math.min(250, menu.offsetHeight || 220) - 1) + 'px';
    } else {
      menu.style.top = (rect.bottom + 1) + 'px';
    }
  }

  function openItemDropdown(rowId) {
    activeDropdownRow = rowId;
    const hid = document.getElementById(`itemSelect_${rowId}`);
    const currentSpareId = hid ? hid.value : '';
    const input = document.getElementById(`itemSearchInput_${rowId}`);
    const query = input ? input.value.trim() : '';
    renderItemDropdownOptions(rowId, query, currentSpareId);
  }

  function toggleItemDropdown(rowId) {
    const menu = ensureSearchDropdownEl();
    if (activeDropdownRow === rowId && menu.style.display === 'block') {
      closeItemDropdown();
    } else {
      const input = document.getElementById(`itemSearchInput_${rowId}`);
      if (input) input.focus();
      openItemDropdown(rowId);
    }
  }

  function filterItemDropdown(rowId) {
    activeDropdownRow = rowId;
    const input = document.getElementById(`itemSearchInput_${rowId}`);
    const query = input ? input.value.trim() : '';

    if (query === '') {
      const hid = document.getElementById(`itemSelect_${rowId}`);
      if (hid) hid.value = '';
      const hidName = document.getElementById(`itemNameHidden_${rowId}`);
      if (hidName) hidName.value = '';
      const tr = document.getElementById(`row_${rowId}`);
      if (tr) {
        const bSel = tr.querySelector('.brand-select');
        const mSel = tr.querySelector('.model-select');
        const bHid = tr.querySelector('.brand-hidden');
        const mHid = tr.querySelector('.model-hidden');
        if (bSel) bSel.value = '';
        if (mSel) mSel.value = '';
        if (bHid) bHid.value = '';
        if (mHid) mHid.value = '';
      }
      autoUpdateOrderStatus();
    }

    renderItemDropdownOptions(rowId, query);
  }

  function renderItemDropdownOptions(rowId, query = '', preselectSpareId = null) {
    const menu = ensureSearchDropdownEl();
    const q = query.toLowerCase();

    if (!q) {
      currentFilteredSpares = SPARES_DATA;
    } else {
      const words = q.split(/\s+/).filter(w => w.length > 0);
      currentFilteredSpares = SPARES_DATA.filter(sp => {
        const name = (sp.spareName || '').toLowerCase();
        const part = (sp.partNo || '').toLowerCase();
        const text = name + ' ' + part;
        return words.every(w => text.includes(w));
      });
    }

    if (preselectSpareId) {
      const foundIdx = currentFilteredSpares.findIndex(s => s.spare_id == preselectSpareId);
      highlightedIndex = (foundIdx !== -1) ? foundIdx : -1;
    } else if (q !== '') {
      highlightedIndex = currentFilteredSpares.length > 0 ? 0 : -1;
    } else {
      highlightedIndex = -1;
    }

    if (currentFilteredSpares.length === 0) {
      menu.innerHTML = `<div class="drop-no-match">No items found matching "${escapeHtml(query)}"</div>`;
    } else {
      let html = '';
      currentFilteredSpares.forEach((sp, idx) => {
        const pno = (sp.partNo && sp.partNo !== '-' && sp.partNo.trim() !== '') ? ` (${sp.partNo.trim()})` : '';
        const fullText = (sp.spareName || '').replace(/\s+/g, ' ').trim() + pno;
        const isHl = (idx === highlightedIndex) ? ' highlighted' : '';
        html += `
                    <div class="drop-item-row${isHl}" data-index="${idx}"
                         onmouseenter="setHighlightedDropdownIndex(${idx})"
                         onmousedown="selectItemOption(${rowId}, ${sp.spare_id}); event.preventDefault();"
                         title="${escapeHtml(fullText)}">
                        ${escapeHtml(fullText)}
                    </div>
                `;
      });
      menu.innerHTML = html;
    }

    menu.style.display = 'block';
    positionSearchDropdown(rowId);

    if (highlightedIndex >= 0) {
      const items = menu.querySelectorAll('.drop-item-row');
      updateHighlightedDropdownItem(items);
    }
  }

  function setHighlightedDropdownIndex(idx) {
    highlightedIndex = idx;
    const menu = ensureSearchDropdownEl();
    const items = menu.querySelectorAll('.drop-item-row');
    items.forEach((item, i) => {
      if (i === idx) {
        item.classList.add('highlighted');
      } else {
        item.classList.remove('highlighted');
      }
    });
  }

  function selectItemOption(rowId, spareId) {
    const sp = SPARES_DATA.find(s => s.spare_id == spareId);
    if (!sp) return;

    const pno = (sp.partNo && sp.partNo !== '-' && sp.partNo.trim() !== '') ? ` (${sp.partNo.trim()})` : '';
    const fullText = (sp.spareName || '').replace(/\s+/g, ' ').trim() + pno;

    const input = document.getElementById(`itemSearchInput_${rowId}`);
    const hid = document.getElementById(`itemSelect_${rowId}`);
    const hidName = document.getElementById(`itemNameHidden_${rowId}`);
    const tr = document.getElementById(`row_${rowId}`);

    if (input) {
      input.value = fullText;
      input.title = fullText;
    }
    if (hid) hid.value = sp.spare_id;
    if (hidName) hidName.value = sp.spareName || fullText;

    if (tr) {
      const brandSel = tr.querySelector('.brand-select');
      const modelSel = tr.querySelector('.model-select');
      const brandHid = tr.querySelector('.brand-hidden');
      const modelHid = tr.querySelector('.model-hidden');

      const bId = sp.brand_id || '';
      const mId = sp.model_id || '';

      if (brandSel) brandSel.value = bId;
      if (modelSel) modelSel.value = mId;
      if (brandHid) brandHid.value = bId;
      if (modelHid) modelHid.value = mId;

      const actual = parseFloat(sp.actualPrice || 0);
      const selling = parseFloat(sp.sellingPrice || 0);
      const gst = parseFloat(sp.gstPercentage || 0);

      const woInput = tr.querySelector('.price-wo-gst');
      const sellInput = tr.querySelector('.selling-price');
      const gstInput = tr.querySelector('.gst-pct');

      if (woInput) woInput.value = Math.round(actual);
      if (sellInput) sellInput.value = Math.round(selling);
      if (gstInput) gstInput.value = Math.round(gst);

      calcRowGst(rowId);
    }

    closeItemDropdown();
    autoUpdateOrderStatus();
    updatePurchasedSummaryTable();
  }

  function moveToNextFormInput(currentEl) {
    if (!currentEl) return;
    const form = currentEl.closest('form');
    if (!form) return;
    const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'));
    const index = inputs.indexOf(currentEl);
    if (index !== -1 && inputs[index + 1]) {
      const nextInput = inputs[index + 1];
      nextInput.focus();
      if (typeof nextInput.select === 'function' && nextInput.tagName === 'INPUT' && nextInput.type === 'text') {
        nextInput.select();
      }
    }
  }

  function handleItemDropdownKey(e, rowId) {
    const input = document.getElementById(`itemSearchInput_${rowId}`);
    const menu = ensureSearchDropdownEl();
    const isMenuOpen = (menu.style.display === 'block' && activeDropdownRow === rowId);

    // Always stop arrow keys from bubbling so global navigation does not jump fields
    if (['ArrowDown', 'ArrowUp', 'PageDown', 'PageUp'].includes(e.key)) {
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
    }

    if (!isMenuOpen) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        openItemDropdown(rowId);
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        moveToNextFormInput(input);
        return;
      }
      return;
    }

    const items = menu.querySelectorAll('.drop-item-row');
    if (!items || items.length === 0) {
      if (e.key === 'Escape') closeItemDropdown();
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        closeItemDropdown();
        moveToNextFormInput(input);
      }
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (highlightedIndex < 0 || highlightedIndex >= items.length - 1) {
        highlightedIndex = 0;
      } else {
        highlightedIndex++;
      }
      updateHighlightedDropdownItem(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (highlightedIndex <= 0) {
        highlightedIndex = items.length - 1;
      } else {
        highlightedIndex--;
      }
      updateHighlightedDropdownItem(items);
    } else if (e.key === 'PageDown') {
      e.preventDefault();
      highlightedIndex = Math.min(items.length - 1, (highlightedIndex < 0 ? 0 : highlightedIndex) + 10);
      updateHighlightedDropdownItem(items);
    } else if (e.key === 'PageUp') {
      e.preventDefault();
      highlightedIndex = Math.max(0, (highlightedIndex < 0 ? 0 : highlightedIndex) - 10);
      updateHighlightedDropdownItem(items);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
      if (highlightedIndex >= 0 && highlightedIndex < currentFilteredSpares.length) {
        const sp = currentFilteredSpares[highlightedIndex];
        selectItemOption(rowId, sp.spare_id);
      } else if (currentFilteredSpares.length > 0) {
        selectItemOption(rowId, currentFilteredSpares[0].spare_id);
      } else {
        closeItemDropdown();
      }
      moveToNextFormInput(input);
    } else if (e.key === 'Tab') {
      if (highlightedIndex >= 0 && highlightedIndex < currentFilteredSpares.length) {
        const sp = currentFilteredSpares[highlightedIndex];
        selectItemOption(rowId, sp.spare_id);
      }
      closeItemDropdown();
    } else if (e.key === 'Escape') {
      closeItemDropdown();
    }
  }

  function updateHighlightedDropdownItem(items) {
    items.forEach((item, idx) => {
      if (idx === highlightedIndex) {
        item.classList.add('highlighted');
        item.scrollIntoView({ block: 'nearest' });
      } else {
        item.classList.remove('highlighted');
      }
    });
  }

  function closeItemDropdown() {
    const menu = document.getElementById('itemSearchDropdownMenu');
    if (menu) menu.style.display = 'none';
    activeDropdownRow = null;
    highlightedIndex = -1;
  }

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.searchable-item-wrapper') && !e.target.closest('#itemSearchDropdownMenu')) {
      closeItemDropdown();
    }
  });

  window.addEventListener('resize', function () {
    if (activeDropdownRow) positionSearchDropdown(activeDropdownRow);
  });

  document.addEventListener('scroll', function () {
    if (activeDropdownRow) positionSearchDropdown(activeDropdownRow);
  }, true);

  function initPoItemHoverTooltips() {
    let tipEl = document.getElementById('poItemHoverTooltip');
    if (!tipEl) {
      tipEl = document.createElement('div');
      tipEl.id = 'poItemHoverTooltip';
      tipEl.className = 'po-item-hover-tip';
      document.body.appendChild(tipEl);
    }

    document.addEventListener('mouseover', function (e) {
      const target = e.target.closest('.item-search-input, .brand-select, .model-select');
      if (!target) return;

      let fullText = '';
      let typeLabel = 'Item';

      if (target.classList.contains('item-search-input')) {
        fullText = target.value.trim();
        typeLabel = 'Item';
      } else {
        const opt = target.options[target.selectedIndex];
        if (!opt || !target.value) {
          tipEl.style.display = 'none';
          return;
        }
        fullText = opt.getAttribute('data-fullname') || opt.text || '';
        fullText = fullText.replace(/^--\s*.*?\s*--$/, '').trim();
        if (target.classList.contains('brand-select')) typeLabel = 'Brand';
        else if (target.classList.contains('model-select')) typeLabel = 'Model';
      }

      if (!fullText) {
        tipEl.style.display = 'none';
        return;
      }

      tipEl.innerHTML = `<span class="tip-type">${typeLabel}:</span> <span class="tip-name">${escapeHtml(fullText)}</span>`;
      tipEl.style.display = 'block';

      const rect = target.getBoundingClientRect();
      const tipHeight = tipEl.offsetHeight || 28;
      let top = rect.top - tipHeight - 6;
      let arrowClass = 'arrow-bottom';

      if (top < 10) {
        top = rect.bottom + 6;
        arrowClass = 'arrow-top';
      }

      let left = rect.left + 4;
      if (left + tipEl.offsetWidth > window.innerWidth - 10) {
        left = window.innerWidth - tipEl.offsetWidth - 10;
      }

      tipEl.className = `po-item-hover-tip ${arrowClass}`;
      tipEl.style.top = `${top}px`;
      tipEl.style.left = `${Math.max(10, left)}px`;
    });

    document.addEventListener('mouseout', function (e) {
      const target = e.target.closest('.item-search-input, .brand-select, .model-select');
      if (target) {
        tipEl.style.display = 'none';
      }
    });

    window.addEventListener('scroll', function () {
      if (tipEl) tipEl.style.display = 'none';
    }, true);
  }

  function updateRowHiddenQty(rowId) {
    const tr = document.getElementById(`row_${rowId}`);
    if (!tr) return;
    const ordVal = tr.querySelector('.qty-ordered')?.value || 1;
    const recInput = tr.querySelector('.qty-received');
    if (recInput) recInput.value = ordVal;
  }

  function openItemReviewModal(rowId) {
    const tr = document.getElementById(`row_${rowId}`);
    if (!tr) return;

    const spareId = tr.querySelector('.item-select')?.value;
    const itemName = tr.querySelector('.item-search-input')?.value || tr.querySelector('.item-name-hidden')?.value || 'Not selected';
    const brandSel = tr.querySelector('.brand-select');
    const brandName = brandSel && brandSel.selectedIndex >= 0 && brandSel.value ? brandSel.options[brandSel.selectedIndex].text : '-';
    const modelSel = tr.querySelector('.model-select');
    const modelName = modelSel && modelSel.selectedIndex >= 0 && modelSel.value ? modelSel.options[modelSel.selectedIndex].text : '-';
    const qty = tr.querySelector('.qty-ordered')?.value || '1';
    const remarks = tr.querySelector('.item-remarks')?.value.trim() || 'No remarks entered';

    if (!spareId) {
      alert('Please select an item first to review its details.');
      return;
    }

    const spare = SPARES_DATA.find(s => s.spare_id == spareId) || {};
    const partNo = spare.partNo || '-';
    const rackNo = spare.rackNumber || '-';
    const availStock = spare.availableQty !== undefined ? spare.availableQty : '-';
    const minOrdered = (spare.minQty !== undefined && parseInt(spare.minQty) > 0) ? spare.minQty : (qty || '1');
    const picture = spare.picture ? `../${spare.picture}` : '';

    let photoHtml = '';
    if (picture) {
      photoHtml = `
                <div style="text-align:center; margin-bottom:16px;">
                    <img src="${escapeHtml(picture)}" alt="${escapeHtml(itemName)}" style="max-height:140px; max-width:100%; border-radius:8px; border:1px solid #e2e8f0; object-fit:contain; box-shadow:0 2px 8px rgba(0,0,0,0.06);" onerror="this.style.display='none'">
                </div>
            `;
    }

    const modalBody = document.getElementById('itemReviewModalBody');
    modalBody.innerHTML = `
            ${photoHtml}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:10px;">
                <div style="grid-column: span 2; background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #e2e8f0;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:0.5px;">Item Name</div>
                    <div style="font-size:15px; font-weight:700; color:#0f172a; margin-top:2px;">${escapeHtml(itemName)}</div>
                </div>
                <div style="background:#f8fafc; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#64748b;">Brand</div>
                    <div style="font-size:13.5px; font-weight:600; color:#1e293b; margin-top:2px;">${escapeHtml(brandName)}</div>
                </div>
                <div style="background:#f8fafc; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#64748b;">Model</div>
                    <div style="font-size:13.5px; font-weight:600; color:#1e293b; margin-top:2px;">${escapeHtml(modelName)}</div>
                </div>
                <div style="background:#eef2ff; padding:10px 14px; border-radius:8px; border:1px solid #c7d2fe;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#4338ca;">Minimum Ordered</div>
                    <div style="font-size:18px; font-weight:700; color:#312e81; margin-top:2px;">${escapeHtml(minOrdered.toString())}</div>
                </div>
                <div style="background:#f0fdf4; padding:10px 14px; border-radius:8px; border:1px solid #bbf7d0;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#15803d;">Current Stock</div>
                    <div style="font-size:18px; font-weight:700; color:#14532d; margin-top:2px;">${escapeHtml(availStock.toString())}</div>
                </div>
                <div style="grid-column: span 2; background:#fffbeb; padding:10px 14px; border-radius:8px; border:1px solid #fde68a;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#b45309; letter-spacing:0.5px;">Remarks</div>
                    <div style="font-size:13.5px; font-weight:500; color:#78350f; margin-top:3px; word-break:break-word;">${escapeHtml(remarks)}</div>
                </div>
            </div>
        `;

    const modal = document.getElementById('itemReviewModal');
    if (modal) modal.style.display = 'flex';
  }

  function closeItemReviewModal() {
    const modal = document.getElementById('itemReviewModal');
    if (modal) modal.style.display = 'none';
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function updateSummarySupplierName() {
    const suppSelect = document.getElementById('supplierSelect');
    const nameEl = document.getElementById('summarySupplierName');
    if (!nameEl) return;
    if (suppSelect && suppSelect.selectedIndex > 0) {
      nameEl.innerText = suppSelect.options[suppSelect.selectedIndex].text;
    } else {
      nameEl.innerText = '-';
    }
  }

  function updatePurchasedSummaryTable() {
    const tbody = document.getElementById('summaryItemsTbody');
    const totalCountEl = document.getElementById('summaryTotalItemsCount');
    if (!tbody) return;

    updateSummarySupplierName();

    const rows = document.querySelectorAll('#itemsTbody tr');
    tbody.innerHTML = '';

    const orderDateInput = document.getElementById('orderDate');
    const orderDateVal = (orderDateInput && orderDateInput.value) ? orderDateInput.value : '-';
    const statusSelect = document.getElementById('orderStatus');
    const currentStatus = statusSelect ? statusSelect.value : 'Purchased';
    const isDelivered = (currentStatus === 'Delivered' || currentStatus === 'Received' || currentStatus === 'Completed');

    let validItemCount = 0;
    rows.forEach((r, idx) => {
      const rowId = r.id.replace('row_', '');
      const spareId = r.querySelector('.item-select')?.value;
      const searchInput = r.querySelector('.item-search-input');
      const hiddenName = r.querySelector('.item-name-hidden');
      let itemName = searchInput?.value.trim() || hiddenName?.value.trim() || '';

      if (!itemName && spareId) {
        const sp = SPARES_DATA.find(s => s.spare_id == spareId);
        if (sp) itemName = sp.spareName;
      }

      if (spareId || (itemName && itemName !== '')) {
        validItemCount++;
      }

      const qty = r.querySelector('.qty-ordered')?.value || '1';

      let deliveredBadge = '';
      if (isDelivered) {
        deliveredBadge = `<span class="badge-delivered">${escapeHtml(orderDateVal)}</span>`;
      } else {
        deliveredBadge = `<span class="badge-pending">Pending</span>`;
      }

      const actionBtnHtml = IS_DELIVERED
        ? `<span style="color:#94a3b8; font-size:12px;">-</span>`
        : `<button type="button" class="btn-summary-edit" title="Edit this item in the table above" onclick="editSummaryItem(${rowId})">Edit</button>`;

      const tr = document.createElement('tr');
      tr.innerHTML = `
                <td style="text-align:center; font-weight:700;">${idx + 1}</td>
                <td style="font-weight:600; color:#1e293b;">${escapeHtml(itemName || '-- Item not selected --')}</td>
                <td style="text-align:center; font-weight:700; color:#0f172a;">${escapeHtml(qty.toString())}</td>
                <td style="color:#475569;">${escapeHtml(PURCHASED_BY)}</td>
                <td style="text-align:center; color:#334155;">${escapeHtml(orderDateVal)}</td>
                <td style="text-align:center;">${deliveredBadge}</td>
                <td style="text-align:center;">${actionBtnHtml}</td>
            `;
      tbody.appendChild(tr);
    });

    if (totalCountEl) {
      totalCountEl.innerText = validItemCount;
    }

    if (validItemCount === 0 && rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:18px;">No items added yet</td></tr>`;
    }
  }

  function editSummaryItem(rowId) {
    if (IS_DELIVERED) return;
    const tr = document.getElementById(`row_${rowId}`);
    if (!tr) return;

    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    tr.classList.remove('row-highlight-pulse');
    void tr.offsetWidth;
    tr.classList.add('row-highlight-pulse');

    const input = tr.querySelector('.item-search-input');
    if (input) {
      setTimeout(() => input.focus(), 350);
    }
  }

  function confirmPurchaseDelivery() {
    if (IS_DELIVERED) return;
    const rows = document.querySelectorAll('#itemsTbody tr');
    let validItems = [];

    rows.forEach((r, idx) => {
      const spareId = r.querySelector('.item-select')?.value;
      const searchInput = r.querySelector('.item-search-input');
      const hiddenName = r.querySelector('.item-name-hidden');
      let itemName = searchInput?.value.trim() || hiddenName?.value.trim() || '';

      if (!itemName && spareId) {
        const sp = SPARES_DATA.find(s => s.spare_id == spareId);
        if (sp) itemName = sp.spareName;
      }

      if ((spareId && parseInt(spareId) > 0) || (itemName && itemName !== '')) {
        const brandSel = r.querySelector('.brand-select');
        const brandName = brandSel && brandSel.selectedIndex >= 0 && brandSel.value ? brandSel.options[brandSel.selectedIndex].text : '-';
        const modelSel = r.querySelector('.model-select');
        const modelName = modelSel && modelSel.selectedIndex >= 0 && modelSel.value ? modelSel.options[modelSel.selectedIndex].text : '-';
        const qty = r.querySelector('.qty-ordered')?.value || '1';
        const remarks = r.querySelector('.item-remarks')?.value.trim() || '-';

        validItems.push({
          name: itemName || 'Item',
          qty: qty
        });
      }
    });

    if (validItems.length === 0) {
      alert('Please add at least one valid purchase item before confirming.');
      return;
    }

    const suppSelect = document.getElementById('supplierSelect');
    if (!suppSelect || !suppSelect.value) {
      alert('Please select a supplier before confirming purchase.');
      return;
    }

    // Gather Order info for Popup
    let suppName = (suppSelect.selectedIndex > 0) ? suppSelect.options[suppSelect.selectedIndex].text : '';
    let suppPhone = document.getElementById('supplierPhone')?.value || '';
    if ((!suppName || suppName === '-' || suppName === '-- Select Supplier --') && suppSelect.value) {
      const sFound = SUPPLIERS_DATA.find(s => s.id == suppSelect.value);
      if (sFound) {
        suppName = sFound.name || '-';
        if (!suppPhone || suppPhone === '-') suppPhone = sFound.phoneNo1 || '-';
      }
    }
    if (!suppPhone) suppPhone = '-';
    if (!suppName) suppName = '-';

    // Update popup table rows with ONLY the first 3 columns
    const tbody = document.getElementById('previewDetailsTbody');
    if (tbody) {
      tbody.innerHTML = '';
      validItems.forEach((itm, idx) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #cbd5e1';
        tr.innerHTML = `
                    <td style="padding:10px 14px; text-align:center; font-weight:700; color:#475569; border-right:1px solid #cbd5e1; width:55px;">${idx + 1}</td>
                    <td style="padding:10px 14px; font-weight:600; color:#0f172a; border-right:1px solid #cbd5e1;">${escapeHtml(itm.name)}</td>
                    <td style="padding:10px 14px; text-align:center; font-weight:700; color:#1e293b; width:80px;"><span style="background:#e0f2fe; color:#0369a1; padding:3px 12px; border-radius:12px; font-size:13.5px; font-weight:700;">${escapeHtml(itm.qty.toString())}</span></td>
                `;
        tbody.appendChild(tr);
      });
    }

    // Set supplier name and number on the bottom left
    const suppNameEl = document.getElementById('popupSupplierName');
    if (suppNameEl) suppNameEl.innerText = suppName;
    const suppPhoneEl = document.getElementById('popupSupplierPhone');
    if (suppPhoneEl) suppPhoneEl.innerText = suppPhone;

    const modal = document.getElementById('poPdfPreviewModal');
    if (modal) modal.style.display = 'flex';
  }

  function closePoPdfPreviewModal() {
    const modal = document.getElementById('poPdfPreviewModal');
    if (modal) modal.style.display = 'none';
  }

  function handlePoConfirmOk() {
    // Set redirect to print flag only when Confirm Purchase flow is executed
    const redirectInput = document.getElementById('redirectToPrint');
    if (redirectInput) {
      redirectInput.value = '1';
    }

    // Set orderStatus to Delivered
    const statusSelect = document.getElementById('orderStatus');
    if (statusSelect) {
      statusSelect.value = 'Delivered';
    }

    // Fill received quantities
    const rows = document.querySelectorAll('#itemsTbody tr');
    rows.forEach(r => {
      const ord = r.querySelector('.qty-ordered')?.value || '1';
      const rec = r.querySelector('.qty-received');
      if (rec) rec.value = ord;
    });

    updatePurchasedSummaryTable();

    closePoPdfPreviewModal();

    const form = document.getElementById('poEditForm') || document.getElementById('poForm');
    if (form) {
      form.submit();
    }
  }

  function resetPurchaseForm() {
    setTimeout(() => {
      const tbody = document.getElementById('itemsTbody');
      if (tbody) tbody.innerHTML = '';
      rowCounter = 0;
      if (typeof EXISTING_ITEMS !== 'undefined' && EXISTING_ITEMS && EXISTING_ITEMS.length > 0) {
        EXISTING_ITEMS.forEach(item => addPurchaseRow(item));
      } else {
        addPurchaseRow();
      }
      updateSummarySupplierName();
      recalculateTotals();
      updatePurchasedSummaryTable();
    }, 50);
  }

  // Wanted / Reorder Level Items Modal Logic
  let selectedReorderIndices = new Set();

  function openReorderModal() {
    selectedReorderIndices.clear();
    if (typeof REORDER_ITEMS_DATA !== 'undefined' && Array.isArray(REORDER_ITEMS_DATA)) {
      REORDER_ITEMS_DATA.forEach((_, idx) => selectedReorderIndices.add(idx));
    }
    const searchInput = document.getElementById('reorderSearchInput');
    if (searchInput) searchInput.value = '';

    renderReorderModalRows();

    const modal = document.getElementById('reorderStockModal');
    if (modal) modal.style.display = 'flex';
  }

  function closeReorderModal() {
    const modal = document.getElementById('reorderStockModal');
    if (modal) modal.style.display = 'none';
  }

  function toggleReorderItemSelection(index) {
    if (selectedReorderIndices.has(index)) {
      selectedReorderIndices.delete(index);
    } else {
      selectedReorderIndices.add(index);
    }
    updateReorderRowDisplay(index);
    updateReorderSelectedCount();
  }

  function toggleAllReorderItems(select) {
    selectedReorderIndices.clear();
    if (select && typeof REORDER_ITEMS_DATA !== 'undefined' && Array.isArray(REORDER_ITEMS_DATA)) {
      REORDER_ITEMS_DATA.forEach((_, idx) => selectedReorderIndices.add(idx));
    }
    const rows = document.querySelectorAll('#reorderModalTbody tr');
    rows.forEach(tr => {
      const idx = parseInt(tr.getAttribute('data-index'));
      if (!isNaN(idx)) {
        updateReorderRowDisplay(idx);
      }
    });
    updateReorderSelectedCount();
  }

  function updateReorderRowDisplay(index) {
    const tr = document.getElementById(`reorderRow_${index}`);
    if (!tr) return;
    const isSel = selectedReorderIndices.has(index);
    const btn = tr.querySelector('.reorder-select-btn');
    const tickBtn = tr.querySelector('.reorder-tick-btn');
    if (btn) {
      if (isSel) {
        btn.classList.add('is-selected');
        btn.title = 'Deselect item';
      } else {
        btn.classList.remove('is-selected');
        btn.title = 'Select item';
      }
    }
    if (tickBtn) {
      if (isSel) {
        tickBtn.classList.add('is-checked');
      } else {
        tickBtn.classList.remove('is-checked');
      }
    }
  }

  function updateReorderSelectedCount() {
    const countEl = document.getElementById('reorderSelectedCount');
    if (countEl) countEl.innerText = selectedReorderIndices.size;
  }

  function filterReorderModalTable() {
    renderReorderModalRows();
  }

  function renderReorderModalRows() {
    const tbody = document.getElementById('reorderModalTbody');
    const emptyMsg = document.getElementById('reorderEmptyMessage');
    const totalBadge = document.getElementById('reorderTotalBadge');
    if (!tbody) return;

    const searchInput = document.getElementById('reorderSearchInput');
    const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const words = q ? q.split(/\s+/) : [];

    tbody.innerHTML = '';

    if (!REORDER_ITEMS_DATA || REORDER_ITEMS_DATA.length === 0) {
      if (totalBadge) totalBadge.innerText = '0 Items';
      if (emptyMsg) {
        emptyMsg.style.display = 'block';
        const p = emptyMsg.querySelector('p');
        if (p) p.innerText = 'No low stock or reorder items found';
      }
      updateReorderSelectedCount();
      return;
    }

    if (totalBadge) totalBadge.innerText = `${REORDER_ITEMS_DATA.length} Items`;

    let visibleCount = 0;
    const fragment = document.createDocumentFragment();

    REORDER_ITEMS_DATA.forEach((item, idx) => {
      const name = (item.spareName || item.itemName || '').trim();
      const barcode = (item.barCode && item.barCode !== '-' && item.barCode.trim() !== '') 
                      ? item.barCode.trim() 
                      : ((item.partNo && item.partNo !== '-' && item.partNo.trim() !== '') ? item.partNo.trim() : '-');
      const searchStr = `${name} ${barcode}`.toLowerCase();

      if (words.length > 0 && !words.every(w => searchStr.includes(w))) {
        return;
      }

      visibleCount++;
      const isSelected = selectedReorderIndices.has(idx);

      const tr = document.createElement('tr');
      tr.id = `reorderRow_${idx}`;
      tr.setAttribute('data-index', idx);
      tr.style.borderBottom = '1px solid #e2e8f0';
      tr.style.backgroundColor = '#ffffff';

      const avail = parseFloat(item.availableQty) || 0;
      const minQ = parseFloat(item.minQty) || 0;

      tr.innerHTML = `
        <td style="padding:10px 16px; border-right:1px solid #e2e8f0;">
          <strong style="color:#0f172a; font-size:13.5px;">${escapeHtml(name)}</strong>
        </td>
        <td style="padding:10px 12px; text-align:center; border-right:1px solid #e2e8f0;">
          <span style="font-size:13px; font-weight:600; color:#475569;">${escapeHtml(barcode)}</span>
        </td>
        <td style="padding:10px 12px; text-align:center; border-right:1px solid #e2e8f0;">
          <span style="font-size:14px; font-weight:700; color:${avail <= 0 ? '#dc2626' : (minQ > 0 && avail <= minQ ? '#d97706' : '#0f172a')};">
            ${avail}
          </span>
        </td>
        <td style="padding:10px 16px; text-align:center;">
          <button type="button"
                  class="reorder-select-btn ${isSelected ? 'is-selected' : ''}"
                  onclick="toggleReorderItemSelection(${idx})"
                  title="${isSelected ? 'Deselect item' : 'Select item'}"
                  aria-label="Select item">
            <span class="reorder-tick-btn ${isSelected ? 'is-checked' : ''}">
              <svg viewBox="0 0 24 24" class="tick-svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </span>
            <span class="reorder-select-text">Select</span>
          </button>
        </td>
      `;

      fragment.appendChild(tr);
    });

    tbody.appendChild(fragment);

    if (emptyMsg) {
      emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    updateReorderSelectedCount();
  }

  function addSelectedReorderItems() {
    if (selectedReorderIndices.size === 0) {
      alert('Please select at least one item using the tick box to add.');
      return;
    }

    const selectedItems = Array.from(selectedReorderIndices).map(i => REORDER_ITEMS_DATA[i]).filter(Boolean);
    if (selectedItems.length === 0) {
      closeReorderModal();
      return;
    }

    const rows = document.querySelectorAll('#itemsTbody tr');
    let replaceFirstRow = false;
    let firstRowId = null;
    if (rows.length === 1) {
      const first = rows[0];
      const itemSel = first.querySelector('.item-select');
      const searchInp = first.querySelector('.item-search-input');
      if ((!itemSel || !itemSel.value) && (!searchInp || !searchInp.value.trim())) {
        replaceFirstRow = true;
        firstRowId = first.id.replace('row_', '');
      }
    }

    selectedItems.forEach((item, idx) => {
      let targetRowId;
      if (idx === 0 && replaceFirstRow && firstRowId) {
        targetRowId = firstRowId;
      } else {
        addPurchaseRow();
        targetRowId = rowCounter;
      }

      const tr = document.getElementById(`row_${targetRowId}`);
      if (!tr) return;

      let orderQty = 1;
      let avail = parseFloat(item.availableQty) || 0;
      let reorder = parseFloat(item.reorderLevel) || 0;
      let minQ = parseFloat(item.minQty) || 0;
      let maxQ = parseFloat(item.maxQty) || 0;

      if (maxQ > avail && maxQ > 0) {
        orderQty = Math.max(1, Math.round(maxQ - avail));
      } else if (reorder > avail && reorder > 0) {
        orderQty = Math.max(1, Math.round(reorder - avail));
      } else if (minQ > avail && minQ > 0) {
        orderQty = Math.max(1, Math.round(minQ - avail));
      }

      const qtyInput = tr.querySelector('.qty-ordered');
      const qtyRecInput = tr.querySelector('.qty-received');
      if (qtyInput) qtyInput.value = orderQty;
      if (qtyRecInput) qtyRecInput.value = orderQty;

      const spareId = parseInt(item.spare_id) || 0;
      const spareMatch = SPARES_DATA.find(s => s.spare_id == spareId);

      if (spareMatch && spareId > 0) {
        selectItemOption(targetRowId, spareId);
        if (qtyInput) qtyInput.value = orderQty;
        if (qtyRecInput) qtyRecInput.value = orderQty;
      } else {
        const input = tr.querySelector('.item-search-input');
        const hid = tr.querySelector('.item-select');
        const hidName = tr.querySelector('.item-name-hidden');
        const brandSel = tr.querySelector('.brand-select');
        const modelSel = tr.querySelector('.model-select');
        const brandHid = tr.querySelector('.brand-hidden');
        const modelHid = tr.querySelector('.model-hidden');

        const itemName = item.spareName || item.itemName || 'Item';
        const pno = (item.partNo && item.partNo !== '-') ? ` (${item.partNo})` : '';
        const fullText = itemName + pno;

        if (input) input.value = fullText;
        if (hid) hid.value = spareId || item.stock_id || '';
        if (hidName) hidName.value = itemName;
        if (brandSel && item.brand_id) brandSel.value = item.brand_id;
        if (modelSel && item.model_id) modelSel.value = item.model_id;
        if (brandHid && item.brand_id) brandHid.value = item.brand_id;
        if (modelHid && item.model_id) modelHid.value = item.model_id;

        const actual = parseFloat(item.actualPrice || 0);
        const selling = parseFloat(item.sellingPrice || 0);
        const gst = parseFloat(item.gstPercentage || 0);

        const woInput = tr.querySelector('.price-wo-gst');
        const sellInput = tr.querySelector('.selling-price');
        const gstInput = tr.querySelector('.gst-pct');

        if (woInput) woInput.value = Math.round(actual);
        if (sellInput) sellInput.value = Math.round(selling);
        if (gstInput) gstInput.value = Math.round(gst);

        calcRowGst(targetRowId);
      }
    });

    updateRowSequences();
    recalculateTotals();
    updatePurchasedSummaryTable();
    closeReorderModal();

    const tbl = document.getElementById('itemsTable');
    if (tbl) tbl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
</script>

<div id="itemReviewModal" class="modal-overlay"
  style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center;">
  <div class="modal-box"
    style="background:#fff; border-radius:12px; width:92%; max-width:520px; box-shadow:0 20px 40px rgba(0,0,0,0.25); overflow:hidden;">
    <div
      style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
        <i class="fa fa-clipboard-list" style="color:#4f46e5;"></i> Purchasing Item Details
      </h3>
      <button type="button" onclick="closeItemReviewModal()"
        style="background:none; border:none; font-size:18px; cursor:pointer; color:#64748b; line-height:1; padding:4px 8px; border-radius:4px;">✕</button>
    </div>
    <div style="padding:18px 20px; max-height:calc(85vh - 120px); overflow-y:auto;" id="itemReviewModalBody">
      <!-- Populated dynamically -->
    </div>
    <div
      style="padding:12px 20px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end;">
      <button type="button" class="btn-res" onclick="closeItemReviewModal()"
        style="padding:7px 18px; font-size:13.5px;">Close</button>
    </div>
  </div>
</div>

<!-- Purchase Order Details Modal -->
<div id="poPdfPreviewModal" class="modal-overlay"
  style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
  <div class="modal-box"
    style="background:#fff; border-radius:12px; width:95%; max-width:580px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); overflow:hidden;">

    <!-- Modal Header -->
    <div
      style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
      <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#2563eb;"></span>
        Purchase Order Details
      </h3>
      <button type="button" onclick="closePoPdfPreviewModal()"
        style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b; line-height:1; padding:4px 8px; border-radius:4px;"
        title="Close">✕</button>
    </div>

    <!-- Modal Body -->
    <div style="padding:20px; overflow-y:auto; flex:1; background:#ffffff;">
      <div style="border:1px solid #cbd5e1; border-radius:8px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left;">
          <thead>
            <tr style="background:#f1f5f9; border-bottom:1px solid #cbd5e1;">
              <th
                style="padding:10px 14px; font-weight:700; color:#334155; text-align:center; width:55px; border-right:1px solid #cbd5e1;">
                S.No</th>
              <th style="padding:10px 14px; font-weight:700; color:#334155; border-right:1px solid #cbd5e1;">Item Name
              </th>
              <th style="padding:10px 14px; font-weight:700; color:#334155; text-align:center; width:80px;">Qty</th>
            </tr>
          </thead>
          <tbody id="previewDetailsTbody">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>

      <!-- Below the table: Supplier name and number on left bottom corner, OK button on right corner -->
      <div
        style="display:flex; justify-content:space-between; align-items:center; margin-top:18px; padding-top:14px; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:14px;">
        <div style="font-size:13.5px; color:#1e293b; line-height:1.6;">
          <div><strong
              style="color:#64748b; font-size:12.5px; text-transform:uppercase; letter-spacing:0.3px;">Supplier:</strong>
            <span id="popupSupplierName"
              style="color:#0f172a; font-weight:700; font-size:14.5px; margin-left:4px;">-</span></div>
          <div><strong
              style="color:#64748b; font-size:12.5px; text-transform:uppercase; letter-spacing:0.3px;">Number:</strong>
            <span id="popupSupplierPhone"
              style="color:#2563eb; font-weight:700; font-size:14px; margin-left:4px;">-</span></div>
        </div>
        <div>
          <button type="button" onclick="handlePoConfirmOk()"
            style="min-width:110px; padding:9px 30px; background:#2563eb; color:#ffffff; font-size:14.5px; font-weight:700; border:none; border-radius:6px; cursor:pointer; box-shadow:0 4px 10px rgba(37,99,235,0.3); transition:all 0.15s ease;"
            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
            OK
          </button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Wanted / Reorder Level Items Modal -->
<div id="reorderStockModal" class="modal-overlay"
  style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
  <div class="modal-box"
    style="background:#fff; border-radius:12px; width:95%; max-width:1060px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); overflow:hidden;">

    <!-- Modal Header -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc; flex-wrap:wrap; gap:12px;">
      <div style="display:flex; align-items:flex-start; gap:10px; flex:1 1 200px;">
        <span style="font-size:20px; margin-top:2px;">⚠️</span>
        <div>
          <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b; line-height:1.3;">Wanted / Reorder Level Items</h3>
          <p style="margin:4px 0 0; font-size:12px; color:#64748b; line-height:1.4;">List of items at reorder level, critical, or out of stock</p>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:12px; flex:0 0 auto; margin-left:auto;">
        <span id="reorderTotalBadge" style="background:#f1f5f9; color:#334155; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; border:1px solid #cbd5e1; white-space:nowrap;">0 Items</span>
        <button type="button" onclick="closeReorderModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b; line-height:1; padding:4px 8px; border-radius:4px;" title="Close">&times;</button>
      </div>
    </div>

    <!-- Filter & Action Bar -->
    <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#ffffff; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;">
      <div style="flex:1 1 200px; position:relative;">
        <input type="text" id="reorderSearchInput" placeholder="🔍 Search item name, part no, model..." oninput="filterReorderModalTable()" style="width:100%; padding:8px 14px; font-size:13px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
      </div>
      <div style="display:flex; gap:10px; flex:0 0 auto;">
        <button type="button" onclick="toggleAllReorderItems(true)" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#0f172a; font-size:12.5px; font-weight:600; padding:7px 16px; border-radius:6px; cursor:pointer; transition:all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Select All</button>
        <button type="button" onclick="toggleAllReorderItems(false)" style="background:#ffffff; border:1px solid #cbd5e1; color:#64748b; font-size:12.5px; font-weight:600; padding:7px 16px; border-radius:6px; cursor:pointer; transition:all 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">Deselect All</button>
      </div>
    </div>

    <!-- Table Container -->
    <div style="padding:18px 24px; overflow-y:auto; flex:1; background:#ffffff;">
      <div style="border:1px solid #cbd5e1; border-radius:8px; overflow-x:auto;">
        <table style="width:100%; min-width:550px; border-collapse:collapse; font-size:13px; text-align:left; table-layout:fixed;" id="reorderModalTable">
          <colgroup>
            <col style="width: 44%;"> <!-- Item Name -->
            <col style="width: 20%;"> <!-- Barcode -->
            <col style="width: 16%;"> <!-- Current Stock -->
            <col style="width: 20%;"> <!-- Action -->
          </colgroup>
          <thead>
            <tr style="background:#f1f5f9; border-bottom:1px solid #cbd5e1;">
              <th style="padding:11px 18px; font-weight:700; color:#334155; border-right:1px solid #cbd5e1;">Item Name</th>
              <th style="padding:11px 12px; font-weight:700; color:#334155; text-align:center; border-right:1px solid #cbd5e1;">Barcode</th>
              <th style="padding:11px 12px; font-weight:700; color:#334155; text-align:center; border-right:1px solid #cbd5e1;">Current Stock</th>
              <th style="padding:11px 16px; font-weight:700; color:#334155; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody id="reorderModalTbody">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>
      <div id="reorderEmptyMessage" style="display:none; text-align:center; padding:32px 16px; color:#64748b;">
        <p style="font-size:15px; font-weight:600; margin:0 0 6px;">No matching reorder items</p>
        <p style="font-size:12.5px; margin:0;">All stock levels are optimal or no items match your search.</p>
      </div>
    </div>

    <!-- Centered Footer Buttons: Add and Cancel -->
    <div
      style="display:flex; justify-content:center; align-items:center; gap:16px; padding:14px 20px; border-top:1px solid #e2e8f0; background:#f8fafc;">
      <button type="button" class="btn-modal-reorder-add" onclick="addSelectedReorderItems()"
        style="min-width:130px; padding:9px 28px; background:#16a34a; color:#ffffff; font-size:14px; font-weight:700; border:none; border-radius:6px; cursor:pointer; box-shadow:0 3px 8px rgba(22,163,74,0.3); transition:all 0.15s ease;"
        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
        Add (<span id="reorderSelectedCount">0</span>)
      </button>
      <button type="button" class="btn-modal-reorder-cancel" onclick="closeReorderModal()"
        style="min-width:110px; padding:9px 28px; background:#64748b; color:#ffffff; font-size:14px; font-weight:600; border:none; border-radius:6px; cursor:pointer; transition:all 0.15s ease;"
        onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#64748b'">
        Cancel
      </button>
    </div>

  </div>
</div>

<!-- ============================================================
     ADD SUPPLIER MODAL  (iframe-based, no AJAX)
============================================================ -->

<!-- Hidden iframe receives the form POST result -->
<iframe name="supplierSaveFrame" id="supplierSaveFrame" style="display:none;" onload="onSupplierFrameLoad()"></iframe>

<div id="addSupplierModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,0.55); backdrop-filter:blur(3px); justify-content:center; align-items:center;">
  <div style="background:#fff; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:100%; max-width:620px; margin:20px; animation:supplierModalIn 0.25s ease;">

    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; padding:20px 28px 16px; border-bottom:2px solid #f1f5f9;">
      <h3 style="margin:0; color:#1e293b; font-size:18px; font-weight:700; display:flex; align-items:center; gap:8px;">
        <span style="background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border-radius:8px; width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; font-size:16px;">+</span>
        Add New Supplier
      </h3>
      <button type="button" onclick="closeAddSupplierModal()" style="background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding:24px 28px; overflow-y:auto; max-height:70vh;">
      <!-- form posts to the hidden iframe — no AJAX/fetch needed -->
      <form id="addSupplierForm" method="POST" action="../supplier/save_and_return.php" target="supplierSaveFrame" onsubmit="onSupplierFormSubmit()">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div class="form-group" style="grid-column:1/-1;">
            <label>Supplier Name <span class="req">*</span></label>
            <input type="text" id="ms_name" name="name" required placeholder="Enter supplier or business name">
          </div>
          <div class="form-group">
            <label>Phone # Primary <span class="req">*</span></label>
            <input type="text" id="ms_phoneNo1" name="phoneNo1" required placeholder="Phone number">
          </div>
          <div class="form-group">
            <label>WhatsApp No</label>
            <input type="text" id="ms_whatsAppNo" name="whatsAppNo" placeholder="WhatsApp number">
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Email ID</label>
            <input type="email" id="ms_emailId" name="emailId" placeholder="supplier@example.com">
          </div>
        </div>

        <div style="border-top:2px solid #f1f5f9; padding-top:16px; margin-bottom:16px;">
          <p style="margin:0 0 14px; font-weight:700; color:#475569; font-size:13px; text-transform:uppercase; letter-spacing:.5px;">Address Details</p>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group" style="grid-column:1/-1;">
              <label>Address Line 1 <span class="req">*</span></label>
              <input type="text" id="ms_line1" name="line1" required placeholder="Street, building, door no.">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
              <label>Address Line 2</label>
              <input type="text" id="ms_line2" name="line2" placeholder="Suite, landmark (optional)">
            </div>
            <div class="form-group">
              <label>City <span class="req">*</span></label>
              <input type="text" id="ms_city" name="city" required placeholder="City name">
            </div>
            <div class="form-group">
              <label>Zip / Pincode <span class="req">*</span></label>
              <input type="text" id="ms_zipCode" name="zipCode" required placeholder="Postal code">
            </div>
          </div>
        </div>

        <div id="addSupplierMsg" style="display:none; padding:10px 14px; border-radius:8px; font-size:13.5px; font-weight:600; margin-bottom:12px;"></div>

        <!-- Footer Buttons -->
        <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:4px;">
          <button type="button" onclick="closeAddSupplierModal()" style="padding:10px 22px; background:#e2e8f0; color:#475569; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer;">Cancel</button>
          <button type="reset" onclick="document.getElementById('addSupplierMsg').style.display='none'" style="padding:10px 22px; background:#94a3b8; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer;">Reset</button>
          <button type="submit" id="addSupplierSubmitBtn" style="padding:10px 26px; background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; box-shadow:0 2px 8px rgba(22,163,74,0.35);">
            Save Supplier
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<style>
@keyframes supplierModalIn {
  from { opacity:0; transform:scale(0.95) translateY(-12px); }
  to   { opacity:1; transform:scale(1)   translateY(0); }
}
</style>

<script>
var _supplierFrameReady = false;

function openAddSupplierModal() {
  _supplierFrameReady = false;
  document.getElementById('addSupplierModal').style.display = 'flex';
  document.getElementById('addSupplierForm').reset();
  document.getElementById('addSupplierMsg').style.display = 'none';
  document.getElementById('addSupplierSubmitBtn').disabled = false;
  document.getElementById('addSupplierSubmitBtn').textContent = 'Save Supplier';
  setTimeout(function(){ document.getElementById('ms_name').focus(); }, 100);
}

function closeAddSupplierModal() {
  document.getElementById('addSupplierModal').style.display = 'none';
}

document.getElementById('addSupplierModal').addEventListener('click', function(e) {
  if (e.target === this) closeAddSupplierModal();
});

function onSupplierFormSubmit() {
  var btn = document.getElementById('addSupplierSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Saving…';
  document.getElementById('addSupplierMsg').style.display = 'none';
  _supplierFrameReady = true;
}

function onSupplierFrameLoad() {
  if (!_supplierFrameReady) return;
  _supplierFrameReady = false;
}

// Called by save_and_return.php on SUCCESS
function onSupplierSaved(supplier) {
  var msg = document.getElementById('addSupplierMsg');
  msg.style.display = 'block';
  msg.style.background = '#f0fdf4';
  msg.style.color = '#16a34a';
  msg.style.border = '1px solid #bbf7d0';
  msg.textContent = '✓ Supplier added successfully!';

  var sel = document.getElementById('supplierSelect');
  var opt = document.createElement('option');
  opt.value = supplier.id;
  opt.text  = supplier.name;
  sel.appendChild(opt);
  sel.value = supplier.id;

  SUPPLIERS_DATA.push({
    id:       String(supplier.id),
    name:     supplier.name,
    phoneNo1: supplier.phone,
    emailId:  supplier.email,
    line1:    supplier.line1,
    line2:    supplier.line2,
    city:     supplier.city,
    zipCode:  supplier.zipCode
  });

  onSupplierChange(supplier.id);
  setTimeout(closeAddSupplierModal, 1200);
}

// Called by save_and_return.php on ERROR
function onSupplierSaveError(message) {
  var msg = document.getElementById('addSupplierMsg');
  msg.style.display = 'block';
  msg.style.background = '#fef2f2';
  msg.style.color = '#dc2626';
  msg.style.border = '1px solid #fecaca';
  msg.textContent = '✗ ' + message;

  var btn = document.getElementById('addSupplierSubmitBtn');
  btn.disabled = false;
  btn.textContent = 'Save Supplier';
}
</script>

<?php include("../includes/footer.php"); ?>

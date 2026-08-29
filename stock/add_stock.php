<?php require_once("../config/db.php"); ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
    exit();
}

// Prevent any PHP undefined variable warnings
$successMsg = (isset($_GET['success']) && $_GET['success'] == 1);
$errorMsg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$successCount = isset($_GET['count']) ? intval($_GET['count']) : 0;
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
    --gray-btn: #5c636a;
    --input-bg: #ffffff;
    --readonly-bg: #e9ecef;
    --radius: 6px;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
  }

  .page-wrapper {
    max-width: 1240px;
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

  .card {
    background: var(--card);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #e9ecef;
    padding: 24px;
    margin-bottom: 20px;
  }

  /* ── Top bar ── */
  .top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .multi-stock-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
  }

  .toggle-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 500;
    color: #adb5bd;
  }

  .toggle {
    position: relative;
    width: 36px;
    height: 20px;
    display: inline-block;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    inset: 0;
    background: #ced4da;
    border-radius: 20px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .slider:before {
    content: '';
    position: absolute;
    width: 14px;
    height: 14px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
  }

  input:checked + .slider {
    background: var(--blue);
  }

  input:checked + .slider:before {
    transform: translateX(16px);
  }

  /* ── Main Layout Grid ── */
  .stock-grid {
    display: grid;
    grid-template-columns: 210px 1fr 1fr;
    gap: 24px;
    align-items: start;
  }

  @media (max-width: 900px) {
    .stock-grid {
      grid-template-columns: 1fr 1fr !important;
    }
  }

  @media (max-width: 600px) {
    .stock-grid, .grid-2, .grid-3 {
      grid-template-columns: 1fr !important;
    }
  }

  label {
    font-size: 12.5px;
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 4px;
  }

  label small {
    font-weight: 400;
    color: #6c757d;
  }

  .add-link {
    color: var(--blue);
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    margin-left: 2px;
  }

  .add-link:hover {
    text-decoration: underline;
  }

  input[type=text],
  input[type=number],
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

  .section-title {
    font-size: 13px;
    font-weight: 700;
    color: #212529;
    margin-top: 10px;
    margin-bottom: 6px;
  }

  /* ── Image Box ── */
  .image-box {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #e9ecef;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
  }

  .image-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .image-box .placeholder-svg {
    width: 80px;
    height: 80px;
    fill: #adb5bd;
  }

  /* ── Search Dropdown ── */
  .search-wrap {
    position: relative;
  }

  #spareList {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    border: 1px solid #ced4da;
    background: #fff;
    border-radius: 0 0 8px 8px;
    max-height: 240px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
    box-shadow: 0 10px 20px rgba(0,0,0,0.12);
  }

  #spareList .spare-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  #spareList .spare-item:hover {
    background: #e7f5ff;
  }

  #spareList .spare-item img {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 4px;
  }

  /* ── Action Buttons at Bottom Right ── */
  .action-bar {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 24px;
  }

  .btn-sub {
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 22px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
  }

  .btn-res {
    background: var(--gray-btn);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
  }

  .btn-sub:hover { background: #0b5ed7; }
  .btn-res:hover { background: #4c535a; }

  /* ── Toast Alert ── */
  .toast {
    position: fixed;
    top: 75px;
    right: 20px;
    z-index: 9999;
    background: #198754;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  }

  /* ── Modals ── */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 2000;
    padding: 20px;
    overflow-y: auto;
  }

  .modal-box {
    background: #fff;
    width: 100%;
    max-width: 440px;
    margin: 80px auto;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }

  .modal-box.lg { max-width: 600px; }

  .modal-box h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6c757d;
  }

  .modal-footer {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 20px;
  }

  /* Multi Stock Batch Container */
  #multiStockContainer {
    display: none;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px dashed var(--border);
  }

  .multi-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  .multi-table th, .multi-table td {
    padding: 8px;
    border: 1px solid var(--border);
    font-size: 12.5px;
  }

  /* ── Responsive Layout Grid & Mobile Viewports ── */
  @media (max-width: 992px) {
    .stock-grid {
      grid-template-columns: 1fr !important;
      gap: 16px;
    }
    .image-box {
      max-width: 210px;
      margin: 0 auto;
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
    .top-bar {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
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
  }

  @media (max-width: 425px) {
    .grid-2, .grid-3 {
      grid-template-columns: 1fr !important;
      gap: 10px;
    }
    .action-bar {
      flex-direction: column;
      width: 100%;
      gap: 8px;
    }
    .btn-sub, .btn-res {
      width: 100%;
      padding: 10px 14px;
      text-align: center;
    }
    input[type=text], input[type=number], select {
      font-size: 14px !important;
      padding: 10px 12px;
    }
  }

  @media (max-width: 375px) {
    .page-wrapper {
      padding: 0 8px;
    }
    .image-box {
      max-width: 180px;
    }
    .page-header h2 {
      font-size: 18px;
    }
  }

  @media (max-width: 320px) {
    .page-wrapper {
      padding: 0 6px;
    }
    .image-box {
      max-width: 150px;
    }
    .page-header h2 {
      font-size: 16px;
    }
    .btn-new-top, .btn-back {
      padding: 5px 10px;
      font-size: 12px;
    }
  }
</style>

<div id="toastContainer"></div>

<?php if ($successMsg): ?>
  <div class="toast" id="phpToast">✅ Stock added successfully!</div>
  <script>setTimeout(() => { let t = document.getElementById('phpToast'); if(t) t.remove(); }, 4000);</script>
<?php endif; ?>

<div class="page-wrapper">

  <div class="page-header">
    <h2>Stock Info</h2>
    <div class="header-actions">
      <a href="javascript:void(0)" onclick="openModal('itemModal')" class="btn-new-top">+ New</a>
      <a href="list.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
        Stocks
      </a>
    </div>
  </div>

  <div class="card">

    <!-- Top Bar -->
    <div class="top-bar"> 
      <div class="toggle-row">
        <label class="toggle" style="margin:0;">
          <input type="checkbox" id="selledToggle" name="selled" value="1">
          <span class="slider"></span>
        </label>
        Selled
      </div>
    </div>

    <form method="POST" action="insert_stock.php" id="stockForm" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event)">

      <div class="stock-grid">

        <!-- COLUMN 1: IMAGE PLACEHOLDER -->
        <div>
          <div class="image-box" id="imageBox" style="position: relative; cursor: pointer;" onclick="if(document.getElementById('spareImg').src && document.getElementById('spareImg').style.display !== 'none') openStockLightbox(document.getElementById('spareImg').src)">
            <img id="spareImg" src="" alt="" style="display:none;" onerror="this.style.display='none'; document.getElementById('imgSvg').style.display='block';">
            <svg id="imgSvg" class="placeholder-svg" viewBox="0 0 16 16">
              <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
              <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
            </svg>
          </div>

          <!-- Dual Photo Upload Buttons for Main Stock Entry -->
          <div style="display: flex; gap: 6px; flex-direction: column; margin-top: 10px;">
            <button type="button" id="btnTakeStockPhoto" onclick="triggerStockCamera()" style="background: #2563eb; color: #fff; border: none; padding: 7px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;">
              📷 Take Photo (Camera)
            </button>
            <button type="button" id="btnChooseStockGallery" onclick="triggerStockGallery()" style="background: #475569; color: #fff; border: none; padding: 7px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;">
              📁 Choose File (System)
            </button>
          </div>

          <input type="file" name="picture" id="addStockCamera" accept="image/*" capture="environment" style="display:none;" onchange="previewAddStockImage(this)">
          <input type="file" name="picture_gallery" id="addStockGallery" accept="image/*" style="display:none;" onchange="previewAddStockImage(this)">
        </div>

        <!-- COLUMN 2: ITEM NAME, RACK #, PART #, BRAND, MODEL, QTY, STOCKS -->
        <div>

          <div class="form-group">
            <label>Item Name <span style="color:#dc3545">*</span></label>
            <div class="search-wrap">
              <input type="text" id="spareSearch" name="itemName" placeholder="" onkeyup="searchSpare()" autocomplete="off" required>
              <div id="spareList"></div>
            </div>
            <input type="hidden" name="spare" id="spareId">
            <input type="hidden" name="sparePicture" id="sparePicture">
          </div>

          <!-- Rack # and Part # Row -->
          <div class="grid-2 form-group">
            <div>
              <label>Rack #</label>
              <input type="text" id="rackNumber" placeholder="" readonly>
            </div>
            <div>
              <label>Part #</label>
              <input type="text" id="partNo" placeholder="" readonly>
            </div>
          </div>

          <!-- Brand and Model Row -->
          <div class="grid-2 form-group">
            <div>
              <label>Brand <span style="color:#dc3545">*</span><a class="add-link" onclick="openModal('brandModal')">Add</a></label>
              <select name="brand" id="brandSelect" required>
                <option value="">Select</option>
                <?php
                $resB = mysqli_query($conn, "SELECT id, brandName FROM brand ORDER BY brandName ASC");
                if ($resB) {
                  while ($r = mysqli_fetch_assoc($resB)) {
                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['brandName']) . "</option>";
                  }
                }
                ?>
              </select>
            </div>
            <div>
              <label>Model <span style="color:#dc3545">*</span><a class="add-link" onclick="openModal('modelModal')">Add</a></label>
              <select name="model" id="modelSelect" required>
                <option value="">Select</option>
                <?php
                $resM = mysqli_query($conn, "SELECT id, model FROM model ORDER BY model ASC");
                if ($resM) {
                  while ($r = mysqli_fetch_assoc($resM)) {
                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['model']) . "</option>";
                  }
                }
                ?>
              </select>
            </div>
          </div>

          <!-- Quantity Row -->
          <div class="form-group">
            <label>Quantity <span style="color:#dc3545">*</span></label>
            <input type="number" id="qtyInput" name="quantity" value="1" min="1" required oninput="syncQty(this)">
          </div>

          <!-- REORDER LEVEL -->
          <div class="section-title">Reorder Level:</div>
          <div class="form-group">
            <label>Min Qunatity</label>
            <input type="number" name="minQty" id="minQty" placeholder="0" min="0">
          </div>

          <!-- STOCKED QUANTITY -->
          <div class="form-group">
            <label>Stock Quantity <span style="color:var(--red)">*</span></label>
            <input type="number" name="quantity" id="qtyInput" value="1" min="1" required oninput="calc()" style="font-weight: 700;">
          </div>

          <!-- Hidden optional fields stored cleanly -->
          <input type="hidden" name="serialNo" id="serialNo" value="">
          <input type="hidden" name="unit" id="unitSelect" value="1">
          <input type="hidden" name="machine" id="machineSelect" value="">
          <input type="hidden" name="purchaseItem" id="purchaseItemSelect" value="">

        </div>

        <!-- COLUMN 3: PRICE PER QUANTITY, TOTAL PRICE, WARRANTY, ACTIONS -->
        <div>

          <div class="section-title">Price Per Quantity</div>
          <div class="grid-3 form-group">
            <div>
              <label>Actual Price</label>
              <input type="number" id="actual" placeholder="0.00" step="0.01" min="0">
            </div>
            <div>
              <label>Selling Price</label>
              <input type="number" id="selling" placeholder="0.00" step="0.01" min="0">
            </div>
            <div>
              <label>GST %</label>
              <input type="number" id="gst" placeholder="0.00" step="0.01" min="0">
            </div>
          </div>

          <div class="section-title">Total Price</div>
          <div class="grid-3 form-group">
            <div>
              <label>Actual Price</label>
              <input type="number" id="displayActualQty" readonly placeholder="0.00">
            </div>
            <div>
              <label>Selling Price</label>
              <input type="number" id="displaySellingQty" readonly placeholder="0.00">
            </div>
            <div>
              <label>Sellled Price</label>
              <input type="number" id="displaySelled" readonly placeholder="0.00">
            </div>
          </div>

          <div class="section-title">Warranty</div>
          <div class="form-group">
            <label>In Months</label>
            <input type="number" name="warrantyInMonths" id="warrantyInMonths" placeholder="0" min="0">
          </div>

          <!-- Hidden calculations -->
          <input type="hidden" name="actualPricePerQty" id="tActual">
          <input type="hidden" name="actualPricePerUnit" id="actualUnit">
          <input type="hidden" name="sellingPricePerQty" id="tSelling">
          <input type="hidden" name="sellingPricePerUnit" id="sellingUnit">
          <input type="hidden" name="selledPricePerUnit" id="selledUnit">
          <input type="hidden" name="gstPercentage" id="gstHidden">
          <input type="hidden" name="availableQty" id="availableQty">

          <!-- Action Buttons -->
          <div class="action-bar">
            <button type="submit" class="btn-sub">Submit</button>
            <button type="button" class="btn-res" onclick="resetForm()">Reset</button>
          </div>

        </div>

      </div>



    </form>

  </div>
</div>

<!-- ================= QUICK ADD MODAL A: BRAND ================= -->
<div id="brandModal" class="modal-overlay">
  <div class="modal-box">
    <h3>Add New Brand <button type="button" class="modal-close" onclick="closeModal('brandModal')">✕</button></h3>
    <div class="form-group">
      <label>Brand Name <span style="color:#dc3545">*</span></label>
      <input type="text" id="bname" placeholder="e.g. BOSCH, SINGER, USHA">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-res" onclick="closeModal('brandModal')">Cancel</button>
      <button type="button" class="btn-sub" onclick="saveBrand()">Save Brand</button>
    </div>
  </div>
</div>

<!-- ================= QUICK ADD MODAL B: MODEL ================= -->
<div id="modelModal" class="modal-overlay">
  <div class="modal-box">
    <h3>Add New Model <button type="button" class="modal-close" onclick="closeModal('modelModal')">✕</button></h3>
    <div class="form-group">
      <label>Model Name <span style="color:#dc3545">*</span></label>
      <input type="text" id="mname" placeholder="e.g. SV-100, TA1, F5">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-res" onclick="closeModal('modelModal')">Cancel</button>
      <button type="button" class="btn-sub" onclick="saveModel()">Save Model</button>
    </div>
  </div>
</div>

<!-- ================= QUICK ADD MODAL C: SPARE INFO (IMAGE 1 MATCHING) ================= -->
<div id="itemModal" class="modal-overlay">
  <div class="modal-box lg">
    <h3>Spare Info <button type="button" class="modal-close" onclick="closeModal('itemModal')">✕</button></h3>
    <form id="newItemForm" onsubmit="saveNewItem(event)" enctype="multipart/form-data">
      <div style="display:flex; gap:20px; align-items:flex-start;">
        
        <!-- Left: Image Box + Active Toggle -->
        <div style="width:160px; flex-shrink:0; text-align:center;">
          <div style="width:160px; height:160px; background:#e9ecef; border:2px dashed #ced4da; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden; position:relative;" onclick="document.getElementById('modalGalleryInput').click()">
            <img id="modalImgPreview" style="max-width:100%; max-height:100%; object-fit:contain; display:none;">
            <svg id="modalImgSvg" style="width:60px; height:60px; fill:#adb5bd;" viewBox="0 0 16 16">
              <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
              <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
            </svg>
          </div>

          <!-- Dual Camera & Gallery Photo Upload Buttons for Modal -->
          <div style="display: flex; gap: 6px; flex-direction: column; margin-top: 8px;">
            <button type="button" onclick="openErpCamera(function(dataUrl, file){ if(dataUrl){ let img = document.getElementById('modalImgPreview'); let svg = document.getElementById('modalImgSvg'); if(img){ img.src = dataUrl; img.style.display = 'block'; } if(svg) svg.style.display = 'none'; if(file){ try { let c = new DataTransfer(); c.items.add(file); document.getElementById('modalGalleryInput').files = c.files; } catch(e){} } } })" style="background: #2563eb; color: #fff; border: none; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
              📷 Take Photo
            </button>
            <button type="button" onclick="document.getElementById('modalGalleryInput').click()" style="background: #475569; color: #fff; border: none; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
              📁 Choose File
            </button>
          </div>

          <input type="file" name="picture" id="modalCameraInput" accept="image/*" capture="environment" style="display:none;" onchange="previewModalImage(this)">
          <input type="file" name="picture" id="modalGalleryInput" accept="image/*" style="display:none;" onchange="previewModalImage(this)">

          <div style="margin-top:10px; display:flex; align-items:center; justify-content:center; gap:6px;">
            <input type="checkbox" name="active" value="1" checked style="width:auto;">
            <span style="background:#8b5cf6; color:#fff; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700;">✓ Active</span>
          </div>
        </div>

        <!-- Right: Spare Name, Part #, Rack # -->
        <div style="flex:1;">
          <div class="form-group">
            <label>Spare Name <span style="color:#dc3545">*</span></label>
            <input type="text" id="ni_name" name="spareName" placeholder="" required>
          </div>
          <div class="grid-2 form-group">
            <div>
              <label>Part # <span style="color:#dc3545">*</span></label>
              <input type="text" id="ni_part" name="partNo" placeholder="" required>
            </div>
            <div>
              <label>Rack # <span style="color:#dc3545">*</span></label>
              <input type="text" id="ni_rack" name="rackNumber" placeholder="" required>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="submit" class="btn-sub">Submit</button>
        <button type="button" class="btn-res" onclick="closeModal('itemModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>





<script>

  // ── Init ──
  window.onload = function () {
    calc();
  };

  // ── Modal Image Preview ──
  function previewModalImage(input) {
    if (input.files && input.files[0]) {
      let reader = new FileReader();
      reader.onload = function (e) {
        let img = document.getElementById("modalImgPreview");
        let svg = document.getElementById("modalImgSvg");
        img.src = e.target.result;
        img.style.display = "block";
        if (svg) svg.style.display = "none";
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  function previewAddStockImage(input) {
    if (input.files && input.files[0]) {
      let reader = new FileReader();
      reader.onload = function (e) {
        let img = document.getElementById("spareImg");
        let svg = document.getElementById("imgSvg");
        if (img) {
          img.src = e.target.result;
          img.style.display = "block";
        }
        if (svg) svg.style.display = "none";
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  function openStockLightbox(src) {
    if (!src || src.endsWith('no-image.png') || src.includes('undefined')) return;
    let modal = document.getElementById('stockLightboxModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'stockLightboxModal';
      modal.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); cursor:pointer;';
      modal.onclick = function() { modal.style.display = 'none'; };
      modal.innerHTML = '<img id="stockLightboxImg" style="max-width:90%; max-height:90%; border-radius:10px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.5); object-fit:contain;"><span style="position:absolute; top:20px; right:25px; color:#fff; font-size:32px; font-weight:bold; cursor:pointer;">&times;</span>';
      document.body.appendChild(modal);
    }
    document.getElementById('stockLightboxImg').src = src;
    modal.style.display = 'flex';
  }

  // ── Toast Helper ──
  function showToast(msg, isSuccess = true) {
    let container = document.getElementById("toastContainer");
    let t = document.createElement("div");
    t.className = "toast";
    if (!isSuccess) t.style.background = "#dc3545";
    t.innerHTML = (isSuccess ? "✅ " : "❌ ") + msg;
    container.appendChild(t);
    setTimeout(() => { t.remove(); }, 3500);
  }

  // ── Spare Image Loader ──
  function loadSpareImage(picturePath) {
    let img = document.getElementById("spareImg");
    let svg = document.getElementById("imgSvg");

    if (picturePath && picturePath !== 'no-image.png' && picturePath !== 'NULL') {
      let srcPath = picturePath.startsWith('uploads/') || picturePath.startsWith('Spare/') || picturePath.startsWith('../')
        ? "../" + picturePath.replace(/^\.\.\//, '')
        : "../uploads/spares/" + picturePath;

      img.src = srcPath;
      img.style.display = "block";
      if (svg) svg.style.display = "none";
    } else {
      img.style.display = "none";
      if (svg) svg.style.display = "block";
    }
  }

  function clearSpareImage() {
    let img = document.getElementById("spareImg");
    let svg = document.getElementById("imgSvg");
    img.src = "";
    img.style.display = "none";
    if (svg) svg.style.display = "block";
  }

  // ── Sync quantity fields ──
  function syncQty(el) {
    let val = Math.max(1, +el.value || 1);
    document.getElementById("qtyInput").value = val;
    document.getElementById("newStock").value = val;
    let oldQty = +document.getElementById("oldStock").value || 0;
    document.getElementById("availableQty").value = val + oldQty;
    calc();
  }

  // ── Calculation ──
  function calc() {
    let q = +document.getElementById("qtyInput").value || 1;
    let aStr = document.getElementById("actual").value;
    let sStr = document.getElementById("selling").value;
    let gStr = document.getElementById("gst").value;

    let a = Math.round(parseFloat(aStr) || 0);
    let s = Math.round(parseFloat(sStr) || 0);
    let g = Math.round(parseFloat(gStr) || 0);

    let totalActual = Math.round(a * q);
    let totalSelling = Math.round(s * q);
    let finalSelled = Math.round(s + (s * g / 100));

    document.getElementById("displayActualQty").value = aStr !== '' ? totalActual : '';
    document.getElementById("displaySellingQty").value = sStr !== '' ? totalSelling : '';
    document.getElementById("displaySelled").value = (sStr !== '' || gStr !== '') ? finalSelled : '';

    document.getElementById("tActual").value = totalActual;
    document.getElementById("actualUnit").value = a;
    document.getElementById("tSelling").value = totalSelling;
    document.getElementById("sellingUnit").value = s;
    document.getElementById("selledUnit").value = finalSelled;
    document.getElementById("gstHidden").value = g;
    let oldQtyEl = document.getElementById("oldStock");
    let oldQty = oldQtyEl ? (+oldQtyEl.value || 0) : 0;
    document.getElementById("availableQty").value = q + oldQty;
  }

  document.querySelectorAll("#actual, #selling, #gst, #qtyInput").forEach(el => {
    el.addEventListener("input", calc);
  });



  // ── Search Spare Autocomplete ──
  function searchSpare() {
    let term = document.getElementById("spareSearch").value.trim();
    if (term.length < 1) {
      document.getElementById("spareList").style.display = "none";
      return;
    }

    fetch("search_spare.php?term=" + encodeURIComponent(term))
      .then(r => r.json())
      .then(data => {
        let list = document.getElementById("spareList");
        list.innerHTML = "";

        if (!data.length) {
          list.innerHTML = '<div style="padding:10px; color:#6c757d; font-size:13px;">No items found</div>';
          list.style.display = "block";
          return;
        }

        data.forEach(item => {
          let div = document.createElement("div");
          div.className = "spare-item";

          let thumbSrc = (item.picture && item.picture !== 'no-image.png')
            ? (item.picture.startsWith('uploads/') || item.picture.startsWith('Spare/') ? "../" + item.picture.replace(/^\.\.\//, '') : "../uploads/spares/" + item.picture)
            : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Crect width='32' height='32' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='55%25' font-size='14' text-anchor='middle' dominant-baseline='middle' fill='%23adb5bd'%3E📦%3C/text%3E%3C/svg%3E";

          div.innerHTML = `
            <img src="${thumbSrc}" alt="" onerror="this.style.display='none'">
            <div style="flex:1;">
              <strong>${item.spareName}</strong>
              <div style="color:#6c757d; font-size:11px;">Rack #: ${item.rackNumber || 'N/A'} &middot; Part #: ${item.partNo || 'N/A'}</div>
            </div>
          `;
          div.onclick = () => selectSpare(item);
          list.appendChild(div);
        });

        list.style.display = "block";
      })
      .catch(() => {
        document.getElementById("spareList").style.display = "none";
      });
  }

  function selectSpare(item) {
    document.getElementById("spareSearch").value = item.spareName;
    document.getElementById("spareId").value = item.id;
    document.getElementById("sparePicture").value = item.picture || '';
    document.getElementById("rackNumber").value = item.rackNumber || '';
    document.getElementById("partNo").value = item.partNo || '';
    document.getElementById("spareList").style.display = "none";

    if (item.brand) document.getElementById("brandSelect").value = item.brand;
    if (item.model) document.getElementById("modelSelect").value = item.model;
    if (item.machine) document.getElementById("machineSelect").value = item.machine;
    if (item.unit) document.getElementById("unitSelect").value = item.unit;
    
    if (item.actualPricePerUnit !== undefined) document.getElementById("actual").value = item.actualPricePerUnit;
    if (item.sellingPricePerUnit !== undefined) document.getElementById("selling").value = item.sellingPricePerUnit;
    if (item.gstPercentage !== undefined) document.getElementById("gst").value = item.gstPercentage;
    if (item.warrantyInMonths !== undefined) document.getElementById("warrantyInMonths").value = item.warrantyInMonths;
    if (item.purchaseItem !== undefined) document.getElementById("purchaseItemSelect").value = item.purchaseItem;



    loadSpareImage(item.picture);

    fetch("get_stock_qty.php?spare=" + item.id)
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          let oldQty = d.qty || 0;
          document.getElementById("oldStock").value = oldQty;
          let newQty = +document.getElementById("qtyInput").value || 1;
          document.getElementById("availableQty").value = newQty + oldQty;

          if (!item.brand && d.brand) document.getElementById("brandSelect").value = d.brand;
          if (!item.model && d.model) document.getElementById("modelSelect").value = d.model;
          if (!item.machine && d.machine) document.getElementById("machineSelect").value = d.machine;
          if (d.actualPricePerUnit && !+document.getElementById("actual").value) document.getElementById("actual").value = d.actualPricePerUnit;
          if (d.sellingPricePerUnit && !+document.getElementById("selling").value) document.getElementById("selling").value = d.sellingPricePerUnit;
          if (d.gstPercentage && !+document.getElementById("gst").value) document.getElementById("gst").value = d.gstPercentage;
          if (d.warrantyInMonths && !+document.getElementById("warrantyInMonths").value) document.getElementById("warrantyInMonths").value = d.warrantyInMonths;
        }
        calc();
      })
      .catch(() => { calc(); });
  }

  // ── Multi Stock Toggle & Manager ──
  function toggleMultiStock(cb) {
    let container = document.getElementById("multiStockContainer");
    if (cb.checked) {
      container.style.display = "block";
      if (document.getElementById("multiStockBody").children.length === 0) {
        addMultiRow();
      }
    } else {
      container.style.display = "none";
    }
  }

  function addMultiRow() {
    let tbody = document.getElementById("multiStockBody");
    let rowIdx = tbody.children.length + 1;
    let tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${rowIdx}</td>
      <td><input type="text" name="multi_items[${rowIdx-1}][serialNo]" placeholder="Optional Serial #" style="font-size:12px; padding:4px 8px;"></td>
      <td><input type="number" name="multi_items[${rowIdx-1}][quantity]" value="1" min="1" style="width:60px; font-size:12px; padding:4px 8px;"></td>
      <td style="text-align:center;"><button type="button" onclick="this.closest('tr').remove()" style="color:#dc3545; background:none; border:none; cursor:pointer; font-weight:bold;">✕</button></td>
    `;
    tbody.appendChild(tr);
  }

  // ── Quick Add Modals JS ──
  function openModal(id) { document.getElementById(id).style.display = 'block'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  function saveBrand() {
    let nameInput = document.getElementById("bname");
    let name = nameInput.value.trim();
    if (!name) return showToast("Enter a brand name.", false);

    fetch("save_brand.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ name: name })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || "Brand saved!");
        closeModal("brandModal");
        nameInput.value = "";
        reloadBrandDropdown(data.id);
      } else {
        showToast(data.message || "Failed to save brand.", false);
      }
    })
    .catch(() => showToast("Error saving brand.", false));
  }

  function reloadBrandDropdown(selectId) {
    fetch("get_brands.php")
      .then(r => r.json())
      .then(brands => {
        let select = document.getElementById("brandSelect");
        select.innerHTML = '<option value="">Select</option>';
        brands.forEach(b => {
          let opt = document.createElement("option");
          opt.value = b.id;
          opt.textContent = b.brandName;
          select.appendChild(opt);
        });
        if (selectId) select.value = selectId;
      });
  }

  function saveModel() {
    let nameInput = document.getElementById("mname");
    let name = nameInput.value.trim();
    if (!name) return showToast("Enter a model name.", false);

    fetch("save_model.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ name: name })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || "Model saved!");
        closeModal("modelModal");
        nameInput.value = "";
        reloadModelDropdown(data.id);
      } else {
        showToast(data.message || "Failed to save model.", false);
      }
    })
    .catch(() => showToast("Error saving model.", false));
  }

  function reloadModelDropdown(selectId) {
    fetch("get_models.php")
      .then(r => r.json())
      .then(models => {
        let select = document.getElementById("modelSelect");
        select.innerHTML = '<option value="">Select</option>';
        models.forEach(m => {
          let opt = document.createElement("option");
          opt.value = m.id;
          opt.textContent = m.model;
          select.appendChild(opt);
        });
        if (selectId) select.value = selectId;
      });
  }

  function saveNewItem(e) {
    e.preventDefault();
    let form = document.getElementById("newItemForm");
    let formData = new FormData(form);

    fetch("save_item.php", {
      method: "POST",
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.spare) {
        showToast(data.message || "Spare created successfully!");
        closeModal("itemModal");
        form.reset();
        
        let preview = document.getElementById("modalImgPreview");
        let svg = document.getElementById("modalImgSvg");
        if (preview) preview.style.display = "none";
        if (svg) svg.style.display = "block";

        selectSpare(data.spare);
      } else {
        showToast(data.message || "Failed to add new spare.", false);
      }
    })
    .catch(() => showToast("Error creating new spare.", false));
  }

  function handleFormSubmit(e) {
    let spareId = document.getElementById("spareId").value;
    if (!spareId) {
      alert("Please search and select an item / spare first.");
      e.preventDefault();
      return false;
    }
    let brand = document.getElementById("brandSelect").value;
    if (!brand || brand === "" || brand === "0") {
      alert("Brand is required. Please select a Brand before saving.");
      document.getElementById("brandSelect").focus();
      e.preventDefault();
      return false;
    }
    let model = document.getElementById("modelSelect").value;
    if (!model || model === "" || model === "0") {
      alert("Model is required. Please select a Model before saving.");
      document.getElementById("modelSelect").focus();
      e.preventDefault();
      return false;
    }
    let qty = +document.getElementById("qtyInput").value || 0;
    if (qty <= 0) {
      alert("Quantity must be at least 1.");
      e.preventDefault();
      return false;
    }
    return true;
  }

  function resetForm() {
    document.getElementById("stockForm").reset();
    document.getElementById("spareSearch").value = "";
    document.getElementById("spareId").value = "";
    document.getElementById("sparePicture").value = "";
    document.getElementById("rackNumber").value = "";
    document.getElementById("partNo").value = "";
    document.getElementById("oldStock").value = 0;
    document.getElementById("newStock").value = 1;
    document.getElementById("qtyInput").value = 1;
    document.getElementById("multiStockContainer").style.display = "none";
    document.getElementById("multiCheck").checked = false;
    document.getElementById("multiStockBody").innerHTML = "";
    clearSpareImage();
    calc();
    showToast("Form reset cleanly.");
  }

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".search-wrap")) {
      document.getElementById("spareList").style.display = "none";
    }
  });

  document.querySelectorAll(".modal-overlay").forEach(m => {
    m.addEventListener("click", e => { if (e.target === m) m.style.display = "none"; });
  });

  // ── UNIFIED ERP CAMERA INTEGRATION ──
  function triggerStockCamera() {
    console.log("Camera button clicked");
    openErpCamera(function(dataUrl, file) {
      let img = document.getElementById("spareImg");
      let svg = document.getElementById("imgSvg");
      if (img) {
        img.src = dataUrl;
        img.style.display = "block";
      }
      if (svg) svg.style.display = "none";

      if (file) {
        try {
          let container = new DataTransfer();
          container.items.add(file);
          let fileInput = document.getElementById("addStockCamera");
          if (fileInput) fileInput.files = container.files;
        } catch(e) {}
      }
    });
  }

  function triggerStockGallery() {
    console.log("Gallery button clicked");
    let fileInput = document.getElementById("addStockGallery");
    if (fileInput) {
      console.log("File input triggered");
      fileInput.click();
    }
  }

  function previewAddStockImage(input) {
    if (input.files && input.files[0]) {
      console.log("Image selected", input.files[0]);
      let reader = new FileReader();
      reader.onload = function (e) {
        let img = document.getElementById("spareImg");
        let svg = document.getElementById("imgSvg");
        if (img) {
          img.src = e.target.result;
          img.style.display = "block";
        }
        if (svg) svg.style.display = "none";
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

</script>

<?php include("../includes/footer.php"); ?>
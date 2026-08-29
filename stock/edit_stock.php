<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
  echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
  exit();
}

// ================= VALIDATE ID =================
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
  die("Invalid Access");
}

$id = mysqli_real_escape_string($conn, trim($_GET['id']));

// ================= FETCH STOCK DATA =================
$query = "
    SELECT st.*,
           s.spareName, s.partNo, s.rackNumber, s.picture,
           b.brandName,
           m.model AS modelName,
           mc.machineName
    FROM stock st
    LEFT JOIN spares  s  ON st.spare   = s.id
    LEFT JOIN brand   b  ON st.brand   = b.id
    LEFT JOIN model   m  ON st.model   = m.id
    LEFT JOIN machine mc ON st.machine = mc.id
    WHERE st.id = '$id'
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
  die("Stock Not Found");
}

$data = mysqli_fetch_assoc($result);

// ================= UPDATE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $oldQty = intval($data['availableQty'] ?? 0);
  $addedStock = intval($_POST['newStock'] ?? 0);
  $newQty = $oldQty + max(0, $addedStock);
  $preserveQuantity = intval($data['quantity'] ?? 0);
  $minQty = intval($_POST['minQty'] ?? 0);
  $warrantyMons = intval($_POST['warrantyInMonths'] ?? 0);
  $brand = intval($_POST['brand'] ?? 0);
  $model = intval($_POST['model'] ?? 0);
  $serialNo = trim($_POST['serialNo'] ?? '');
  $barCode = trim($_POST['barCode'] ?? '');
  $selled = isset($_POST['selled']) ? 1 : 0;

  if ($brand <= 0 || $model <= 0) {
    echo "<script>alert('Brand and Model are required. Please select both Brand and Model before saving.'); window.history.back();</script>";
    exit();
  }

  $actualUnit = floatval($_POST['actualPricePerUnit'] ?? 0);
  $sellingUnit = floatval($_POST['sellingPricePerUnit'] ?? 0);
  $gstPct = floatval($_POST['gstPercentage'] ?? 0);

  // Image upload handling for 'picture' or 'image'
  $fileObj = isset($_FILES['picture']) ? $_FILES['picture'] : (isset($_FILES['image']) ? $_FILES['image'] : null);
  if ($fileObj && $fileObj['error'] === UPLOAD_ERR_OK) {
      $fileTmp = $fileObj['tmp_name'];
      $fileName = $fileObj['name'];
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      
      if (in_array($fileExt, $allowed)) {
          $uploadDir = '../uploads/stock/';
          if (!is_dir($uploadDir)) {
              mkdir($uploadDir, 0777, true);
          }
          $itemCode = !empty($data['partNo']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $data['partNo']) : ('STK' . $id);
          $relPath = 'uploads/stock/' . $itemCode . '_' . time() . '_1.' . $fileExt;
          $destination = '../' . $relPath;
          
          if (move_uploaded_file($fileTmp, $destination)) {
              $spareIdToUpdate = intval($data['spare']);
              if ($spareIdToUpdate > 0) {
                  $stmtPic = mysqli_prepare($conn, "UPDATE spares SET picture = ? WHERE id = ?");
                  mysqli_stmt_bind_param($stmtPic, "si", $relPath, $spareIdToUpdate);
                  mysqli_stmt_execute($stmtPic);
                  $data['picture'] = $relPath;
              }
          }
      }
  }

  // Re-calculate derived fields
  $selledUnit = round($sellingUnit + ($sellingUnit * $gstPct / 100), 4);
  $actualQty = round($actualUnit * $newQty, 4);
  $sellingQty = round($sellingUnit * $newQty, 4);

  $brandVal = ($brand > 0) ? $brand : NULL;
  $modelVal = ($model > 0) ? $model : NULL;
  $serialVal = ($serialNo !== '') ? $serialNo : NULL;

  $stmt = mysqli_prepare($conn, "
        UPDATE stock SET
            availableQty        = ?,
            quantity            = ?,
            actualPricePerUnit  = ?,
            actualPricePerQty   = ?,
            sellingPricePerUnit = ?,
            sellingPricePerQty  = ?,
            selledPricePerUnit  = ?,
            gstPercentage       = ?,
            brand               = ?,
            model               = ?,
            serialNo            = ?,
            warrantyInMonths    = ?,
            selled              = ?,
            barCode             = ?
        WHERE id = ?
        LIMIT 1
    ");

  if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
  }

  // 15 params: i i d d d d d d i i s i i s s
  mysqli_stmt_bind_param(
    $stmt,
    "iiddddddiiisiss",
    $newQty,
    $preserveQuantity,
    $actualUnit,
    $actualQty,
    $sellingUnit,
    $sellingQty,
    $selledUnit,
    $gstPct,
    $brandVal,
    $modelVal,
    $serialVal,
    $warrantyMons,
    $selled,
    $barCode,
    $id
  );

  if (mysqli_stmt_execute($stmt)) {
    echo "<script>alert('✅ Stock Updated Successfully'); window.location='list.php';</script>";
    exit;
  } else {
    die("Update Failed: " . mysqli_stmt_error($stmt));
  }
}
?>
<?php include("../includes/header.php"); ?>

<link
  href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
  rel="stylesheet">

<style>
  :root {
    --bg: #f0f2f5;
    --card: #ffffff;
    --border: #e2e8f0;
    --label: #64748b;
    --text: #1e293b;
    --accent: #2563eb;
    --green: #10b981;
    --red: #ef4444;
    --input-bg: #f8fafc;
    --radius: 8px;
    --shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
  }

  .page-wrapper {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 16px;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .page-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.02em;
  }

  .header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .btn-nav {
    font-size: 13px;
    font-weight: 600;
    color: var(--label);
    text-decoration: none;
    padding: 7px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--card);
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  .btn-nav:hover {
    background: var(--bg);
    color: var(--text);
  }

  .btn-nav.new {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }

  .btn-nav.new:hover {
    background: #1d4ed8;
  }

  .card {
    background: var(--card);
    border-radius: 14px;
    box-shadow: var(--shadow);
    padding: 28px;
    margin-bottom: 16px;
  }

  .main-grid {
    display: grid;
    grid-template-columns: 220px 1fr 300px;
    gap: 24px;
    align-items: start;
  }

  @media (max-width: 1024px) {
    .main-grid {
      grid-template-columns: 1fr 1fr !important;
    }
  }

  @media (max-width: 768px) {
    .main-grid, .grid-2, .grid-3 {
      grid-template-columns: 1fr !important;
    }
  }

  label {
    font-size: 12px;
    font-weight: 600;
    color: var(--label);
    display: block;
    margin-bottom: 5px;
  }

  input[type=text],
  input[type=number],
  select {
    width: 100%;
    padding: 9px 11px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: var(--text);
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
    appearance: none;
    -webkit-appearance: none;
  }

  input:focus,
  select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  input:disabled,
  input[readonly] {
    background: var(--input-bg);
    color: var(--label);
    cursor: not-allowed;
  }

  select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
  }

  .form-group {
    margin-bottom: 14px;
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

  /* ── Spare image ── */
  .spare-image-box {
    border: 1px solid var(--border);
    border-radius: 12px;
    height: 210px;
    overflow: hidden;
    background: var(--input-bg);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .spare-image-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 10px;
  }

  .spare-image-box .no-img {
    text-align: center;
    color: #94a3b8;
  }

  .spare-image-box .no-img .icon {
    font-size: 40px;
    margin-bottom: 6px;
  }

  .spare-image-box .no-img span {
    font-size: 11px;
    font-weight: 600;
    display: block;
  }

  .img-label {
    margin-top: 8px;
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: var(--label);
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 4px 8px;
  }

  /* ── Top bar ── */
  .top-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 20px;
  }

  .toggle-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--label);
  }

  .toggle {
    position: relative;
    width: 40px;
    height: 22px;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 22px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .slider:before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
  }

  input:checked+.slider {
    background: var(--green);
  }

  input:checked+.slider:before {
    transform: translateX(18px);
  }

  /* ── Section divider ── */
  .section-divider {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--label);
    padding: 6px 0 10px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 14px;
  }

  /* ── Barcode ── */
  .barcode-field {
    font-family: 'DM Mono', monospace;
    text-align: center;
    font-size: 15px;
    letter-spacing: 0.05em;
  }

  .barcode-box {
    margin-top: 14px;
    padding: 14px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    text-align: center;
  }

  /* ── Action Buttons ── */
  .action-bar {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    margin-top: 20px;
  }

  .btn {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    border: none;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.15s;
  }

  .btn-reset {
    background: var(--input-bg);
    color: var(--label);
    border: 1px solid var(--border);
  }

  .btn-reset:hover {
    background: #e2e8f0;
  }

  .btn-submit {
    background: linear-gradient(135deg, var(--green), #059669);
    color: #fff;
    padding: 10px 36px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .btn-submit:hover {
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
  }

  @media (max-width: 1024px) {
    .main-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="page-wrapper">

  <div class="page-header">
    <h2>Stock Info</h2>
    <div class="header-actions">
      <a href="add_stock.php" class="btn-nav new">+ New</a>
      <a href="list.php" class="btn-nav">☰ Stocks</a>
    </div>
  </div>

  <div class="card">

    <!-- Selled toggle -->
    <div class="top-bar">
      <div class="toggle-row">
        <label class="toggle" style="margin:0;">
          <input type="checkbox" id="selledToggle" <?= $data['selled'] ? 'checked' : '' ?>>
          <span class="slider"></span>
        </label>
        Selled
      </div>
    </div>

    <form method="POST" id="editForm" enctype="multipart/form-data" onsubmit="return validateEditStockForm(event)">
      <!-- pass selled value via hidden (checkbox outside form) -->
      <input type="hidden" name="selled" id="selledHidden" value="<?= $data['selled'] ? '1' : '0' ?>">

      <div class="main-grid">

        <!-- LEFT: Spare Image -->
        <div>
          <div class="spare-image-box" style="position: relative; cursor: pointer;" onclick="if(document.getElementById('editSpareImg').src) openStockLightbox(document.getElementById('editSpareImg').src)">
            <?php
            $picPath = '';
            if (!empty($data['picture'])) {
              // Try common path patterns
              $candidates = [
                "../uploads/spares/" . $data['picture'],
                "../uploads/" . $data['picture'],
                "../" . $data['picture'],
                $data['picture']
              ];
              foreach ($candidates as $c) {
                if (file_exists($c)) {
                  $picPath = $c;
                  break;
                }
              }
            }
            ?>
            <img id="editSpareImg" src="<?= htmlspecialchars($picPath ? $picPath : '') ?>" alt="<?= htmlspecialchars($data['spareName']) ?>" style="max-width:100%; max-height:100%; object-fit:contain; display: <?= $picPath ? 'block' : 'none' ?>;">
            <div id="noImgBox" class="no-img" style="display: <?= $picPath ? 'none' : 'flex' ?>;">
              <div class="icon">📦</div>
              <span>No Image</span>
            </div>
          </div>
          <div class="img-label" style="font-weight: 700; margin-top: 6px; text-align: center; color: #1e293b;"><?= htmlspecialchars($data['spareName'] ?? '—') ?></div>

          <!-- Dual Camera & Gallery Photo Upload Buttons for Edit Stock -->
          <div style="display: flex; gap: 8px; flex-direction: column; margin-top: 12px;">
            <button type="button" onclick="openErpCamera(function(dataUrl, file){ const img = document.getElementById('editSpareImg'); const noImg = document.getElementById('noImgBox'); if (img) { img.src = dataUrl; img.style.display = 'block'; } if (noImg) noImg.style.display = 'none'; if (file) { try { let c = new DataTransfer(); c.items.add(file); document.getElementById('editStockCamera').files = c.files; } catch(e){} } })" style="background: #2563eb; color: #ffffff; border: none; padding: 9px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
              📷 Take Photo (Camera)
            </button>
            <button type="button" onclick="document.getElementById('editStockGallery').click()" style="background: #475569; color: #ffffff; border: none; padding: 9px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
              📁 Choose File (System)
            </button>
          </div>

          <input type="file" name="picture" id="editStockCamera" accept="image/*" capture="environment" style="display: none;" onchange="previewEditStockImage(this)">
          <input type="file" name="picture" id="editStockGallery" accept="image/*" style="display: none;" onchange="previewEditStockImage(this)">
        </div>

        <!-- CENTER: Item Data -->
        <div>

          <div class="form-group">
            <label>Item Name <span style="color:var(--red)">*</span></label>
            <input type="text" value="<?= htmlspecialchars($data['spareName'] ?? '') ?>" disabled>
          </div>

          <div class="grid-2 form-group">
            <div>
              <label>Rack #</label>
              <input type="text" value="<?= htmlspecialchars($data['rackNumber'] ?? '') ?>" disabled>
            </div>
            <div>
              <label>Part #</label>
              <input type="text" value="<?= htmlspecialchars($data['partNo'] ?? '') ?>" disabled>
            </div>
          </div>

          <!-- Brand dropdown (editable) -->
          <div class="grid-2 form-group">
            <div>
              <label>Brand <span style="color:var(--red)">*</span></label>
              <select name="brand" id="brandSelect" required>
                <option value="">-- Select --</option>
                <?php
                $bRes = mysqli_query($conn, "SELECT * FROM brand ORDER BY brandName");
                while ($b = mysqli_fetch_assoc($bRes)) {
                  $sel = ($b['id'] == $data['brand']) ? 'selected' : '';
                  echo "<option value='{$b['id']}' $sel>" . htmlspecialchars($b['brandName']) . "</option>";
                }
                ?>
              </select>
            </div>
            <div>
              <label>Model <span style="color:var(--red)">*</span></label>
              <select name="model" id="modelSelect" required>
                <option value="">-- Select --</option>
                <?php
                $mRes = mysqli_query($conn, "SELECT * FROM model ORDER BY model");
                while ($m = mysqli_fetch_assoc($mRes)) {
                  $sel = ($m['id'] == $data['model']) ? 'selected' : '';
                  echo "<option value='{$m['id']}' $sel>" . htmlspecialchars($m['model']) . "</option>";
                }
                ?>
              </select>
            </div>
          </div>

          <div class="grid-2 form-group">
            <div>
              <label>Quantity <span style="color:var(--red)">*</span></label>
              <input type="number" name="availableQty" id="qtyInput" value="<?= intval($data['availableQty']) ?>" min="0" required oninput="calc()" style="font-weight: 700;"disabled>
            </div>
            <div>
              <label>Barcode <small style="color:var(--label); font-weight:400;">Code 128</small></label>
              <div style="display:flex; gap:6px;">
                <input type="text" name="barCode" id="barCodeInput" class="barcode-field" value="<?= htmlspecialchars($data['barCode'] ?? '') ?>" oninput="updateBarcodeSvg(this.value)">
                <button type="button" onclick="regenerateBarcode()" title="Generate New Barcode" style="padding:6px 12px; background:#e2e8f0; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; font-weight:700;">⟳</button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Serial #</label>
            <input type="text" name="serialNo" value="<?= htmlspecialchars($data['serialNo'] ?? '') ?>"
              placeholder="Optional">
          </div>

          <!-- Reorder / Stocked / Warranty -->
          <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:12px;" class="form-group">
            <div>
              <label>Reorder Level</label>
              <small style="display:block; font-size:10px; color:var(--label); margin-bottom:4px;">Min Quantity</small>
              <input type="number" name="minQty" value="<?= intval($data['minQty'] ?? 0) ?>" min="0">
            </div>
            <div>
              <label>Stocked Qty</label>
              <small style="display:block; font-size:10px; color:var(--label); margin-bottom:4px;">Old Stock</small>
              <input type="number" id="oldStock" value="<?= intval($data['availableQty']) ?>" readonly style="background:#f1f5f9; font-weight:700; color:#475569; cursor:not-allowed;">
            </div>
            <div>
              <label>&nbsp;</label>
              <small style="display:block; font-size:10px; color:var(--label); margin-bottom:4px;">New Stock (+ Add)</small>
              <input type="number" name="newStock" id="newStock" value="0" min="0" oninput="calculateEditTotalStock()" style="font-weight:700; color:#2563eb;">
            </div>
            <div>
              <label>Warranty</label>
              <small style="display:block; font-size:10px; color:var(--label); margin-bottom:4px;">In Months</small>
              <input type="number" name="warrantyInMonths" value="<?= intval($data['warrantyInMonths'] ?? 0) ?>" min="0">
            </div>
          </div>

        </div>

        <!-- RIGHT: Pricing -->
        <div>
          <div class="section-divider">Price Per Quantity</div>

          <div class="form-group">
            <label>Actual Price</label>
            <input type="number" name="actualPricePerUnit" id="actual"
              value="<?= floatval($data['actualPricePerUnit']) ?>" step="0.01" min="0" oninput="calc()">
          </div>
          <div class="form-group">
            <label>Selling Price</label>
            <input type="number" name="sellingPricePerUnit" id="selling"
              value="<?= floatval($data['sellingPricePerUnit']) ?>" step="0.01" min="0" oninput="calc()">
          </div>
          <div class="form-group">
            <label>GST %</label>
            <input type="number" name="gstPercentage" id="gst" value="<?= floatval($data['gstPercentage'] ?? 0) ?>"
              step="0.01" min="0" oninput="calc()">
          </div>

          <div class="section-divider">Total Price</div>

          <div class="grid-3 form-group">
            <div>
              <label>Actual</label>
              <input type="number" id="displayActual" readonly style="background:var(--input-bg); font-weight:700;"
                value="<?= round(floatval($data['actualPricePerUnit']) * intval($data['availableQty']), 2) ?>">
            </div>
            <div>
              <label>Selling</label>
              <input type="number" id="displaySelling" readonly style="background:var(--input-bg); font-weight:700;"
                value="<?= round(floatval($data['sellingPricePerUnit']) * intval($data['availableQty']), 2) ?>">
            </div>
            <div>
              <label>Selled</label>
              <input type="number" id="displaySelled" readonly
                style="background:#f0fdf4; color:var(--green); font-weight:700;"
                value="<?= floatval($data['selledPricePerUnit'] ?? 0) ?>">
            </div>
          </div>

          <!-- Barcode display -->
          <div class="barcode-box">
            <svg id="barcodeSvg" style="max-width:100%;"></svg>
            <div id="barcodeTextDisplay" style="font-size:11px; color:var(--label); margin-top:4px; font-family:'DM Mono',monospace; font-weight:700;">
              <?= htmlspecialchars($data['barCode'] ?? '') ?>
            </div>
            <button type="button" onclick="printBarcodeLabel()"
              style="margin-top:8px; padding:6px 18px; border-radius:6px; background:#475569; color:#fff; border:none; cursor:pointer; font-size:12px; font-weight:700; font-family:'DM Sans',sans-serif;">
              🖨 Print Barcode Label
            </button>
          </div>

        </div>
      </div>

      <!-- Action Bar -->
      <div class="action-bar">
        <button type="submit" class="btn btn-submit">Submit</button>
        <button type="button" class="btn btn-reset" onclick="resetToOriginal()">Reset</button>
      </div>

    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
  // ── Original values for reset ──
  const ORIG = {
    qty: <?= intval($data['availableQty']) ?>,
    actual: <?= floatval($data['actualPricePerUnit']) ?>,
    selling: <?= floatval($data['sellingPricePerUnit']) ?>,
    gst: <?= floatval($data['gstPercentage'] ?? 0) ?>
  };

  // ── Selled toggle sync ──
  document.getElementById('selledToggle').addEventListener('change', function () {
    document.getElementById('selledHidden').value = this.checked ? '1' : '0';
  });

  // ── Sync new stock → qty ──
  function syncNewStock(el) {
    document.getElementById('qtyInput').value = el.value;
    calc();
  }

  // ── Live price calculation ──
  function calc() {
    let q = +document.getElementById('qtyInput').value || 0;
    let a = +document.getElementById('actual').value || 0;
    let s = +document.getElementById('selling').value || 0;
    let g = +document.getElementById('gst').value || 0;

    document.getElementById('displayActual').value = (a * q).toFixed(2);
    document.getElementById('displaySelling').value = (s * q).toFixed(2);
    document.getElementById('displaySelled').value = (s + (s * g / 100)).toFixed(2);

    // calculate totals cleanly without overriding newStock
  }

  function calculateTotalStock() {
    const oldStock = parseInt(document.getElementById('oldStockVal').value) || 0;
    const addedStock = parseInt(document.getElementById('addNewStockInput').value) || 0;
    const totalStock = oldStock + Math.max(0, addedStock);
    document.getElementById('qtyInput').value = totalStock;
    calc();
  }

  function calculateEditTotalStock() {
    const oldStock = parseInt(document.getElementById('oldStock').value) || 0;
    const addedStock = parseInt(document.getElementById('newStock').value) || 0;
    const totalStock = oldStock + Math.max(0, addedStock);
    if (document.getElementById('qtyInput')) {
      document.getElementById('qtyInput').value = totalStock;
    }
    calc();
  }

  // ── Reset to original DB values ──
  function resetToOriginal() {
    document.getElementById('qtyInput').value = ORIG.qty;
    document.getElementById('actual').value = ORIG.actual;
    document.getElementById('selling').value = ORIG.selling;
    document.getElementById('gst').value = ORIG.gst;
    document.getElementById('newStock').value = 0;
    calc();
  }

  // ── Barcode ──
  function regenerateBarcode() {
    fetch('generate_barcode.php')
      .then(res => res.text())
      .then(code => {
        code = code.trim();
        if (code) {
          document.getElementById('barCodeInput').value = code;
          updateBarcodeSvg(code);
        }
      });
  }

  function updateBarcodeSvg(code) {
    if (!code) return;
    try {
      JsBarcode("#barcodeSvg", code, {
        format: "CODE128", width: 2, height: 50,
        displayValue: false, fontSize: 13
      });
      document.getElementById('barcodeTextDisplay').innerText = code;
    } catch(e) {}
  }

  function printBarcodeLabel() {
    let code = document.getElementById('barCodeInput').value.trim();
    let name = "<?= addslashes($data['spareName'] ?? '') ?>";
    let part = "<?= addslashes($data['partNo'] ?? '') ?>";

    let w = window.open('', '_blank');
    let html = `
    <html>
    <head>
        <title>Print Barcode - ${code}</title>
        <style>
            @page { size: auto; margin: 0; }
            body { font-family: 'Courier New', monospace, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #fff; }
            .label-card { border: 2px dashed #000; padding: 15px 20px; text-align: center; width: 260px; border-radius: 8px; }
            .company { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
            .barcode-num { font-size: 16px; font-weight: bold; letter-spacing: 2px; margin-top: 4px; }
            .item-name { font-size: 13px; font-weight: bold; margin-top: 8px; text-transform: uppercase; word-wrap: break-word; }
            .part-no { font-size: 12px; color: #444; margin-top: 3px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="label-card">
            <div class="company">* Sunder BILLING *</div>
            <svg id="labelSvg"></svg>
            <div class="barcode-num">${code}</div>
            <div class="item-name">${name}</div>
            <div class="part-no">Part #: ${part || '-'}</div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
        <script>
            window.onload = function() {
                try {
                    JsBarcode("#labelSvg", "${code}", { format: "CODE128", width: 1.8, height: 48, displayValue: false });
                } catch(e) {}
                setTimeout(function() { window.print(); }, 500);
            }
        <\/script>
    </body>
    </html>
    `;
    w.document.write(html);
    w.document.close();
  }

  function previewEditStockImage(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.getElementById('editSpareImg');
        const noImg = document.getElementById('noImgBox');
        if (img) {
          img.src = e.target.result;
          img.style.display = 'block';
        }
        if (noImg) {
          noImg.style.display = 'none';
        }
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

  function validateEditStockForm(e) {
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
    return true;
  }

  // ── Init ──
  window.addEventListener('DOMContentLoaded', function () {
    let code = "<?= addslashes($data['barCode'] ?? '') ?>";
    if (code) {
      updateBarcodeSvg(code);
    }
    calc();
  });
</script>

<?php include("../includes/footer.php"); ?>
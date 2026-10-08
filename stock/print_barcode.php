<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "Access denied.";
    exit();
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, trim($_GET['id'])) : '';
if (empty($id)) {
    echo "Invalid ID.";
    exit();
}

$query = "
SELECT
    st.barCode,
    st.sellingPricePerUnit,
    COALESCE(s.spareName, st.itemName) AS spareName,
    s.partNo
FROM stock st
LEFT JOIN spares s ON st.spare = s.id
WHERE st.id = '$id'
";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) === 0) {
    echo "Item not found.";
    exit();
}
$item = mysqli_fetch_assoc($result);

$barcode = $item['barCode'];
$name = $item['spareName'];
$price = $item['sellingPricePerUnit'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Barcode - <?= htmlspecialchars($barcode) ?></title>
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
        <div class="company">* SUNDER MACHNES WORLD *</div>
        <svg id="labelSvg"></svg>
        <div class="barcode-num"><?= htmlspecialchars($barcode) ?></div>
        <div class="item-name"><?= htmlspecialchars($name) ?></div>
        <div class="part-no">Part #: <?= htmlspecialchars($item['partNo'] ?: '-') ?></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        window.onload = function() {
            try {
                JsBarcode("#labelSvg", "<?= htmlspecialchars($barcode) ?>", { format: "CODE128", width: 1.8, height: 48, displayValue: false });
            } catch(e) {}
            setTimeout(function() { window.print(); }, 500);
        }
    </script>
</body>
</html>

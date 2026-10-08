<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if (!isset($_GET['id'])) {
    die("Invalid Access");
}

$id = (int) $_GET['id'];

$query = "SELECT * FROM spares WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Spare Not Found");
}

$picturePath = '';
if (!empty($data['picture'])) {
    $rawPic = $data['picture'];
    if (strpos($rawPic, 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../' . $rawPic;
        if (file_exists($fullPath)) {
            $picturePath = '../' . $rawPic;
        }
    } else {
        $fullPath = __DIR__ . '/../uploads/spares/' . $rawPic;
        if (file_exists($fullPath)) {
            $picturePath = '../uploads/spares/' . $rawPic;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name = mysqli_real_escape_string($conn, trim($_POST['spareName'] ?? ''));
    $part = mysqli_real_escape_string($conn, trim($_POST['partNo'] ?? ''));
    $rack = mysqli_real_escape_string($conn, trim($_POST['rackNumber'] ?? ''));
    $active = isset($_POST['active']) ? 1 : 0;

    $imageName = $data['picture'];
    $uploadDir = "../uploads/spares/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        $imageName = NULL;
    }

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }
    elseif (!empty($_POST['camera_image'])) {
        $camData = $_POST['camera_image'];
        $camData = preg_replace('#^data:image/\w+;base64,#i', '', $camData);
        $camData = str_replace(' ', '+', $camData);
        $imageData = base64_decode($camData);
        if ($imageData !== false) {
            $imageName = "cam_" . time() . "_" . rand(1000, 9999) . ".png";
            file_put_contents($uploadDir . $imageName, $imageData);
        }
    }

    $pictureValue = $imageName === NULL ? "NULL" : "'$imageName'";

    $update = "
    UPDATE spares SET
        spareName='$name',
        partNo='$part',
        rackNumber='$rack',
        active=b'$active',
        picture=$pictureValue
    WHERE id=$id
    ";

    mysqli_query($conn, $update);

    echo "<script>alert('Updated Successfully'); window.location='list_spare.php';</script>";
    exit();
}
?>

<?php include("../includes/header.php"); ?>

<div class="erp-container" style="max-width: 900px; width: 100%; margin: 20px auto; padding: 0 15px;">

    <div class="erp-header-bar" style="margin-bottom: 20px; border-bottom: 1px solid #cbd5e1; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
        <div class="erp-header-title" style="font-size: 20px; font-weight: 700; color: #1e293b;">Edit Spare Part</div>
        <div class="erp-header-actions">
            <a href="list_spare.php" class="btn-erp btn-erp-secondary" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none;">📋 Spare List</a>
        </div>
    </div>

    <div class="erp-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <form method="POST" enctype="multipart/form-data" id="spareEditForm">
            <div class="erp-form-grid" style="display: grid; grid-template-columns: 220px 1fr; gap: 24px;">

                <div>
                    <div class="image-box" onclick="triggerSpareCamera()"
                        style="width:100%; height:180px; border:2px dashed #cbd5e1; display:flex; flex-direction:column; justify-content:center; align-items:center; cursor:pointer; background:#f8fafc; border-radius:12px; overflow:hidden; position:relative;">
                        <img id="preview" src="<?= htmlspecialchars($picturePath) ?>" style="max-width:100%; max-height:100%; object-fit:contain; display:<?= !empty($picturePath) ? 'block' : 'none' ?>;">
                        <div id="imagePlaceholder" style="text-align:center; color:#64748b; display:<?= empty($picturePath) ? 'block' : 'none' ?>;">
                            <span style="font-size:32px; display:block;">🖼️</span>
                            <span style="font-size:12px; font-weight:600;">No Image Selected</span>
                        </div>
                    </div>

                    <div style="margin-top:15px; display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <button type="button" class="choice-btn" onclick="triggerSpareCamera()"
                            style="padding:10px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; display:flex; flex-direction:column; align-items:center; transition:0.2s;">
                            <span style="font-size:20px;">📷</span>
                            <span style="font-size:11px; font-weight:700; color:#475569; margin-top:4px;">Camera</span>
                        </button>
                        <button type="button" class="choice-btn" onclick="document.getElementById('img').click()"
                            style="padding:10px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; display:flex; flex-direction:column; align-items:center; transition:0.2s;">
                            <span style="font-size:20px;">📁</span>
                            <span style="font-size:11px; font-weight:700; color:#475569; margin-top:4px;">Gallery</span>
                        </button>
                    </div>

                    <button type="button" id="removeBtn" onclick="removeImage()"
                        style="display:<?= !empty($picturePath) ? 'block' : 'none' ?>; width:100%; margin-top:10px; padding:10px; border:none; border-radius:8px; background:#fee2e2; color:#ef4444; font-weight:700; font-size:13px; cursor:pointer;">Delete Image</button>

                    <input type="file" name="image" id="img" accept="image/*" capture="environment" hidden onchange="preview(event)">
                    <input type="hidden" name="camera_image" id="camera_image">
                    <input type="hidden" name="remove_image" id="remove_image" value="0">
                </div>

                <div>
                    <div class="erp-form-group" style="margin-bottom: 16px;">
                        <label class="erp-label" style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13.5px;">Spare Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="spareName" value="<?= htmlspecialchars($data['spareName']) ?>" required class="erp-input" placeholder="Spare Name" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="erp-form-group" style="margin-bottom: 16px;">
                        <label class="erp-label" style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13.5px;">Part # / Barcode <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="partNo" value="<?= htmlspecialchars($data['partNo']) ?>" required class="erp-input" placeholder="Part Number or Barcode" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="erp-form-group" style="margin-bottom: 16px;">
                        <label class="erp-label" style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13.5px;">Rack #</label>
                        <input type="text" name="rackNumber" value="<?= htmlspecialchars($data['rackNumber'] ?? '') ?>" class="erp-input" placeholder="Rack Number" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div class="erp-form-group" style="margin-bottom: 20px;">
                        <label class="erp-label" style="display:inline-flex; align-items:center; gap:8px; font-weight:600; color:#334155; font-size:14px; cursor:pointer;">
                            <input type="checkbox" name="active" <?= $data['active'] ? 'checked' : '' ?> style="width:18px; height:18px;"> Active
                        </label>
                    </div>

                    <div style="margin-top:24px; display:flex; gap:12px;">
                        <button type="reset" onclick="resetFormImage()" style="flex: 1; padding: 12px 0; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 600; font-size: 14.5px; cursor: pointer;">Reset</button>
                        <button type="submit" style="flex: 1; padding: 12px 0; background: #2563eb; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; font-size: 14.5px; cursor: pointer; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .choice-btn:hover {
        border-color: #2563eb !important;
        background: #eff6ff !important;
    }
    input:focus {
        outline: none;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }
    @media (max-width: 768px) {
        .erp-form-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    function triggerSpareCamera() {
        openErpCamera(function(dataUrl, file) {
            if (dataUrl) {
                document.getElementById("preview").src = dataUrl;
                document.getElementById("preview").style.display = "block";
                document.getElementById("imagePlaceholder").style.display = "none";
                document.getElementById("removeBtn").style.display = "block";
                document.getElementById("camera_image").value = dataUrl;
                document.getElementById("remove_image").value = "0";
            }
            if (file) {
                try {
                    let c = new DataTransfer();
                    c.items.add(file);
                    document.getElementById("img").files = c.files;
                } catch(e) {}
            }
        });
    }

    function preview(e) {
        let file = e.target.files[0];
        if (!file) return;

        document.getElementById("camera_image").value = "";
        document.getElementById("remove_image").value = "0";

        let reader = new FileReader();
        reader.onload = function () {
            document.getElementById("preview").src = reader.result;
            document.getElementById("preview").style.display = "block";
            document.getElementById("imagePlaceholder").style.display = "none";
            document.getElementById("removeBtn").style.display = "block";
        };
        reader.readAsDataURL(file);
    }

    function removeImage() {
        document.getElementById("preview").src = "";
        document.getElementById("preview").style.display = "none";
        document.getElementById("imagePlaceholder").style.display = "block";
        document.getElementById("img").value = "";
        document.getElementById("camera_image").value = "";
        document.getElementById("remove_image").value = "1";
        document.getElementById("removeBtn").style.display = "none";
    }

    function resetFormImage() {
        setTimeout(() => {
            const initialPath = <?= json_encode($picturePath) ?>;
            if (initialPath) {
                document.getElementById("preview").src = initialPath;
                document.getElementById("preview").style.display = "block";
                document.getElementById("imagePlaceholder").style.display = "none";
                document.getElementById("removeBtn").style.display = "block";
                document.getElementById("remove_image").value = "0";
            } else {
                removeImage();
            }
        }, 50);
    }
</script>

<?php include("../includes/footer.php"); ?>

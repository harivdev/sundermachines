<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if (!isset($_GET['id'])) {
    die("Invalid Access");
}

$id = (int) $_GET['id'];

// ================= FETCH =================
$query = "SELECT * FROM machine WHERE id=$id";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    die("Machine Not Found");
}

// ================= UPDATE =================
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name = mysqli_real_escape_string($conn, $_POST['machineName']);
    $type = mysqli_real_escape_string($conn, $_POST['machineType']);
    $active = isset($_POST['active']) ? 1 : 0;
    $assembled = isset($_POST['assembled']) ? 1 : 0;

    $imageName = $data['picture'];

    // IMAGE UPDATE
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

            if (!is_dir("../uploads/machine/")) {
                mkdir("../uploads/machine/", 0777, true);
            }

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                "../uploads/machine/" . $imageName
            );
        }
    }

    $modifiedBy = "System Admin";
    $modifiedOn = date("Y-m-d H:i:s");

    $update = "
    UPDATE machine SET
        machineName='$name',
        machineType='$type',
        active=b'$active',
        assembeldByUs=b'$assembled',
        picture='$imageName',
        modifiedBy='$modifiedBy',
        modifiedOn='$modifiedOn'
    WHERE id=$id
    ";

    mysqli_query($conn, $update);

    echo "<script>alert('Machine Updated Successfully!');window.location='list_machine.php';</script>";
}
?>

<?php include("../includes/header.php"); ?>

<style>
    .machine-form-container {
        max-width: 900px;
        margin: 25px auto;
        padding: 0 15px;
    }
    
    .machine-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        padding: 30px;
    }

    .machine-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
    }

    @media (max-width: 768px) {
        .machine-grid {
            grid-template-columns: 1fr;
        }
    }

    /* IMAGE UPLOAD BOX STYLING */
    .image-upload-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .image-preview-box {
        width: 100%;
        height: 220px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .image-preview-box:hover {
        border-color: #d97706;
        background: #fffdf5;
    }

    .image-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .placeholder-content {
        text-align: center;
        padding: 20px;
        color: #64748b;
    }

    .placeholder-icon {
        font-size: 38px;
        margin-bottom: 8px;
        color: #94a3b8;
    }

    .placeholder-text {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }

    .placeholder-subtext {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .file-input-btn {
        margin-top: 15px;
        width: 100%;
        padding: 10px 16px;
        background: #0f172a;
        color: #ffffff;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .file-input-btn:hover {
        background: #1e293b;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-group-custom label {
        display: block;
        font-weight: 700;
        font-size: 14px;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .form-input-custom {
        width: 100%;
        padding: 11px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-input-custom:focus {
        outline: none;
        border-color: #d97706;
        box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
        cursor: pointer;
    }

    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #d97706;
        cursor: pointer;
    }

    .checkbox-group label {
        margin-bottom: 0;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        color: #334155;
    }

    .btn-actions {
        display: flex;
        gap: 12px;
        margin-top: 25px;
    }

    .btn-save {
        background: #0f172a;
        color: #FDD017;
        border: none;
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-save:hover {
        background: #1e293b;
        color: #ffffff;
    }

    .btn-cancel {
        background: #e2e8f0;
        color: #475569;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-cancel:hover {
        background: #cbd5e1;
        color: #1e293b;
    }
</style>

<div class="machine-form-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #0f172a; font-weight: 800; font-size: 22px;">Edit Machine</h2>
        <a href="list_machine.php" style="text-decoration: none; color: #64748b; font-weight: 600; font-size: 14px;">
            ← Back to Machine List
        </a>
    </div>

    <div class="machine-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="machine-grid">

                <!-- IMAGE UPLOAD SECTION -->
                <div class="image-upload-wrapper">
                    <label style="font-weight: 700; color: #1e293b; font-size: 14px; margin-bottom: 8px; width: 100%;">
                        Machine Photo / Image
                    </label>

                    <?php 
                    $hasImg = !empty($data['picture']) && file_exists("../uploads/machine/" . $data['picture']);
                    $imgSrc = $hasImg ? "../uploads/machine/" . htmlspecialchars($data['picture']) : "";
                    ?>

                    <div class="image-preview-box" onclick="document.getElementById('machineImageInput').click();">
                        <img id="previewImg" src="<?= $imgSrc ?>" style="<?= $hasImg ? 'display: block;' : 'display: none;' ?>">
                        <div id="placeholderBox" class="placeholder-content" style="<?= $hasImg ? 'display: none;' : 'display: block;' ?>">
                            <div class="placeholder-icon">📷</div>
                            <div class="placeholder-text">Upload Machine Photo</div>
                            <div class="placeholder-subtext">Click to select (JPG, PNG, WEBP)</div>
                        </div>
                    </div>

                    <input type="file" id="machineImageInput" name="image" accept="image/*" onchange="previewImage(event)" hidden>

                    <label for="machineImageInput" class="file-input-btn">
                        <span>📁 Change Photo</span>
                    </label>
                    <span id="fileNameDisplay" style="font-size: 12px; color: #64748b; margin-top: 6px; word-break: break-all;"></span>
                </div>

                <!-- FORM FIELDS SECTION -->
                <div>
                    <div class="form-group-custom">
                        <label for="machineName">Machine Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="machineName" name="machineName" class="form-input-custom" value="<?= htmlspecialchars($data['machineName']) ?>" required>
                    </div>

                    <div class="form-group-custom">
                        <label for="machineType">Machine Type</label>
                        <input type="text" id="machineType" name="machineType" class="form-input-custom" value="<?= htmlspecialchars($data['machineType'] ?? '') ?>">
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="assembled" name="assembled" value="1" <?= (ord($data['assembeldByUs'] ?? 0) == 1) ? 'checked' : '' ?>>
                        <label for="assembled">Assembled By Sunder</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="active" name="active" value="1" <?= (ord($data['active'] ?? 0) == 1) ? 'checked' : '' ?>>
                        <label for="active">Active</label>
                    </div>

                    <div class="btn-actions">
                        <button type="submit" class="btn-save">Update Machine</button>
                        <a href="list_machine.php" class="btn-cancel">Cancel</a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        const previewImg = document.getElementById('previewImg');
        const placeholderBox = document.getElementById('placeholderBox');
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                placeholderBox.style.display = 'none';
            }
            reader.readAsDataURL(file);
            fileNameDisplay.textContent = file.name;
        }
    }
</script>

<?php include("../includes/footer.php"); ?>
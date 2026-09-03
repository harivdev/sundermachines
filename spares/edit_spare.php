<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if (!isset($_GET['id'])) {
    die("Invalid Access");
}

$id = (int) $_GET['id'];

// ================= FETCH DATA =================
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

// ================= UPDATE =================
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name = mysqli_real_escape_string($conn, $_POST['spareName']);
    $part = mysqli_real_escape_string($conn, $_POST['partNo']);
    $rack = mysqli_real_escape_string($conn, $_POST['rackNumber']);
    $active = isset($_POST['active']) ? 1 : 0;

    $imageName = $data['picture'];

    // ===== IMAGE UPDATE =====
    if (!empty($_FILES['image']['name'])) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {

            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                "../uploads/spares/" . $imageName
            );
        }
    }

    // ================= UPDATE QUERY =================
    $update = "
    UPDATE spares SET
        spareName='$name',
        partNo='$part',
        rackNumber='$rack',
        active=b'$active',
        picture='$imageName'
    WHERE id=$id
    ";

    mysqli_query($conn, $update);

    echo "<script>alert('Updated Successfully');window.location='list_spare.php';</script>";
}
?>

<?php include("../includes/header.php"); ?>

<style>
    body {
        background: #f1f5f9;
        font-family: sans-serif;
    }

    .container {
        width: 95%;
        margin: auto;
    }

    .card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
    }

    .grid {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }

        .row {
            display: flex !important;
            gap: 12px !important;
        }

        .col {
            flex: 1 !important;
            min-width: 0 !important;
        }
    }

    .input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        margin-bottom: 10px;
        box-sizing: border-box;
    }

    .row {
        display: flex;
        gap: 15px;
    }

    .col {
        flex: 1;
    }

    img {
        width: 100%;
        border-radius: 10px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .primary {
        background: #2563eb;
        color: #fff;
    }

    .gray {
        background: #64748b;
        color: #fff;
    }
</style>

<div class="page-main-container erp-container" style="padding: 20px;">

    <h2 style="margin-bottom: 20px; color: #1e293b; font-weight: 700;">Spare Info</h2>

    <div class="card" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">

        <form method="POST" enctype="multipart/form-data">

            <div class="grid">

                <!-- IMAGE -->
                <div style="text-align: center;">
                    <?php if (!empty($picturePath)): ?>
                        <img src="<?= htmlspecialchars($picturePath) ?>" style="max-height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; margin-bottom: 12px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 180px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px; margin-bottom: 12px;">
                            <span style="font-size: 32px; margin-bottom: 6px;">🖼️</span>
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="image" style="width: 100%; font-size: 13px;">
                </div>

                <!-- FORM -->
                <div>

                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155;">Spare Name *</label>
                    <input type="text" name="spareName" class="input" value="<?= htmlspecialchars($data['spareName']) ?>" required>

                    <div class="row" style="display: flex; gap: 12px; margin-bottom: 10px;">
                        <div class="col" style="flex: 1;">
                            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155;">Part # *</label>
                            <input type="text" name="partNo" class="input" value="<?= htmlspecialchars($data['partNo']) ?>" required>
                        </div>

                        <div class="col" style="flex: 1;">
                            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155;">Rack # *</label>
                            <input type="text" name="rackNumber" class="input" value="<?= htmlspecialchars($data['rackNumber']) ?>" required>
                        </div>
                    </div>

                    <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: #334155; margin-bottom: 15px;">
                        <input type="checkbox" name="active" <?= $data['active'] ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        Active
                    </label>

                    <div style="display: flex; gap: 12px; margin-top: 15px;">
                        <button type="submit" class="btn primary" style="flex: 1; padding: 12px 0; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Submit</button>
                        <button type="reset" class="btn gray" style="flex: 1; padding: 12px 0; background: #64748b; color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Reset</button>
                    </div>

                </div>

            </div>

        </form>

    </div>
</div>

<?php include("../includes/footer.php"); ?>
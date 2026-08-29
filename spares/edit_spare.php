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

    .input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        margin-bottom: 10px;
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

<div class="container">

    <h2>Spare Info</h2>

    <div class="card">

        <form method="POST" enctype="multipart/form-data">

            <div class="grid">

                <!-- IMAGE -->
                <div>
                    <?php if (!empty($picturePath)): ?>
                        <img src="<?= htmlspecialchars($picturePath) ?>" style="max-height: 250px; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%;">
                    <?php else: ?>
                        <div style="width: 100%; height: 200px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px;">
                            <span style="font-size: 32px; margin-bottom: 6px;">🖼️</span>
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>

                    <br><br>
                    <input type="file" name="image">
                </div>

                <!-- FORM -->
                <div>

                    <label>Spare Name *</label>
                    <input type="text" name="spareName" class="input" value="<?= $data['spareName'] ?>" required>

                    <div class="row">
                        <div class="col">
                            <label>Part # *</label>
                            <input type="text" name="partNo" class="input" value="<?= $data['partNo'] ?>" required>
                        </div>

                        <div class="col">
                            <label>Rack # *</label>
                            <input type="text" name="rackNumber" class="input" value="<?= $data['rackNumber'] ?>"
                                required>
                        </div>
                    </div>

                    <label>
                        <input type="checkbox" name="active" <?= $data['active'] ? 'checked' : '' ?>>
                        Active
                    </label>

                    <br><br>

                    <button class="btn primary">Submit</button>
                    <button type="reset" class="btn gray">Reset</button>

                </div>

            </div>

        </form>

    </div>
</div>

<?php include("../includes/footer.php"); ?>
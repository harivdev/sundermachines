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
    $active = isset($_POST['active']) ? 1 : 0;
    $assembled = isset($_POST['assembled']) ? 1 : 0;

    $imageName = $data['picture'];

    // IMAGE UPDATE
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                "../uploads/machine/" . $imageName
            );
        }
    }

    $modifiedBy = 1;
    $modifiedOn = date("Y-m-d H:i:s");

    $update = "
    UPDATE machine SET
        machineName='$name',
        active=b'$active',
        assembeldByUs=b'$assembled',
        picture='$imageName',
        modifiedBy='$modifiedBy',
        modifiedOn='$modifiedOn'
    WHERE id=$id
    ";

    mysqli_query($conn, $update);

    echo "<script>alert('Machine Updated');window.location='list_machine.php';</script>";
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
    }

    .toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
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

    <h2>Machine Info</h2>

    <div class="card">

        <form method="POST" enctype="multipart/form-data">

            <div class="grid">

                <!-- IMAGE -->
                <div>
                    <?php if (!empty($data['picture']) && file_exists("../uploads/machine/" . $data['picture'])): ?>
                        <img id="preview" src="../uploads/machine/<?= $data['picture'] ?>">
                    <?php else: ?>
                        <img id="preview" src="../uploads/no-image.png">
                    <?php endif; ?>

                    <br><br>
                    <input type="file" name="image" onchange="previewImage(event)">
                </div>

                <!-- FORM -->
                <div>

                    <label>Machine Name *</label>
                    <input type="text" name="machineName" class="input" value="<?= $data['machineName'] ?>" required>

                    <div class="toggle">
                        <input type="checkbox" name="assembled" <?= $data['assembeldByUs'] ? 'checked' : '' ?>>
                        <label>Assembled By Sunder</label>
                    </div>

                    <div class="toggle">
                        <input type="checkbox" name="active" <?= $data['active'] ? 'checked' : '' ?>>
                        <label>Active</label>
                    </div>

                    <br>

                    <button class="btn primary">Submit</button>
                    <button type="reset" class="btn gray">Reset</button>

                </div>

            </div>

        </form>

    </div>
</div>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function () {
            document.getElementById('preview').src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

<?php include("../includes/footer.php"); ?>
<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();
include("../includes/header.php");

// Handle Actions (Add / Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $brandName = mysqli_real_escape_string($conn, $_POST['brandName']);
        $now = date('Y-m-d H:i:s');
        $user = "System Admin";

        if ($_POST['action'] == 'add' && !empty($brandName)) {
            // CHECK FOR DUPLICATE
            $check = mysqli_query($conn, "SELECT id FROM brand WHERE brandName = '$brandName'");
            if (mysqli_num_rows($check) > 0) {
                echo "<script>alert('Error: This brand name already exists!'); window.location.href='list.php';</script>";
                exit();
            }
            $sql = "INSERT INTO brand (brandName, createdBy, createdOn, modifiedBy, modifiedOn) VALUES ('$brandName', '$user', '$now', '$user', '$now')";
            mysqli_query($conn, $sql);
        } elseif ($_POST['action'] == 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            // CHECK FOR DUPLICATE (excluding current ID)
            $check = mysqli_query($conn, "SELECT id FROM brand WHERE brandName = '$brandName' AND id != $id");
            if (mysqli_num_rows($check) > 0) {
                echo "<script>alert('Error: Another brand with this name already exists!'); window.location.href='list.php';</script>";
                exit();
            }
            $sql = "UPDATE brand SET brandName = '$brandName', modifiedBy = '$user', modifiedOn = '$now' WHERE id = $id";
            mysqli_query($conn, $sql);
        }
        // Redirect to avoid form resubmission
        echo "<script>window.location.href='list.php';</script>";
        exit();
    }
}

// Fetch Brands
$brands = mysqli_query($conn, "SELECT * FROM brand ORDER BY id ASC");
?>

<div class="page-main-container erp-container" style="max-width: 1000px; width: 100%; margin: 0 auto; padding: 20px;">
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Brands</div>
        <div class="erp-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="focusAddBrandField()" style="background: #2563eb; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">➕ New Brand</button>
        </div>
    </div>

    <div class="erp-table-box" style="overflow-x: auto; width: 100%;">
        <table class="erp-table master-table" style="width: 100%; min-width: 0;">
            <thead>
                <tr>
                    <th style="width: 45px; padding: 12px 10px; text-align: center;">#</th>
                    <th style="padding: 12px 10px;">Brand Name</th>
                    <th style="text-align: right; width: 100px; padding: 12px 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                while ($brand = mysqli_fetch_assoc($brands)): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;"
                        onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                        <td style="padding: 10px 10px; color: #64748b; font-size: 13.5px; font-weight: 600; width: 45px; text-align: center;"><?= $i++ ?></td>
                        <td colspan="2" style="padding: 8px 12px;">
                            <form action="list.php" method="POST"
                                style="display: flex; gap: 8px; width: 100%; align-items: center;">
                                <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="text" name="brandName" value="<?= htmlspecialchars($brand['brandName']) ?>" required
                                    style="flex: 1; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13.5px; background: #fff; box-sizing: border-box;">
                                <button type="submit"
                                    style="background: #475569; color: #fff; border: none; padding: 7px 16px; border-radius: 6px; font-weight: 600; font-size: 12.5px; cursor: pointer; flex-shrink: 0; white-space: nowrap;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr id="addBrandSection" style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <td style="padding: 10px 10px; color: #2563eb; font-size: 13px; font-weight: 700; width: 45px; text-align: center;">Add</td>
                    <td colspan="2" style="padding: 8px 12px;">
                        <form action="list.php" method="POST" style="display: flex; gap: 8px; width: 100%; align-items: center;">
                            <input type="hidden" name="action" value="add">
                            <input type="text" name="brandName" id="newBrandInput" placeholder="Brand Name" required
                                style="flex: 1; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13.5px; background: #fff; box-sizing: border-box; transition: all 0.3s ease;">
                            <button type="submit"
                                style="background: #2563eb; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; flex-shrink: 0; white-space: nowrap; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">Add</button>
                        </form>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
function focusAddBrandField() {
    const input = document.getElementById('newBrandInput');
    if (input) {
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
            input.focus();
            input.style.borderColor = '#f97316';
            input.style.boxShadow = '0 0 0 4px rgba(249, 115, 22, 0.25)';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('focus') === 'add' || window.location.hash === '#add') {
        focusAddBrandField();
    }
});
</script>

<style>
    input:focus {
        outline: none;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }

    button:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }

    button:active {
        transform: translateY(0);
    }
</style>

<?php include("../includes/footer.php"); ?>
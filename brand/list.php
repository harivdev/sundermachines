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

<div class="erp-container" style="max-width: 1000px; width: 100%; margin: 0 auto;">
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Brands</div>
        <div class="erp-header-actions">
            <button type="button" onclick="openAddModal()" class="btn-erp btn-erp-new">+ New Brand</button>
        </div>
    </div>

    <div class="erp-table-box" style="overflow-x: auto;">
        <table class="erp-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 60px; padding: 12px 15px;">#</th>
                    <th style="padding: 12px 15px;">Brand Name</th>
                    <th style="text-align: right; width: 140px; padding: 12px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                while ($brand = mysqli_fetch_assoc($brands)): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;"
                        onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                        <td style="padding: 12px 15px; color: #64748b; font-size: 14px; width: 60px;"><?= $i++ ?></td>
                        <td colspan="2" style="padding: 12px 20px;">
                            <!-- Form for each row -->
                            <form action="list.php" method="POST"
                                style="display: flex; gap: 15px; width: 100%; align-items: center;">
                                <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="text" name="brandName" value="<?= htmlspecialchars($brand['brandName']) ?>"
                                    required
                                    style="flex: 1; min-width: 0; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 14px; background: #fff; transition: 0.2s;">
                                <button type="submit"
                                    style="background: #64748b; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; flex-shrink: 0; white-space: nowrap;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- ADD NEW BRAND (Footer-like row) -->
        <div style="background: #f8fafc; padding: 20px; border-top: 2px solid #e2e8f0;">
            <form action="list.php" method="POST" style="display: flex; gap: 15px; width: 100%; align-items: center;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="brandName" placeholder="Brand Name" required
                    style="flex: 1; min-width: 0; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 12px 15px; font-size: 14px; background: #fff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                <button type="submit"
                    style="background: #2563eb; color: #fff; border: none; padding: 12px 35px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s; flex-shrink: 0; white-space: nowrap; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">Add</button>
            </form>
        </div>
    </div>
</div>

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
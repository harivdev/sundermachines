<?php require_once("../config/db.php"); ?>
<?php require_once("../includes/auth.php"); ?>
<?php requireAdmin(); ?>
<?php include("../includes/header.php"); ?>

<div class="erp-container">
    <div class="erp-header-bar" style="margin-bottom: 20px; border-bottom: 1px solid var(--erp-border);">
        <div class="erp-header-title">Add Spare Part</div>
        <div class="erp-header-actions">
            <a href="list_spare.php" class="btn-erp btn-erp-secondary">📋 Spare List</a>
        </div>
    </div>

    <div class="erp-card">
        <form method="POST" action="insert_spare.php" enctype="multipart/form-data" id="spareForm">
            <div class="erp-form-grid" style="grid-template-columns: 220px 1fr;">
                <!-- IMAGE -->
                <div>
                    <div class="image-box" onclick="openImageOptions()"
                        style="width:100%; height:180px; border:2px dashed #cbd5e1; display:flex; flex-direction:column; justify-content:center; align-items:center; cursor:pointer; background:#f8fafc; border-radius:12px; overflow:hidden; position:relative;">
                        <img id="preview" style="max-width:100%; max-height:100%; object-fit:contain; display:none;">
                        <div id="imagePlaceholder" style="text-align:center; color:#64748b;">
                            <span style="font-size:32px; display:block;">🖼️</span>
                            <span style="font-size:12px; font-weight:600;">No Image Selected</span>
                        </div>
                    </div>

                    <!-- CHOICE BUTTONS -->
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
                        style="display:none; width:100%; margin-top:10px; padding:10px; border:none; border-radius:8px; background:#fee2e2; color:#ef4444; font-weight:700; font-size:13px; cursor:pointer;">Delete
                        Image</button>

                    <!-- Hidden Inputs -->
                    <input type="file" name="image" id="img" accept="image/*" capture="environment" hidden
                        onchange="preview(event)">
                    <input type="hidden" name="camera_image" id="camera_image">
                </div>

                <!-- FORM -->
                <div>
                    <div class="erp-form-group">
                        <label class="erp-label">Spare Name <span class="req">*</span></label>
                        <input type="text" name="spareName" required class="erp-input" placeholder="Spare Name">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-label">Part # / Barcode <span class="req">*</span></label>
                        <input type="text" name="partNo" id="sparePartNoInput" required class="erp-input" placeholder="Part Number or Barcode">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-label">Rack #</label>
                        <input type="text" name="rackNumber" class="erp-input" placeholder="Rack Number">
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-label" style="display:inline-flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="active" checked style="width:auto;"> Active
                        </label>
                    </div>

                    <div style="margin-top:24px; display:flex; gap:12px;">
                        <button type="submit" class="btn-erp btn-erp-primary" style="flex: 1; padding: 12px 0; text-align: center; justify-content: center;">Submit Spare</button>
                        <button type="reset" class="btn-erp btn-erp-secondary" onclick="resetFormImage()" style="flex: 1; padding: 12px 0; text-align: center; justify-content: center;">Reset</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



<style>
    .container {
        width: 90%;
        max-width: 800px;
        margin: auto;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .row {
        display: flex;
        align-items: flex-start;
    }

    h2 {
        margin-bottom: 25px;
        color: #1e293b;
        font-weight: 700;
    }

    .choice-btn:hover {
        border-color: #2563eb !important;
        background: #eff6ff !important;
    }
</style>

<script>
    let stream = null;

    function triggerSpareCamera() {
        openErpCamera(function(dataUrl, file) {
            if (dataUrl) {
                document.getElementById("preview").src = dataUrl;
                document.getElementById("preview").style.display = "block";
                document.getElementById("imagePlaceholder").style.display = "none";
                document.getElementById("removeBtn").style.display = "block";
                document.getElementById("camera_image").value = dataUrl;
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

    function openImageOptions() {
        triggerSpareCamera();
    }

    function preview(e) {
        let file = e.target.files[0];
        if (!file) return;

        document.getElementById("camera_image").value = "";

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
        document.getElementById("removeBtn").style.display = "none";
    }

    function resetFormImage() {
        setTimeout(() => {
            removeImage();
        }, 50);
    }
</script>

<?php include("../includes/footer.php"); ?>
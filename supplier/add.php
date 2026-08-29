<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();
include("../includes/header.php");
?>

<div style="padding: 20px; background: #f8fafc; min-height: calc(100vh - 110px);">

    <!-- HEADER BAR -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;">Supplier Info</h3>
        <a href="list.php"
            style="text-decoration: none; color: #1e293b; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
            <span>☰</span> Suppliers
        </a>
    </div>

    <!-- MAIN CONTENT CARD -->
    <div
        style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 30px; border: 1px solid #e2e8f0;">

        <form action="insert.php" method="POST">

            <!-- SECTION 1: SUPPLIER INFO -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                <!-- ACTIVE TOGGLE -->
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <div class="switch-container">
                        <input type="checkbox" name="active" checked id="activeSwitch" hidden>
                        <div class="switch-slider"></div>
                    </div>
                    <span style="font-weight: 700; color: #64748b; font-size: 13px;">Active</span>
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group" style="grid-column: span 1;">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" required placeholder="">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 20px; margin-bottom: 25px;">
                <div class="form-group">
                    <label>Phone # Primary<span class="required">*</span></label>
                    <input type="text" name="phoneNo1" required placeholder="">
                </div>
                <div class="form-group">
                    <label>Phone # WhatsApp</label>
                    <input type="text" name="whatsAppNo" placeholder="">
                </div>
                <div class="form-group">
                    <label>Email ID</label>
                    <input type="email" name="emailId" placeholder="">
                </div>
            </div>

            <!-- SECTION 2: ADDRESS -->
            <div style="margin-top: 40px; margin-bottom: 20px; border-top: 1px solid #f1f5f9; padding-top: 25px;">
                <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 18px; font-weight: 700;">Address</h3>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Line 1 <span class="required">*</span></label>
                    <input type="text" name="line1" required placeholder="">
                </div>
                <div class="form-group">
                    <label>Line 2</label>
                    <input type="text" name="line2" placeholder="">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>City <span class="required">*</span></label>
                    <input type="text" name="city" required placeholder="">
                </div>
                <div class="form-group">
                    <label>Zip Code <span class="required">*</span></label>
                    <input type="text" name="zipCode" required placeholder="">
                </div>
            </div>

            <!-- ACTIONS -->
            <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="prime-btn" style="background: #2563eb;">Submit</button>
                <button type="reset" class="prime-btn" style="background: #64748b;">Reset</button>
            </div>

        </form>
    </div>
</div>

<style>
    /* TYPOGRAPHY */
    .form-group label {
        display: block;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .required {
        color: #ef4444;
    }

    /* INPUTS */
    .form-group input {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        color: #334155;
        background: #fff;
        transition: 0.2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* GRID LAYOUTS */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    /* SWITCH TOGGLE */
    .switch-container {
        width: 44px;
        height: 22px;
        position: relative;
    }

    .switch-slider {
        position: absolute;
        inset: 0;
        background: #e2e8f0;
        border-radius: 30px;
        transition: 0.3s;
    }

    .switch-slider::before {
        content: "";
        position: absolute;
        height: 16px;
        width: 16px;
        left: 3px;
        top: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    #activeSwitch:checked+.switch-slider {
        background: #6366f1;
    }

    #activeSwitch:checked+.switch-slider::before {
        transform: translateX(22px);
    }

    /* BUTTONS */
    .prime-btn {
        padding: 10px 25px;
        border: none;
        border-radius: 6px;
        color: white;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: 0.2s;
    }

    .prime-btn:hover {
        opacity: 0.9;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include("../includes/footer.php"); ?>
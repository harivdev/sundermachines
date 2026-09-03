<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();
include("../includes/header.php");
?>

<div class="page-main-container erp-container" style="padding: 30px 20px; background: #f8fafc; min-height: calc(100vh - 110px); display: flex; justify-content: center;">

    <div style="width: 100%; max-width: 820px;">

        <!-- HEADER BAR -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1e293b; font-size: 22px; font-weight: 700;">Add New Supplier</h3>
            <a href="list.php"
                style="text-decoration: none; color: #475569; font-weight: 700; font-size: 13.5px; display: inline-flex; align-items: center; gap: 6px; background: #fff; padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <span>☰</span> Supplier List
            </a>
        </div>

        <!-- MAIN CONTENT CARD -->
        <div style="background: #fff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); padding: 35px 40px; border: 1px solid #e2e8f0;">

            <form action="insert.php" method="POST">

                <!-- SECTION 1: SUPPLIER INFO HEADER & ACTIVE TOGGLE -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                    <span style="font-size: 16px; font-weight: 700; color: #1e293b;">Supplier Information</span>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <div class="switch-container">
                            <input type="checkbox" name="active" checked id="activeSwitch" hidden>
                            <div class="switch-slider"></div>
                        </div>
                        <span style="font-weight: 700; color: #64748b; font-size: 13px;">Active</span>
                    </label>
                </div>

                <!-- NAME FIELD (FULL WIDTH) -->
                <div class="form-group" style="margin-bottom: 22px;">
                    <label>Supplier Name <span class="required">*</span></label>
                    <input type="text" name="name" required placeholder="Enter supplier or business name">
                </div>

                <!-- CONTACT FIELDS (3 EQUAL COLUMNS) -->
                <div class="contact-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Phone # Primary <span class="required">*</span></label>
                        <input type="text" name="phoneNo1" required placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label>Phone # WhatsApp</label>
                        <input type="text" name="whatsAppNo" placeholder="WhatsApp number">
                    </div>
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="emailId" placeholder="supplier@example.com">
                    </div>
                </div>

                <!-- SECTION 2: ADDRESS -->
                <div style="margin-top: 35px; margin-bottom: 22px; border-top: 2px solid #f1f5f9; padding-top: 22px;">
                    <h3 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">Address Details</h3>
                </div>

                <!-- ADDRESS LINES (FULL WIDTH EACH) -->
                <div class="form-group" style="margin-bottom: 18px;">
                    <label>Address Line 1 <span class="required">*</span></label>
                    <input type="text" name="line1" required placeholder="Street address, building, door no.">
                </div>

                <div class="form-group" style="margin-bottom: 22px;">
                    <label>Address Line 2</label>
                    <input type="text" name="line2" placeholder="Suite, unit, landmark (optional)">
                </div>

                <!-- CITY & ZIP CODE (2 EQUAL COLUMNS) -->
                <div class="location-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 35px;">
                    <div class="form-group">
                        <label>City <span class="required">*</span></label>
                        <input type="text" name="city" required placeholder="City name">
                    </div>
                    <div class="form-group">
                        <label>Zip / Pincode <span class="required">*</span></label>
                        <input type="text" name="zipCode" required placeholder="Pincode / Postal code">
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div style="margin-top: 35px; border-top: 1px solid #f1f5f9; padding-top: 25px; display: flex; justify-content: flex-end; gap: 12px;">
                    <a href="list.php" class="prime-btn" style="background: #e2e8f0; color: #475569; text-decoration: none; display: inline-flex; align-items: center;">Cancel</a>
                    <button type="reset" class="prime-btn" style="background: #94a3b8; color: #fff;">Reset</button>
                    <button type="submit" class="prime-btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff;">Submit Supplier</button>
                </div>

            </form>
        </div>
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
        height: 44px;
        padding: 0 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        color: #334155;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
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
        background: #cbd5e1;
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
        background: #2563eb;
    }

    #activeSwitch:checked+.switch-slider::before {
        transform: translateX(22px);
    }

    /* BUTTONS */
    .prime-btn {
        padding: 11px 26px;
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: opacity 0.2s, transform 0.1s;
    }

    .prime-btn:hover {
        opacity: 0.92;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .contact-grid, .location-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php include("../includes/footer.php"); ?>
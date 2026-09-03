<?php
require_once("../config/db.php");
include("../includes/header.php");

$salesId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($salesId <= 0) {
    echo "<script>alert('Invalid Sales ID'); window.location.href='list.php';</script>";
    exit;
}

// Fetch sales info
$sQuery = "
    SELECT s.*, c.name as customer_name, c.phoneNo1, c.whatsAppNo 
    FROM sales s 
    LEFT JOIN customer c ON s.customer = c.id 
    WHERE s.id = $salesId
";
$sRes = mysqli_query($conn, $sQuery);
if (!$sRes || mysqli_num_rows($sRes) == 0) {
    echo "<script>alert('Sales Order not found'); window.location.href='list.php';</script>";
    exit;
}
$sale = mysqli_fetch_assoc($sRes);

// Fetch items
$iQuery = "SELECT * FROM salesitems WHERE sales = $salesId AND deleted = 0";
$iRes = mysqli_query($conn, $iQuery);
$items = [];
while ($row = mysqli_fetch_assoc($iRes)) {
    $items[] = $row;
}
?>

<div class="page-main-container erp-container" style="padding: 20px; background: #f8fafc; min-height: calc(100vh - 110px); display: flex; justify-content: center;">
    
    <div style="width: 100%; max-width: 900px;">
        <!-- ACTION BAR -->
        <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <a href="list.php" style="text-decoration: none; color: #475569; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>←</span> Back to List
            </a>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.open('print_receipt.php?id=<?= $salesId ?>', '_blank')" style="background: #198754; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <span>🧾</span> Thermal Receipt
                </button>
                <button onclick="window.print()" style="background: #1e293b; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <span>🖨️</span> Print Invoice (A4)
                </button>
            </div>
        </div>

        <!-- INVOICE CARD -->
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; padding: 40px;">
            
            <!-- INVOICE HEADER -->
            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 30px; margin-bottom: 30px;">
                <div>
                    <h1 style="margin: 0; color: #1e293b; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">INVOICE</h1>
                    <div style="color: #64748b; font-weight: 600; margin-top: 5px;">#<?= htmlspecialchars($sale['orderNo']) ?></div>
                    <div style="margin-top: 15px; font-size: 14px; line-height: 1.6; color: #475569;">
                        <strong>Date:</strong> <?= date('d M Y', strtotime($sale['orderDate'])) ?><br>
                        <strong>Status:</strong> 
                        <span style="font-weight: 700; color: <?= $sale['orderStatus'] == 'Completed' ? '#16a34a' : '#ea580c' ?>;">
                            <?= htmlspecialchars($sale['orderStatus']) ?>
                        </span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 24px; font-weight: 800; color: #16a34a;">Sunder Billing</div>
                    <div style="color: #64748b; font-size: 13px; line-height: 1.6; margin-top: 10px;">
                        123 Tech Street, IT Park<br>
                        Coimbatore, TN, 641001<br>
                        Ph: +91 9876543210
                    </div>
                </div>
            </div>

            <!-- CUSTOMER DETAILS -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Bill To</h3>
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong style="color: #1e293b; font-size: 18px;"><?= htmlspecialchars($sale['customer_name'] ?: 'Guest / Cash') ?></strong>
                    <div style="color: #475569; font-size: 14px; margin-top: 8px;">
                        <?php if (!empty($sale['phoneNo1'])): ?>
                            <strong>Phone:</strong> <?= htmlspecialchars($sale['phoneNo1']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($sale['whatsAppNo'])): ?>
                            <strong>WhatsApp:</strong> <?= htmlspecialchars($sale['whatsAppNo']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ITEMS TABLE -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="background: #1e293b; color: #fff;">
                        <th style="padding: 12px 15px; text-align: left; font-size: 13px; text-transform: uppercase; border-radius: 6px 0 0 6px;">#</th>
                        <th style="padding: 12px 15px; text-align: left; font-size: 13px; text-transform: uppercase;">Item Description</th>
                        <th style="padding: 12px 15px; text-align: center; font-size: 13px; text-transform: uppercase;">Qty</th>
                        <th style="padding: 12px 15px; text-align: right; font-size: 13px; text-transform: uppercase;">Rate</th>
                        <th style="padding: 12px 15px; text-align: center; font-size: 13px; text-transform: uppercase;">GST %</th>
                        <th style="padding: 12px 15px; text-align: right; font-size: 13px; text-transform: uppercase; border-radius: 0 6px 6px 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    $subtotal = 0;
                    $totalGst = 0;
                    foreach ($items as $item): 
                        $qty = intval($item['quantity'] ?? 1);
                        $rate = floatval($item['pricePerQty'] ?? 0);
                        $gstVal = floatval($item['gstValue'] ?? 0);
                        $lineTotal = floatval($item['totalPrice'] ?? 0);
                        
                        $lineBase = $qty * $rate;
                        $subtotal += $lineBase;
                        $totalGst += $gstVal;
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 15px; color: #64748b; font-size: 14px;"><?= $counter++ ?></td>
                        <td style="padding: 15px; color: #1e293b; font-weight: 600; font-size: 14px;">
                            <?= htmlspecialchars($item['itemName']) ?>
                            <?php if(!empty($item['serialNo'])): ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: 400; margin-top: 4px;">S/N: <?= htmlspecialchars($item['serialNo']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; text-align: center; color: #475569; font-size: 14px;"><?= $qty ?></td>
                        <td style="padding: 15px; text-align: right; color: #475569; font-size: 14px;">₹<?= number_format(round($rate), 0) ?></td>
                        <td style="padding: 15px; text-align: center; color: #475569; font-size: 14px;"><?= htmlspecialchars($item['gstPercentage'] ?? 0) ?>%</td>
                        <td style="padding: 15px; text-align: right; color: #1e293b; font-weight: 700; font-size: 14px;">₹<?= number_format(round($lineTotal), 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TOTALS -->
            <div style="display: flex; justify-content: flex-end; padding-top: 20px;">
                <div style="width: 350px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; color: #475569; font-size: 15px;">
                        <span>Subtotal</span>
                        <span style="font-weight: 600;">₹<?= number_format(round($subtotal), 0) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; color: #475569; font-size: 15px; border-bottom: 1px solid #e2e8f0;">
                        <span>Total GST</span>
                        <span style="font-weight: 600;">₹<?= number_format(round($totalGst), 0) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 15px 0; color: #1e293b; font-size: 20px; font-weight: 800;">
                        <span>Grand Total</span>
                        <span style="color: #16a34a;">₹<?= number_format(round($sale['actualAmountSum']), 0) ?></span>
                    </div>
                    
                    <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #475569; font-size: 14px;">
                            <span>Paid Amount</span>
                            <span style="font-weight: 700;">₹<?= number_format(round($sale['paidAmountSum']), 0) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #b91c1c; font-size: 15px; font-weight: 700;">
                            <span>Balance Due</span>
                            <span>₹<?= number_format(round($sale['actualAmountSum'] - $sale['paidAmountSum']), 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div style="margin-top: 50px; padding-top: 20px; border-top: 2px dashed #e2e8f0; text-align: center; color: #64748b; font-size: 13px;">
                Thank you for your business!<br>
                For any queries regarding this invoice, please contact support.
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    body { background: #fff !important; padding: 0 !important; }
    .no-print, .topbar, .menu-container { display: none !important; }
    .footer { display: none !important; }
    
    table th { background: #f1f5f9 !important; color: #1e293b !important; -webkit-print-color-adjust: exact; }
}
</style>

<?php include("../includes/footer.php"); ?>

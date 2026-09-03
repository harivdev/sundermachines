<?php
require_once("../config/db.php");
include("../includes/header.php");

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (j.cardNo LIKE '%$safeSearch%' OR c.name LIKE '%$safeSearch%' OR c.phoneNo1 LIKE '%$safeSearch%' OR m.machineName LIKE '%$safeSearch%')";
}

if ($statusFilter !== '') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND j.jobStatus = '$safeStatus'";
}

$countQuery = "
    SELECT COUNT(DISTINCT j.id) AS total
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    LEFT JOIN jobcarditems ji ON j.id = ji.jobCard
    LEFT JOIN machine m ON ji.machine = m.id
    $where
";
$countResult = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$query = "
    SELECT 
        j.id,
        j.cardNo,
        j.jobStatus,
        j.givenDate,
        j.completed,
        j.completedDate,
        j.delivered,
        j.deliveryDate,
        j.laborCharge,
        j.actualAmountSum,
        j.receivedAmountSum,
        j.modifiedOn,
        c.name AS customerName,
        c.phoneNo1,
        emp.name AS allocatedTo,
        MAX(m.machineName) AS machineName
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    LEFT JOIN employee emp ON j.employee = emp.id
    LEFT JOIN jobcarditems ji ON j.id = ji.jobCard
    LEFT JOIN machine m ON ji.machine = m.id
    $where
    GROUP BY j.id, j.cardNo, j.jobStatus, j.givenDate, j.completed, j.completedDate, j.delivered, j.deliveryDate, j.laborCharge, j.actualAmountSum, j.receivedAmountSum, j.modifiedOn, c.name, c.phoneNo1, emp.name
    ORDER BY j.id DESC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching job cards: " . mysqli_error($conn));
}

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div class="page-main-container erp-container" style="padding: 20px; background: #f8fafc; min-height: calc(100vh - 110px);">
    
    <!-- HEADER BAR -->
    <div class="list-header-bar" style="background: #ffffff; display: flex; align-items: center; justify-content: space-between; border-radius: 8px 8px 0 0; padding: 15px 20px; border: 1px solid #e2e8f0; border-bottom: none; flex-wrap: wrap; gap: 10px;">
        <div class="list-header-title" style="color: #1e293b; font-weight: 700; font-size: 20px;">Job Card Bills</div>
        <div class="list-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="create.php" style="background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); transition: all 0.15s ease;">
                <span style="font-size: 13px;">➕</span> New
            </a>
            <a href="javascript:void(0)" onclick="location.reload()" style="background: #475569; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2); transition: all 0.15s ease;">
                <span style="font-size: 13px;">🔄</span> Refresh
            </a>
            <a href="print_summary.php?<?= http_build_query($_GET) ?>" target="_blank" style="background: #16a34a; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2); transition: all 0.15s ease;">
                <span style="font-size: 13px;">📄</span> Print A4 Summary
            </a>
            <a href="javascript:void(0)" onclick="document.getElementById('jobcardFilter').style.display = document.getElementById('jobcardFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2); transition: all 0.15s ease;">
                <span style="font-size: 13px;">🔽</span> Filter
            </a>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="jobcardFilter" style="display:<?= ($search !== '' || $statusFilter !== '') ? 'block' : 'none' ?>; background:#ffffff; padding:15px 20px; border:1px solid #e2e8f0; border-bottom:none;">
        <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Card#, customer, phone..." style="padding:8px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; width:220px;">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Status</label>
                <select name="status" style="padding:8px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px;">
                    <option value="">-- All --</option>
                    <option value="New Job" <?= $statusFilter === 'New Job' ? 'selected' : '' ?>>New Job</option>
                    <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            <div>
                <button type="submit" style="background:#2563eb; color:#fff; padding:8px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer;">Apply</button>
                <a href="list.php" style="background:#f59e0b; color:#fff; padding:8px 16px; border-radius:5px; font-weight:600; text-decoration:none; display:inline-block;">Clear</a>
            </div>
        </form>
    </div>

    <!-- MAIN LIST -->
    <div style="background: #fff; border-radius: 0 0 12px 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); padding: 0; border: 1px solid #e2e8f0; overflow-x: auto;">
        
        <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 1350px; white-space: nowrap;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">#</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Category</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Job Card #</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Status</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Allocated To</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Machine</th>
                    <th colspan="2" style="text-align: center; padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b;">Customer</th>
                    <th colspan="3" style="text-align: center; padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b;">Date</th>
                    <th colspan="3" style="text-align: center; padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #dcfce7; border-left: 1px solid #e2e8f0;">Amount</th>
                    <th rowspan="2" style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; text-align: center;">Action</th>
                </tr>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; border-right: 1px solid #e2e8f0;">Name</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b;">Contact #</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; border-right: 1px solid #e2e8f0;">Given</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #bfdbfe; border-right: 1px solid #e2e8f0;">Completed</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #bfdbfe;">Delivered</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #dcfce7; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">Labour</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #dcfce7; border-right: 1px solid #e2e8f0;">Billed</th>
                    <th style="padding: 12px 15px; border-bottom: 2px solid #e2e8f0; font-size: 14px; color: #1e293b; background: #86efac; font-weight: 700;">Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($totalRows > 0):
                    $counter = $offset + 1;
                    while($row = mysqli_fetch_assoc($result)): 
                        $rowBg = ($counter % 2 == 0) ? '#f8fafc' : '#ffffff';
                        
                        // Format Card No without slashes or spaces
                        $cleanCardNo = str_replace(['/', ' '], '', $row['cardNo'] ?? '');

                        // Format Dates
                        $givenDate = (!empty($row['givenDate']) && $row['givenDate'] !== '0000-00-00') ? htmlspecialchars($row['givenDate']) : '-';
                        
                        $completedRaw = $row['completedDate'] ?? '';
                        if (empty($completedRaw) || $completedRaw === '0000-00-00') {
                            if ($row['jobStatus'] === 'Completed' || $row['jobStatus'] === 'Delivered' || (!empty($row['completed']) && $row['completed'] != '0')) {
                                $completedRaw = !empty($row['modifiedOn']) ? date('Y-m-d', strtotime($row['modifiedOn'])) : date('Y-m-d');
                            }
                        }
                        $completed = (!empty($completedRaw) && $completedRaw !== '0000-00-00') ? htmlspecialchars($completedRaw) : '-';

                        $deliveredRaw = $row['deliveryDate'] ?? '';
                        if (empty($deliveredRaw) || $deliveredRaw === '0000-00-00') {
                            if ($row['jobStatus'] === 'Delivered' || (!empty($row['delivered']) && $row['delivered'] != '0')) {
                                $deliveredRaw = !empty($row['modifiedOn']) ? date('Y-m-d', strtotime($row['modifiedOn'])) : date('Y-m-d');
                            }
                        }
                        $delivered = (!empty($deliveredRaw) && $deliveredRaw !== '0000-00-00') ? htmlspecialchars($deliveredRaw) : '-';
                        
                        // Format Amounts
                        $labor = number_format(round((float)$row['laborCharge']));
                        $billed = number_format(round((float)$row['actualAmountSum']));
                        $paid = number_format(round((float)($row['receivedAmountSum'] ?? 0)));
                        
                        // Format Status with Color Scheme
                        $rawSt = $row['jobStatus'] ?? 'New';
                        $statusBg = '#e11d48';
                        $statusDisplay = 'New<br>Job';

                        if ($rawSt === 'New' || $rawSt === 'New Job') {
                            $statusBg = '#e11d48';
                            $statusDisplay = 'New<br>Job';
                        } elseif ($rawSt === 'In Progress' || $rawSt === 'Job Progress') {
                            $statusBg = '#6b21a8';
                            $statusDisplay = 'Job<br>Progress';
                        } elseif ($rawSt === 'Completed' || $rawSt === 'Job Completed') {
                            $statusBg = '#00b4d8';
                            $statusDisplay = 'Job<br>Completed';
                        } elseif ($rawSt === 'Delivered' || $rawSt === 'Job Delivered') {
                            $statusBg = '#38a169';
                            $statusDisplay = 'Job<br>Delivered';
                        } elseif ($rawSt === 'Cancelled') {
                            $statusBg = '#64748b';
                            $statusDisplay = 'Cancelled';
                        } else {
                            $statusDisplay = htmlspecialchars($rawSt);
                        }
                ?>
                <tr style="background: <?= $rowBg ?>; border-bottom: 1px solid #e2e8f0; transition: 0.2s;">
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155;"><?= $counter++ ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155;">Offsite</td>
                    <td style="padding: 12px 15px; font-size: 14px;">
                        <a href="edit.php?id=<?= $row['id'] ?>" style="color: #2563eb; text-decoration: underline; font-weight: 700;"><?= htmlspecialchars($cleanCardNo) ?></a>
                    </td>
                    <td style="padding: 0; font-size: 13.5px; text-align: center; vertical-align: middle;">
                        <div style="background: <?= $statusBg ?>; color: #ffffff; padding: 10px 6px; height: 100%; min-height: 52px; display: flex; align-items: center; justify-content: center; font-weight: 700; line-height: 1.2; box-sizing: border-box;">
                            <?= $statusDisplay ?>
                        </div>
                    </td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; font-weight: 600;"><?= htmlspecialchars($row['allocatedTo'] ?? '-') ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155;"><?= htmlspecialchars($row['machineName'] ?? '-') ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; border-right: 1px solid #e2e8f0;"><?= htmlspecialchars($row['customerName'] ?? '-') ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155;"><?= htmlspecialchars($row['phoneNo1'] ?? '-') ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; border-right: 1px solid #e2e8f0;"><?= $givenDate ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; background: #bfdbfe; border-right: 1px solid #e2e8f0;"><?= $completed ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; background: #bfdbfe;"><?= $delivered ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; background: #dcfce7; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;"><?= $labor ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #334155; background: #dcfce7; border-right: 1px solid #e2e8f0;"><?= $billed ?></td>
                    <td style="padding: 12px 15px; font-size: 14px; color: #166534; background: #bbf7d0; font-weight: 700;"><?= $paid ?></td>
                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" style="background: #f59e0b; color: #ffffff; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="print_receipt.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-info btn-sm" style="background: #2563eb; color: #ffffff; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fa fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php else: ?>
                <tr>
                    <td colspan="14" style="padding: 30px; text-align: center; color: #64748b; font-size: 15px;">
                        No Job Cards Found.
                    </td>
                </tr>
                <?php endif; ?>
                
            </tbody>
        </table>
        
    </div>

    <!-- PAGINATION -->
    <div class="list-pagination-bar" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; font-size:14px; color:#64748b; flex-wrap:wrap; gap:10px;">
        <div class="pagination-info">
            <?php 
            $startRecord = $totalRows > 0 ? $offset + 1 : 0;
            $endRecord = min($offset + $limit, $totalRows);
            ?>
            Showing <?= $startRecord ?>–<?= $endRecord ?> of <?= $totalRows ?> records &nbsp;|&nbsp; Page <?= $page ?> of <?= $totalPages ?>
        </div>

        <div class="pagination-buttons" style="display:flex; gap:5px; align-items:center; flex-wrap:wrap;">
            <?php if ($page <= 1): ?>
                <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">First</span>
                <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Previous</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=1" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">First</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page - 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Previous</a>
            <?php endif; ?>

            <a style="padding:6px 12px; background:#2563eb; color:#fff; border-radius:5px; text-decoration:none; font-weight:bold;"><?= $page ?></a>

            <?php if ($page >= $totalPages): ?>
                <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Next</span>
                <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Last</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page + 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Next</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $totalPages ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Last</a>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
    tbody tr:hover {
        background-color: #f1f5f9 !important;
    }
</style>

<?php include("../includes/footer.php"); ?>

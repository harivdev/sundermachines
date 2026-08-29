<?php
// manage_customers.php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();
include("../includes/header.php");

$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filter_id     = isset($_GET['customerId']) ? trim($_GET['customerId']) : '';
$filter_name   = isset($_GET['name'])       ? trim($_GET['name'])       : '';
$filter_phone  = isset($_GET['phoneNo1'])   ? trim($_GET['phoneNo1'])   : '';
$filter_email  = isset($_GET['emailId'])    ? trim($_GET['emailId'])    : '';
$filter_active = isset($_GET['active'])     ? trim($_GET['active'])     : '';
$filter_city   = isset($_GET['city'])       ? trim($_GET['city'])       : '';
$filter_zip    = isset($_GET['zipCode'])    ? trim($_GET['zipCode'])    : '';

$f_id    = $conn->real_escape_string($filter_id);
$f_name  = $conn->real_escape_string($filter_name);
$f_phone = $conn->real_escape_string($filter_phone);
$f_email = $conn->real_escape_string($filter_email);
$f_city  = $conn->real_escape_string($filter_city);
$f_zip   = $conn->real_escape_string($filter_zip);

$where = "WHERE 1=1";
if ($f_id    !== '') $where .= " AND c.customerId LIKE '%$f_id%'";
if ($f_name  !== '') $where .= " AND c.name LIKE '%$f_name%'";
if ($f_phone !== '') $where .= " AND c.phoneNo1 LIKE '%$f_phone%'";
if ($f_email !== '') $where .= " AND c.emailId LIKE '%$f_email%'";
if ($filter_active !== '') {
    $active_val = ($filter_active === 'Yes') ? 1 : 0;
    $where .= " AND c.active = $active_val";
}
if ($f_city !== '') $where .= " AND a.city LIKE '%$f_city%'";
if ($f_zip  !== '') $where .= " AND a.zipCode LIKE '%$f_zip%'";

$count_result  = $conn->query("SELECT COUNT(*) as total FROM customer c LEFT JOIN address a ON c.address = a.id $where");
$total_records = (int)$count_result->fetch_assoc()['total'];
$total_pages   = $total_records > 0 ? ceil($total_records / $limit) : 1;

$sql = "SELECT c.id, c.customerId, c.name, c.phoneNo1, c.phoneNo2, c.whatsAppNo, c.emailId,
               c.active, a.line1, a.line2, a.city, a.zipCode, a.id as address_id
        FROM customer c
        LEFT JOIN address a ON c.address = a.id
        $where ORDER BY c.id ASC LIMIT $limit OFFSET $offset";

$result    = $conn->query($sql);
$customers = [];
while ($row = $result->fetch_assoc()) { $customers[] = $row; }

$row_start = $total_records > 0 ? $offset + 1 : 0;
$row_end   = min($offset + $limit, $total_records);

// Auto-generate next sequential Customer ID
$last_row = $conn->query("SELECT customerId FROM customer ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_num = 1;
if ($last_row && preg_match('/(\d+)$/', $last_row['customerId'], $m)) {
    $next_num = (int)$m[1] + 1;
}
$next_customer_id = 'C' . str_pad($next_num, 7, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Customers – Sunder Machine</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --green:#1a7a4a; --green-d:#145f39; --green-l:#e6f4ed;
  --red:#d93025; --text:#1e2d24; --muted:#6b7c70;
  --border:#d4e4da; --bg:#f4faf6; --white:#ffffff;
  --radius:8px; --shadow:0 2px 12px rgba(26,122,74,.10);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

/* HEADER */
.page-header{background:var(--green);color:#fff;padding:10px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,.18);}
.page-header h1{font-family:'Space Grotesk',sans-serif;font-size:1.35rem;letter-spacing:.5px;}
.header-actions{display:flex;gap:10px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:var(--radius);border:none;cursor:pointer;font-family:inherit;font-size:.875rem;font-weight:500;transition:all .18s;}
.btn-white{background:#fff;color:var(--green);}
.btn-white:hover{background:var(--green-l);}
.btn-outline{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.55);}
.btn-outline:hover{background:rgba(255,255,255,.12);}
.btn-green{background:var(--green);color:#fff;}
.btn-green:hover{background:var(--green-d);}
.btn-grey{background:#e2e8e4;color:var(--text);}
.btn-grey:hover{background:#cdd7d1;}

/* FILTER */
.filter-panel{display:none;background:var(--white);border-bottom:1.5px solid var(--border);padding:18px 28px;gap:14px;flex-wrap:wrap;align-items:flex-end;}
.filter-panel.open{display:flex;}
.filter-group{display:flex;flex-direction:column;gap:4px;min-width:160px;}
.filter-group label{font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.filter-group input,.filter-group select{border:1.5px solid var(--border);border-radius:var(--radius);padding:7px 10px;font-family:inherit;font-size:.875rem;background:var(--bg);transition:border-color .18s;}
.filter-group input:focus,.filter-group select:focus{outline:none;border-color:var(--green);}
.filter-actions{display:flex;gap:8px;margin-top:4px;}

/* TABLE */
.table-wrap{padding:20px 28px;}
table{width:100%;border-collapse:collapse;background:var(--white);border-radius:10px;box-shadow:var(--shadow);table-layout:fixed;}
thead{background:var(--green);color:#fff;}
thead th{padding:11px 8px;font-size:.76rem;font-weight:600;text-align:left;text-transform:uppercase;letter-spacing:.5px;overflow:hidden;white-space:nowrap;}
thead th:nth-child(1){width:40px;}
thead th:nth-child(2){width:105px;}
thead th:nth-child(3){width:120px;}
thead th:nth-child(4){width:120px;}
thead th:nth-child(5){width:105px;}
thead th:nth-child(6){width:105px;}
thead th:nth-child(7){width:135px;}
thead th:nth-child(8){width:58px;}
thead th:nth-child(9){width:96px;}

tbody tr{border-bottom:1px solid var(--border);transition:background .14s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:var(--green-l);}
tbody td{padding:10px 8px;font-size:.83rem;vertical-align:middle;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

.cust-link{color:var(--green);font-weight:600;text-decoration:none;}
.cust-link:hover{text-decoration:underline;}
.badge-active{background:#d1f5e0;color:#176b3a;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:700;}
.badge-inactive{background:#fde8e8;color:var(--red);padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:700;}

/* ACTION BUTTONS */
.action-cell{display:flex;gap:4px;align-items:center;justify-content:center;flex-wrap:nowrap;}
.icon-btn{width:26px;height:26px;min-width:26px;max-width:26px;border-radius:5px;border:none;cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center;font-size:.73rem;line-height:1;flex-shrink:0;transition:all .15s;}
.icon-btn.id-card{background:#e9f0ff;color:#3b5bdb;}
.icon-btn.id-card:hover{background:#3b5bdb;color:#fff;}
.icon-btn.address{background:#fff3e0;color:#e67700;}
.icon-btn.address:hover{background:#e67700;color:#fff;}
.icon-btn.edit{background:var(--green-l);color:var(--green);}
.icon-btn.edit:hover{background:var(--green);color:#fff;}

/* PAGINATION */
.pagination-bar{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:var(--white);border-top:1.5px solid var(--border);font-size:.875rem;color:var(--muted);}
.pag-btns{display:flex;gap:6px;}
.pag-btn{padding:6px 14px;border-radius:var(--radius);border:1.5px solid var(--border);background:var(--white);color:var(--text);cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:500;transition:all .16s;}
.pag-btn:hover:not(:disabled){background:var(--green);color:#fff;border-color:var(--green);}
.pag-btn:disabled{opacity:.4;cursor:not-allowed;}

/* MODALS */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(10,30,15,.45);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--white);border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.22);width:90%;max-width:520px;max-height:92vh;overflow-y:auto;animation:slideUp .22s ease;}
@keyframes slideUp{from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.modal-header{padding:18px 22px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-header h2{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;color:var(--green);}
.close-btn{background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--muted);line-height:1;}
.close-btn:hover{color:var(--red);}
.modal-body{padding:22px;}
.modal-footer{padding:16px 22px;border-top:1.5px solid var(--border);display:flex;justify-content:flex-end;gap:10px;}

/* FORM */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-grid .full{grid-column:1/-1;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-group label{font-size:.8rem;font-weight:600;color:var(--muted);text-transform:uppercase;}
.form-group input,.form-group select{border:1.5px solid var(--border);border-radius:var(--radius);padding:9px 12px;font-family:inherit;font-size:.9rem;background:var(--bg);transition:border-color .18s;}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--green);}
.form-group input[readonly]{background:#eef5f1;color:var(--muted);cursor:not-allowed;}

/* Validation states */
.form-group input.is-invalid,.form-group select.is-invalid{border-color:var(--red)!important;background:#fff8f8;}
.form-group input.is-valid{border-color:#1a7a4a!important;}
.field-error{font-size:.72rem;color:var(--red);min-height:14px;margin-top:1px;}

.toggle-wrap{display:flex;align-items:center;gap:10px;padding-top:6px;}
.toggle{position:relative;width:44px;height:24px;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.3s;}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
.toggle input:checked+.toggle-slider{background:var(--green);}
.toggle input:checked+.toggle-slider::before{transform:translateX(20px);}

/* ID CARD */
.id-card-container{display:flex;justify-content:center;padding:10px 0 20px;}
.id-card{width:300px;border-radius:16px;overflow:hidden;box-shadow:0 6px 28px rgba(26,122,74,.25);}
.id-card-top{background:linear-gradient(135deg,#1a7a4a 60%,#25a86a 100%);color:#fff;text-align:center;padding:20px 16px 14px;}
.id-card-top .company-name{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:700;letter-spacing:.5px;}
.id-card-top .company-addr{font-size:.72rem;opacity:.85;margin-top:3px;line-height:1.4;}
.id-card-logo{width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;margin:12px auto 8px;display:flex;align-items:center;justify-content:center;overflow:hidden;}
.logo-placeholder{width:66px;height:66px;background:#e6f4ed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;}
.id-card-cust-name{font-size:1.25rem;font-weight:700;letter-spacing:1px;margin-top:4px;}
.id-card-barcode{background:#fff;margin:0 12px;padding:10px;border-radius:6px;}
.id-card-barcode svg{display:block;width:100%;height:48px;}
.id-card-details{background:#fff;padding:12px 16px;font-size:.82rem;line-height:1.7;color:#2d3d32;}
.id-card-details .row{display:flex;gap:4px;}
.id-card-details .lbl{font-weight:600;min-width:72px;}
.id-card-bottom{background:#1a7a4a;color:#fff;text-align:center;padding:10px;font-size:.8rem;}
.id-card-bottom strong{display:block;font-size:.95rem;letter-spacing:.5px;}
.print-btn-wrap{text-align:center;margin-top:14px;}

/* ADDRESS CARD */
.addr-card{background:var(--green-l);border-radius:10px;padding:18px 20px;font-size:.9rem;line-height:2.2;}
.addr-card .row{display:flex;gap:8px;align-items:baseline;}
.addr-card .lbl{font-weight:700;min-width:90px;color:var(--green);}

/* TOAST */
.toast{position:fixed;bottom:28px;right:28px;background:var(--green);color:#fff;padding:12px 22px;border-radius:8px;font-weight:600;font-size:.9rem;box-shadow:0 4px 16px rgba(0,0,0,.18);z-index:9999;display:none;}
.toast.error{background:var(--red);}
.toast.show{display:block;}

@media print{body>*:not(#printArea){display:none!important;}#printArea{display:block!important;}}
#printArea{display:none;}
</style>
</head>
<body>

<?php if (isset($_GET['saved'])): ?>
<div class="toast show" id="savedToast"><i class="fa fa-check-circle"></i>&nbsp; Customer saved successfully!</div>
<script>setTimeout(function(){ document.getElementById('savedToast').classList.remove('show'); }, 3000);</script>
<?php endif; ?>

<div class="erp-container">
  <div class="erp-header-bar">
    <div class="erp-header-title"><i class="fa fa-users" style="margin-right:8px"></i>Manage Customers</div>
    <div class="erp-header-actions">
      <button type="button" class="btn-erp btn-erp-new" onclick="openNewCustomer()">
        <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New
      </button>
      <button type="button" class="btn-erp btn-erp-secondary" onclick="location.reload()">🔄 Refresh</button>
      <button type="button" class="btn-erp btn-erp-secondary" onclick="toggleFilter()">🔽 Filter</button>
    </div>
  </div>

  <!-- FILTER -->
  <form method="GET" action="" id="filterForm">
  <div class="erp-filter-panel filter-panel <?= ($filter_id||$filter_name||$filter_phone||$filter_email||$filter_active!==''||$filter_city||$filter_zip)?'open':'' ?>" id="filterPanel">
  <div class="filter-group">
    <label>Customer ID</label>
    <input type="text" name="customerId" id="f_customerId" value="<?= htmlspecialchars($filter_id) ?>" placeholder="Search ID…" maxlength="20">
  </div>
  <div class="filter-group">
    <label>Name</label>
    <input type="text" name="name" id="f_name" value="<?= htmlspecialchars($filter_name) ?>" placeholder="Search name…" maxlength="100">
  </div>
  <div class="filter-group">
    <label>Phone #</label>
    <input type="text" name="phoneNo1" id="f_phoneNo1" value="<?= htmlspecialchars($filter_phone) ?>" placeholder="10-digit number" maxlength="10">
  </div>
  <div class="filter-group">
    <label>Email ID</label>
    <input type="text" name="emailId" id="f_emailId" value="<?= htmlspecialchars($filter_email) ?>" placeholder="Search email…" maxlength="150">
  </div>
  <div class="filter-group">
    <label>Active</label>
    <select name="active">
      <option value="">All</option>
      <option value="Yes" <?= $filter_active==='Yes'?'selected':'' ?>>Yes</option>
      <option value="No"  <?= $filter_active==='No' ?'selected':'' ?>>No</option>
    </select>
  </div>
  <div class="filter-group">
    <label>City</label>
    <input type="text" name="city" id="f_city" value="<?= htmlspecialchars($filter_city) ?>" placeholder="City…" maxlength="100">
  </div>
  <div class="filter-group">
    <label>Zip Code</label>
    <input type="text" name="zipCode" id="f_zipCode" value="<?= htmlspecialchars($filter_zip) ?>" placeholder="6-digit PIN" maxlength="6">
  </div>
  <div class="filter-actions">
    <button type="button" class="btn btn-green" onclick="submitFilter()"><i class="fa fa-search"></i> Apply</button>
    <a href="manage_customers.php" class="btn btn-grey"><i class="fa fa-xmark"></i> Clear</a>
  </div>
</div>
</form>

<!-- TABLE -->
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th><th>Customer ID</th><th>Name</th><th>Address</th>
        <th>Contact #</th><th>WhatsApp #</th><th>Email ID</th>
        <th>Active</th><th style="text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($customers)): ?>
      <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--muted)">
        <i class="fa fa-inbox" style="font-size:1.5rem;margin-bottom:6px;display:block"></i>No records found.
      </td></tr>
    <?php else: ?>
    <?php $i = $offset + 1; foreach ($customers as $c): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><a class="cust-link" href="#"><?= htmlspecialchars($c['customerId']) ?></a></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?php $ap=array_filter([$c['line1']??'',$c['city']??'']); echo htmlspecialchars(implode(', ',$ap)); ?></td>
        <td><?= htmlspecialchars($c['phoneNo1']??'') ?></td>
        <td><?= htmlspecialchars($c['whatsAppNo']??'') ?></td>
        <td><?= htmlspecialchars($c['emailId']??'') ?></td>
        <td><span class="<?= $c['active']?'badge-active':'badge-inactive' ?>"><?= $c['active']?'Yes':'No' ?></span></td>
        <td>
          <div class="action-cell">
            <button class="icon-btn id-card" title="ID Card"  onclick='showIdCard(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)'><i class="fa fa-id-card"></i></button>
            <button class="icon-btn address" title="Address"  onclick='showAddress(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)'><i class="fa fa-location-dot"></i></button>
            <button class="icon-btn edit"    title="Edit"     onclick='openEdit(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)'><i class="fa fa-pen"></i></button>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- PAGINATION -->
<div class="pagination-bar">
  <span>Showing <?= $row_start ?> – <?= $row_end ?> of <?= $total_records ?> records.</span>
  <div class="pag-btns">
    <?php $qp=$_GET; unset($qp['page']); $qs=http_build_query($qp); $qsp=$qs?"?$qs&":'?'; ?>
    <button class="pag-btn" <?= $page<=1?'disabled':'' ?> onclick="location.href='<?= $qsp ?>page=1'">|◀ First</button>
    <button class="pag-btn" <?= $page<=1?'disabled':'' ?> onclick="location.href='<?= $qsp ?>page=<?= $page-1 ?>'">◀ Prev</button>
    <button class="pag-btn" <?= $page>=$total_pages?'disabled':'' ?> onclick="location.href='<?= $qsp ?>page=<?= $page+1 ?>'">Next ▶</button>
    <button class="pag-btn" <?= $page>=$total_pages?'disabled':'' ?> onclick="location.href='<?= $qsp ?>page=<?= $total_pages ?>'">Last ▶|</button>
  </div>
</div>

<!-- ══ ID CARD MODAL ══ -->
<div class="modal-overlay" id="idCardModal">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fa fa-id-card" style="margin-right:8px"></i>Customer ID Card</h2>
      <button class="close-btn" onclick="closeModal('idCardModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="id-card-container">
        <div class="id-card" id="idCardPrint">
          <div class="id-card-top">
            <div class="company-name">SUNDER MACHINES WORKS</div>
            <div class="company-addr">4, Sunder Building, Gobichettypalayam,<br>Erode - Dt TN - 638476.</div>
            <div class="id-card-logo"><div class="logo-placeholder">🧵</div></div>
            <div class="id-card-cust-name" id="ic_name">—</div>
          </div>
          <div class="id-card-barcode"><svg id="ic_barcode" xmlns="http://www.w3.org/2000/svg"></svg></div>
          <div class="id-card-details">
            <div class="row"><span class="lbl">ID</span><span>:&nbsp;<span id="ic_id">—</span></span></div>
            <div class="row"><span class="lbl">Address</span><span>:&nbsp;<span id="ic_address">—</span></span></div>
            <div class="row"><span class="lbl">PIN</span><span>:&nbsp;<span id="ic_pin">—</span></span></div>
            <div class="row"><span class="lbl">Phone #</span><span>:&nbsp;<span id="ic_phone">—</span></span></div>
          </div>
          <div class="id-card-bottom">CUSTOMER SUPPORT<br><strong>9843361326 / 04285 224176</strong></div>
        </div>
      </div>
      <div class="print-btn-wrap">
        <button class="btn btn-green" onclick="printIdCard()"><i class="fa fa-print"></i> Print / Save PDF</button>
      </div>
    </div>
  </div>
</div>

<!-- ══ ADDRESS MODAL ══ -->
<div class="modal-overlay" id="addressModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h2><i class="fa fa-location-dot" style="margin-right:8px"></i>Customer Address</h2>
      <button class="close-btn" onclick="closeModal('addressModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="addr-card">
        <div class="row"><span class="lbl">Name</span><span id="ad_name">—</span></div>
        <div class="row"><span class="lbl">Line 1</span><span id="ad_line1">—</span></div>
        <div class="row"><span class="lbl">Line 2</span><span id="ad_line2">—</span></div>
        <div class="row"><span class="lbl">City</span><span id="ad_city">—</span></div>
        <div class="row"><span class="lbl">PIN</span><span id="ad_pin">—</span></div>
        <div class="row"><span class="lbl">Phone #</span><span id="ad_phone">—</span></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-grey" onclick="closeModal('addressModal')">Close</button></div>
  </div>
</div>

<!-- ══ EDIT / NEW CUSTOMER MODAL ══ -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:580px">
    <div class="modal-header">
      <h2 id="editModalTitle"><i class="fa fa-pen" style="margin-right:8px"></i>Edit Customer</h2>
      <button class="close-btn" onclick="closeModal('editModal')">✕</button>
    </div>
    <form id="editForm" onsubmit="return submitEditForm(event)">
      <div class="modal-body">
        <input type="hidden" name="id"         id="edit_id">
        <input type="hidden" name="address_id" id="edit_address_id">
        <div class="form-grid">

          <!-- Customer ID -->
          <div class="form-group">
            <label>Customer ID <span style="color:var(--red)">*</span></label>
            <input type="text" name="customerId" id="edit_customerId" placeholder="C0001234" maxlength="20">
            <span class="field-error" id="err_customerId"></span>
          </div>

          <!-- Name -->
          <div class="form-group">
            <label>Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" id="edit_name" placeholder="Full name" maxlength="100">
            <span class="field-error" id="err_name"></span>
          </div>

          <!-- Phone Primary -->
          <div class="form-group">
            <label>Phone # Primary <span style="color:var(--red)">*</span></label>
            <input type="text" name="phoneNo1" id="edit_phoneNo1" placeholder="10-digit number" maxlength="10">
            <span class="field-error" id="err_phoneNo1"></span>
          </div>

          <!-- Phone Secondary -->
          <div class="form-group">
            <label>Phone # Secondary</label>
            <input type="text" name="phoneNo2" id="edit_phoneNo2" placeholder="10-digit number (optional)" maxlength="10">
            <span class="field-error" id="err_phoneNo2"></span>
          </div>

          <!-- WhatsApp -->
          <div class="form-group">
            <label>WhatsApp #</label>
            <input type="text" name="whatsAppNo" id="edit_whatsAppNo" placeholder="10-digit number (optional)" maxlength="10">
            <span class="field-error" id="err_whatsAppNo"></span>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email ID</label>
            <input type="text" name="emailId" id="edit_emailId" placeholder="email@example.com" maxlength="150">
            <span class="field-error" id="err_emailId"></span>
          </div>

          <!-- Active toggle -->
          <div class="form-group full">
            <label>Active</label>
            <div class="toggle-wrap">
              <label class="toggle"><input type="checkbox" name="active" id="edit_active" value="1"><span class="toggle-slider"></span></label>
              <span id="activeLabel">Active</span>
            </div>
          </div>

          <!-- Address section -->
          <div class="form-group full" style="border-top:1.5px solid var(--border);padding-top:12px;margin-top:4px">
            <label style="color:var(--green);font-size:.85rem">— Address —</label>
          </div>

          <!-- Line 1 -->
          <div class="form-group full">
            <label>Line 1</label>
            <input type="text" name="line1" id="edit_line1" placeholder="Street / Area" maxlength="200">
            <span class="field-error" id="err_line1"></span>
          </div>

          <!-- Line 2 -->
          <div class="form-group full">
            <label>Line 2</label>
            <input type="text" name="line2" id="edit_line2" placeholder="Landmark / etc." maxlength="200">
            <span class="field-error" id="err_line2"></span>
          </div>

          <!-- City -->
          <div class="form-group">
            <label>City</label>
            <input type="text" name="city" id="edit_city" placeholder="City" maxlength="100">
            <span class="field-error" id="err_city"></span>
          </div>

          <!-- Zip Code -->
          <div class="form-group">
            <label>Zip Code</label>
            <input type="text" name="zipCode" id="edit_zipCode" placeholder="6-digit PIN" maxlength="6">
            <span class="field-error" id="err_zipCode"></span>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-grey" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-green"><i class="fa fa-floppy-disk"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<div id="printArea"></div>

<script>
var nextCustomerId = <?= json_encode($next_customer_id) ?>;
</script>

<script>
/* ═══════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════ */
function toggleFilter(){document.getElementById('filterPanel').classList.toggle('open');}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){
  document.getElementById(id).classList.remove('open');
  if(id==='editModal') clearFormErrors();
}
document.querySelectorAll('.modal-overlay').forEach(function(o){
  o.addEventListener('click',function(e){if(e.target===o){o.classList.remove('open');clearFormErrors();}});
});

/* ═══════════════════════════════════════════
   FILTER VALIDATION
   Rules:
     phoneNo1  → digits only, exactly 10 if filled
     zipCode   → digits only, exactly 6 if filled
     emailId   → basic email format if filled
═══════════════════════════════════════════ */
function submitFilter(){
  var ok = true;

  var phone = document.getElementById('f_phoneNo1').value.trim();
  if(phone !== '' && !/^\d{10}$/.test(phone)){
    showFilterError('f_phoneNo1', 'Enter a valid 10-digit phone number');
    ok = false;
  } else { clearFilterError('f_phoneNo1'); }

  var zip = document.getElementById('f_zipCode').value.trim();
  if(zip !== '' && !/^\d{6}$/.test(zip)){
    showFilterError('f_zipCode', 'Enter a valid 6-digit ZIP/PIN');
    ok = false;
  } else { clearFilterError('f_zipCode'); }

  var email = document.getElementById('f_emailId').value.trim();
  if(email !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
    showFilterError('f_emailId', 'Enter a valid email address');
    ok = false;
  } else { clearFilterError('f_emailId'); }

  if(ok) document.getElementById('filterForm').submit();
}

function showFilterError(fieldId, msg){
  var el = document.getElementById(fieldId);
  el.classList.add('is-invalid');
  el.title = msg;
  el.setAttribute('placeholder', msg);
}
function clearFilterError(fieldId){
  var el = document.getElementById(fieldId);
  el.classList.remove('is-invalid');
  el.title = '';
}

/* Allow only digits in filter phone / zip */
document.getElementById('f_phoneNo1').addEventListener('input', function(){
  this.value = this.value.replace(/\D/g,'');
});
document.getElementById('f_zipCode').addEventListener('input', function(){
  this.value = this.value.replace(/\D/g,'');
});

/* ═══════════════════════════════════════════
   FORM VALIDATION — EDIT / NEW MODAL
   Rules:
     customerId → required, letters+digits only
     name       → required, letters/spaces/dots/hyphens only, 2–100 chars
     phoneNo1   → required, exactly 10 digits
     phoneNo2   → optional, exactly 10 digits if filled
     whatsAppNo → optional, exactly 10 digits if filled
     emailId    → optional, valid email format if filled
     city       → optional, letters/spaces only if filled
     zipCode    → optional, exactly 6 digits if filled
     line1/line2→ optional, no special script chars
═══════════════════════════════════════════ */
var RULES = {
  edit_customerId: {
    required: true,
    pattern: /^[A-Za-z0-9]+$/,
    label: 'Customer ID',
    patternMsg: 'Only letters and numbers allowed'
  },
  edit_name: {
    required: true,
    pattern: /^[A-Za-z\s.\-']{2,100}$/,
    label: 'Name',
    patternMsg: 'Only letters, spaces, dots and hyphens (2–100 chars)'
  },
  edit_phoneNo1: {
    required: true,
    pattern: /^\d{10}$/,
    label: 'Phone # Primary',
    patternMsg: 'Must be exactly 10 digits'
  },
  edit_phoneNo2: {
    required: false,
    pattern: /^\d{10}$/,
    label: 'Phone # Secondary',
    patternMsg: 'Must be exactly 10 digits'
  },
  edit_whatsAppNo: {
    required: false,
    pattern: /^\d{10}$/,
    label: 'WhatsApp #',
    patternMsg: 'Must be exactly 10 digits'
  },
  edit_emailId: {
    required: false,
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    label: 'Email ID',
    patternMsg: 'Enter a valid email address (e.g. name@domain.com)'
  },
  edit_line1: {
    required: false,
    pattern: /^[^<>{}|\\^`]{0,200}$/,
    label: 'Address Line 1',
    patternMsg: 'Invalid characters'
  },
  edit_line2: {
    required: false,
    pattern: /^[^<>{}|\\^`]{0,200}$/,
    label: 'Address Line 2',
    patternMsg: 'Invalid characters'
  },
  edit_city: {
    required: false,
    pattern: /^[A-Za-z\s.\-]{0,100}$/,
    label: 'City',
    patternMsg: 'Only letters and spaces allowed'
  },
  edit_zipCode: {
    required: false,
    pattern: /^\d{6}$/,
    label: 'Zip Code',
    patternMsg: 'Must be exactly 6 digits'
  }
};

/* Restrict phone/whatsapp/zip input to digits only */
['edit_phoneNo1','edit_phoneNo2','edit_whatsAppNo','edit_zipCode'].forEach(function(id){
  document.getElementById(id).addEventListener('input', function(){
    this.value = this.value.replace(/\D/g,'');
  });
});

/* Restrict name / city to letters/spaces */
['edit_name','edit_city'].forEach(function(id){
  document.getElementById(id).addEventListener('input', function(){
    this.value = this.value.replace(/[^A-Za-z\s.\-']/g,'');
  });
});

function validateField(id){
  var rule = RULES[id];
  if(!rule) return true;
  var el  = document.getElementById(id);
  var err = document.getElementById('err_' + id.replace('edit_',''));
  var val = el.value.trim();

  if(rule.required && val === ''){
    setError(el, err, rule.label + ' is required');
    return false;
  }
  if(!rule.required && val === ''){
    clearError(el, err); return true;
  }
  if(!rule.pattern.test(val)){
    setError(el, err, rule.patternMsg);
    return false;
  }
  clearError(el, err);
  return true;
}

function setError(el, errEl, msg){
  el.classList.add('is-invalid');
  el.classList.remove('is-valid');
  if(errEl) errEl.textContent = msg;
}
function clearError(el, errEl){
  el.classList.remove('is-invalid');
  el.classList.add('is-valid');
  if(errEl) errEl.textContent = '';
}
function clearFormErrors(){
  Object.keys(RULES).forEach(function(id){
    var el  = document.getElementById(id);
    var key = id.replace('edit_','');
    var err = document.getElementById('err_' + key);
    if(el){ el.classList.remove('is-invalid','is-valid'); }
    if(err){ err.textContent = ''; }
  });
}

/* Live validation on blur */
Object.keys(RULES).forEach(function(id){
  var el = document.getElementById(id);
  if(el){
    el.addEventListener('blur', function(){ validateField(id); });
    el.addEventListener('input', function(){
      // clear error while typing
      var err = document.getElementById('err_' + id.replace('edit_',''));
      el.classList.remove('is-invalid');
      if(err) err.textContent = '';
    });
  }
});

/* Full form validation on submit */
function submitEditForm(e){
  e.preventDefault();
  var valid = true;
  Object.keys(RULES).forEach(function(id){
    if(!validateField(id)) valid = false;
  });
  if(!valid){
    // scroll to first error
    var first = document.querySelector('#editModal .is-invalid');
    if(first) first.scrollIntoView({behavior:'smooth', block:'center'});
    return false;
  }
  // Build and POST form data
  var fd = new FormData();
  fd.append('id',          document.getElementById('edit_id').value);
  fd.append('address_id',  document.getElementById('edit_address_id').value);
  fd.append('customerId',  document.getElementById('edit_customerId').value.trim());
  fd.append('name',        document.getElementById('edit_name').value.trim());
  fd.append('phoneNo1',    document.getElementById('edit_phoneNo1').value.trim());
  fd.append('phoneNo2',    document.getElementById('edit_phoneNo2').value.trim());
  fd.append('whatsAppNo',  document.getElementById('edit_whatsAppNo').value.trim());
  fd.append('emailId',     document.getElementById('edit_emailId').value.trim());
  fd.append('line1',       document.getElementById('edit_line1').value.trim());
  fd.append('line2',       document.getElementById('edit_line2').value.trim());
  fd.append('city',        document.getElementById('edit_city').value.trim());
  fd.append('zipCode',     document.getElementById('edit_zipCode').value.trim());
  if(document.getElementById('edit_active').checked) fd.append('active','1');

  fetch('customer_save.php', {method:'POST', body:fd})
    .then(function(r){ return r.text(); })
    .then(function(){
      closeModal('editModal');
      location.href = 'manage_customers.php?saved=1';
    })
    .catch(function(){
      alert('Save failed. Please try again.');
    });
  return false;
}

/* ═══════════════════════════════════════════
   ID CARD
═══════════════════════════════════════════ */
function showIdCard(c){
  document.getElementById('ic_name').textContent=(c.name||'').toUpperCase();
  document.getElementById('ic_id').textContent=c.customerId||'—';
  document.getElementById('ic_address').textContent=[c.line1,c.city].filter(Boolean).join(', ')||'—';
  document.getElementById('ic_pin').textContent=c.zipCode||'—';
  document.getElementById('ic_phone').textContent=c.phoneNo1||'—';
  generateBarcode(c.customerId||'C0000000');
  openModal('idCardModal');
}
function generateBarcode(text){
  var svg=document.getElementById('ic_barcode');
  svg.innerHTML='';
  var ns='http://www.w3.org/2000/svg',codes=[],i;
  for(i=0;i<text.length;i++)codes.push(text.charCodeAt(i));
  var totalW=8;
  for(i=0;i<codes.length;i++)totalW+=(codes[i]%5+3)+2;
  svg.setAttribute('viewBox','0 0 '+totalW+' 48');
  svg.setAttribute('preserveAspectRatio','none');
  var x=4;
  function addBar(xp,w){var r=document.createElementNS(ns,'rect');r.setAttribute('x',xp);r.setAttribute('y',0);r.setAttribute('width',w);r.setAttribute('height',40);r.setAttribute('fill','#000');svg.appendChild(r);}
  addBar(x,2);x+=3;addBar(x,1);x+=2;
  for(i=0;i<codes.length;i++){
    var code=codes[i],ws=[1+code%3,1+Math.floor(code/10)%3,1+Math.floor(code/100)%3,1+code%5];
    for(var j=0;j<ws.length;j++){if(j%2===0)addBar(x,ws[j]);x+=ws[j]+1;}
  }
  addBar(x,1);x+=2;addBar(x,2);
  var t=document.createElementNS(ns,'text');
  t.setAttribute('x',totalW/2);t.setAttribute('y',47);
  t.setAttribute('text-anchor','middle');t.setAttribute('font-size','6');
  t.setAttribute('font-family','monospace');t.textContent=text;
  svg.appendChild(t);
}
function printIdCard(){
  var card=document.getElementById('idCardPrint').outerHTML;
  var w=window.open('','','width=420,height=720');
  w.document.write('<!DOCTYPE html><html><head>'
    +'<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">'
    +'<style>body{margin:20px;font-family:"DM Sans",sans-serif;display:flex;justify-content:center}'
    +'.id-card{width:300px;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.2)}'
    +'.id-card-top{background:linear-gradient(135deg,#1a7a4a 60%,#25a86a);color:#fff;text-align:center;padding:20px 16px 14px}'
    +'.company-name{font-family:"Space Grotesk",sans-serif;font-size:1.1rem;font-weight:700}'
    +'.company-addr{font-size:.72rem;opacity:.85;margin-top:3px;line-height:1.4}'
    +'.id-card-logo{width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;margin:12px auto 8px;display:flex;align-items:center;justify-content:center;overflow:hidden}'
    +'.logo-placeholder{width:66px;height:66px;background:#e6f4ed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem}'
    +'.id-card-cust-name{font-size:1.25rem;font-weight:700;letter-spacing:1px;margin-top:4px}'
    +'.id-card-barcode{background:#fff;margin:0 12px;padding:10px;border-radius:6px}'
    +'.id-card-barcode svg{display:block;width:100%;height:48px}'
    +'.id-card-details{background:#fff;padding:12px 16px;font-size:.82rem;line-height:1.7;color:#2d3d32}'
    +'.id-card-details .row{display:flex;gap:4px}.id-card-details .lbl{font-weight:600;min-width:72px}'
    +'.id-card-bottom{background:#1a7a4a;color:#fff;text-align:center;padding:10px;font-size:.8rem}'
    +'.id-card-bottom strong{display:block;font-size:.95rem;letter-spacing:.5px}'
    +'</style></head><body>'+card+'</body></html>');
  w.document.close();
  setTimeout(function(){w.print();w.close();},600);
}

/* ═══════════════════════════════════════════
   ADDRESS
═══════════════════════════════════════════ */
function showAddress(c){
  document.getElementById('ad_name').textContent=c.name||'—';
  document.getElementById('ad_line1').textContent=c.line1||'—';
  document.getElementById('ad_line2').textContent=c.line2||'—';
  document.getElementById('ad_city').textContent=c.city||'—';
  document.getElementById('ad_pin').textContent=c.zipCode||'—';
  document.getElementById('ad_phone').textContent=c.phoneNo1||'—';
  openModal('addressModal');
}

/* ═══════════════════════════════════════════
   EDIT existing
═══════════════════════════════════════════ */
function openEdit(c){
  document.getElementById('editModalTitle').innerHTML='<i class="fa fa-pen" style="margin-right:8px"></i>Edit Customer';
  clearFormErrors();
  document.getElementById('edit_id').value=c.id||'';
  document.getElementById('edit_address_id').value=c.address_id||'';
  document.getElementById('edit_customerId').value=c.customerId||'';
  document.getElementById('edit_customerId').readOnly=false;
  document.getElementById('edit_customerId').style.background='';
  document.getElementById('edit_customerId').style.cursor='';
  document.getElementById('edit_name').value=c.name||'';
  document.getElementById('edit_phoneNo1').value=c.phoneNo1||'';
  document.getElementById('edit_phoneNo2').value=c.phoneNo2||'';
  document.getElementById('edit_whatsAppNo').value=c.whatsAppNo||'';
  document.getElementById('edit_emailId').value=c.emailId||'';
  document.getElementById('edit_active').checked=(c.active==1);
  document.getElementById('activeLabel').textContent=(c.active==1)?'Active':'Inactive';
  document.getElementById('edit_line1').value=c.line1||'';
  document.getElementById('edit_line2').value=c.line2||'';
  document.getElementById('edit_city').value=c.city||'';
  document.getElementById('edit_zipCode').value=c.zipCode||'';
  openModal('editModal');
}

/* ═══════════════════════════════════════════
   NEW customer
═══════════════════════════════════════════ */
function openNewCustomer(){
  document.getElementById('editModalTitle').innerHTML='<i class="fa fa-plus" style="margin-right:8px"></i>New Customer';
  document.getElementById('editForm').reset();
  clearFormErrors();
  document.getElementById('edit_id').value='';
  document.getElementById('edit_address_id').value='';
  document.getElementById('edit_customerId').value=nextCustomerId;
  document.getElementById('edit_customerId').readOnly=true;
  document.getElementById('edit_customerId').style.background='#eef5f1';
  document.getElementById('edit_customerId').style.cursor='not-allowed';
  document.getElementById('edit_active').checked=true;
  document.getElementById('activeLabel').textContent='Active';
  openModal('editModal');
}

document.getElementById('edit_active').addEventListener('change',function(){
  document.getElementById('activeLabel').textContent=this.checked?'Active':'Inactive';
});
</script>
</body>
</html>
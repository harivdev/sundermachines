<?php
// manage_users.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
  header("Location: ../index.php");
  exit();
}

require_once("../config/db.php");
include("../includes/header.php");

function ensureUserProfileColumns($conn_login)
{
  $existing = [];
  $res = $conn_login->query("SHOW COLUMNS FROM user");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $existing[] = $row['Field'];
    }
  }

  $definitions = [
    'name' => 'VARCHAR(100) NULL',
    'phone_number' => 'VARCHAR(20) NULL',
    'phone_number_2' => 'VARCHAR(20) NULL',
    'email' => 'VARCHAR(100) NULL',
    'dob' => 'DATE NULL',
    'address' => 'TEXT NULL',
    'gender' => 'VARCHAR(50) NULL',
    'photo' => 'VARCHAR(255) NULL'
  ];

  foreach ($definitions as $column => $definition) {
    if (!in_array($column, $existing, true)) {
      $conn_login->query("ALTER TABLE user ADD COLUMN `$column` $definition");
    }
  }
}

ensureUserProfileColumns($conn_login);

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$filter_username = isset($_GET['username']) ? trim($_GET['username']) : '';
$f_username = $conn_login->real_escape_string($filter_username);

$where = "WHERE 1=1";
if ($f_username !== '') {
  $where .= " AND username LIKE '%$f_username%'";
}

$count_res = $conn_login->query("SELECT COUNT(*) AS total FROM user $where");
$total_records = $count_res ? (int)$count_res->fetch_assoc()['total'] : 0;
$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
if ($page > $total_pages && $total_pages > 0) {
  $page = $total_pages;
  $offset = ($page - 1) * $limit;
}

$sql = "SELECT id, name, username, phone_number, phone_number_2, email, dob, address, gender, photo, role, createdOn FROM user $where ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = $conn_login->query($sql);
$users = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
}

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users – Sunder Machines World</title>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Grotesk:wght@700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --green: #1a7a4a;
      --green-d: #145f39;
      --green-l: #e6f4ed;
      --red: #d93025;
      --text: #1e2d24;
      --muted: #6b7c70;
      --border: #d4e4da;
      --bg: #f4faf6;
      --white: #ffffff;
      --radius: 8px;
      --shadow: 0 2px 12px rgba(26, 122, 74, .10);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* HEADER */
    .page-header {
      background: var(--green);
      color: #fff;
      padding: 10px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
    }

    .page-header h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.35rem;
      letter-spacing: .5px;
    }

    .header-actions {
      display: flex;
      gap: 10px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 16px;
      border-radius: var(--radius);
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-size: .875rem;
      font-weight: 500;
      transition: all .18s;
    }

    .btn-white {
      background: #fff;
      color: var(--green);
    }

    .btn-white:hover {
      background: var(--green-l);
    }

    .btn-outline {
      background: transparent;
      color: #fff;
      border: 1.5px solid rgba(255, 255, 255, .55);
    }

    .btn-outline:hover {
      background: rgba(255, 255, 255, .12);
    }

    .btn-green {
      background: var(--green);
      color: #fff;
    }

    .btn-green:hover {
      background: var(--green-d);
    }

    .btn-grey {
      background: #e2e8e4;
      color: var(--text);
    }

    .btn-grey:hover {
      background: #cdd7d1;
    }

    /* FILTER */
    .filter-panel {
      display: none;
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      padding: 18px 28px;
      gap: 14px;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    .filter-panel.open {
      display: flex;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 180px;
    }

    .filter-group label {
      font-size: .78rem;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .filter-group input,
    .filter-group select {
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 7px 10px;
      font-family: inherit;
      font-size: .875rem;
      background: var(--bg);
      transition: border-color .18s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: var(--green);
    }

    .filter-actions {
      display: flex;
      gap: 8px;
      margin-top: 4px;
    }

    /* TABLE */
    .table-wrap {
      padding: 20px 28px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      border-radius: 10px;
      box-shadow: var(--shadow);
      table-layout: fixed;
    }

    thead {
      background: var(--green);
      color: #fff;
    }

    thead th {
      padding: 11px 8px;
      font-size: .76rem;
      font-weight: 600;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: .5px;
      overflow: hidden;
      white-space: nowrap;
    }

    thead th:nth-child(1) {
      width: 60px;
    }

    thead th:nth-child(2) {
      width: 250px;
    }

    thead th:nth-child(3) {
      width: 180px;
    }

    thead th:nth-child(4) {
      width: 220px;
    }

    thead th:nth-child(5) {
      width: 120px;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .14s;
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody tr:hover {
      background: var(--green-l);
    }

    tbody td {
      padding: 10px 8px;
      font-size: .83rem;
      vertical-align: middle;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .badge-role {
      background: #d1f5e0;
      color: #176b3a;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .badge-admin {
      background: #dbeafe;
      color: #1e40af;
    }

    .badge-user {
      background: #f3f4f6;
      color: #374151;
    }

    /* ACTION BUTTONS */
    .action-cell {
      display: flex;
      gap: 6px;
      align-items: center;
      justify-content: flex-start;
    }

    .icon-btn {
      width: 28px;
      height: 28px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .8rem;
      transition: all .15s;
    }

    .icon-btn.edit {
      background: var(--green-l);
      color: var(--green);
    }

    .icon-btn.edit:hover {
      background: var(--green);
      color: #fff;
    }

    .icon-btn.delete {
      background: #fde8e8;
      color: var(--red);
    }

    .icon-btn.delete:hover {
      background: var(--red);
      color: #fff;
    }

    /* MODALS */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10, 30, 15, .45);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(2px);
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal {
      background: var(--white);
      border-radius: 14px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, .22);
      width: 90%;
      max-width: 480px;
      max-height: 92vh;
      overflow-y: auto;
      animation: slideUp .22s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      padding: 18px 22px;
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-header h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.1rem;
      color: var(--green);
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 1.3rem;
      cursor: pointer;
      color: var(--muted);
      line-height: 1;
    }

    .close-btn:hover {
      color: var(--red);
    }

    .modal-body {
      padding: 22px;
    }

    .modal-footer {
      padding: 16px 22px;
      border-top: 1.5px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* FORM */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 14px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .form-group label {
      font-size: .8rem;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 9px 12px;
      font-family: inherit;
      font-size: .9rem;
      background: var(--bg);
      transition: border-color .18s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--green);
    }

    .form-group input[readonly] {
      background: #eef5f1;
      color: var(--muted);
      cursor: not-allowed;
    }

    .photo-upload-box {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .photo-trigger {
      width: fit-content;
      background: transparent;
      border: none;
      padding: 0;
      cursor: pointer;
      text-align: left;
    }

    .photo-preview {
      width: 112px;
      height: 112px;
      border: 1.5px dashed var(--border);
      border-radius: 14px;
      background: #f8fcfa;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .photo-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    .photo-preview i {
      font-size: 1.4rem;
      color: var(--muted);
    }

    .photo-hint {
      font-size: .75rem;
      color: var(--muted);
    }

    .user-photo {
      width: 42px;
      height: 42px;
      object-fit: cover;
      border-radius: 50%;
      border: 1.5px solid var(--border);
      display: block;
      background: #f8fcfa;
    }

    .form-group textarea {
      min-height: 80px;
      resize: vertical;
    }

    .form-group input.is-invalid,
    .form-group select.is-invalid,
    .form-group textarea.is-invalid {
      border-color: var(--red) !important;
      background: #fff8f8;
    }

    .field-error {
      font-size: .72rem;
      color: var(--red);
      min-height: 14px;
      margin-top: 1px;
    }

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 28px;
      right: 28px;
      background: var(--green);
      color: #fff;
      padding: 12px 22px;
      border-radius: 8px;
      font-weight: 600;
      font-size: .9rem;
      box-shadow: 0 4px 16px rgba(0, 0, 0, .18);
      z-index: 9999;
      display: none;
    }

    .toast.error {
      background: var(--red);
    }

    .toast.show {
      display: block;
    }
  </style>
</head>

<body>

  <?php if (isset($_GET['saved'])): ?>
    <div class="toast show" id="savedToast"><i class="fa fa-check-circle"></i>&nbsp; User saved successfully!</div>
    <script>setTimeout(function () { document.getElementById('savedToast').classList.remove('show'); }, 3000);</script>
  <?php endif; ?>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="toast show" id="deletedToast"><i class="fa fa-check-circle"></i>&nbsp; User deleted successfully!</div>
    <script>setTimeout(function () { document.getElementById('deletedToast').classList.remove('show'); }, 3000);</script>
  <?php endif; ?>

  <div class="erp-container">
    <div class="erp-header-bar">
      <div class="erp-header-title"><i class="fa fa-user-shield" style="margin-right:8px"></i>Manage Admin Users</div>
      <div class="erp-header-actions">
        <button type="button" class="btn-erp btn-erp-new" onclick="openNewUser()">
          <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New User
        </button>
        <button type="button" class="btn-erp btn-erp-secondary" onclick="location.reload()">🔄 Refresh</button>
        <button type="button" class="btn-erp btn-erp-secondary" onclick="toggleFilter()">🔽 Filter</button>
      </div>
    </div>

    <!-- FILTER -->
    <form method="GET" action="" id="filterForm">
      <div class="erp-filter-panel filter-panel <?= ($filter_username !== '') ? 'open' : '' ?>" id="filterPanel">
      <div class="filter-group">
        <label>Username</label>
        <input type="text" name="username" id="f_username" value="<?= htmlspecialchars($filter_username) ?>"
          placeholder="Search username…" maxlength="50">
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn btn-green"><i class="fa fa-search"></i> Apply</button>
        <a href="manage_users.php" class="btn btn-grey"><i class="fa fa-xmark"></i> Clear</a>
      </div>
    </div>
  </form>

  <!-- TABLE -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Photo</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Created On</th>
          <th style="text-align:center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">
              <i class="fa fa-inbox" style="font-size:1.5rem;margin-bottom:6px;display:block"></i>No users found.
            </td>
          </tr>
        <?php else: ?>
          <?php $i = $offset + 1;
          foreach ($users as $u): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <?php if (!empty($u['photo'])): ?>
                  <img src="../uploads/user_photos/<?= htmlspecialchars($u['photo']) ?>" alt="Photo" class="user-photo">
                <?php else: ?>
                  <i class="fa fa-user" style="font-size:1.5rem;color:var(--muted)"></i>
                <?php endif; ?>
              </td>

              <td><?= htmlspecialchars($u['name'] ?? '—') ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
              <td>
                <span class="badge-role <?= (strtoupper($u['role']) === 'ADMIN') ? 'badge-admin' : 'badge-user' ?>">
                  <?= htmlspecialchars($u['role']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($u['createdOn'] ?? '—') ?></td>
              <td>
                <div class="action-cell">
                  <button class="icon-btn edit" title="Edit"
                    onclick='openEdit(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)'><i
                      class="fa fa-pen"></i></button>
                  <?php if ($_SESSION['username'] !== $u['username']): ?>
                    <button class="icon-btn delete" title="Delete"
                      onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"><i
                        class="fa fa-trash"></i></button>
                  <?php else: ?>
                    <button class="icon-btn delete" title="You cannot delete yourself"
                      style="opacity:0.3; cursor:not-allowed;" disabled><i class="fa fa-trash"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <div class="pagination-bar" style="display:flex; justify-content:space-between; align-items:center; margin:15px 28px; font-size:14px; color:var(--muted);">
    <div>
      <?php 
      $startRecord = $total_records > 0 ? $offset + 1 : 0;
      $endRecord = min($offset + $limit, $total_records);
      ?>
      Showing <?= $startRecord ?>–<?= $endRecord ?> of <?= $total_records ?> records. &nbsp;|&nbsp; Page <?= $page ?> of <?= $total_pages ?>
    </div>

    <div style="display:flex; gap:5px; align-items:center;">
      <?php if ($page <= 1): ?>
        <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">First</span>
        <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Previous</span>
      <?php else: ?>
        <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=1" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">First</a>
        <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page - 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Previous</a>
      <?php endif; ?>

      <a style="padding:6px 12px; background:var(--green); color:#fff; border-radius:5px; text-decoration:none; font-weight:bold;"><?= $page ?></a>

      <?php if ($page >= $total_pages): ?>
        <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Next</span>
        <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Last</span>
      <?php else: ?>
        <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page + 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Next</a>
        <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $total_pages ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Last</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- EDIT / NEW USER MODAL -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="editModalTitle"><i class="fa fa-user-plus" style="margin-right:8px"></i>Add New User</h2>
        <button class="close-btn" onclick="closeModal('editModal')">✕</button>
      </div>
      <form id="editForm" onsubmit="return submitEditForm(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="form-grid">

            <div class="form-group">
              <label>Name <span style="color:var(--red)">*</span></label>
              <input type="text" name="name" id="edit_name" placeholder="Full name" maxlength="100" required>
              <span class="field-error" id="err_name"></span>
            </div>

            <div class="form-group">
              <label>Username <span style="color:var(--red)">*</span></label>
              <input type="text" name="username" id="edit_username" placeholder="Username (Alphanumeric)" maxlength="50"
                required>
              <span class="field-error" id="err_username"></span>
            </div>

            <div class="form-group">
              <label id="pass_label">Password <span style="color:var(--red)">*</span></label>
              <input type="password" name="password" id="edit_password" placeholder="Password" maxlength="50" required>
              <span class="field-error" id="err_password"></span>
            </div>

            <div class="form-group">
              <label>Phone Number 1</label>
              <input type="text" name="phone_number" id="edit_phone_number" placeholder="Phone number" maxlength="20">
              <span class="field-error" id="err_phone_number"></span>
            </div>

            <div class="form-group">
              <label>Phone Number 2</label>
              <input type="text" name="phone_number_2" id="edit_phone_number_2" placeholder="Alternate phone number"
                maxlength="20">
              <span class="field-error" id="err_phone_number_2"></span>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" id="edit_email" placeholder="Email address" maxlength="100">
              <span class="field-error" id="err_email"></span>
            </div>

            <div class="form-group">
              <label>Date of Birth</label>
              <input type="date" name="dob" id="edit_dob">
              <span class="field-error" id="err_dob"></span>
            </div>

            <div class="form-group">
              <label>Address</label>
              <textarea name="address" id="edit_address" placeholder="Address"></textarea>
              <span class="field-error" id="err_address"></span>
            </div>

            <div class="form-group">
              <label>Gender</label>
              <select name="gender" id="edit_gender">
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
                <option value="Prefer not to say">Prefer not to say</option>
              </select>
              <span class="field-error" id="err_gender"></span>
            </div>

            <div class="form-group">
              <label>Photo</label>
              <div class="photo-upload-box">
                <button type="button" class="photo-trigger" onclick="triggerPhotoCapture()" aria-label="Take photo">
                  <div class="photo-preview" id="edit_photo_preview_box">
                    <img id="edit_photo_preview" src="" alt="Photo preview">
                    <i id="edit_photo_placeholder" class="fa fa-camera"></i>
                  </div>
                </button>
                <input type="file" name="photo" id="edit_photo" accept="image/*" capture="environment"
                  onchange="previewPhotoInput(this)">
                <input type="hidden" name="existing_photo" id="edit_existing_photo" value="">
                <span class="photo-hint">Tap the preview to take a photo or choose from gallery</span>
              </div>
            </div>

            <div class="form-group">
              <label>Role <span style="color:var(--red)">*</span></label>
              <select name="role" id="edit_role" required>
                <option value="ADMIN">ADMIN</option>
                <option value="USER">USER</option>
                <option value="MECHANIC">MECHANIC</option>
                <option value="BILLING">BILLING</option>
              </select>
              <span class="field-error" id="err_role"></span>
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

  <script>
    function toggleFilter() { document.getElementById('filterPanel').classList.toggle('open'); }
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) {
      document.getElementById(id).classList.remove('open');
      clearFormErrors();
    }

    document.querySelectorAll('.modal-overlay').forEach(function (o) {
      o.addEventListener('click', function (e) { if (e.target === o) { o.classList.remove('open'); clearFormErrors(); } });
    });

    function clearFormErrors() {
      ['edit_name', 'edit_username', 'edit_password', 'edit_phone_number', 'edit_phone_number_2', 'edit_email', 'edit_dob', 'edit_address', 'edit_gender', 'edit_role'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('is-invalid');
        var err = document.getElementById('err_' + id.replace('edit_', ''));
        if (err) err.textContent = '';
      });
    }

    function resetPhotoPreview() {
      var preview = document.getElementById('edit_photo_preview');
      var placeholder = document.getElementById('edit_photo_placeholder');
      var input = document.getElementById('edit_photo');
      var existing = document.getElementById('edit_existing_photo');
      if (preview) {
        preview.src = '';
        preview.style.display = 'none';
      }
      if (placeholder) placeholder.style.display = 'inline-block';
      if (input) input.value = '';
      if (existing) existing.value = '';
    }

    function previewPhotoInput(input) {
      var preview = document.getElementById('edit_photo_preview');
      var placeholder = document.getElementById('edit_photo_placeholder');
      if (!input || !input.files || !input.files[0]) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        if (preview) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        }
        if (placeholder) placeholder.style.display = 'none';
      };
      reader.readAsDataURL(input.files[0]);
    }

    function triggerPhotoCapture() {
      var input = document.getElementById('edit_photo');
      if (input) input.click();
    }

    function openNewUser() {
      document.getElementById('edit_id').value = '';
      document.getElementById('edit_name').value = '';
      document.getElementById('edit_username').value = '';
      document.getElementById('edit_username').readOnly = false;
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_password').required = true;
      document.getElementById('edit_phone_number').value = '';
      document.getElementById('edit_phone_number_2').value = '';
      document.getElementById('edit_email').value = '';
      document.getElementById('edit_dob').value = '';
      document.getElementById('edit_address').value = '';
      document.getElementById('edit_gender').value = '';
      resetPhotoPreview();
      document.getElementById('pass_label').innerHTML = 'Password <span style="color:var(--red)">*</span>';
      document.getElementById('edit_role').value = 'ADMIN';
      document.getElementById('editModalTitle').innerHTML = '<i class="fa fa-user-plus" style="margin-right:8px"></i>Add New User';
      openModal('editModal');
    }

    function openEdit(u) {
      document.getElementById('edit_id').value = u.id;
      document.getElementById('edit_name').value = u.name || '';
      document.getElementById('edit_username').value = u.username;
      document.getElementById('edit_username').readOnly = true;
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_password').required = false;
      document.getElementById('edit_phone_number').value = u.phone_number || '';
      document.getElementById('edit_phone_number_2').value = u.phone_number_2 || '';
      document.getElementById('edit_email').value = u.email || '';
      document.getElementById('edit_dob').value = u.dob || '';
      document.getElementById('edit_address').value = u.address || '';
      document.getElementById('edit_gender').value = u.gender || '';
      var existingPhoto = u.photo || '';
      var preview = document.getElementById('edit_photo_preview');
      var placeholder = document.getElementById('edit_photo_placeholder');
      var existingInput = document.getElementById('edit_existing_photo');
      var photoInput = document.getElementById('edit_photo');
      if (existingInput) existingInput.value = existingPhoto;
      if (photoInput) photoInput.value = '';
      if (preview) {
        if (existingPhoto) {
          preview.src = '../uploads/user_photos/' + existingPhoto;
          preview.style.display = 'block';
          if (placeholder) placeholder.style.display = 'none';
        } else {
          preview.src = '';
          preview.style.display = 'none';
          if (placeholder) placeholder.style.display = 'inline-block';
        }
      }
      document.getElementById('pass_label').innerHTML = 'Password <span style="font-weight:normal; text-transform:none; font-size:11px; color:var(--muted)">(Leave blank to keep unchanged)</span>';
      document.getElementById('edit_role').value = (u.role || 'ADMIN').toUpperCase();
      document.getElementById('editModalTitle').innerHTML = '<i class="fa fa-user-pen" style="margin-right:8px"></i>Edit User';
      openModal('editModal');
    }

    function submitEditForm(e) {
      e.preventDefault();
      var id = document.getElementById('edit_id').value;
      var name = document.getElementById('edit_name').value.trim();
      var username = document.getElementById('edit_username').value.trim();
      var password = document.getElementById('edit_password').value;
      var phoneNumber = document.getElementById('edit_phone_number').value.trim();
      var phoneNumber2 = document.getElementById('edit_phone_number_2').value.trim();
      var email = document.getElementById('edit_email').value.trim();
      var dob = document.getElementById('edit_dob').value;
      var address = document.getElementById('edit_address').value.trim();
      var gender = document.getElementById('edit_gender').value;
      var role = document.getElementById('edit_role').value;

      var valid = true;
      clearFormErrors();

      if (!name) {
        setError('edit_name', 'Name is required');
        valid = false;
      }

      if (!id && !password) {
        setError('edit_password', 'Password is required for new users');
        valid = false;
      }

      if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
        setError('edit_username', 'Username must be 3-30 alphanumeric characters or underscores');
        valid = false;
      }

      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setError('edit_email', 'Please enter a valid email address');
        valid = false;
      }

      if (!valid) return false;

      var fd = new FormData();
      var photoInput = document.getElementById('edit_photo');
      fd.append('id', id);
      fd.append('name', name);
      fd.append('username', username);
      fd.append('password', password);
      fd.append('phone_number', phoneNumber);
      fd.append('phone_number_2', phoneNumber2);
      fd.append('email', email);
      fd.append('dob', dob);
      fd.append('address', address);
      fd.append('gender', gender);
      fd.append('existing_photo', document.getElementById('edit_existing_photo').value);
      fd.append('role', role);
      if (photoInput && photoInput.files && photoInput.files[0]) {
        fd.append('photo', photoInput.files[0]);
      }

      fetch('user_save.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            closeModal('editModal');
            location.href = 'manage_users.php?saved=1';
          } else {
            alert(res.message || 'Operation failed.');
          }
        })
        .catch(function () {
          alert('Save failed. Please try again.');
        });

      return false;
    }

    function setError(id, msg) {
      var el = document.getElementById(id);
      if (el) el.classList.add('is-invalid');
      var err = document.getElementById('err_' + id.replace('edit_', ''));
      if (err) err.textContent = msg;
    }

    function deleteUser(id, username) {
      if (confirm("Are you sure you want to delete user '" + username + "'?")) {
        location.href = "user_delete.php?id=" + id;
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('action') === 'change_password') {
        openNewUser();
        var passInput = document.getElementById('edit_password');
        if (passInput) {
          setTimeout(function () {
            passInput.focus();
            passInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }, 300);
        }
      }
    });
  </script>

  <?php include("../includes/footer.php"); ?>
</body>

</html>
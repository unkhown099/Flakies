<?php
// admin/manage_users.php
require __DIR__ . '/../config/db_connect.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}
// fetch staff list
$stmt = $conn->prepare("SELECT id, username, name, role, status, created_at FROM staff ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Manage Users | Flakies Admin</title>
  <link rel="icon" href="../assets/logo-placeholder.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --gold1: #d9ed42;
      --gold2: #d39e2a;
      --cream: #e0d979ff;
      --dark: #000;
      --light: #fafafa;
      --muted: #f7f8fa;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      display: flex;
      min-height: 100vh;
      background: var(--muted);
      color: var(--dark);
    }

    /* === UNIVERSAL SIDEBAR STYLE === */
    .sidebar {
      width: 260px;
      flex-shrink: 0;
      background: linear-gradient(180deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
      color: #000;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: flex-start;
      padding: 25px 20px;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
      border-top-right-radius: 20px;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      margin: 0 !important;
      box-sizing: border-box !important;
    }

    main.container,
    .main-content {
      margin-left: 260px !important;
      padding: 40px 50px;
      background: #fafafa;
      min-height: 100vh;
      box-sizing: border-box;
    }


    .sidebar .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 26px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .sidebar .logo img {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
    }


    .sidebar .welcome {
      font-size: 14px;
      color: rgba(0, 0, 0, 0.7);
      margin-bottom: 25px;
      font-weight: 500;
    }

    /* MENU LINKS */
    .menu {
      list-style: none;
      width: 100%;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 6px;
      /* consistent even spacing between each item */
    }

    .menu li {
      margin: 0;
    }

    .menu a {
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: #000;
      font-weight: 600;
      padding: 12px 18px;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .menu a:hover,
    .menu a.active {
      background: rgba(0, 0, 0, 0.1);
      color: #000;
      transform: translateX(4px);
    }


    /* LOGOUT BUTTON */
    /* LOGOUT BUTTON — Professional Version */
    .btn-logout {
      margin-top: auto;
      width: 100%;
      background: linear-gradient(135deg, #000 0%, #222 100%);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 15px;
      padding: 12px 0;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
      position: relative;
      overflow: hidden;
    }

    /* Gold accent glow when hovered */
    .btn-logout::before {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(120deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
      transition: all 0.5s ease;
    }

    .btn-logout:hover::before {
      left: 100%;
    }

    .btn-logout:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      background: linear-gradient(135deg, #111 0%, #000 100%);
    }

    /* Optional small gold outline on hover */
    .btn-logout:hover {
      border: 1px solid #e0c65a;
    }


    /* ENSURE CONSISTENT BOX MODEL */
    * {
      box-sizing: border-box;
    }


    /* MAIN CONTAINER */
    .container {
      flex-grow: 1;
      margin-left: 260px;
      /* push beside sidebar */
      padding: 40px 50px;
      background: #fafafa;
      overflow-y: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    /* === GLOBAL HEADER STYLING === */
    h1,
    h2,
    h3 {
      font-family: "Poppins", sans-serif;
      font-weight: 700;
      color: #000;
      letter-spacing: 0.5px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
    }

    /* Subtle underline accent for top-level headers */
    h1::after,
    h2::after,
    h3::after {
      content: "";
      flex-grow: 1;
      height: 3px;
      border-radius: 10px;
      background: linear-gradient(90deg, #d9ed42, #d39e2a);
      margin-left: 12px;
      opacity: 0.4;
    }

    /* Sizes for hierarchy */
    h1 {
      font-size: 28px;
    }

    h2 {
      font-size: 22px;
    }

    h3 {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    /* Optional: give icons or emojis inside headers consistent look */
    h1 span.icon,
    h2 span.icon,
    h3 span.icon {
      font-size: 24px;
    }

    /* BUTTONS */
    .btn-primary {
      background: linear-gradient(135deg, var(--gold1), var(--gold2));
      color: #000;
      font-weight: 700;
      padding: 10px 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, var(--gold2), var(--gold1));
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #f1f1f1;
      border: 0;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
      font-weight: 600;
    }

    .btn-secondary:hover {
      background: #e4e4e4;
    }

    /* TABLE */
    .table-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px 25px;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    th,
    td {
      padding: 12px 14px;
      text-align: left;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }

    th {
      background: #fafafa;
      font-weight: 700;
      color: #000;
    }

    /* ROLES */
    .role {
      padding: 6px 10px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 12px;
      color: #fff;
      text-transform: capitalize;
    }

    .role.admin {
      background: #b7410e;
    }

    .role.manager {
      background: #2d6a9f;
    }

    .role.encoder {
      background: #6b8e23;
    }

    .role.cashier {
      background: #a67c00;
    }

    .role.kitchen {
      background: #7b00ffff;
    }

    .role.inventory_clerk {
      background: #555;
    }

    /* STATUS */
    .status {
      font-weight: 600;
      font-size: 13px;
    }

    /* MODALS */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      align-items: center;
      justify-content: center;
      z-index: 999;
    }

    .modal .content {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      width: 360px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }

    .modal h3 {
      margin-top: 0;
      font-size: 20px;
      color: #000;
    }

    .form-row {
      margin: 10px 0;
    }

    .form-row input,
    .form-row select {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-family: inherit;
    }

    .small-note {
      font-size: 13px;
      color: #666;
      margin-top: 6px;
    }

    .actions button {
      margin-right: 6px;
    }
  </style>
</head>

<body>
  <aside class="sidebar">
    <div class="logo">
      <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
      <span>Flakies</span>
    </div>
    <div class="welcome">Admin Panel</div>
    <nav class="menu">
      <a href="dashboard.php">🏠 Dashboard</a>
      <a class="active" href="manage_users.php">👥 Manage Users</a>
      <a href="manage_products.php">📦 Manage Products</a>
      <a href="manage_report.php">📊 Reports</a>
      <li><a href="manage_pages.php">📝 Manage Pages</a></li>
    </nav>
    <a class="btn-logout" href="../login/logout.php">🚪 Logout</a>
  </aside>

  <main class="container">
    <div class="h1">
      <h1><span class="icon">👥</span> Manage Users</h1>

      <button id="openAdd" class="btn-primary">+ Add User</button>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="usersTbody">
          <?php foreach ($staff as $s): ?>
            <tr data-id="<?= $s['id'] ?>">
              <td><?= htmlspecialchars($s['id']) ?></td>
              <td><?= htmlspecialchars($s['username']) ?></td>
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><span class="role <?= htmlspecialchars($s['role']) ?>"><?= ucfirst(htmlspecialchars($s['role'])) ?></span></td>
              <td class="status"><?= ucfirst(htmlspecialchars($s['status'])) ?></td>
              <td><?= date("M d, Y", strtotime($s['created_at'])) ?></td>
              <td class="actions">
                <button class="btn-secondary editBtn" data-id="<?= $s['id'] ?>">✏️ Edit</button>
                <button class="btn-secondary toggleBtn" data-id="<?= $s['id'] ?>"><?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                <button class="btn-secondary changePassBtn" data-id="<?= $s['id'] ?>">🔑 Change Password</button>
                <button class="btn-secondary deleteBtn" data-id="<?= $s['id'] ?>">🗑 Delete</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Add/Edit Modal -->
  <div id="modal" class="modal" aria-hidden="true">
    <div class="content">
      <h3 id="modalTitle">Add User</h3>
      <form id="userForm">
        <input type="hidden" name="id" id="uid">
        <div class="form-row">
          <input id="uusername" name="username" placeholder="Username" required>
        </div>
        <div class="form-row">
          <input id="uname" name="name" placeholder="Full name" required>
        </div>
        <div class="form-row">
          <select id="urole" name="role" required>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="encoder">Encoder</option>
            <option value="cashier">Cashier</option>
            <option value="kitchen">Kitchen</option>
            <option value="inventory_clerk">Inventory Clerk</option>
          </select>
        </div>
        <div class="form-row">
          <input id="upassword" type="password" name="password" placeholder="Password" required>
          <div class="small-note">Password will be hashed automatically.</div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" id="closeModal" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Change Pass Modal -->
  <div id="passModal" class="modal">
    <div class="content">
      <h3>Change Password</h3>
      <form id="passForm">
        <input type="hidden" id="pass_user_id" name="user_id">
        <div class="form-row">
          <input id="new_password" name="new_password" placeholder="New password" required>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" onclick="closePassModal()" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary">Change</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // helper
    const $ = sel => document.querySelector(sel);
    const $$ = sel => document.querySelectorAll(sel);

    const modal = $('#modal');
    const passModal = $('#passModal');
    const openAdd = $('#openAdd');
    const closeModal = $('#closeModal');
    const userForm = $('#userForm');
    const passForm = $('#passForm');

    openAdd.addEventListener('click', () => {
      $('#modalTitle').textContent = 'Add User';
      $('#uid').value = '';
      $('#uusername').value = '';
      $('#uname').value = '';
      $('#urole').value = 'cashier';
      $('#upassword').value = '';
      modal.style.display = 'flex';
    });

    closeModal.addEventListener('click', () => modal.style.display = 'none');

    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('editBtn')) {
        const id = e.target.dataset.id;
        fetch(`edit_user.php?id=${id}`)
          .then(r => r.json()).then(data => {
            $('#modalTitle').textContent = 'Edit User';
            $('#uid').value = data.id;
            $('#uusername').value = data.username;
            $('#uname').value = data.name;
            $('#urole').value = data.role;
            $('#upassword').value = ''; // blank: only set if want to change
            modal.style.display = 'flex';
          }).catch(() => Swal.fire('Error', 'Failed to fetch user', 'error'));
      }

      if (e.target.classList.contains('deleteBtn')) {
        const id = e.target.dataset.id;
        Swal.fire({
          title: 'Delete?',
          text: 'This will permanently delete the user.',
          icon: 'warning',
          showCancelButton: true
        }).then(res => {
          if (res.isConfirmed) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${id}`
              })
              .then(r => r.json()).then(resp => {
                if (resp.status === 'success') Swal.fire('Deleted', 'User deleted', 'success').then(() => location.reload());
                else Swal.fire('Error', resp.message || 'Failed', 'error');
              });
          }
        });
      }

      if (e.target.classList.contains('toggleBtn')) {
        const id = e.target.dataset.id;
        fetch('toggle_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}`
          })
          .then(r => r.json()).then(resp => {
            if (resp.status === 'success') Swal.fire('Updated', resp.message, 'success').then(() => location.reload());
            else Swal.fire('Error', resp.message, 'error');
          });
      }

      if (e.target.classList.contains('changePassBtn')) {
        const id = e.target.dataset.id;
        $('#pass_user_id').value = id;
        passModal.style.display = 'flex';
      }
    });

    userForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      const fd = new FormData(userForm);
      fetch('save_user.php', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json()).then(resp => {
          if (resp.status === 'success') {
            Swal.fire('Saved', resp.message, 'success').then(() => location.reload());
          } else {
            Swal.fire('Error', resp.message || 'Failed', 'error');
          }
        }).catch(() => Swal.fire('Error', 'Request failed', 'error'));
    });

    passForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      const fd = new FormData(passForm);
      fetch('change_password.php', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json()).then(resp => {
          if (resp.status === 'success') {
            Swal.fire('Changed', 'Password updated', 'success').then(() => passModal.style.display = 'none');
          } else Swal.fire('Error', resp.message || 'Failed', 'error');
        }).catch(() => Swal.fire('Error', 'Request failed', 'error'));
    });

    function closePassModal() {
      passModal.style.display = 'none';
    }
  </script>
</body>

</html>
<?php
session_start();

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role'])) {
  header("Location: ../login.php");
  exit();
}

if ($_SESSION['role'] !== 'admin') {
  echo "Access denied. You must be an admin to view this page.";
  exit();
}

require __DIR__ . '/../config/db_connect.php';

$staff_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Define About Us sections (section_name values from database)
$sections = [
  'hero' => 'About Flakies',
  'our_story' => 'Our Story',
  'our_mission' => 'Our Mission',
  'our_values' => 'Our Values',
  'meet_team' => 'Meet Our Team'
];

// Handle form submission
if (isset($_POST['update_section'])) {
  $section = $_POST['section'];
  $content = $_POST['content'];

  // Update based on page_name='about' and section_name
  $stmt = $conn->prepare("UPDATE pages SET content = ?, updated_at = NOW() WHERE page_name = 'about' AND section_name = ?");
  $stmt->bind_param("ss", $content, $section);
  $stmt->execute();
  $stmt->close();

  $message = $sections[$section] . " section updated successfully!";
}

// Fetch sections content from database
$page_contents = [];
foreach (array_keys($sections) as $section_name) {
  $stmt = $conn->prepare("SELECT content FROM pages WHERE page_name = 'about' AND section_name = ?");
  $stmt->bind_param("s", $section_name);
  $stmt->execute();
  $stmt->bind_result($content);
  $stmt->fetch();
  $page_contents[$section_name] = $content ?? '';
  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Flakies | Manage Pages</title>
  <link rel="icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
  <style>
    body {
      display: flex;
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #f7f8fa;
      color: #222;
    }

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
    }

    main.container,
    .main-content {
      margin-left: 260px;
      padding: 40px 50px;
      background: #fafafa;
      min-height: 100vh;
      box-sizing: border-box;
      width: calc(100% - 260px);
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

    .menu {
      list-style: none;
      width: 100%;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 6px;
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
      cursor: pointer;
      text-decoration: none;
      display: block;
    }

    h1 {
      font-size: 28px;
      font-weight: 700;
      color: #000;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .message {
      margin-bottom: 20px;
      padding: 15px;
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      border-radius: 6px;
      font-weight: 600;
    }

    .sections-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .section-card {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .section-card:hover {
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }

    .section-title {
      font-size: 20px;
      font-weight: 700;
      color: #000;
      margin: 0;
    }

    .section-content {
      padding: 15px;
      background: #f9f9f9;
      border-radius: 6px;
      min-height: 100px;
      margin-bottom: 15px;
      line-height: 1.6;
      color: #333;
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .section-content.empty {
      color: #999;
      font-style: italic;
    }

    .btn-edit {
      background: #d9ed42;
      color: #000;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
    }

    .btn-edit:hover {
      background: #c5d939;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      animation: fadeIn 0.3s ease;
    }

    .modal.active {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 700px;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    @keyframes slideUp {
      from {
        transform: translateY(50px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 22px;
      font-weight: 700;
      color: #000;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 28px;
      cursor: pointer;
      color: #999;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      background: #f0f0f0;
      color: #000;
    }

    .modal-body label {
      font-weight: 600;
      display: block;
      margin-bottom: 8px;
      color: #000;
    }

    .modal-body textarea {
      width: 100%;
      min-height: 200px;
      padding: 12px;
      border-radius: 6px;
      border: 1px solid #ccc;
      resize: vertical;
      font-family: "Poppins", sans-serif;
      font-size: 14px;
      line-height: 1.6;
      box-sizing: border-box;
    }

    .modal-body textarea:focus {
      outline: none;
      border-color: #d9ed42;
      box-shadow: 0 0 0 3px rgba(217, 237, 66, 0.2);
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .btn-save {
      background: #d9ed42;
      color: #000;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .btn-save:hover {
      background: #c5d939;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-cancel {
      background: #e0e0e0;
      color: #333;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .btn-cancel:hover {
      background: #d0d0d0;
    }

    input[type="hidden"] {
      display: none;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
      <span>Flakies</span>
    </div>
    <div class="welcome"><?= ucfirst($role) ?> Panel</div>
    <ul class="menu">
      <li><a href="dashboard.php">🏠 Dashboard</a></li>
      <li><a href="manage_users.php">👥 Manage Users</a></li>
      <li><a href="manage_products.php">📦 Manage Products</a></li>
      <a href="manage_report.php">📊 Reports</a>
      <li><a href="manage_pages.php" class="active">📝 Manage Pages</a></li>
    </ul>
    <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
  </div>

  <div class="main-content">
    <h1>📝 Manage About Us Sections</h1>

    <?php if (isset($message)) echo "<div class='message'>✓ $message</div>"; ?>

    <div class="sections-container">
      <?php foreach ($sections as $section_name => $section_title): ?>
        <div class="section-card">
          <div class="section-header">
            <h3 class="section-title"><?= $section_title ?></h3>
            <button class="btn-edit" onclick="openEditModal('<?= $section_name ?>', '<?= $section_title ?>')">✏️ Edit</button>
          </div>
          <div class="section-content <?= empty($page_contents[$section_name]) ? 'empty' : '' ?>">
            <?= empty($page_contents[$section_name]) ? 'No content yet. Click Edit to add content.' : $page_contents[$section_name] ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Edit Section</h2>
        <button class="close-btn" onclick="closeEditModal()">&times;</button>
      </div>
      <form method="POST" id="editForm">
        <div class="modal-body">
          <input type="hidden" name="section" id="modalSection">
          <label for="modalContent">Content:</label>
          <textarea name="content" id="modalContent" required></textarea>
          <label>Preview:</label>
          <div id="livePreview" style="padding:10px; border:1px solid #ccc; border-radius:6px; min-height:100px;">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" name="update_section" class="btn-save">💾 Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../assets/tinymce/js/tinymce/tinymce.min.js"></script>
  <script>
    const sections = <?= json_encode($page_contents); ?>;
    const modal = document.getElementById('editModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSection = document.getElementById('modalSection');
    const livePreview = document.getElementById('livePreview');

    // Initialize TinyMCE
    tinymce.init({
      selector: '#modalContent',
      height: 300,
      menubar: false,
      plugins: 'lists link image table code',
      toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | code',
      branding: false,
      license_key: 'gpl',
      setup: function(editor) {
        // Update live preview on content change
        editor.on('keyup change', function() {
          livePreview.innerHTML = editor.getContent();
        });
      }
    });

    function openEditModal(section, title) {
      modalTitle.textContent = 'Edit ' + title;
      modalSection.value = section;

      // Wait until TinyMCE is ready
      tinymce.get('modalContent').setContent(sections[section] || '');
      livePreview.innerHTML = sections[section] || '';

      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeEditModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeEditModal();
      }
    });
  </script>
</body>

</html>
<?php
require_once __DIR__ . '/controllers/edit_profile_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>แก้ไขโปรไฟล์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/php-edit_profile.css">
</head>
<body>
  <!-- Top Bar -->
  <div class="topbar">
    <div class="container">
      <a class="btn btn-dark btn-sm rounded-pill px-3" href="profile.php">&larr; กลับ</a>
      <div class="topbar-title ms-1">แก้ไขข้อมูลโปรไฟล์</div>
      <div class="ms-auto d-none d-sm-block help">อัปเดตชื่อผู้ใช้ ชื่อ–นามสกุล และรหัสผ่าน</div>
    </div>
  </div>

  <main class="page">
    <?php if ($error): ?>
      <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card-modern">
      <div class="card-body">
        <h5 class="mb-3 fw-bold">ข้อมูลบัญชี</h5>
        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label">ชื่อผู้ใช้</label>
            <input type="text" name="username" class="form-control" required
                   value="<?= htmlspecialchars($user['username'] ?? '') ?>">
            <div class="help mt-1">ต้องไม่ซ้ำกับผู้ใช้อื่น</div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label">ชื่อจริง</label>
              <input type="text" name="fname" class="form-control" required
                     value="<?= htmlspecialchars($user['fname'] ?? '') ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label">นามสกุล</label>
              <input type="text" name="lname" class="form-control" required
                     value="<?= htmlspecialchars($user['lname'] ?? '') ?>">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">รหัสผ่านใหม่ <span class="help">(ถ้าไม่เปลี่ยน ให้เว้นว่าง)</span></label>
            <div class="input-group">
              <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••">
              <button class="btn btn-outline-secondary" type="button" id="togglePwd">แสดง</button>
            </div>
          </div>

          <div class="actions d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-brand flex-fill">บันทึกการเปลี่ยนแปลง</button>
            <a href="profile.php" class="btn btn-ghost flex-fill">กลับ</a>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script>
    // แสดง/ซ่อนรหัสผ่าน
    const toggle = document.getElementById('togglePwd');
    const pwd = document.getElementById('pwd');
    if (toggle && pwd) {
      toggle.addEventListener('click', () => {
        const isPwd = pwd.type === 'password';
        pwd.type = isPwd ? 'text' : 'password';
        toggle.textContent = isPwd ? 'ซ่อน' : 'แสดง';
      });
    }
  </script>
</body>
</html>

<?php
// navbar_main.php
// Expected Variables from Parent:
// $currentUserId (optional, default 0)
// $userDisplayName (optional, default '')
// $userAvatarImage (optional, default '')
// $userAvatarText (optional, default 'U')

$currentUserId = $currentUserId ?? 0;
if ($currentUserId === 0 && !empty($_SESSION['user_id'])) {
    $currentUserId = (int)$_SESSION['user_id'];
}

$userDisplayName = $userDisplayName ?? '';
$userAvatarImage = $userAvatarImage ?? '';
$userAvatarText = $userAvatarText ?? 'U';

if ($currentUserId > 0 && empty($userDisplayName)) {
    global $pdo; // Assume $pdo is available for DB queries if needed, or use $conn if using mysqli
    if (isset($pdo)) {
        $stmt_nav = $pdo->prepare("SELECT fname, lname, username, img FROM users WHERE user_id = ? LIMIT 1");
        $stmt_nav->execute([$currentUserId]);
        if ($u_nav = $stmt_nav->fetch()) {
            if (!empty($u_nav['img'])) $userAvatarImage = (strpos($u_nav['img'], 'http') === 0) ? $u_nav['img'] : $baseUrl . '/uploads/avatars/' . basename($u_nav['img']);
            $fn = trim((string)($u_nav['fname'] ?? ''));
            $ln = trim((string)($u_nav['lname'] ?? ''));
            $un = h(trim((string)($u_nav['username'] ?? '')));
            $userDisplayName = ($fn !== '' || $ln !== '') ? h(trim("$fn $ln")) : $un;
            if ($userDisplayName === '') $userDisplayName = "User #$currentUserId";
            $userAvatarText = h(mb_substr($userDisplayName, 0, 1) ?: 'U');
        }
    }
}

// Helper to determine active link
$current_page = basename($_SERVER['PHP_SELF']);
function navClass($pageName, $current_page) {
    if ($current_page === $pageName) {
        return "text-primary text-base font-bold transition-colors";
    }
    return "text-slate-300 text-base font-medium hover:text-white transition-colors";
}
?>

<!-- Simplified Helper for base URL -->
<?php $url = $baseUrl ?? ''; ?>

<!-- Navigation Header -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-800 bg-slate-900 px-6 lg:px-20 py-4 sticky top-0 z-50 shadow-sm relative">
  <div class="flex items-center gap-8">
    <a href="<?= $url ?>/" class="flex items-center gap-3 text-white">
      <div class="w-8 h-8 bg-primary flex items-center justify-center rounded-lg">
        <span class="material-symbols-outlined text-slate-900">storefront</span>
      </div>
      <h2 class="text-xl font-bold leading-tight tracking-tight">Marketplace</h2>
    </a>
    <nav class="hidden xl:flex items-center gap-6">
      <a class="<?= navClass('index.php', $current_page) ?>" href="<?= $url ?>/">หน้าแรก</a>
      <a class="<?= navClass('sell.php', $current_page) ?>" href="<?= $url ?>/sell">ลงขายสินค้า</a>
      <a class="<?= navClass('exchange.php', $current_page) ?>" href="<?= $url ?>/exchange">แลกเปลี่ยน</a>
      <a class="<?= navClass('chat_list.php', $current_page) ?> flex items-center gap-1" href="<?= $url ?>/chat">
        แชท <span id="unreadBadge" class="bg-red-500 text-white rounded-full px-2 py-0 text-xs font-bold hidden"></span>
      </a>
      <a class="<?= navClass('my_products.php', $current_page) ?>" href="<?= $url ?>/my-products">สินค้าของฉัน</a>
      <a class="<?= navClass('topup.php', $current_page) ?>" href="<?= $url ?>/topup">เติมเงิน MSU-PAY</a>
      <a class="<?= navClass('getting-started.php', $current_page) ?>" href="<?= $url ?>/php/help/getting-started.php">ช่วยเหลือ</a>
    </nav>
  </div>
  
  <div class="flex flex-1 justify-end gap-6 items-center">
    <?php if (isLoggedIn()): ?>
      <div class="flex items-center gap-4">
        <?php if (isAdmin()): ?>
          <a href="<?= $url ?>/admin/dashboard.php" class="text-primary hover:text-white transition-colors text-sm font-bold flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span> Admin
          </a>
        <?php endif; ?>
        
        <div class="flex items-center gap-3 border-l border-slate-700 pl-4">
          <a href="<?= $url ?>/profile" class="flex items-center gap-3 group">
              <div class="flex flex-col items-end hidden sm:flex">
                <p class="text-base font-bold text-white group-hover:text-primary transition-colors"><?= $userDisplayName ?></p>
              </div>
              <?php if (!empty($userAvatarImage)): ?>
                <div class="bg-center bg-no-repeat w-10  h-10 bg-cover rounded-full border-2 border-primary shadow-sm group-hover:shadow-md transition-all" style="background-image: url('<?= $userAvatarImage ?>');"></div>
              <?php else: ?>
                <div class="bg-primary w-10 h-10 rounded-full border-2 border-primary flex items-center justify-center font-bold text-slate-900 shadow-sm group-hover:shadow-md transition-all"><?= $userAvatarText ?></div>
              <?php endif; ?>
          </a>
          <a href="<?= $url ?>/logout" class="bg-slate-800 p-2 rounded-lg hover:bg-slate-700 transition-colors ml-2" title="ออกจากระบบ">
            <span class="material-symbols-outlined text-slate-300">logout</span>
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="flex items-center gap-4">
        <a href="<?= $url ?>/login" class="text-slate-300 hover:text-white font-bold transition-colors">เข้าสู่ระบบ</a>
        <a href="<?= $url ?>/register" class="bg-primary text-slate-900 px-6 py-2 rounded-lg text-base font-bold hover:bg-opacity-90 transition-all">สมัครสมาชิก</a>
      </div>
    <?php endif; ?>
    
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn" class="xl:hidden bg-slate-800 px-4 py-2 rounded-xl text-slate-300 flex items-center gap-2 hover:bg-slate-700 transition-colors">
        <span class="material-symbols-outlined text-[20px]">menu</span>
        <span class="text-base font-bold">เมนู</span>
    </button>
  </div>
</header>

<!-- Mobile Navigation Menu -->
<div id="mobileMenu" class="hidden xl:hidden bg-slate-900 border-b border-slate-800 px-6 py-4 flex flex-col gap-4 absolute w-full z-40 shadow-lg top-full left-0">
    <a class="<?= navClass('index.php', $current_page) ?> block py-2 border-b border-slate-800 text-base" href="<?= $url ?>/">หน้าแรก</a>
    <a class="<?= navClass('sell.php', $current_page) ?> block py-2 border-b border-slate-800 text-base" href="<?= $url ?>/sell">ลงขายสินค้า</a>
    <a class="<?= navClass('exchange.php', $current_page) ?> block py-2 border-b border-slate-800 text-base" href="<?= $url ?>/exchange">แลกเปลี่ยน</a>
    <a class="<?= navClass('chat_list.php', $current_page) ?> block py-2 border-b border-slate-800 text-base flex items-center justify-between" href="<?= $url ?>/chat">
        แชท <span id="mobileUnreadBadge" class="bg-red-500 text-white rounded-full px-2 py-0 text-xs font-bold hidden"></span>
    </a>
    <a class="<?= navClass('my_products.php', $current_page) ?> block py-2 border-b border-slate-800 text-base" href="<?= $url ?>/my-products">สินค้าของฉัน</a>
    <a class="<?= navClass('topup.php', $current_page) ?> block py-2 border-b border-slate-800 text-base" href="<?= $url ?>/topup">เติมเงิน</a>
    <a class="<?= navClass('getting-started.php', $current_page) ?> block py-2 text-base" href="<?= $url ?>/php/help/getting-started.php">ช่วยเหลือ</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('mobileMenuBtn');
        const menu = document.getElementById('mobileMenu');
        if(btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        }
    });
</script>

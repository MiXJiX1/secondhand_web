<?php
// includes/navbar_back.php
$navTitle = $navTitle ?? 'Secondhand Market';
$backLink = $backLink ?? '../index.php';
$backText = $backText ?? 'กลับหน้าแรก';
$navRight = $navRight ?? '<div style="width:100px" class="spacer"></div>';
?>
<header class="navbar-back" style="background-color: #0f172a; border-bottom-color: #1e293b;">
    <a href="<?= htmlspecialchars($backLink) ?>" class="btn-header" style="background-color: #1e293b; color: #f8fafc; border-color: #334155;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:4px"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <span><?= htmlspecialchars($backText) ?></span>
    </a>
    <h1 class="title" style="color: #ffffff;"><?= $navTitle ?></h1>
    <?= $navRight ?>
</header>


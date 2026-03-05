<?php
require_once __DIR__ . '/controllers/profile_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน | Midnight Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#f9e71f" },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
        
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('bg-primary', 'font-bold', 'text-slate-900', 'shadow-sm');
                el.classList.add('text-slate-600', 'hover:bg-slate-100');
            });
            
            const activeBtn = document.querySelector(`[data-tab="${tabId}"]`);
            if(activeBtn) {
                activeBtn.classList.remove('text-slate-600', 'hover:bg-slate-100');
                activeBtn.classList.add('bg-primary', 'font-bold', 'text-slate-900', 'shadow-sm');
            }
        }

        function openWithdrawModal() {
            document.getElementById('withdrawModal').classList.remove('hidden');
            document.getElementById('withdrawModal').classList.add('flex');
        }

        function closeWithdrawModal() {
            document.getElementById('withdrawModal').classList.add('hidden');
            document.getElementById('withdrawModal').classList.remove('flex');
        }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col">

<!-- Top Navbar -->
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
include __DIR__ . '/../includes/navbar_main.php'; 
?>

<!-- Main Content -->
<main class="flex-1 w-full max-w-5xl mx-auto px-6 py-8">
    
    <!-- Header Section -->
    <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 pb-8 border-b border-slate-100 relative">
            
            <!-- User Identity -->
            <div class="flex items-center gap-6">
                <!-- Avatar -->
                <div class="relative w-28 h-28 flex-shrink-0">
                    <div class="w-full h-full rounded-full ring-4 ring-primary/20 overflow-hidden bg-slate-100 flex items-center justify-center border-2 border-white shadow-sm">
                        <?php if ($avatarPath !== '../assets/no-avatar.png'): ?>
                            <img src="<?= h($avatarPath) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/no-avatar.png';">
                        <?php else: ?>
                            <span class="text-4xl">🙂</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Upload Button -->
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <input type="hidden" name="action" value="upload_avatar">
                        <input type="hidden" name="csrf_token" value="<?= $CSRF ?>">
                        <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="this.form.submit()">
                        <button type="button" onclick="document.getElementById('avatarInput').click()" class="absolute bottom-0 right-0 w-8 h-8 bg-primary text-slate-900 rounded-full flex items-center justify-center hover:bg-yellow-400 transition-colors shadow-md border-2 border-white cursor-pointer tooltip tooltip-top" title="เปลี่ยนรูปโปรไฟล์">
                            <span class="material-symbols-outlined text-[16px] font-bold">edit</span>
                        </button>
                    </form>
                </div>
                
                <!-- Info -->
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h1 class="text-3xl font-bold text-slate-900 tracking-tight mr-1"><?= h($fullName) ?></h1>
                        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                            <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-purple-200"><span class="material-symbols-outlined text-[12px]">shield_person</span> Admin</span>
                        <?php endif; ?>
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-blue-200"><span class="material-symbols-outlined text-[12px]">verified</span> Verified</span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-6 text-sm text-slate-500 font-medium mt-3">
                        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">calendar_month</span> สมาชิกตั้งแต่ <?= $joinDateTh ?></span>
                        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">location_on</span> <?= $location ?></span>
                    </div>
                </div>
            </div>
            
            <button onclick="switchTab('personalInfo')" class="hidden md:block bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-2.5 px-6 rounded-xl transition-all shadow-sm active:scale-95 ml-auto whitespace-nowrap flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">edit</span> แก้ไขโปรไฟล์
            </button>
        </div>
        
        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 divide-x divide-slate-100">
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1"><?= $activeCount ?></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">กำลังลงขาย (Active)</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1"><?= $soldCount ?></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">ขายแล้ว (Sold)</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1 flex items-center justify-center gap-1"><?= $avgRating ?> <span class="material-symbols-outlined text-yellow-400 text-[24px]">star</span></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">คะแนนผู้ใช้ (<?= $ratingCount ?> รีวิว)</p>
            </div>
        </div>
    </div>

    <!-- Two-column Layout -->
    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Left Sidebar (Tabs) -->
        <aside class="w-full lg:w-64 flex-shrink-0 flex flex-col gap-2">
            <button data-tab="personalInfo" onclick="switchTab('personalInfo')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 bg-primary font-bold text-slate-900 shadow-sm transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">person</span> ข้อมูลส่วนตัว
            </button>
            <button data-tab="credits" onclick="switchTab('credits')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span> เครดิต & การถอนเงิน
            </button>
            <button data-tab="history" onclick="switchTab('history')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">history</span> ประวัติการทำรายการ
            </button>
            <button data-tab="bank" onclick="switchTab('bank')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">account_balance</span> บัญชีธนาคาร
            </button>
            <button data-tab="orders" onclick="switchTab('orders')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">receipt_long</span> ประวัติการสั่งซื้อ
            </button>
            <button data-tab="sales" onclick="switchTab('sales')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">storefront</span> ประวัติการขาย
            </button>
            <button data-tab="favorites" onclick="switchTab('favorites')" class="tab-btn w-full text-left px-5 py-3 rounded-xl flex items-center gap-3 text-slate-600 hover:bg-slate-100 font-medium transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">favorite</span> รายการที่สนใจ
            </button>
        </aside>

        <!-- Right Content Area -->
        <div class="flex-1 bg-white rounded-3xl p-8 border border-slate-200 shadow-sm w-full mx-auto md:max-w-3xl">
            
            <?php if (isset($flash)): ?>
                <div class="p-4 mb-6 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?> border text-sm font-medium flex items-center gap-2 animate-in fade-in slide-in-from-top-4 duration-300">
                    <span class="material-symbols-outlined"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span> 
                    <?= htmlspecialchars($flash['msg']) ?>
                </div>
            <?php endif; ?>

            <!-- Tab 1: Personal Information -->
            <div id="personalInfo" class="tab-content">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">ข้อมูลส่วนตัว (Profile Info)</h3>
                </div>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= $CSRF ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">ชื่อผู้ใช้ (Username)</label>
                            <input type="text" value="<?= h($user['username']) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all cursor-not-allowed opacity-60" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">ชื่อจริง (First Name) <span class="text-red-500">*</span></label>
                            <input type="text" name="fname" value="<?= h($user['fname']) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">นามสกุล (Last Name) <span class="text-red-500">*</span></label>
                            <input type="text" name="lname" value="<?= h($user['lname']) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">อีเมล (Email Address) <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?= h($user['email']) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">เบอร์โทรศัพท์ (Phone Number)</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-slate-200 bg-slate-50 text-slate-400 sm:text-sm font-bold">
                                TH
                            </span>
                            <input type="tel" name="phone" value="<?= h($user['phone'] ?? '') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-r-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all flex-1" placeholder="08x-xxx-xxxx">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">ไบโอ (Bio)</label>
                        <textarea name="bio" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="บอกความเป็นตัวคุณให้ผู้ซื้อทราบ..."><?= h($user['bio'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-3 px-8 rounded-xl transition-colors shadow-sm active:scale-95">บันทึกข้อมูลส่วนตัว</button>
                    </div>
                </form>

                <hr class="my-10 border-slate-100">

                <!-- Password Change Section -->
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">ความปลอดภัย (Security)</h3>
                    <p class="text-sm text-slate-500 font-medium">เปลี่ยนรหัสผ่านเพื่อความปลอดภัยของบัญชีคุณ</p>
                </div>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?= $CSRF ?>">

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="old_password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="••••••••" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="อย่างน้อย 6 ตัวอักษร" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-8 rounded-xl transition-colors shadow-sm active:scale-95">เปลี่ยนรหัสผ่าน</button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Credits -->
            <div id="credits" class="tab-content hidden">
                <h3 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">ยอดเครดิตในระบบ</h3>
                
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center max-w-sm mx-auto shadow-inner mb-8">
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs mb-2">เครดิตคงเหลือ</p>
                    <h2 class="text-5xl font-black text-green-600 mb-6 drop-shadow-sm">฿<?= number_format($credit, 2) ?></h2>
                    
                    <div class="flex flex-col gap-3">
                        <a href="<?= $baseUrl ?>/topup" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition-colors flex justify-center items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">add_circle</span> เเติมเครดิต
                        </a>
                        <button type="button" onclick="openWithdrawModal()" class="w-full bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold py-3 px-6 rounded-xl transition-colors flex justify-center items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">payments</span> ถอนเงิน
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Bank -->
            <div id="bank" class="tab-content hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">บัญชีธนาคารสำหรับถอนเงิน</h3>
                </div>
                
                <?php if (!empty($user['bank_account'])): 
                    $bankName = $user['bank_name'] ?? '';
                    $logoFile = $bankLogoMap[$bankName] ?? '';
                ?>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm mb-8">
                        <div class="w-14 h-14 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 flex-shrink-0 overflow-hidden p-1">
                            <?php if ($logoFile): ?>
                                <img src="../assets/banks/<?= $logoFile ?>" class="w-full h-full object-contain">
                            <?php else: ?>
                                <span class="text-3xl">🏦</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-lg"><?= h($user['bank_name']) ?></div>
                            <div class="text-sm text-slate-500 font-medium"><?= h($user['bank_account_name']) ?></div>
                            <code class="text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-sm mt-1 inline-block font-mono border border-slate-200">
                                <?= h(preg_replace('/(\d{3})\d+(\d{4})/', '$1-xxx-xxx-$2', $user['bank_account'])) ?>
                            </code>
                        </div>
                        <div>
                            <?php if ($user['bank_verified']): ?>
                                <span class="bg-green-100 text-green-700 border border-green-200 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">verified</span> ยืนยันแล้ว</span>
                            <?php else: ?>
                                <span class="bg-yellow-100 text-yellow-800 border border-yellow-200 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">pending</span> รอการตรวจสอบ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 text-slate-500 border border-slate-200 rounded-2xl p-8 text-center border-dashed mb-8">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">account_balance</span>
                        <p class="font-medium text-sm">ยังไม่ได้ผูกบัญชีธนาคารสำหรับการถอนเครดิต</p>
                    </div>
                <?php endif; ?>

                <?php if (!$user['bank_verified']): ?>
                <div class="bg-slate-50/50 border border-slate-200 rounded-3xl p-6">
                    <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">edit_square</span> 
                        <?= empty($user['bank_account']) ? 'เพิ่มบัญชีธนาคาร' : 'แก้ไขข้อมูลบัญชีธนาคาร' ?>
                    </h4>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_bank">
                        <input type="hidden" name="csrf_token" value="<?= $CSRF ?>">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ชื่อธนาคาร</label>
                            <select name="bank_name" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" required>
                                <option value="" disabled <?= empty($user['bank_name']) ? 'selected' : '' ?>>เลือกธนาคาร...</option>
                                <?php 
                                $banks = ['กสิกรไทย', 'ไทยพาณิชย์', 'กรุงไทย', 'กรุงเทพ', 'กรุงศรีอยุธยา', 'ทหารไทยธนชาต', 'ออมสิน', 'ธ.ก.ส.'];
                                foreach($banks as $b): ?>
                                    <option value="<?= $b ?>" <?= ($user['bank_name'] === $b) ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">เลขที่บัญชี</label>
                            <input type="text" name="bank_account" value="<?= h($user['bank_account']) ?>" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="xxxxxxxxxx" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ชื่อบัญชี (ภาษาไทย/อังกฤษ ให้ตรงกับสมุดบัญชี)</label>
                            <input type="text" name="bank_account_name" value="<?= h($user['bank_account_name']) ?>" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="นาย สมชาย ใจดี" required>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md active:scale-[0.98]">
                                บันทึกข้อมูลบัญชี
                            </button>
                        </div>
                    </form>
                    <p class="text-[11px] text-slate-400 mt-4 text-center">
                        * ข้อมูลนี้ใช้สำหรับถอนเงินออกจากระบบเท่านั้น กรุณาตรวจสอบให้ถูกต้องเพื่อป้องกันความล่าช้า
                    </p>
                </div>
                <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
                    <span class="material-symbols-outlined text-green-500 text-4xl mb-2">verified_user</span>
                    <h4 class="font-bold text-green-700 text-lg mb-1">บัญชีได้รับการยืนยันแล้ว</h4>
                    <p class="text-sm text-green-600">หากคุณต้องการเปลี่ยนบัญชีธนาคาร กรุณาติดต่อผู้ดูแลระบบ</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab 4: Orders -->
            <div id="orders" class="tab-content hidden">
                <h3 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">ประวัติการสั่งซื้อล่าสุด</h3>
                
                <?php if ($purchases): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                                <th class="pb-3 font-bold pl-2">รายการสินค้า</th>
                                <th class="pb-3 font-bold">เลขออเดอร์</th>
                                <th class="pb-3 font-bold">วันที่</th>
                                <th class="pb-3 font-bold text-right pr-2">ยอดเงิน (฿)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($purchases as $p): 
                                $img = !empty($p['product_image']) ? $baseUrl . '/uploads/'.basename($p['product_image']) : $baseUrl . '/assets/default.png';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="py-4 pl-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-slate-100 rounded overflow-hidden flex-shrink-0">
                                            <img src="<?= h($img) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                                        </div>
                                        <span class="font-bold text-sm text-slate-900 line-clamp-1 max-w-[200px]"><?= h($p['product_name']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <code class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded border border-slate-200">#<?= h($p['order_no']) ?></code>
                                </td>
                                <td class="py-4 text-sm text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($p['created_at'])) ?>
                                </td>
                                <td class="py-4 text-right pr-2">
                                    <span class="font-bold text-slate-900 bg-primary/20 text-yellow-800 px-2 py-1 rounded text-sm whitespace-nowrap">฿<?= number_format((float)$p['amount'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-slate-50 text-slate-500 border border-slate-200 rounded-2xl p-8 text-center border-dashed">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">shopping_bag</span>
                    <p class="font-medium">คุณยังไม่มีประวัติการสั่งซื้อสินค้า</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Sales -->
            <div id="sales" class="tab-content hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">ประวัติการขายสินค้า</h3>
                    <a href="<?= $baseUrl ?>/sales-income" class="text-sm font-bold text-primary hover:text-yellow-600 flex items-center gap-1 transition-colors">
                        ดูรายรับทั้งหมด <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
                
                <?php if ($sales): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                                <th class="pb-3 font-bold pl-2">รายการสินค้า</th>
                                <th class="pb-3 font-bold">เลขออเดอร์</th>
                                <th class="pb-3 font-bold">ผู้ซื้อ</th>
                                <th class="pb-3 font-bold">วันที่</th>
                                <th class="pb-3 font-bold text-right pr-2">ยอดเงิน (฿)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($sales as $s): 
                                $img = !empty($s['product_image']) ? $baseUrl . '/uploads/'.basename($s['product_image']) : $baseUrl . '/assets/default.png';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="py-4 pl-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-slate-100 rounded overflow-hidden flex-shrink-0">
                                            <img src="<?= h($img) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                                        </div>
                                        <span class="font-bold text-sm text-slate-900 line-clamp-1 max-w-[150px]"><?= h($s['product_name']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <code class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded border border-slate-200">#<?= h($s['order_no']) ?></code>
                                </td>
                                <td class="py-4 text-sm font-bold text-slate-700">
                                    <?= h($s['buyer_name']) ?>
                                </td>
                                <td class="py-4 text-sm text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($s['created_at'])) ?>
                                </td>
                                <td class="py-4 text-right pr-2">
                                    <span class="font-bold text-slate-900 bg-green-100/50 text-green-700 px-2 py-1 rounded text-sm whitespace-nowrap">+ ฿<?= number_format((float)$s['amount'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-slate-50 text-slate-500 border border-slate-200 rounded-2xl p-8 text-center border-dashed">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">storefront</span>
                    <p class="font-medium">คุณยังไม่มีประวัติการขายสินค้า</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab 5: Favorites -->
            <div id="favorites" class="tab-content hidden">
                <h3 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">รายการที่สนใจ (Favorites)</h3>
                
                <?php if ($favorites): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($favorites as $f): 
                        $imgField = $f['product_image'];
                        $img = '../assets/default.png';
                        if ($imgField) {
                            if ($imgField[0] === '[') {
                                $arr = json_decode($imgField, true);
                                if (!empty($arr)) $img = $baseUrl . '/uploads/'.basename($arr[0]);
                            } else {
                                $img = $baseUrl . '/uploads/'.basename(explode('|', $imgField)[0]);
                            }
                        }
                    ?>
                    <a href="<?= $baseUrl ?>/product/<?= (int)$f['product_id'] ?>" class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hover:border-primary transition-all group">
                        <div class="w-16 h-16 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img src="<?= h($img) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-slate-900 truncate mb-1"><?= h($f['product_name']) ?></h4>
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-primary">฿<?= number_format((float)$f['product_price'], 2) ?></p>
                                <?php if (!empty($f['location_name'])): ?>
                                    <span class="flex items-center gap-0.5 text-slate-400 text-sm">
                                        <span class="material-symbols-outlined text-[16px]">location_on</span>
                                        <?= h($f['location_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors">chevron_right</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="bg-slate-50 text-slate-500 border border-slate-200 rounded-2xl p-8 text-center border-dashed">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">favorite</span>
                    <p class="font-medium">คุณยังไม่มีรายการที่สนใจ</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Transaction History Tab -->
            <div id="history" class="tab-content hidden animate-in fade-in slide-in-from-bottom-4 duration-300">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">history</span> ประวัติการทำรายการ
                    </h2>
                </div>

                <?php if (count($history) > 0): ?>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">วันที่ - เวลา</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">รายการ</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap text-right">จำนวนเงิน</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($history as $tr): 
                                    $isWithdraw = ($tr['type'] === 'withdraw');
                                    $amtColor   = $isWithdraw ? 'text-red-500' : 'text-green-500';
                                    $amtPrefix  = $isWithdraw ? '-' : '+';
                                    
                                    $stLabel = ''; $stColor = '';
                                    $status = strtolower($tr['status'] ?? '');
                                    switch($status) {
                                        case 'pending': 
                                        case 'requested': $stLabel = 'รอตรวจสอบ'; $stColor = 'bg-yellow-50 text-yellow-600 border-yellow-100'; break;
                                        case 'approved':  $stLabel = 'อนุมัติแล้ว'; $stColor = 'bg-blue-50 text-blue-600 border-blue-100'; break;
                                        case 'paid':
                                        case 'completed': 
                                        case 'success':   $stLabel = 'สำเร็จ'; $stColor = 'bg-green-50 text-green-600 border-green-100'; break;
                                        case 'rejected':
                                        case 'failed':    $stLabel = 'ล้มเหลว'; $stColor = 'bg-red-50 text-red-600 border-red-100'; break;
                                        default:          $stLabel = strtoupper($tr['status'] ?? 'N/A'); $stColor = 'bg-slate-50 text-slate-600 border-slate-100';
                                    }
                                    
                                    $icon = $isWithdraw ? 'arrow_upward' : 'arrow_downward';
                                    $iconBg = $isWithdraw ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-500';
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-xs font-bold text-slate-900"><?= h(date('d/m/Y', strtotime($tr['created_at']))) ?></p>
                                        <p class="text-[10px] text-slate-400"><?= h(date('H:i', strtotime($tr['created_at']))) ?> น.</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3 min-w-[150px]">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center <?= $iconBg ?>">
                                                <span class="material-symbols-outlined text-[18px] font-bold"><?= $icon ?></span>
                                            </div>
                                            <div class="flex-1 truncate">
                                                <p class="text-sm font-bold text-slate-900"><?= $isWithdraw ? 'ถอนเครดิต' : 'เติมเครดิต' ?></p>
                                                <p class="text-[10px] text-slate-500 truncate"><?= h($tr['detail']) ?> • <?= h($tr['ref'] ?: '-') ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <p class="text-sm font-black <?= $amtColor ?>"><?= $amtPrefix ?>฿<?= number_format($tr['amount'], 2) ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex justify-center">
                                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border <?= $stColor ?>">
                                                <?= $stLabel ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 border-dashed">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">ไม่พบประวัติการทำรายการ</h3>
                    <p class="text-sm text-slate-500">คุณยังไม่ได้ทำการเติมเงินหรือถอนเงินในระบบ</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

<!-- Footer -->
<footer class="mt-auto py-8 text-center text-sm text-slate-400 font-medium border-t border-slate-100 w-full" style="background:#f8fafc">
    <div class="flex items-center justify-center gap-2 mb-2 text-slate-800">
        <div class="w-5 h-5 bg-primary rounded flex items-center justify-center">
            <span class="material-symbols-outlined text-[14px]">storefront</span>
        </div>
        <span class="font-bold tracking-tight">Marketplace</span>
    </div>
    <p>&copy; 2024 Marketplace Inc. All rights reserved.</p>
</footer>

<!-- Withdrawal Modal -->
<div id="withdrawModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">payments</span>
                ถอนเงินออกจากระบบ
            </h3>
            <button onclick="closeWithdrawModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="request_withdrawal">
            <input type="hidden" name="csrf_token" value="<?= $CSRF ?>">
            
            <?php if ($user['bank_verified'] != 1): ?>
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl flex items-start gap-3">
                    <span class="material-symbols-outlined text-yellow-600">warning</span>
                    <div class="text-sm text-yellow-800">
                        <p class="font-bold mb-1">บัญชีธนาคารยังไม่ได้รับการยืนยัน</p>
                        <p>กรุณารอแอดมินตรวจสอบข้อมูลบัญชีธนาคารของคุณในแท็บ "บัญชีธนาคาร" ก่อนจึงจะสามารถถอนเงินได้</p>
                    </div>
                </div>
                <button type="button" onclick="closeWithdrawModal()" class="w-full bg-slate-100 text-slate-500 font-bold py-3 rounded-xl cursor-not-allowed" disabled>
                    ถอนเงินไม่ได้ในขณะนี้
                </button>
            <?php else: ?>
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">บัญชีรับเงิน</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-blue-100">
                            <?php 
                            $bLogo = $bankLogoMap[$user['bank_name'] ?? ''] ?? '';
                            if ($bLogo): ?>
                                <img src="../assets/banks/<?= $bLogo ?>" class="w-full h-full object-contain p-1">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-blue-400">account_balance</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm"><?= h($user['bank_name']) ?></p>
                            <p class="text-xs text-slate-500"><?= h($user['bank_account_name']) ?></p>
                            <p class="text-xs font-mono text-blue-600 mt-0.5"><?= h(preg_replace('/(\d{3})\d+(\d{4})/', '$1-xxx-xxx-$2', $user['bank_account'])) ?></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">จำนวนเงินที่ต้องการถอน (บาท)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">฿</span>
                        <input type="number" name="amount" min="20" max="<?= (int)$user['credit_balance'] ?>" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 font-black text-2xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="0.00" required>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 flex justify-between">
                        <span>ถอนขั้นต่ำ: 20.00 บาท</span>
                        <span>ยอดถอนได้สูงสุด: ฿<?= number_format($user['credit_balance'], 2) ?></span>
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-4 rounded-2xl transition-all shadow-lg shadow-primary/20 active:scale-[0.98]">
                        ยืนยันการถอนเงิน
                    </button>
                    <p class="text-[10px] text-slate-400 text-center mt-4">
                        * การถอนจะยื่นเรื่องไปยังแอดมินเพื่อทำการโอนเงินให้คุณภายใน 24-48 ชม.
                    </p>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>

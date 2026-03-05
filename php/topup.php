<?php
require_once __DIR__ . '/controllers/topup_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>เติมเงิน | Midnight Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#f9e71f" },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
    <style>
        .radio-box:checked + label {
            border-color: #f9e71f;
            background-color: #fffae8;
            box-shadow: 0 0 0 1px #f9e71f;
        }
        .method-radio:checked + label {
            border-color: #f9e71f;
            background-color: #fffae8;
        }
        .method-radio:checked + label .custom-radio {
            border-color: #f9e71f;
            background-color: #f9e71f;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col items-center">

<!-- Top Navbar -->
<div class="w-full">
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
include __DIR__ . '/../includes/navbar_main.php'; 
?>
</div>

<!-- Main Content -->
<main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
    
    <!-- User Header Panel -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                <?php if ($userAvatarImage): ?>
                    <img src="<?= h($userAvatarImage) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/default.png';">
                <?php else: ?>
                    <span class="text-2xl font-bold text-slate-400"><?= $userAvatarText ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight leading-none mb-1.5"><?= h($userDisplayName) ?></h2>
                <?php if ($isVerified): ?>
                    <p class="text-slate-500 text-sm font-medium flex items-center justify-center md:justify-start gap-1">Verified Member</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bg-slate-50 border border-slate-200 rounded-xl px-6 py-4 flex items-center gap-4 w-full md:w-auto">
            <div class="w-12 h-12 bg-primary/20 text-yellow-700 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                <span class="material-symbols-outlined text-[24px]">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">ยอดคงเหลือ</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black text-slate-900 leading-none"><?= number_format($bal) ?></span>
                    <span class="text-sm font-bold text-slate-600">บาท</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Side: Selection -->
        <div class="lg:col-span-8 flex flex-col gap-8">
            
            <!-- Packages -->
            <section>
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 mb-4">
                    <span class="material-symbols-outlined text-primary text-[24px]">stat_3</span>
                    เลือกจำนวนเงิน
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Pack 1 -->
                    <div class="relative">
                        <input type="radio" name="package" id="pack_100" value="100" class="radio-box peer hidden" checked>
                        <label for="pack_100" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-slate-300 transition-all text-center h-full">
                            <span class="text-3xl font-black text-slate-900">100</span>
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-3">บาท</span>
                            <span class="text-lg font-bold text-slate-700 mt-auto">฿100.00</span>
                        </label>
                    </div>
                    
                    <!-- Pack 2 (Best Value) -->
                    <div class="relative mt-2 md:mt-0">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-slate-900 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-sm z-10 whitespace-nowrap">Best Value</span>
                        <input type="radio" name="package" id="pack_500" value="500" class="radio-box peer hidden">
                        <label for="pack_500" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-slate-300 transition-all text-center h-full relative overflow-hidden">
                            <span class="text-3xl font-black text-slate-900 mt-1">500</span>
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1">บาท</span>
                            <span class="text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded text-center mb-2">โบนัส 50 บาท</span>
                            <span class="text-lg font-bold text-slate-700 mt-auto">฿500.00</span>
                        </label>
                    </div>
                    
                    <!-- Pack 3 -->
                    <div class="relative">
                        <input type="radio" name="package" id="pack_1000" value="1000" class="radio-box peer hidden">
                        <label for="pack_1000" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-slate-300 transition-all text-center h-full">
                            <span class="text-3xl font-black text-slate-900">1,000</span>
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1">บาท</span>
                            <span class="text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded text-center mb-2">โบนัส 150 บาท</span>
                            <span class="text-lg font-bold text-slate-700 mt-auto">฿1,000.00</span>
                        </label>
                    </div>
                    
                    <!-- Pack 4 -->
                    <div class="relative">
                        <input type="radio" name="package" id="pack_5000" value="5000" class="radio-box peer hidden">
                        <label for="pack_5000" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-slate-300 transition-all text-center h-full">
                            <span class="text-3xl font-black text-slate-900">5,000</span>
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1">บาท</span>
                            <span class="text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded text-center mb-2">โบนัส 200 บาท</span>
                            <span class="text-lg font-bold text-slate-700 mt-auto">฿5,000.00</span>
                        </label>
                    </div>

                    <!-- Custom Amount -->
                    <div class="relative sm:col-span-2 md:col-span-4">
                        <input type="radio" name="package" id="pack_custom" value="custom" class="radio-box peer hidden">
                        <label for="pack_custom" class="flex flex-col md:flex-row items-center justify-between p-6 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-slate-300 transition-all gap-4">
                            <div class="flex flex-col items-center md:items-start">
                                <span class="text-xl font-black text-slate-900 leading-none">ระบุจำนวนเงินเอง</span>
                                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mt-1">ขั้นต่ำ 20 บาท</span>
                            </div>
                            <div id="custom-input-box" class="hidden w-full md:w-64">
                                <div class="relative tracking-tight">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <span class="text-slate-400 font-bold">฿</span>
                                    </div>
                                    <input type="number" id="custom-amount-input" class="block w-full pl-8 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all transition-all" placeholder="0.00" min="20" step="1">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-xs font-bold text-slate-400">บาท</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </section>
            
            <!-- Payment Method -->
            <section>
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 mb-4">
                    <span class="material-symbols-outlined text-primary text-[24px]">payments</span>
                    Payment Method
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="relative">
                        <input type="radio" name="method" id="method_qr" value="qr" class="method-radio peer hidden" checked>
                        <label for="method_qr" class="flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors h-full">
                            <div class="custom-radio w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center flex-shrink-0 transition-colors">
                                <div class="w-2 h-2 bg-slate-900 rounded-full opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                            </div>
                            <span class="material-symbols-outlined text-slate-700">qr_code_scanner</span>
                            <span class="font-bold text-sm text-slate-900">QR / PromptPay</span>
                        </label>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Right Side: Order Summary -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:sticky top-6">
                <h3 class="text-xl font-bold text-slate-900 mb-6">สรุปรายการ</h3>
                <!-- Instructions -->
                <div class="space-y-4 mb-8">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">จำนวนเงิน</span>
                        <span class="font-bold text-slate-900" id="summary-credits">100 บาท</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">โบนัส</span>
                        <span class="font-bold text-green-600" id="summary-bonus">0 บาท</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">ช่องทางชำระเงิน</span>
                        <span class="font-bold text-slate-900">QR Code</span>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-slate-100 flex justify-between items-end mb-8">
                    <span class="text-sm font-bold text-slate-900">ยอดชำระทั้งหมด</span>
                    <span class="text-3xl font-black text-slate-900" id="summary-price">฿100.00</span>
                </div>
                
                <!-- Action Flow Container -->
                <div id="action-container">
                    <button type="button" id="btnTopUpNow" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-4 px-6 rounded-xl transition-colors shadow-sm text-lg flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined font-black">bolt</span> Top Up Now
                    </button>
                    <p class="text-center text-[9px] uppercase tracking-wider text-slate-400 font-bold mt-4">Safe & Secure Transaction protected by 256-bit SSL</p>
                </div>

                <!-- Hidden QR Box (Shown after clicking Top Up Now) -->
                <div id="qrBox" class="hidden mt-4 pt-4 border-t border-dashed border-slate-200">
                    <div class="text-center mb-4">
                        <h4 class="font-bold text-slate-900 text-sm mb-1">สแกน QR Code เพื่อชำระเงิน</h4>
                        <p class="text-xs text-slate-500">กรุณาโอนเงินภายใน 15 นาที</p>
                    </div>
                    
                    <div class="bg-slate-50 p-4 rounded-xl flex flex-col items-center justify-center mb-4 border border-slate-100 shadow-inner">
                        <!-- Loading spinner for QR -->
                        <div id="qrSpinner" class="flex flex-col items-center justify-center py-8">
                            <span class="material-symbols-outlined animate-spin text-3xl text-slate-400 mb-2">progress_activity</span>
                            <span class="text-xs text-slate-500">Generating QR...</span>
                        </div>
                        <img id="qrImg" src="" alt="QR Code" class="hidden w-48 h-48 mix-blend-multiply opacity-50 transition-opacity duration-300">
                        <div id="refBox" class="hidden mt-3 text-center">
                            <span class="text-xs text-slate-400 font-medium">รหัสอ้างอิง:</span>
                            <span id="refText" class="font-mono text-sm font-bold text-slate-800 ml-1 bg-slate-200 px-2 py-0.5 rounded">-</span>
                        </div>
                    </div>

                    <form id="verifyForm" action="topup_process.php?action=verify_slip" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="ref" id="refField">
                        <label class="block text-xs font-bold text-slate-700 mb-2">อัปโหลดสลิปยืนยันการโอนเงิน</label>
                        <div class="flex items-center gap-2 mb-4">
                            <input class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer focus:outline-none" type="file" name="slip" id="slipInput" accept="image/*" required>
                        </div>
                        <button type="submit" id="btnVerify" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            ยืนยันและอัปโหลดสลิป
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Mini History -->
    <?php if ($historyRows): ?>
    <div class="mt-12 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 w-full">
        <h3 class="text-lg font-bold text-slate-900 mb-4">ประวัติการทำรายการล่าสุด</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[500px]">
                <thead>
                    <tr class="border-b border-slate-100 text-xs text-slate-400 uppercase tracking-wider">
                        <th class="pb-3 pl-2 font-bold">วันที่</th>
                        <th class="pb-3 font-bold">อ้างอิง</th>
                        <th class="pb-3 font-bold text-right">จำนวน</th>
                        <th class="pb-3 font-bold text-right pr-2">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($historyRows as $r): 
                        $amt = number_format((float)$r['amount'],2);
                        $dt = date('d M Y H:i', strtotime($r['created_at']));
                        $ref = h($r['reference_no'] ?: '-');
                        $s = $r['status'];
                        if ($s==='pending')       { $badge = '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-[10px] font-bold">รอตรวจสอบ</span>'; }
                        elseif ($s==='approved')  { $badge = '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold">สำเร็จ</span>'; }
                        elseif ($s==='rejected')  { $badge = '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">ถูกปฏิเสธ</span>'; }
                        else                      { $badge = '<span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">'.$s.'</span>'; }
                    ?>
                    <tr class="text-sm hover:bg-slate-50 transition-colors">
                        <td class="py-4 pl-2 text-slate-500 font-medium"><?= $dt ?></td>
                        <td class="py-4 font-mono text-xs font-bold text-slate-600">#<?= $ref ?></td>
                        <td class="py-4 font-bold text-slate-900 text-right">฿<?= $amt ?></td>
                        <td class="py-4 text-right pr-2"><?= $badge ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
    const packages = {
        '100':  { credits: 100,  bonus: 0,    price: 100 },
        '500':  { credits: 500,  bonus: 50,   price: 500 },
        '1000': { credits: 1000, bonus: 150,  price: 1000 },
        '5000': { credits: 5000, bonus: 200, price: 5000 }
    };
    
    let currentAmount = 100;

    // Format currency like mockup ($1.00 but bath)
    function formatCurrency(c) {
        return '฿' + Number(c).toFixed(2);
    }

    // Update UI on package select
    document.querySelectorAll('.radio-box').forEach(radio => {
        radio.addEventListener('change', function() {
            const val = this.value;
            const customBox = document.getElementById('custom-input-box');
            
            if (val === 'custom') {
                customBox.classList.remove('hidden');
                updateFromCustom();
            } else {
                customBox.classList.add('hidden');
                const p = packages[val];
                if(p) {
                    currentAmount = p.price;
                    updateDisplays(p.credits, p.bonus, p.price);
                }
            }
            
            // Hide QR Box if it's currently open
            document.getElementById('qrBox').classList.add('hidden');
            document.getElementById('action-container').classList.remove('hidden');
        });
    });

    document.getElementById('custom-amount-input').addEventListener('input', function() {
        updateFromCustom();
    });

    function updateFromCustom() {
        const val = document.getElementById('custom-amount-input').value;
        const amt = parseFloat(val) || 0;
        currentAmount = amt;
        updateDisplays(amt, 0, amt);
    }

    function updateDisplays(credits, bonus, price) {
        document.getElementById('summary-credits').textContent = credits.toLocaleString() + ' บาท';
        
        const bonusEl = document.getElementById('summary-bonus');
        if (bonus > 0) {
            bonusEl.textContent = 'โบนัส ' + bonus + ' บาท';
            bonusEl.className = 'font-bold text-green-600';
        } else {
            bonusEl.textContent = '0 บาท';
            bonusEl.className = 'font-bold text-slate-400';
        }
        
        document.getElementById('summary-price').textContent = formatCurrency(price);
    }

    // Generate QR API Call
    document.getElementById('btnTopUpNow').addEventListener('click', function() {
        if (currentAmount < 20) {
            Swal.fire({ icon: 'warning', title: 'ยอดเงินไม่ถูกต้อง', text: 'กรุณาระบุจำนวนเงินอย่างน้อย 20 บาท' });
            return;
        }

        // Show QR box, hide button
        document.getElementById('action-container').classList.add('hidden');
        document.getElementById('qrBox').classList.remove('hidden');
        
        // Show Spinner, hide img/ref
        document.getElementById('qrSpinner').classList.remove('hidden');
        document.getElementById('qrImg').classList.add('hidden');
        document.getElementById('refBox').classList.add('hidden');

        document.getElementById('qrBox').scrollIntoView({ behavior: 'smooth', block: 'end' });

        fetch('topup_process.php?action=create_qr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount: currentAmount })
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok === true) {
                const qrImg = document.getElementById('qrImg');
                qrImg.src = data.qr_img;
                
                // Wait for image to load before hiding spinner
                qrImg.onload = function() {
                    document.getElementById('qrSpinner').classList.add('hidden');
                    qrImg.classList.remove('hidden', 'opacity-50', 'mix-blend-multiply');
                    document.getElementById('refBox').classList.remove('hidden');
                    document.getElementById('refText').textContent = data.ref;
                    document.getElementById('refField').value = data.ref;
                };
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'สร้าง QR ไม่สำเร็จ' });
                resetTopUpAction();
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
            resetTopUpAction();
        });
    });

    function resetTopUpAction() {
        document.getElementById('action-container').classList.remove('hidden');
        document.getElementById('qrBox').classList.add('hidden');
    }

    // Enabling submit button only on slip selection
    document.getElementById('slipInput').addEventListener('change', function() {
        const btnVer = document.getElementById('btnVerify');
        if (this.files && this.files.length > 0) {
            btnVer.disabled = false;
        } else {
            btnVer.disabled = true;
        }
    });

    // Form submission parsing
    document.getElementById('verifyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btnVer = document.getElementById('btnVerify');
        btnVer.disabled = true;
        btnVer.innerHTML = 'กำลังประมวลผล...';

        const fd = new FormData(this);
        const refObj = document.getElementById('refField');
        if (refObj && refObj.value) {
            fd.append('ref', refObj.value);
        }
        
        fetch('topup_process.php?action=verify_slip', {
            method:'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if(data.ok === true) {
                Swal.fire({
                    icon: 'success',
                    title: 'อัปโหลดสลิปสำเร็จ',
                    text: data.message || 'กรุณารอระบบตรวจสอบยอดชำระสักครู่'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message || 'ไม่สามารถตรวจสอบสลิปได้'
                });
                btnVer.disabled = false;
                btnVer.innerHTML = 'ยืนยันและอัปโหลดสลิป';
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', text: 'Network Error' });
            btnVer.disabled = false;
            btnVer.innerHTML = 'ยืนยันและอัปโหลดสลิป';
        });
    });
</script>
</body>
</html>

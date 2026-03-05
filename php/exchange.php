<?php
require_once __DIR__ . '/controllers/exchange_controller.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการแลกเปลี่ยนสินค้า | Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        primary: "#f9e71f",
                        customBg: "#fbfbfb",
                        navBg: "#ffffff"
                    },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Status Badge Colors */
        .status-pending { background-color: #fef3c7; color: #b45309; }  /* Yellowish */
        .status-accepted { background-color: #dbeafe; color: #1e40af; } /* Blueish */
        .status-completed { background-color: #d1fae5; color: #065f46; } /* Greenish */
        
        .tab-active {
            border-bottom: 3px solid #f9e71f;
            color: #1e293b; /* slate-900 */
            font-weight: 800;
        }
        .tab-inactive {
            color: #64748b; /* slate-500 */
            font-weight: 600;
        }
        .tab-inactive:hover {
            color: #334155; /* slate-700 */
        }
    </style>
</head>
<body class="bg-customBg font-sans text-slate-800 min-h-screen flex flex-col">

    <!-- Top Navigation Placeholder (Usually handled by navbar_main.php) -->
    <?php include __DIR__ . '/../includes/navbar_main.php'; ?>

    <!-- Main Header -->
    <header class="bg-customBg pt-8 pb-4 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 mb-2">รายการแลกเปลี่ยนสินค้า</h1>
            <p class="text-slate-500 font-medium">จัดการรายการแลกเปลี่ยนและข้อเสนอของคุณได้ที่นี่</p>
        </div>
        <div>
            <a href="edit_exchange.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-md hover:shadow-lg active:scale-95 text-sm">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                ลงประกาศแลกเปลี่ยน
            </a>
        </div>
    </header>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-200">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full flex space-x-6 overflow-x-auto no-scrollbar pt-2">
            <a href="?tab=all" class="pb-3 text-[14px] whitespace-nowrap transition-colors <?= $tab === 'all' ? 'tab-active' : 'tab-inactive' ?>">
                ทั้งหมด (<?= count($trades['all']) ?>)
            </a>
            <a href="?tab=incoming" class="pb-3 text-[14px] whitespace-nowrap transition-colors <?= $tab === 'incoming' ? 'tab-active' : 'tab-inactive' ?>">
                ได้รับข้อเสนอ (<?= count($trades['incoming']) ?>)
            </a>
            <a href="?tab=outgoing" class="pb-3 text-[14px] whitespace-nowrap transition-colors <?= $tab === 'outgoing' ? 'tab-active' : 'tab-inactive' ?>">
                เสนอแลกไป (<?= count($trades['outgoing']) ?>)
            </a>
            <a href="?tab=completed" class="pb-3 text-[14px] whitespace-nowrap transition-colors <?= $tab === 'completed' ? 'tab-active' : 'tab-inactive' ?>">
                สำเร็จแล้ว (<?= count($trades['completed']) ?>)
            </a>
        </div>
    </div>

    <!-- Main Content List -->
    <main class="flex-1 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full py-8 space-y-4">
        
        <?php if (empty($active_trades)): ?>
            <!-- Empty State -->
            <div class="bg-white border text-center border-slate-200 shadow-sm rounded-2xl p-12 mt-4 border-dashed border-2 px-4 py-16">
                <div class="flex justify-center mb-6">
                    <span class="material-symbols-outlined text-[60px] text-slate-300">handshake</span>
                </div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">ยังไม่มีรายการแลกเปลี่ยน?</h2>
                <p class="text-[15px] text-slate-500 font-medium mb-8 max-w-md mx-auto">
                    ลองค้นหาสินค้าที่น่าสนใจในพื้นที่ของคุณและเริ่มเสนอแลกเปลี่ยนได้ทันที
                </p>
                <a href="../index.php" class="inline-flex justify-center items-center px-6 py-3 bg-primary hover:bg-yellow-400 text-slate-900 font-bold rounded-xl transition-colors shadow-sm active:scale-95">
                    สำรวจตลาดสินค้า
                </a>
            </div>
        <?php else: ?>
            
            <?php foreach ($active_trades as $t): 
                $myImg = $t['my_image'] ? '../uploads/'.htmlspecialchars($t['my_image']) : '../assets/no-img.png';
                $theirImg = $t['their_image'] ? '../uploads/'.htmlspecialchars($t['their_image']) : '../assets/no-img.png';
                $cashAdj = (float)$t['cash_adjustment'];
                $isMine = ($t['seller_id'] == $userId); // incoming

                $statusBadgeClass = 'status-' . strtolower($t['display_status']);
                
                // time formatting logic
                $timeLabel = "ยื่นข้อเสนอเมื่อ";
                $timeDate = $t['offer_date'];
                if ($t['display_status'] === 'ACCEPTED') {
                    $timeLabel = "ยอมรับเมื่อ";
                    $timeDate = $t['offer_responded'] ?: $t['offer_date'];
                } elseif ($t['display_status'] === 'COMPLETED') {
                    $timeLabel = "สำเร็จเมื่อ";
                    $timeDate = $t['offer_responded'] ?: $t['offer_date'];
                }
                
                $timeStr = date("M j, Y", strtotime($timeDate));
            ?>
            <!-- Trade Card -->
            <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row gap-5 items-start sm:items-center transition-shadow hover:shadow-md">
                
                <!-- Images Graphic -->
                <div class="flex items-center gap-2 sm:gap-4 shrink-0 bg-slate-50/50 p-2 rounded-xl">
                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shadow-sm">
                        <img src="<?= $myImg ?>" class="w-full h-full object-cover mix-blend-multiply" onerror="this.onerror=null; this.src='/assets/default.png';">
                        <div class="absolute top-0 left-0 bg-slate-800 text-white text-[9px] font-black px-1.5 py-0.5 rounded-br-lg shadow-sm">ของฉัน</div>
                    </div>
                    
                    <div class="w-8 h-8 rounded-full bg-[#fefce8] text-[#eab308] flex items-center justify-center shrink-0 z-10 shadow-sm border border-[#fef08a]">
                        <?php if ($t['display_status'] === 'COMPLETED'): ?>
                            <span class="material-symbols-outlined text-[16px] text-green-500" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        <?php elseif ($t['display_status'] === 'ACCEPTED'): ?>
                            <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                        <?php endif; ?>
                    </div>

                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shadow-sm">
                        <img src="<?= $theirImg ?>" class="w-full h-full object-cover mix-blend-multiply" onerror="this.onerror=null; this.src='/assets/default.png';">
                        <div class="absolute top-0 left-0 bg-primary text-slate-900 text-[9px] font-black px-1.5 py-0.5 rounded-br-lg shadow-sm">ของเขา</div>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0 flex flex-col justify-center w-full">
                    <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $statusBadgeClass ?>">
                                <?= $t['display_status'] === 'PENDING' ? 'รอการตอบรับ' : ($t['display_status'] === 'ACCEPTED' ? 'ยอมรับแล้ว' : ($t['display_status'] === 'COMPLETED' ? 'สำเร็จแล้ว' : 'ยกเลิกแล้ว')) ?>
                            </span>
                            <span class="text-slate-400 text-[11px] font-medium">• <?= $timeLabel ?> <?= $timeStr ?></span>
                        </div>
                        <h3 class="font-bold text-slate-900 text-[14px] sm:text-[16px] line-clamp-1 group-hover:text-primary transition-colors">
                            <?= htmlspecialchars($t['offer_title']) ?> <span class="text-slate-400 font-normal mx-1">↔</span> <?= htmlspecialchars($t['item_title']) ?>
                        </h3>
                        <p class="text-slate-500 text-[12px] font-medium mt-0.5">
                            คู่แลก: <span class="text-slate-700 font-bold"><?= htmlspecialchars($t['partner_name']) ?></span>
                        </p>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-[13px] font-medium text-slate-500">
                        <!-- Cash Adjustment -->
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">payments</span>
                            <?php if ($cashAdj > 0): ?>
                                <?php if ($isMine): ?>
                                    <span class="text-green-600 font-bold">Cash Adjustment: +฿<?= number_format($cashAdj) ?></span>
                                <?php else: ?>
                                    <span class="text-red-500 font-bold">Cash Adjustment: -฿<?= number_format($cashAdj) ?></span>
                                <?php endif; ?>
                            <?php elseif ($cashAdj < 0): ?>
                                <?php if ($isMine): ?>
                                    <span class="text-red-500 font-bold">Cash Adjustment: -฿<?= number_format(abs($cashAdj)) ?></span>
                                <?php else: ?>
                                    <span class="text-green-600 font-bold">Cash Adjustment: +฿<?= number_format(abs($cashAdj)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span>ไม่มีส่วนต่างเงินสด</span>
                            <?php endif; ?>
                        </div>

                        <!-- Date -->
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]"><?= $t['display_status'] === 'COMPLETED' ? 'check_circle' : 'calendar_today' ?></span>
                            <span><?= $timeLabel ?> <?= $timeStr ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 sm:ml-4 w-full sm:w-auto justify-end mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-slate-100">
                    
                    <?php if ($t['display_status'] === 'AVAILABLE' && !$isMine): ?>
                        <button onclick="openOfferModal(<?= (int)$t['item_id'] ?>, '<?= htmlspecialchars(addslashes($t['title'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($t['their_image'] ?? '')) ?>')" class="w-full sm:w-auto px-5 py-2.5 bg-primary hover:bg-yellow-400 text-slate-900 font-bold rounded-xl text-[14px] transition-colors shadow-sm">
                            Trade Component
                        </button>
                    
                    <?php elseif ($t['display_status'] === 'PENDING' && $isMine): ?>
                        <form method="post" class="flex gap-2 w-full sm:w-auto">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="item_id" value="<?= $t['item_id'] ?>">
                            <input type="hidden" name="offer_id" value="<?= $t['offer_id'] ?>">
                            <button name="status" value="swapped" class="flex-1 sm:flex-none px-4 py-2.5 bg-primary hover:bg-yellow-400 text-slate-900 font-bold rounded-xl text-[14px] transition-colors shadow-sm">ยอมรับ</button>
                            <button name="status" value="available" class="flex-1 sm:flex-none px-4 py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold rounded-xl text-[14px] transition-colors">ปฏิเสธ</button>
                        </form>
                    
                    <?php elseif ($t['display_status'] === 'ACCEPTED'): ?>
                        <?php 
                            // Determine if current user confirmed receipt
                            $rk = $t['item_id'].'-'.$t['offer_id'];
                            $rc = $receiptsByKey[$rk] ?? null;
                            $meConfirmed = $rc ? ($isMine ? (int)$rc['seller_confirm']===1 : (int)$rc['buyer_confirm']===1) : false;
                        ?>
                        <?php if (!$meConfirmed): ?>
                             <button onclick="openConfirmModal(<?= $t['item_id'] ?>, <?= $t['offer_id'] ?>)" class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-[14px] transition-colors shadow-md">ยืนยันรับของ</button>
                        <?php else: ?>
                             <span class="px-4 py-2.5 bg-slate-100/50 text-slate-500 font-bold rounded-xl text-[14px] flex items-center gap-2 border border-slate-200/50"><span class="material-symbols-outlined text-[16px] text-green-500">check</span> รอการยืนยันจากคู่แลก</span>
                        <?php endif; ?>

                    <?php elseif ($t['display_status'] === 'COMPLETED'): ?>
                        <button class="w-full sm:w-auto px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-[14px] transition-colors shadow-sm">เขียนรีวิว</button>
                    <?php endif; ?>

                    <!-- Action Icons -->
                    <?php if ($t['display_status'] === 'ACCEPTED'): ?>
                        <?php $reqId = 'EXC-'.$t['item_id'].'-'.$t['offer_id']; ?>
                        <a href="../chatapp/chat.php?request_id=<?= urlencode($reqId) ?>&product_id=0" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center shrink-0 transition-colors tooltip" title="แชทตกลงนัด">
                            <span class="material-symbols-outlined text-[20px]">chat</span>
                        </a>
                    <?php endif; ?>
                    
                    <button class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center shrink-0 transition-colors tooltip" title="ดูรายละเอียด">
                        <span class="material-symbols-outlined text-[20px]"><?= $t['display_status'] === 'COMPLETED' ? 'receipt_long' : 'visibility' ?></span>
                    </button>

                    <?php if ($isMine && $t['display_status'] !== 'COMPLETED'): ?>
                        <a href="edit_exchange.php?id=<?= (int)$t['item_id'] ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center shrink-0 transition-colors tooltip" title="แก้ไขรายการ">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($t['display_status'] === 'ACCEPTED'): ?>
                    <button class="w-10 h-10 rounded-xl bg-transparent hover:bg-slate-100 text-slate-400 hover:text-slate-600 hidden sm:flex items-center justify-center shrink-0 transition-colors">
                        <span class="material-symbols-outlined text-[24px]">more_vert</span>
                    </button>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
            
        <?php endif; ?>

    </main>

    <!-- Modals and Scripts -->
    <?php
    // Fetch my available items for the Propose Exchange Modal
    $st_my = $pdo->prepare("SELECT item_id, title, images FROM exchange_items WHERE user_id=? AND status='available' ORDER BY item_id DESC");
    $st_my->execute([$userId]);
    $my_available_items = $st_my->fetchAll();
    ?>

    <!-- JavaScript to Handle Modals -->
    <script>
        function openOfferModal(targetItemId, targetItemTitle, targetItemImage) {
            document.getElementById('offerModal').classList.remove('hidden');
            document.getElementById('offer_target_item_id').value = targetItemId;
            document.getElementById('offer_target_title').innerText = targetItemTitle;
            document.getElementById('offer_target_img').src = targetItemImage || '/assets/default.png';
        }
        function closeOfferModal() {
            document.getElementById('offerModal').classList.add('hidden');
        }

        // Custom Cash Adjustment Toggle
        function checkCashToggle(val) {
            const wrap = document.getElementById('cash_adjustment_wrap');
            if (val === 'none') {
                wrap.classList.add('hidden');
                document.getElementById('cash_adjustment_input').value = '0';
            } else {
                wrap.classList.remove('hidden');
                document.getElementById('cash_adjustment_input').value = '';
                document.getElementById('cash_adjustment_input').focus();
            }
        }
    </script>

    <!-- Propose Exchange Modal -->
    <div id="offerModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
        <div class="min-h-screen px-4 text-center flex items-center justify-center">
            <div class="inline-block w-full max-w-lg p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative">
                
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-xl font-bold text-slate-900">ยื่นข้อเสนอแลกเปลี่ยน</h3>
                    <button type="button" onclick="closeOfferModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form method="post" action="exchange.php">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="offer">
                    <input type="hidden" name="item_id" id="offer_target_item_id" value="">

                    <!-- Target Item Summary -->
                    <div class="flex items-center gap-4 p-3 bg-slate-50 border border-slate-200 rounded-xl mb-6">
                        <img id="offer_target_img" src="/assets/default.png" class="w-12 h-12 rounded-lg object-cover bg-white border border-slate-200" alt="" onerror="this.onerror=null; this.src='/assets/default.png';">
                        <div>
                            <div class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-0.5">สินค้าที่เสนอแลก</div>
                            <div id="offer_target_title" class="text-[14px] font-bold text-slate-900 line-clamp-1">Item Title</div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-[13px] font-bold text-slate-700 mb-2">ของส่วนตัวที่คุณต้องการแลก <span class="text-red-500">*</span></label>
                        <?php if (empty($my_available_items)): ?>
                            <div class="p-3 bg-red-50 text-red-600 border border-red-200 rounded-xl text-[14px] font-medium">
                                คุณยังไม่มีสินค้าที่พร้อมสำหรับแลกเปลี่ยน <a href="edit_exchange.php" class="underline font-bold">กรุณาลงประกาศสินค้าก่อน</a> เพื่อยื่นข้อเสนอ
                            </div>
                        <?php else: ?>
                            <select name="offer_item_id" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all shadow-sm font-medium" required>
                                <option value="" disabled selected>เลือกสินค้าของคุณจากรายการคลังสินค้า...</option>
                                <?php foreach($my_available_items as $my_item): ?>
                                    <option value="<?= $my_item['item_id'] ?>"><?= htmlspecialchars($my_item['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <!-- Cash Adjustment -->
                    <div class="mb-5">
                        <label class="block text-[13px] font-bold text-slate-700 mb-2">การปรับสมดุลการแลกเปลี่ยน (ส่วนต่างเงินสด)</label>
                        <select name="cash_type" onchange="checkCashToggle(this.value)" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all shadow-sm font-medium mb-2">
                            <option value="none">แลกเปลี่ยนกันโดยตรง (ไม่มีส่วนต่างเงิน)</option>
                            <option value="pay">ฉันจะเพิ่มเงินให้เขา (+)</option>
                            <option value="receive">ฉันต้องการให้เขาเพิ่มเงินให้ (-)</option>
                        </select>
                        
                        <div id="cash_adjustment_wrap" class="hidden relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-500">฿</span>
                            <input type="number" name="cash_adjustment_amount" id="cash_adjustment_input" placeholder="0.00" min="0" step="0.01" class="w-full pl-9 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all shadow-sm font-bold placeholder:font-normal">
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-8">
                        <label class="block text-[13px] font-bold text-slate-700 mb-2">ข้อความถึงเจ้าของ <span class="text-red-500">*</span></label>
                        <textarea name="offer_text" rows="3" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all shadow-sm" placeholder="สวัสดี! ฉันอยากจะแลกเปลี่ยนสินค้าชิ้นนี้กับชิ้นของคุณ..." required></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button type="button" onclick="closeOfferModal()" class="flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                            ยกเลิก
                        </button>
                        <button type="submit" class="flex-1 py-3 px-4 bg-primary hover:bg-yellow-400 text-slate-900 font-bold shadow-sm rounded-xl transition-colors <?= empty($my_available_items) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($my_available_items) ? 'disabled' : '' ?>>
                            ส่งคำขอแลกเปลี่ยน
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Confirm Receipt Modal (Alpine style simplified logic using vanilla JS) -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
        <div class="min-h-screen px-4 text-center flex items-center justify-center">
            <div class="inline-block w-full max-w-sm p-6 my-8 text-center align-middle transition-all transform bg-white shadow-xl rounded-3xl relative overflow-hidden">
                
                <div class="w-full h-32 bg-green-50 absolute top-0 left-0 -z-10"></div>
                
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mt-4 mb-5 border-4 border-white shadow-sm">
                    <span class="material-symbols-outlined text-[40px] text-green-500 font-bold">inventory_2</span>
                </div>
                
                <h3 class="text-xl font-bold text-slate-900 mb-2">ยืนยันการรับสินค้า</h3>
                <p class="text-[14px] text-slate-500 mb-8 font-medium px-4">
                    กรุณายืนยันก็ต่อเมื่อคุณได้รับสินค้าจริงและตรวจสอบสภาพเรียบร้อยแล้วเท่านั้น การดำเนินการนี้ไม่สามารถย้อนกลับได้
                </p>

                <form method="post" action="exchange.php" id="confirmReceiptForm">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="confirm_received">
                    <input type="hidden" name="item_id" id="confirm_item_id" value="">
                    <input type="hidden" name="offer_id" id="confirm_offer_id" value="">

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full py-3.5 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl transition-colors shadow-sm text-[15px]">
                            ใช่ ฉันได้รับสินค้าแล้ว
                        </button>
                        <button type="button" onclick="closeConfirmModal()" class="w-full py-3.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold rounded-xl transition-colors text-[15px]">
                            ยังไม่ได้รับ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openConfirmModal(itemId, offerId) {
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirm_item_id').value = itemId;
            document.getElementById('confirm_offer_id').value = offerId;
        }
        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

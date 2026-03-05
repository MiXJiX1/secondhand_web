<?php
require_once __DIR__ . '/controllers/ban_appeals_controller.php';
?>
<?php
$pageTitle = 'คำร้องอุทธรณ์แบน';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-red-500" style="font-variation-settings: 'FILL' 1;">block</span> 
                คำร้องอุทธรณ์แบน
            </h3>
            <div class="text-sm text-slate-500">
                คำร้องทั้งหมด: <strong class="text-slate-700"><?= number_format($total) ?></strong> รายการ
            </div>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="dashboard.php" class="w-full md:w-auto bg-slate-100 text-slate-700 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">dashboard</span> แดชบอร์ด
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-8 lg:col-span-9">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input id="q" type="search" class="w-full pl-10 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50" placeholder="ค้นหา: ผู้ใช้ / อีเมล / ข้อความ / ID">
            </div>
        </div>
        <div class="md:col-span-4 lg:col-span-3">
            <select id="statusFilter" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="">สถานะ: ทั้งหมด</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1000px]" id="appealTable">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                    <th class="py-3 px-4 w-24">ID</th>
                    <th class="py-3 px-4 w-32">ผู้ใช้</th>
                    <th class="py-3 px-4 w-40 hidden lg:table-cell">อีเมล</th>
                    <th class="py-3 px-4">ข้อความอุทธรณ์</th>
                    <th class="py-3 px-4 w-32">สถานะ</th>
                    <th class="py-3 px-4 w-32 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
            <?php if(empty($rows)): ?>
                <tr><td colspan="6" class="py-12 text-center text-slate-500 font-medium">— ไม่พบคำร้องอุทธรณ์ —</td></tr>
            <?php else: foreach($rows as $r):
                $id     = (int)$r['appeal_id'];
                $status = $r['status'] ?: 'pending';
                $created = $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-';
                $review  = $r['reviewed_at'] ? date('d/m/Y H:i', strtotime($r['reviewed_at'])) : '';
                
                $badgeColors = [
                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'rejected' => 'bg-red-100 text-red-700 border-red-200'
                ];
                $bClass = $badgeColors[$status] ?? 'bg-slate-100 text-slate-700';
            ?>
                <tr data-status="<?= htmlspecialchars($status) ?>" class="hover:bg-slate-50 transition-colors">
                    <td class="py-3 px-4 text-slate-500 align-top">
                        <div class="font-medium text-slate-700">#<?= $id ?></div>
                        <div class="text-[11px]"><?= htmlspecialchars($created) ?></div>
                    </td>
                    <td class="py-3 px-4 align-top">
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($r['username'] ?? '-') ?></div>
                    </td>
                    <td class="py-3 px-4 hidden lg:table-cell align-top text-slate-600">
                        <?= htmlspecialchars($r['email'] ?? '-') ?>
                    </td>
                    <td class="py-3 px-4 align-top">
                        <div class="text-slate-700 whitespace-pre-wrap break-words text-sm max-w-md line-clamp-3" title="<?= htmlspecialchars($r['message'] ?: '-') ?>">
                            <?= nl2br(htmlspecialchars($r['message'] ?: '-')) ?>
                        </div>
                        <?php if($status !== 'pending'): ?>
                        <div class="mt-2 text-[11px] text-slate-500 bg-slate-50 p-2 rounded border border-slate-100">
                            <strong>ตัดสินโดย:</strong> ผู้ดูแล #<?= (int)$r['reviewed_by'] ?> @ <?= htmlspecialchars($review) ?>
                            <?php if(!empty($r['decision_note'])): ?>
                                <br><span class="text-slate-600 mt-1 block border-t border-slate-200 pt-1"><strong>Note:</strong> <?= htmlspecialchars($r['decision_note']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 align-top pt-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold border <?= $bClass ?> capitalize tracking-wide">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center align-top pt-3">
                        <?php if($status === 'pending'): ?>
                        <div class="flex flex-col gap-2">
                            <button type="button" class="bg-primary text-white rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-primary/90 transition-colors shadow-sm" onclick="openModal('judge', <?= $id ?>)">
                                ตัดสิน
                            </button>
                            <button type="button" class="bg-white text-slate-600 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50 transition-colors" onclick="openModal('view', <?= $id ?>)">
                                รายละเอียด
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-1 rounded border border-transparent">ปิดแล้ว</span>
                            <button type="button" class="bg-white text-slate-600 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50 transition-colors" onclick="openModal('view', <?= $id ?>)">
                                ดูรายละเอียด
                            </button>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if($status === 'pending'): ?>
                <!-- Modal ตัดสิน -->
                <div id="modal-judge-<?= $id ?>" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
                        <form method="post">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900">ตัดสินคำร้อง #<?= $id ?></h3>
                                <button type="button" class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 w-8 h-8 rounded-full flex items-center justify-center transition-colors" onclick="closeModal('judge', <?= $id ?>)">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            
                            <div class="p-6">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="appeal_id" value="<?= $id ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">โน้ตถึงผู้ใช้ (optional)</label>
                                    <textarea name="note" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50 resize-none h-24" placeholder="ข้อความถึงผู้ใช้ (ถ้ามี)"></textarea>
                                </div>
                                
                                <div class="bg-amber-50 text-amber-800 border border-amber-200 rounded-lg p-3 text-xs flex gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-amber-500 shrink-0">warning</span>
                                    <span>การอนุมัติจะทำการ "ปลดแบน" ผู้ใช้นี้ทันที ใหักลับมาใช้งานระบบได้ปกติ</span>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row gap-2">
                                <button type="submit" name="action" value="approve" class="flex-1 bg-emerald-500 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-emerald-600 transition-colors shadow-sm" onclick="return confirm('ยืนยัน “อนุมัติ & ปลดแบน” ผู้ใช้นี้?')">
                                    อนุมัติ & ปลดแบน
                                </button>
                                <button type="submit" name="action" value="reject" class="flex-1 bg-red-500 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-red-600 transition-colors shadow-sm" onclick="return confirm('ยืนยัน “ปฏิเสธคำร้อง” ?')">
                                    ปฏิเสธคำร้อง
                                </button>
                                <button type="button" class="sm:flex-none bg-white text-slate-600 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 transition-colors" onclick="closeModal('judge', <?= $id ?>)">
                                    ยกเลิก
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Modal ดูรายละเอียด -->
                <div id="modal-view-<?= $id ?>" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                            <h3 class="text-lg font-bold text-slate-900">รายละเอียดคำร้อง #<?= $id ?></h3>
                            <button type="button" class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 w-8 h-8 rounded-full flex items-center justify-center transition-colors" onclick="closeModal('view', <?= $id ?>)">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        
                        <div class="p-6 overflow-y-auto grow">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">ผู้ใช้</div>
                                    <div class="text-slate-900 font-bold"><?= htmlspecialchars($r['username'] ?? '-') ?></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">อีเมล</div>
                                    <div class="text-slate-700 break-all"><?= htmlspecialchars($r['email'] ?? '-') ?></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">สถานะ</div>
                                    <div><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border <?= $bClass ?> capitalize tracking-wide"><?= htmlspecialchars($status) ?></span></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">วันที่สร้าง</div>
                                <div class="text-slate-700"><?= htmlspecialchars($created) ?></div>
                            </div>
                            
                            <hr class="border-slate-100 my-4">
                            
                            <div class="mb-6">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">ข้อความอุทธรณ์</div>
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-slate-700 whitespace-pre-wrap break-words text-sm"><?= nl2br(htmlspecialchars($r['message'] ?: '-')) ?></div>
                            </div>
                            
                            <?php if($status !== 'pending'): ?>
                            <hr class="border-slate-100 my-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">สรุปการตัดสิน</div>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm">
                                    <div class="text-blue-800 mb-2"><strong>โดย:</strong> ผู้ดูแล #<?= (int)$r['reviewed_by'] ?> <strong>เมื่อ:</strong> <?= htmlspecialchars($review) ?></div>
                                    <?php if(!empty($r['decision_note'])): ?>
                                        <div class="bg-white/60 p-3 rounded border border-blue-100 text-blue-900"><strong>Note:</strong> <?= htmlspecialchars($r['decision_note']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0 flex justify-end">
                            <button type="button" class="bg-white text-slate-600 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm" onclick="closeModal('view', <?= $id ?>)">
                                ปิด
                            </button>
                        </div>
                    </div>
                </div>

            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-slate-50 border-t border-slate-100 px-4 py-3 flex items-center justify-between">
        <div class="text-xs text-slate-500 font-medium" id="countText">
            แสดงทั้งหมด <?= number_format($total) ?> รายการ
        </div>
    </div>
</div>

<script>
    // Client-side search and filtering
    const q = document.getElementById('q');
    const statusFilter = document.getElementById('statusFilter');
    const tbody = document.querySelector('#appealTable tbody');
    const countText = document.getElementById('countText');
    const totalRows = <?= (int)$total ?>;

    function normalize(s){ return (s||'').toString().toLowerCase().trim(); }
    function applyFilter(){
        const qv = normalize(q?.value), sf = normalize(statusFilter?.value);
        let shown = 0;
        
        if (!tbody) return;
        
        [...tbody.rows].forEach(tr => {
            if (!tr.dataset.status) return; // Skip non-data rows
            
            const st = normalize(tr.dataset.status);
            const text = normalize(tr.innerText);
            const visible = (!qv || text.includes(qv)) && (!sf || st === sf);
            tr.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        
        if (countText) {
            countText.textContent = `แสดง ${shown} รายการ จากทั้งหมด ${totalRows} รายการ`;
        }
    }
    
    q?.addEventListener('input', applyFilter);
    statusFilter?.addEventListener('change', applyFilter);

    // Modal Control
    function openModal(type, id) {
        const modalId = `modal-${type}-${id}`;
        const modal = document.getElementById(modalId);
        if(!modal) return;
        
        modal.classList.remove('hidden');
        // trigger animation
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeModal(type, id) {
        const modalId = `modal-${type}-${id}`;
        const modal = document.getElementById(modalId);
        if(!modal) return;
        
        modal.classList.add('opacity-0');
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    
    // Close modal on backdrop click
    document.querySelectorAll('[id^="modal-"]').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                // Extract type and id from ID e.g., "modal-judge-123" -> type="judge", id="123"
                const parts = this.id.split('-');
                if(parts.length >= 3) {
                    closeModal(parts[1], parts[2]);
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

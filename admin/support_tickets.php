<?php
require_once __DIR__ . '/controllers/support_tickets_controller.php';
?>
<?php
$pageTitle = 'จัดการคำขอผู้ใช้';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">mail</span> 
                จัดการคำขอผู้ใช้
            </h3>
            <div class="text-sm text-slate-500">
                คำขอทั้งหมดในระบบ: <strong class="text-slate-700"><?= number_format($allCount) ?></strong> รายการ
            </div>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="dashboard.php" class="w-full md:w-auto bg-slate-100 text-slate-700 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">dashboard</span> แดชบอร์ด
            </a>
        </div>
    </div>
</div>

<?php if($msg): ?>
<div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl p-4 mb-6 flex items-center gap-3">
    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
    <span class="font-medium"><?= h($msg) ?></span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-6">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input id="q" type="search" class="w-full pl-10 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50" placeholder="ค้นหา: ผู้ใช้ / อีเมล / หัวข้อ / อ้างอิง">
            </div>
        </div>
        <div class="md:col-span-3">
            <select id="statusFilter" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="">สถานะ: ทั้งหมด</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <select id="catFilter" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="">ประเภท: ทั้งหมด</option>
                <?php foreach($categories as $c): ?>
                    <option value="<?= h($c) ?>"><?= h($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1000px]" id="ticketTable">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                    <th class="py-3 px-4 w-16">#</th>
                    <th class="py-3 px-4 w-32 hidden lg:table-cell">อ้างอิง</th>
                    <th class="py-3 px-4 w-48">ผู้ใช้</th>
                    <th class="py-3 px-4">หัวข้อ</th>
                    <th class="py-3 px-4 w-32 hidden md:table-cell">ประเภท</th>
                    <th class="py-3 px-4 w-32">สถานะ</th>
                    <th class="py-3 px-4 w-32 hidden lg:table-cell">วันที่</th>
                    <th class="py-3 px-4 w-24 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
            <?php if(empty($tickets)): ?>
                <tr><td colspan="8" class="py-12 text-center text-slate-500 font-medium">— ไม่พบคำขอผู้ใช้ —</td></tr>
            <?php else: foreach($tickets as $t): 
                $id   = (int)$t['ticket_id'];
                $ref  = $t['ref_code'] ?: '-';
                $name = trim($t['uname'] ?: $t['username']);
                $email= $t['email'] ?: '-';
                $cat  = $t['category'] ?: '-';
                $subj = $t['subject'] ?: '-';
                $sts  = $t['status'] ?: 'open';
                $created = $t['created_at'] ? date('d/m/Y H:i', strtotime($t['created_at'])) : '-';
                
                $badgeColors = [
                    'open' => 'bg-red-100 text-red-700 border-red-200',
                    'in_progress' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'resolved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'closed' => 'bg-slate-100 text-slate-700 border-slate-200'
                ];
                $bClass = $badgeColors[$sts] ?? 'bg-slate-100 text-slate-700';
            ?>
                <tr data-status="<?= h($sts) ?>" data-cat="<?= h($cat) ?>" class="hover:bg-slate-50 transition-colors">
                    <td class="py-3 px-4 text-slate-500">#<?= $id ?></td>
                    <td class="py-3 px-4 hidden lg:table-cell">
                        <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded border border-slate-200 text-slate-600"><?= h($ref) ?></span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="font-semibold text-slate-900"><?= h($name) ?></div>
                        <div class="text-[11px] text-slate-500"><?= h($email) ?></div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="text-slate-700 font-medium truncate max-w-xs" title="<?= h($subj) ?>">
                            <?= h($subj) ?>
                        </div>
                    </td>
                    <td class="py-3 px-4 hidden md:table-cell">
                        <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-medium border border-slate-200">
                            <?= h($cat) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold border <?= $bClass ?> capitalize tracking-wide">
                            <?= h($sts) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 hidden lg:table-cell text-[11px] text-slate-500">
                        <?= h($created) ?>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <button type="button" class="inline-flex justify-center items-center w-8 h-8 rounded-full bg-slate-50 text-primary hover:bg-primary/10 transition-colors border border-primary/20" onclick="openModal(<?= $id ?>)" title="รายละเอียดอ้างอิง">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </td>
                </tr>

                <!-- Modal รายละเอียด (Custom Tailwind Modal) -->
                <div id="modal-<?= $id ?>" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">รายละเอียดคำขอ #<?= $id ?></h3>
                                <div class="text-xs text-slate-500 mt-1">อ้างอิง: <span class="font-mono bg-slate-100 px-1 py-0.5 rounded"><?= h($ref) ?></span></div>
                            </div>
                            <button type="button" class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 w-8 h-8 rounded-full flex items-center justify-center transition-colors" onclick="closeModal(<?= $id ?>)">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="px-6 py-4 overflow-y-auto grow">
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">ผู้ใช้</div>
                                <div><strong class="text-slate-900"><?= h($name) ?></strong> <span class="text-slate-400 mx-1">•</span> <span class="text-slate-600"><?= h($email) ?></span></div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <div class="md:col-span-2">
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">หัวข้อ</div>
                                    <div class="text-slate-800 font-medium"><?= h($subj) ?></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">ประเภท</div>
                                    <div class="text-slate-800"><?= h($cat) ?></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">สถานะ</div>
                                    <div><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border <?= $bClass ?> capitalize tracking-wide"><?= h($sts) ?></span></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">วันที่สร้าง</div>
                                <div class="text-slate-800"><?= h($created) ?></div>
                            </div>
                            
                            <hr class="border-slate-100 my-4">
                            
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">ข้อความ</div>
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-slate-700 whitespace-pre-wrap break-words text-sm"><?= nl2br(h($t['message'])) ?></div>
                            </div>
                            
                            <?php
                            $att = $pdo->prepare("SELECT file_name, file_path FROM support_attachments WHERE ticket_id=?");
                            $att->execute([$id]);
                            $atts = $att->fetchAll();
                            ?>
                            <?php if($atts): ?>
                            <div class="mt-4">
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">ไฟล์แนบ</div>
                                <ul class="flex flex-col gap-2">
                                    <?php foreach($atts as $a): ?>
                                    <li>
                                        <a href="<?= h($a['file_path']) ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-primary hover:bg-slate-100 transition-colors w-full md:w-auto">
                                            <span class="material-symbols-outlined text-[18px]">attach_file</span>
                                            <span class="truncate"><?= h($a['file_name']) ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Modal Footer/Actions -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0">
                            <form method="post" class="flex flex-col sm:flex-row items-center gap-3 w-full justify-end">
                                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                <input type="hidden" name="ticket_id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="set_status">
                                
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <label class="text-sm font-medium text-slate-600 whitespace-nowrap hidden sm:block">ตั้งสถานะ:</label>
                                    <select name="status" class="w-full sm:w-auto border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white" required>
                                        <?php foreach($statuses as $v): ?>
                                            <option value="<?= $v ?>" <?= $sts === $v ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button type="submit" class="flex-1 sm:flex-none bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors">
                                        บันทึก
                                    </button>
                                    <button type="button" class="flex-1 sm:flex-none bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors" onclick="closeModal(<?= $id ?>)">
                                        ปิด
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-slate-50 border-t border-slate-100 px-4 py-3 flex items-center justify-between">
        <div class="text-xs text-slate-500 font-medium" id="countText">
            แสดงทั้งหมด <?= number_format($allCount) ?> รายการ
        </div>
    </div>
</div>

<script>
    // Client-side search and filtering
    const q = document.getElementById('q');
    const statusFilter = document.getElementById('statusFilter');
    const catFilter = document.getElementById('catFilter');
    const tableBody = document.querySelector('#ticketTable tbody');
    const countText = document.getElementById('countText');
    const totalRows = <?= (int)$allCount ?>;

    function normalize(s){ return (s||'').toString().toLowerCase().trim(); }
    function applyFilter(){
        const qv = normalize(q.value), sf = normalize(statusFilter.value), cf = normalize(catFilter.value);
        let shown = 0;
        [...tableBody.rows].forEach(tr => {
            const status = normalize(tr.dataset.status);
            const cat    = normalize(tr.dataset.cat);
            const text   = normalize(tr.innerText);
            const visible = (!qv || text.includes(qv)) && (!sf || status === sf) && (!cf || cat === cf);
            tr.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        countText.textContent = `แสดง ${shown} รายการ จากทั้งหมด ${totalRows} รายการ`;
    }
    
    q?.addEventListener('input', applyFilter);
    statusFilter?.addEventListener('change', applyFilter);
    catFilter?.addEventListener('change', applyFilter);

    // Modal Control
    function openModal(id) {
        const modal = document.getElementById('modal-' + id);
        if(!modal) return;
        modal.classList.remove('hidden');
        // trigger animation
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById('modal-' + id);
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
                const id = this.id.split('-')[1];
                closeModal(id);
            }
        });
    });
</script>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

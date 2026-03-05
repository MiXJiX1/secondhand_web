<?php
require_once __DIR__ . '/controllers/bank_verifications_controller.php';

$pageTitle = 'ยืนยันบัญชีธนาคาร';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex-1 w-full max-w-md relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input type="text" id="q" placeholder="ค้นหาชื่อผู้ใช้, เลขบัญชี..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm">
    </div>
    <div class="flex gap-2 w-full sm:w-auto">
        <select id="statusFilter" class="flex-1 sm:w-auto px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700">
            <option value="">ทุกสถานะ</option>
            <option value="pending" selected>รอตรวจสอบ (<?= $pendingCount ?>)</option>
            <option value="verified">ยืนยันแล้ว</option>
        </select>
    </div>
</div>

<?php if ($flash): ?>
<div class="mb-6 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-semibold animate-fade-in <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
    <span class="material-symbols-outlined"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
    <?= h($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Main Table Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="bankTable">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[13px] text-slate-500 font-semibold tracking-wide">
                    <th class="py-3 px-4 w-12 text-center">#ID</th>
                    <th class="py-3 px-4">ผู้ใช้งาน</th>
                    <th class="py-3 px-4">ข้อมูลบัญชีธนาคาร</th>
                    <th class="py-3 px-4">สถานะ</th>
                    <th class="py-3 px-4 w-1">การจัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (count($bankRecords) === 0): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-400">ไม่มีข้อมูลบัญชีธนาคารที่ต้องตรวจสอบ</td></tr>
                <?php else: ?>
                    <?php foreach ($bankRecords as $r):
                        $fullUser = trim(($r['fname'] ?? '').' '.($r['lname'] ?? ''));
                        if (empty($fullUser)) $fullUser = '-';
                        
                        $isVerified = (bool)$r['bank_verified'];
                        $statusText = $isVerified ? 'verified' : 'pending';
                        $statusUI = $isVerified 
                            ? '<span class="px-2 py-1 bg-green-100 text-green-700 border border-green-200 rounded-md text-xs font-semibold flex items-center gap-1 w-max"><span class="material-symbols-outlined text-[14px]">verified</span> ยืนยันแล้ว</span>'
                            : '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-md text-xs font-semibold flex items-center gap-1 w-max"><span class="material-symbols-outlined text-[14px]">pending</span> รอตรวจสอบ</span>';
                        
                        $avatar = !empty($r['img']) ? '../uploads/avatars/'.basename($r['img']) : '../assets/no-avatar.png';
                        
                        // Bank Image Map
                        $bankLogoMap = [
                            'กสิกรไทย' => 'kbank.png',
                            'ไทยพาณิชย์' => 'scb.png',
                            'กรุงไทย' => 'ktb.png',
                            'กรุงเทพ' => 'bbl.png',
                            'กรุงศรีอยุธยา' => 'bay.png',
                            'ทหารไทยธนชาต' => 'ttb.png',
                            'ออมสิน' => 'gsb.png',
                            'ธ.ก.ส.' => 'baac.png' // example fallback
                        ];
                        $bankLogo = $bankLogoMap[$r['bank_name']] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors bank-row"
                        data-status="<?= $statusText ?>"
                        data-search="<?= strtolower(h($r['username'].' '.$r['bank_account'].' '.$r['bank_account_name'])) ?>">
                        
                        <td class="py-3 px-4 text-center text-slate-500 font-mono"><?= (int)$r['user_id'] ?></td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <img src="<?= h($avatar) ?>" class="w-10 h-10 rounded-full object-cover border-2 border-slate-100" onerror="this.onerror=null; this.src='../assets/default.png';">
                                <div>
                                    <div class="font-bold text-slate-800"><?= h($r['username']) ?></div>
                                    <div class="text-xs text-slate-500 line-clamp-1"><?= h($r['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <?php if ($bankLogo): ?>
                                    <div class="w-8 h-8 rounded border border-slate-200 bg-white p-1 overflow-hidden flex-shrink-0">
                                        <img src="../assets/banks/<?= h($bankLogo) ?>" class="w-full h-full object-contain">
                                    </div>
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded border border-slate-200 bg-slate-100 flex-shrink-0 flex justify-center items-center">
                                        <span class="material-symbols-outlined text-[18px] text-slate-400">account_balance</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-bold text-slate-900 text-[13px]"><?= h($r['bank_name']) ?></div>
                                    <div class="text-xs text-slate-600"><?= h($r['bank_account_name']) ?></div>
                                    <code class="text-[11px] font-bold text-blue-700 bg-blue-50 px-1.5 rounded mt-0.5 inline-block"><?= h($r['bank_account']) ?></code>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4"><?= $statusUI ?></td>
                        <td class="py-3 px-4">
                            <?php if (!$isVerified): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="confirmAction('approve', <?= $r['user_id'] ?>)" class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-1.5 transition-colors tooltip" aria-label="ยืนยันบัญชี">
                                        <span class="material-symbols-outlined text-[18px]">check</span>
                                    </button>
                                    
                                    <button type="button" onclick="confirmAction('reject', <?= $r['user_id'] ?>)" class="bg-red-100 hover:bg-red-200 text-red-700 rounded-lg p-1.5 transition-colors tooltip" aria-label="ปฏิเสธ (ล้างข้อมูล)">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs italic">จัดการแล้ว</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Simple Client-side Filtering
document.addEventListener('DOMContentLoaded', () => {
    const qInput = document.getElementById('q');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.bank-row');
    
    function filterTable() {
        const q = qInput.value.toLowerCase();
        const stat = statusFilter.value;
        
        rows.forEach(row => {
            const rSearch = row.getAttribute('data-search');
            const rStat = row.getAttribute('data-status');
            
            let showQ = (q === '' || rSearch.includes(q));
            let showS = (stat === '' || rStat === stat);
            
            if (showQ && showS) row.style.display = '';
            else row.style.display = 'none';
        });
    }

    // Initial run to apply 'pending' filter by default
    filterTable();
});

// SweetAlert2 Confirmation Logic
function confirmAction(action, userId) {
    let title, text, confirmButtonText, confirmButtonColor;
    
    if (action === 'approve') {
        title = 'ยืนยันบัญชีธนาคาร?';
        text = 'คุณแน่ใจหรือไม่ว่าต้องการอนุมัติบัญชีธนาคารนี้ให้สามารถใช้งานได้?';
        confirmButtonText = 'ใช่, อนุมัติเลย';
        confirmButtonColor = '#16a34a'; // bg-green-600
    } else {
        title = 'ปฏิเสธและล้างข้อมูล?';
        text = 'การปฏิเสธจะทำรูปภาพและข้อมูลบัญชีธนาคารของลูกค้ารายนี้ถูกลบ เพื่อให้ลูกค้าสามารถยืนยันเข้ามาใหม่ได้ คุณแน่ใจหรือไม่?';
        confirmButtonText = 'ใช่, ปฏิเสธและลบเลย';
        confirmButtonColor = '#dc2626'; // bg-red-600
    }

    Swal.fire({
        title: title,
        text: text,
        icon: action === 'approve' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: '#94a3b8', // bg-slate-400
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a hidden form to submit the action via POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= $_SESSION['csrf_token'] ?>';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            form.appendChild(csrfInput);
            form.appendChild(actionInput);
            form.appendChild(userIdInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

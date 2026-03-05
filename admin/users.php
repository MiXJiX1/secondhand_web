<?php
require_once __DIR__ . '/controllers/users_controller.php';

if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$pageTitle = 'จัดการผู้ใช้';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex-1 w-full max-w-md relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input type="text" id="q" placeholder="ค้นหาชื่อผู้ใช้, อีเมล..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm">
    </div>
    <div class="flex gap-2 w-full sm:w-auto">
        <select id="roleFilter" class="flex-1 sm:w-auto px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700">
            <option value="">ทุก Role</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <select id="statusFilter" class="flex-1 sm:w-auto px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700">
            <option value="">ทุกสถานะ</option>
            <option value="active">Active</option>
            <option value="banned">Banned</option>
        </select>
    </div>
</div>

<!-- Main Table Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="userTable">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[13px] text-slate-500 font-semibold tracking-wide">
                    <th class="py-3 px-4 w-12 text-center">#</th>
                    <th class="py-3 px-4">ผู้ใช้</th>
                    <th class="py-3 px-4">ชื่อ-สกุล</th>
                    <th class="py-3 px-4 hidden lg:table-cell">อีเมล</th>
                    <th class="py-3 px-4 text-right">เครดิตคงเหลือ</th>
                    <th class="py-3 px-4">Role</th>
                    <th class="py-3 px-4">สถานะ</th>
                    <th class="py-3 px-4 w-1">การจัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm cursor-pointer">
                <?php foreach ($users as $u):
                    $full = trim(($u['fname'] ?? '').' '.($u['lname'] ?? ''));
                    $initial = mb_strtoupper(mb_substr($u['username'] ?? '',0,1));
                    $credit = (float)($u['credit_balance'] ?? 0);
                    $role = $u['role'] ?? 'user';
                    $status = $u['status'] ?? 'active';
                    
                    if ($status === 'active') $stUI = '<span class="px-2 py-1 bg-green-100 text-green-700 border border-green-200 rounded-md text-xs font-semibold">ACTIVE</span>';
                    elseif ($status === 'banned') $stUI = '<span class="px-2 py-1 bg-red-100 text-red-700 border border-red-200 rounded-md text-xs font-semibold">BANNED</span>';
                    else $stUI = '<span class="px-2 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-md text-xs font-semibold uppercase">'.h($status).'</span>';

                    $roleUI = $role === 'admin' ? '<span class="px-2 py-1 bg-primary/20 text-slate-900 border border-primary/30 rounded-md text-xs font-bold uppercase">ADMIN</span>' : '<span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-medium uppercase">USER</span>';
                ?>
                <tr class="hover:bg-slate-50 transition-colors"
                    data-user-id="<?= (int)$u['user_id'] ?>"
                    data-username="<?= h($u['username']) ?>"
                    data-fullname="<?= h($full ?: '-') ?>"
                    data-role="<?= h($role) ?>"
                    data-status="<?= h($status) ?>">
                    
                    <td class="py-3 px-4 text-center text-slate-500"><?= (int)$u['user_id'] ?></td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0 border border-slate-300">
                                <?= h($initial) ?>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900"><?= h($u['username']) ?></div>
                                <div class="text-[11px] text-slate-400 lg:hidden"><?= h($u['email'] ?: '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-slate-600 font-medium"><?= h($full ?: '-') ?></td>
                    <td class="py-3 px-4 text-slate-500 hidden lg:table-cell"><?= h($u['email'] ?: '-') ?></td>
                    <td class="py-3 px-4 text-right">
                        <span class="font-mono text-slate-700 font-medium"><?= number_format($credit,2) ?></span>
                        <span class="text-xs text-slate-400 ml-1">฿</span>
                    </td>
                    <td class="py-3 px-4"><?= $roleUI ?></td>
                    <td class="py-3 px-4"><?= $stUI ?></td>
                    
                    <td class="py-3 px-4 text-right relative">
                        <?php if (($u['role'] ?? 'user') !== 'admin'): ?>
                            <button type="button" onclick="toggleDropdown(this, event)" class="manage-btn p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">more_vert</span>
                            </button>
                            
                            <!-- Dropdown -->
                            <div class="dropdown-menu hidden absolute right-6 top-10 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50 text-left">
                                <a href="upgrade_user.php?id=<?= (int)$u['user_id'] ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">อัปเกรดเป็น Admin</a>
                                <div class="h-px bg-slate-100 my-1"></div>
                                
                                <?php if ($status !== 'banned'): ?>
                                    <button type="button" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors btn-open-ban"
                                            data-user-id="<?= (int)$u['user_id'] ?>"
                                            data-username="<?= h($u['username']) ?>">
                                        แบนผู้ใช้
                                    </button>
                                <?php else: ?>
                                    <form method="post" action="controllers/user_status_action_controller.php" onsubmit="return confirm('ยืนยันการยกเลิกแบนผู้ใช้ <?= h($u['username']) ?> ?')">
                                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="action" value="unban">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50 transition-colors" type="submit">ยกเลิกแบน</button>
                                    </form>
                                <?php endif; ?>
                                
                                <div class="h-px bg-slate-100 my-1"></div>
                                <a href="delete_user.php?id=<?= (int)$u['user_id'] ?>" onclick="return confirm('ยืนยันการลบผู้ใช้ <?= h($u['username']) ?> ?')" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">ลบผู้ใช้</a>
                            </div>
                        <?php else: ?>
                            <span class="text-slate-300 flex justify-center"><span class="material-symbols-outlined text-[18px]">admin_panel_settings</span></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 p-3 flex justify-between items-center text-xs text-slate-500">
        <span id="countText">แสดงทั้งหมด <?= count($users) ?> รายการ</span>
    </div>
</div>

<!-- Modal: Ban user -->
<div id="banModal" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0" id="banModalOverlay" onclick="closeBanModal()"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md w-full opacity-0 scale-95" id="banModalContent">
            <form method="post" action="controllers/user_status_action_controller.php">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-bold text-slate-900">แบนผู้ใช้</h3>
                        <button type="button" onclick="closeBanModal()" class="text-slate-400 hover:text-slate-500">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                    <input type="hidden" name="action" value="ban">
                    <input type="hidden" name="user_id" id="banUserId" value="">
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-500 font-medium" id="banUserLabel"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">เหตุผลในการแบน</label>
                        <textarea name="ban_reason" class="w-full border border-slate-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500 text-sm" rows="4" placeholder="โปรดระบุเหตุผลในการแบน..." required></textarea>
                        <p class="mt-2 text-xs text-slate-500">ตัวอย่าง: พฤติกรรมสแปม / หลอกลวง / ฝ่าฝืนนโยบายซ้ำซาก ฯลฯ</p>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl">
                    <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">ยันยืนการแบน</button>
                    <button type="button" onclick="closeBanModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: User Stats -->
<div id="userStatsModal" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0" id="statsModalOverlay" onclick="closeStatsModal()"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full opacity-0 scale-95" id="statsModalContent">
            
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg leading-6 font-bold text-slate-900">สถิติผู้ใช้</h3>
                <button type="button" onclick="closeStatsModal()" class="text-slate-400 hover:text-slate-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <div id="userStatsLoading" class="text-center py-8 text-slate-500 text-sm font-medium">กำลังโหลดวิเคราะห์ข้อมูล...</div>
                
                <div id="userStatsContent" class="hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-lg font-bold border border-slate-200" id="statsAvatar">U</div>
                        <div>
                            <div class="font-bold text-slate-900 text-lg leading-tight" id="statsUsername">username</div>
                            <div class="text-sm text-slate-500" id="statsFullname">-</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        <div class="p-3 border border-slate-100 rounded-xl bg-slate-50 flex flex-col justify-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">ยอดซื้อรวม</div>
                            <div class="font-bold text-slate-900"><span id="statsPurchaseAmount">0.00</span> <span class="text-[10px] text-slate-400">บาท</span></div>
                        </div>
                        <div class="p-3 border border-slate-100 rounded-xl bg-slate-50 flex flex-col justify-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">ยอดขายรวม</div>
                            <div class="font-bold text-slate-900"><span id="statsSalesAmount">0.00</span> <span class="text-[10px] text-slate-400">บาท</span></div>
                        </div>
                        <div class="p-3 border border-slate-100 rounded-xl bg-slate-50 flex flex-col justify-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">เป็นสมาชิกตั้งแต่</div>
                            <div class="font-bold text-slate-900 text-[13px]" id="statsMemberSince">-</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center mb-6">
                        <div class="p-3 border border-slate-100 rounded-xl flex flex-col justify-center items-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">ขายแล้ว</div>
                            <div class="text-xl font-bold text-green-600" id="statsSold">0</div>
                        </div>
                        <div class="p-3 border border-slate-100 rounded-xl flex flex-col justify-center items-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">ซื้อสำเร็จ</div>
                            <div class="text-xl font-bold text-blue-600" id="statsBought">0</div>
                        </div>
                        <div class="p-3 border border-slate-100 rounded-xl flex flex-col justify-center items-center">
                            <div class="text-[11px] text-slate-500 font-medium mb-1">แลกเปลี่ยน</div>
                            <div class="text-xl font-bold text-orange-500" id="statsSwap">0</div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-200 pb-2">
                            <div class="font-semibold text-slate-800 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[18px] text-yellow-500" style="font-variation-settings: 'FILL' 1;">star</span> 
                                เรทติ้งเฉลี่ย
                            </div>
                            <div class="text-sm"><span class="font-bold text-slate-900" id="statsAvg">0.00</span> / 5 <span class="text-slate-300 mx-1">•</span> <span class="text-slate-500"><span id="statsCount">0</span> รีวิว</span></div>
                        </div>
                        <div class="text-sm">
                            <div class="text-xs text-slate-400 font-medium mb-2 uppercase tracking-wide">รีวิวล่าสุด</div>
                            <ul id="statsLatest" class="space-y-3"></ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex justify-end">
                <button type="button" onclick="closeStatsModal()" class="inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none transition-colors">ปิด</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

<script>
    // ---------- Dropdown Logic ----------
    let activeDropdown = null;
    function toggleDropdown(button, event) {
        event.stopPropagation();
        const dropdown = button.nextElementSibling;
        
        if (activeDropdown && activeDropdown !== dropdown) {
            activeDropdown.classList.add('hidden');
        }
        
        dropdown.classList.toggle('hidden');
        activeDropdown = dropdown.classList.contains('hidden') ? null : dropdown;
    }
    
    document.addEventListener('click', (e) => {
        if (activeDropdown && !activeDropdown.contains(e.target) && !e.target.closest('.manage-btn')) {
            activeDropdown.classList.add('hidden');
            activeDropdown = null;
        }
    });

    // ---------- Search & Filter ----------
    const q = document.getElementById('q');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.querySelector('#userTable tbody');
    const countText = document.getElementById('countText');
    const totalRows = <?= count($users) ?>;

    function normalize(s){ return (s||'').toString().toLowerCase().trim(); }
    function applyFilter(){
        const qv = normalize(q.value), rf = normalize(roleFilter.value), sf = normalize(statusFilter.value);
        let shown = 0;
        [...tableBody.rows].forEach(tr => {
            const role = normalize(tr.dataset.role), status = normalize(tr.dataset.status), text = normalize(tr.innerText);
            const visible = (!qv || text.includes(qv)) && (!rf || role===rf) && (!sf || status===sf);
            tr.style.display = visible ? '' : 'none'; 
            if (visible) shown++;
        });
        countText.textContent = `แสดง ${shown} รายการ จากทั้งหมด ${totalRows} รายการ`;
    }
    q.addEventListener('input', applyFilter);
    roleFilter.addEventListener('change', applyFilter);
    statusFilter.addEventListener('change', applyFilter);

    // ---------- Ban Modal ----------
    const banModal = document.getElementById('banModal');
    const banModalOverlay = document.getElementById('banModalOverlay');
    const banModalContent = document.getElementById('banModalContent');

    document.querySelectorAll('.btn-open-ban').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            if(activeDropdown) activeDropdown.classList.add('hidden');
            
            const id = btn.dataset.userId;
            const name = btn.dataset.username;
            document.getElementById('banUserId').value = id;
            document.getElementById('banUserLabel').innerHTML = `กำลังจะแบนผู้ใช้: <strong class="text-slate-900">${name}</strong> (ID: ${id})`;
            
            banModal.classList.remove('hidden');
            // trigger reflow
            void banModal.offsetWidth;
            banModalOverlay.classList.remove('opacity-0');
            banModalContent.classList.remove('opacity-0', 'scale-95');
        });
    });

    function closeBanModal() {
        banModalOverlay.classList.add('opacity-0');
        banModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            banModal.classList.add('hidden');
        }, 300);
    }

    // ---------- Stats Modal ----------
    const statsModal = document.getElementById('userStatsModal');
    const statsModalOverlay = document.getElementById('statsModalOverlay');
    const statsModalContent = document.getElementById('statsModalContent');

    function closeStatsModal() {
        statsModalOverlay.classList.add('opacity-0');
        statsModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            statsModal.classList.add('hidden');
        }, 300);
    }

    function openStats(tr) {
        const uid = tr.getAttribute('data-user-id');
        const uname = tr.getAttribute('data-username') || '';
        const fullname = tr.getAttribute('data-fullname') || '';

        // UI Prep
        statsModal.classList.remove('hidden');
        void statsModal.offsetWidth;
        statsModalOverlay.classList.remove('opacity-0');
        statsModalContent.classList.remove('opacity-0', 'scale-95');

        if (!uid || uid === 'null' || uid === '0') {
            document.getElementById('userStatsLoading').textContent = 'โหลดไม่สำเร็จ: ไม่พบ user_id ในแถวนี้';
            document.getElementById('userStatsContent').classList.add('hidden');
            document.getElementById('userStatsLoading').classList.remove('hidden');
            return;
        }

        // reset UI
        document.getElementById('userStatsLoading').textContent = 'กำลังโหลดวิเคราะห์ข้อมูล...';
        document.getElementById('userStatsLoading').classList.remove('hidden');
        document.getElementById('userStatsContent').classList.add('hidden');
        
        document.getElementById('statsAvatar').textContent  = (uname||'?').trim().charAt(0).toUpperCase();
        document.getElementById('statsUsername').textContent = uname || '-';
        document.getElementById('statsFullname').textContent = fullname || '-';

        document.getElementById('statsPurchaseAmount').textContent = '0.00';
        document.getElementById('statsSalesAmount').textContent    = '0.00';
        document.getElementById('statsMemberSince').textContent    = '-';
        document.getElementById('statsSold').textContent           = '0';
        document.getElementById('statsBought').textContent         = '0';
        document.getElementById('statsSwap').textContent           = '0';
        document.getElementById('statsAvg').textContent            = '0.00';
        document.getElementById('statsCount').textContent          = '0';
        document.getElementById('statsLatest').innerHTML           = '';

        fetch('user_stats.php?user_id=' + encodeURIComponent(uid), { cache:'no-store', headers:{'Accept':'application/json'} })
            .then(async r => {
                if (!r.ok) throw new Error('HTTP '+r.status+' '+(await r.text()).slice(0,120));
                return r.json();
            })
            .then(({ok, data, error}) => {
                if(!ok){ throw new Error(error || 'fetch error'); }

                document.getElementById('statsSold').textContent           = data.sold_count;
                document.getElementById('statsBought').textContent         = data.bought_count;
                document.getElementById('statsSwap').textContent           = data.swap_count;
                document.getElementById('statsAvg').textContent            = (+data.avg_score).toFixed(2);
                document.getElementById('statsCount').textContent          = data.rating_count;

                document.getElementById('statsPurchaseAmount').textContent = (+data.purchases_amount).toLocaleString('th-TH', {minimumFractionDigits: 2});
                document.getElementById('statsSalesAmount').textContent    = (+data.sales_amount).toLocaleString('th-TH', {minimumFractionDigits: 2});
                document.getElementById('statsMemberSince').textContent    = data.member_since ?? '-';

                const ul = document.getElementById('statsLatest');
                if (Array.isArray(data.latest_ratings) && data.latest_ratings.length) {
                    data.latest_ratings.forEach(r => {
                        const li = document.createElement('li');
                        li.className = 'border-l-2 border-primary pl-3 py-1';
                        
                        let stars = '';
                        for(let i=0; i<5; i++) {
                            stars += `<span class="material-symbols-outlined text-[14px] ${i < r.score ? 'text-yellow-500' : 'text-slate-200'}" style="font-variation-settings: 'FILL' 1;">star</span>`;
                        }
                        
                        li.innerHTML = `
                            <div class="flex items-center gap-2 mb-1">
                                <div class="flex">${stars}</div>
                                <span class="text-[11px] text-slate-400">&bull; ${r.created_at ?? ''}</span>
                            </div>
                            <p class="text-[13px] text-slate-700 leading-snug">${r.comment || '<em class="text-slate-400">ไม่มีความคิดเห็น</em>'}</p>
                        `;
                        ul.appendChild(li);
                    });
                } else {
                    ul.innerHTML = '<li class="text-xs text-slate-400 mt-2">— ไม่พบรีวิวล่าสุด —</li>';
                }

                document.getElementById('userStatsLoading').classList.add('hidden');
                document.getElementById('userStatsContent').classList.remove('hidden');
            })
            .catch(err => {
                document.getElementById('userStatsLoading').textContent = 'โหลดไม่สำเร็จ: ' + err.message;
            });
    }

    // Bind row click
    document.querySelectorAll('#userTable tbody tr').forEach(tr => {
        tr.addEventListener('click', (ev) => {
            // Ignore click if it's in the action column dropdown or buttons
            if (ev.target.closest('button') || ev.target.closest('a') || ev.target.closest('.dropdown-menu')) return;
            openStats(tr);
        });
    });
</script>
</body>
</html>

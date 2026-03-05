<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    redirect('../login');
}
$me = (int)$_SESSION['user_id'];

// --- NEW CHAT INITIATION LOGIC ---
$requestId = isset($_GET['request_id']) ? trim($_GET['request_id']) : '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$sellerUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($requestId === '' && $productId > 0 && $sellerUserId > 0) {
    if ($me === $sellerUserId) {
        throw new Exception("คุณไม่สามารถแชทกับตัวเองได้", 400);
    }
    
    // Check if a request already exists
    $checkReq = $pdo->prepare("SELECT request_id FROM chat_requests WHERE product_id = ? AND ( (buyer_id = ? AND seller_id = ?) OR (buyer_id = ? AND seller_id = ?) ) LIMIT 1");
    $checkReq->execute([$productId, $me, $sellerUserId, $sellerUserId, $me]);
    $existingReq = $checkReq->fetch();
    
    if ($existingReq) {
        $requestId = $existingReq['request_id'];
    } else {
        // Create new request
        $requestId = 'REQ_' . bin2hex(random_bytes(8));
        $insReq = $pdo->prepare("INSERT INTO chat_requests (request_id, product_id, buyer_id, seller_id) VALUES (?, ?, ?, ?)");
        $insReq->execute([$requestId, $productId, $me, $sellerUserId]);
    }
    // Redirect to self with requestId to trigger autoload and clean URL
    redirect("chat?request_id=" . urlencode($requestId) . "&product_id=" . $productId);
}
// ----------------------------------

// Get user info for sidebar
$stmt_me = $pdo->prepare("SELECT fname, lname, img FROM users WHERE user_id = ? LIMIT 1");
$stmt_me->execute([$me]);
$myInfo = $stmt_me->fetch();
$myName = trim(($myInfo['fname']??'').' '.($myInfo['lname']??''));
$myAvatarText = mb_substr($myName, 0, 1) ?: 'U';
$myAvatarUrl = !empty($myInfo['img']) && $myInfo['img']!=='default.png' ? $baseUrl . '/uploads/avatars/'.basename($myInfo['img']) : null;

// Fetch all chats
$sql = "
SELECT m1.request_id, m1.message AS last_message, m1.created_at AS last_time,
       cr.product_id, cr.seller_id, cr.buyer_id,
       p.product_name, p.product_image, p.status AS product_status,
       us.fname AS seller_fname, us.lname AS seller_lname, us.img AS seller_img,
       ub.fname AS buyer_fname,  ub.lname AS buyer_lname, ub.img AS buyer_img,
       (SELECT COUNT(*) FROM messages mu WHERE mu.request_id = cr.request_id AND mu.receiver_id = ? AND mu.is_read = 0) AS unread_count
FROM messages m1
JOIN (SELECT request_id, MAX(id) AS last_id FROM messages GROUP BY request_id) m2 ON m1.id = m2.last_id
JOIN chat_requests cr ON m1.request_id = cr.request_id
LEFT JOIN products p  ON cr.product_id = p.product_id
LEFT JOIN users us    ON cr.seller_id  = us.user_id
LEFT JOIN users ub    ON cr.buyer_id   = ub.user_id
WHERE cr.seller_id = ? OR cr.buyer_id = ?
ORDER BY m1.created_at DESC";

$stmt = $pdo->prepare($sql); 
$stmt->execute([$me, $me, $me]);
$allChats = $stmt->fetchAll();

// Format Time helper
function formatChatTimeMockup($datetimeStr) {
    if (!$datetimeStr) return '';
    $ts = strtotime($datetimeStr);
    $diff = time() - $ts;
    
    if ($diff < 60) return 'เมื่อครู่';
    elseif ($diff < 3600) return floor($diff / 60) . ' นาทีที่แล้ว';
    elseif ($diff < 86400) return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    else return date('j M', $ts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อความ | Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        body { overflow: hidden; }
    </style>
</head>
<body class="bg-customBg font-sans text-slate-800 h-screen w-screen flex flex-col overflow-hidden">

    <!-- Main Navbar -->
    <?php include __DIR__ . '/../includes/navbar_main.php'; ?>

    <!-- Main Content Area: Chat List (Left) + Empty State (Right) -->
    <div class="flex-1 flex overflow-hidden relative">

    <!-- 1. Left Panel (Chat List) -->
    <section class="w-full sm:w-[360px] lg:w-[400px] bg-white border-r border-slate-100 flex flex-col h-full shrink-0 z-10 transition-all">
        
        <!-- Header -->
        <div class="p-6 pb-4 border-b border-transparent shadow-[0_4px_20px_rgb(0,0,0,0.01)] relative z-10">
            <div class="flex justify-between items-center mb-5">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">ข้อความ</h1>
                <button class="w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-900 transition-colors shrink-0">
                    <span class="material-symbols-outlined text-[22px]" style="font-variation-settings: 'FILL' 1;">edit_square</span>
                </button>
            </div>
            
            <!-- Filters -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                <button onclick="filterChats('all', this)" class="filter-btn px-4 py-1.5 rounded-full bg-primary text-slate-900 text-xs font-bold leading-none shrink-0 border border-transparent shadow-[0_2px_8px_rgb(249,231,31,0.3)]">ทั้งหมด</button>
                <button onclick="filterChats('buying', this)" class="filter-btn px-4 py-1.5 rounded-full bg-slate-100/80 hover:bg-slate-200 text-slate-600 text-xs font-semibold leading-none shrink-0 transition-colors">กำลังซื้อ</button>
                <button onclick="filterChats('selling', this)" class="filter-btn px-4 py-1.5 rounded-full bg-slate-100/80 hover:bg-slate-200 text-slate-600 text-xs font-semibold leading-none shrink-0 transition-colors">กำลังขาย</button>
                <button onclick="filterChats('unread', this)" class="filter-btn px-4 py-1.5 rounded-full bg-slate-100/80 hover:bg-slate-200 text-slate-600 text-xs font-semibold leading-none shrink-0 transition-colors">ยังไม่ได้อ่าน</button>
                <button class="w-7 h-7 rounded-full bg-slate-100/80 hover:bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors ml-1">
                    <span class="material-symbols-outlined text-[16px]">tune</span>
                </button>
            </div>
        </div>

        <!-- Chat Items -->
        <div class="flex-1 overflow-y-auto no-scrollbar pb-4 bg-white relative z-0">
            <?php if (empty($allChats)): ?>
                <div class="p-8 text-center text-slate-400 pt-16">
                    <span class="material-symbols-outlined text-[64px] mb-4 opacity-20 text-slate-300">question_answer</span>
                    <p class="text-[15px] font-medium text-slate-500">ยังไม่มีข้อความ</p>
                </div>
            <?php else: ?>
                <?php foreach ($allChats as $c): 
                    $isUnread = ($c['unread_count'] > 0);
                    
                    if ($me == $c['seller_id']) {
                        $cName = trim($c['buyer_fname'].' '.$c['buyer_lname']) ?: 'Buyer';
                        $cUserImg = $c['buyer_img'] ? $baseUrl . '/uploads/avatars/'.basename($c['buyer_img']) : null;
                        $roleTag = "SELLING";
                    } else {
                        $cName = trim($c['seller_fname'].' '.$c['seller_lname']) ?: 'Seller';
                        $cUserImg = $c['seller_img'] ? $baseUrl . '/uploads/avatars/'.basename($c['seller_img']) : null;
                        $roleTag = "BUYING";
                    }
                    $productNamePreview = $c['product_name'] ?: 'Unknown Item';
                    $isSold = ($c['product_status'] === 'sold');
                ?>
                <!-- Chat List Item: Loaded via AJAX now -->
                <a href="<?= $baseUrl ?>/chat-window?request_id=<?= urlencode($c['request_id']) ?>&product_id=<?= (int)$c['product_id'] ?>" 
                   onclick="loadChat(event, '<?= htmlspecialchars($c['request_id']) ?>', <?= (int)$c['product_id'] ?>, this)"
                   data-role="<?= strtolower($roleTag) ?>"
                   data-unread="<?= $isUnread ? 'true' : 'false' ?>"
                   class="chat-list-item block p-4 mx-3 mb-1.5 rounded-2xl transition-all relative <?= $isUnread ? 'bg-white hover:bg-slate-50' : 'bg-transparent hover:bg-slate-50' ?> border border-transparent hover:border-slate-100">
                    
                    <div class="flex gap-4 relative">
                        <!-- Avatar -->
                        <div class="w-14 h-14 rounded-full bg-slate-200 flex-shrink-0 overflow-hidden relative border border-slate-100/50 shadow-sm">
                            <?php if ($cUserImg): ?>
                                <img src="<?= htmlspecialchars($cUserImg) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                            <?php else: ?>
                                <span class="absolute inset-0 flex items-center justify-center font-bold text-slate-500 text-lg"><?= mb_substr($cName, 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Details -->
                        <div class="flex-1 min-w-0 flex flex-col justify-center -mt-0.5">
                            <div class="flex justify-between items-baseline mb-1">
                                <h3 class="font-bold text-[15px] text-slate-900 truncate <?= $isUnread ? 'text-black' : '' ?> pr-2"><?= htmlspecialchars($cName) ?></h3>
                                <span class="text-[10px] sm:text-[11px] uppercase font-bold text-slate-400 whitespace-nowrap tracking-wider"><?= formatChatTimeMockup($c['last_time']) ?></span>
                            </div>
                            
                            <!-- Preview Message -->
                            <p class="text-[13.5px] text-slate-500 truncate <?= $isUnread ? 'font-bold text-slate-800' : '' ?> pr-8">
                                <?= htmlspecialchars(strip_tags($c['last_message'])) ?>
                            </p>
                            
                            <!-- Internal Product Ref Tag -->
                            <div class="mt-2.5 flex items-center gap-2 relative">
                                <span class="inline-flex bg-slate-100 text-slate-600 text-[9.5px] font-bold px-2 py-0.5 rounded shadow-sm uppercase tracking-widest border border-slate-200/60 max-w-[85%] truncate">
                                    <?= $roleTag === 'BUYING' ? 'กำลังซื้อ' : 'กำลังขาย' ?>: <?= htmlspecialchars($productNamePreview) ?>
                                </span>
                                <?php if($isSold): ?>
                                <span class="inline-flex bg-red-100 text-red-600 text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm uppercase">ขายแล้ว</span>
                                <?php endif; ?>
                            </div>

                            <!-- Unread Badge Yellow -->
                            <?php if ($isUnread): ?>
                                <div class="absolute right-0 top-1/2 mt-1 -translate-y-1/2 w-[22px] h-[22px] min-w-fit px-1 bg-primary rounded-full flex items-center justify-center text-[11px] font-black text-slate-900 shadow-sm font-sans mx-auto">
                                    <?= $c['unread_count'] > 99 ? '99+' : $c['unread_count'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <div class="h-px bg-slate-50 mx-6 last:hidden"></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3. Right Pane: Chat Area (Starts Empty) -->
    <main id="chat-content-area" class="flex-1 bg-customBg hidden sm:flex flex-col relative h-full z-0 transition-transform duration-300">
        
        <!-- EMPTY STATE -->
        <div class="m-auto flex flex-col items-center justify-center text-center px-4 -mt-20">
            <!-- Glowing Circle Icon -->
            <div class="relative w-72 h-72 flex items-center justify-center mb-10">
                <div class="absolute inset-0 bg-yellow-50 rounded-full animate-pulse opacity-60 mix-blend-multiply"></div>
                <div class="absolute inset-6 bg-yellow-100/40 rounded-full mix-blend-multiply"></div>
                <div class="absolute inset-12 bg-white rounded-full shadow-[0_8px_30px_rgb(249,231,31,0.15)] flex items-center justify-center border border-yellow-100/50">
                    <span class="material-symbols-outlined text-[110px] text-primary drop-shadow-sm" style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
                </div>
            </div>

            <h2 class="text-[32px] font-black text-slate-900 tracking-tight mb-4 leading-tight">เลือกการสนทนา</h2>
            <p class="text-[15px] font-medium text-slate-500 max-w-sm leading-relaxed mb-10">
                เลือกข้อความจากทางซ้ายเพื่อเริ่มพูดคุยเกี่ยวกับสินค้าหรือต่อรองราคา
            </p>

            <button class="bg-primary hover:bg-yellow-400 text-slate-900 font-bold px-7 py-3.5 rounded-xl shadow-[0_4px_14px_0_rgba(249,231,31,0.39)] hover:shadow-[0_6px_20px_rgba(249,231,31,0.23)] hover:-translate-y-0.5 transition-all flex items-center gap-2 text-[15px]">
                <span class="material-symbols-outlined text-[20px] font-bold">add</span>
                ข้อความใหม่
            </button>
        </div>

    </main>
    
    </div> <!-- End Main Content Area -->

    <!-- Mobile Overlay for Chat List -> Chat View transition -->
    <style>
        @media (max-width: 767px) {
            #chat-content-area.mobile-active {
                display: flex !important;
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                z-index: 50;
            }
            .chat-list-hidden-mobile .w-full.sm\:w-\[360px\] {
                display: none;
            }
        }
    </style>

    <script>
        // SPA Logic for loading chats
        window.loadChat = function(e_or_reqId, productId, element = null) {
            let reqId = e_or_reqId;
            
            // Handle if called from click event: loadChat(event, reqId, productId, element)
            if (typeof e_or_reqId === 'object' && e_or_reqId.preventDefault) {
                e_or_reqId.preventDefault();
                reqId = arguments[1]; 
                productId = arguments[2];
                element = arguments[3];
            }
            
            if (!reqId || !productId) return;

            // Highlight selected item in list
            document.querySelectorAll('.chat-list-item').forEach(el => {
                el.classList.remove('bg-white', 'border-slate-200', 'shadow-sm');
                el.classList.add('bg-transparent', 'border-transparent');
            });
            if (element) {
                element.classList.remove('bg-transparent', 'border-transparent');
                element.classList.add('bg-white', 'border-slate-200', 'shadow-sm');
                
                // Remove notification dot if exists
                const dot = element.querySelector('.bg-red-500.absolute');
                if(dot) dot.remove();
            }

            // Show loading state in right panel
            const contentArea = document.getElementById('chat-content-area');
            contentArea.innerHTML = `
                <div class="flex-1 flex items-center justify-center bg-customBg">
                    <div class="w-10 h-10 border-4 border-slate-200 border-t-primary rounded-full animate-spin"></div>
                </div>
            `;
            
            // Handle mobile view sliding
            if (window.innerWidth < 768) {
                contentArea.classList.add('mobile-active');
                document.getElementById('chat-content-area').parentElement.classList.add('chat-list-hidden-mobile');
            }

            // Update URL without reloading
            const baseUrl = "<?= $baseUrl ?>";
            const newUrl = `${baseUrl}/chat-window?request_id=${encodeURIComponent(reqId)}&product_id=${productId}`;
            window.history.pushState({path: newUrl}, '', newUrl);

            // Fetch partial UI
            fetch(newUrl + '&partial=true')
                .then(r => r.text())
                .then(html => {
                    contentArea.innerHTML = html;
                    // Any inline scripts in the fetched HTML will NOT execute automatically via innerHTML
                    // But since we put the scripts in an IIFE in chat.php that runs on DOMContentLoaded (which already fired),
                    // We need to manually evaluate the script tags if they exist.
                    const scripts = contentArea.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.textContent;
                        }
                        document.body.appendChild(newScript);
                        document.body.removeChild(newScript); // Clean up
                    });
                })
                .catch(err => {
                    console.error(err);
                    contentArea.innerHTML = `<div class="p-8 text-center text-red-500">Error loading chat interface. <a href="${newUrl}" class="underline">Try full reload</a></div>`;
                });
        };

        window.closeMobileChat = function() {
            const contentArea = document.getElementById('chat-content-area');
            contentArea.classList.remove('mobile-active');
            document.getElementById('chat-content-area').parentElement.classList.remove('chat-list-hidden-mobile');
            
            // Clear polling if any
            if (window.chatPollInterval) {
                clearInterval(window.chatPollInterval);
            }
            // Restore URL to chat list
            window.history.pushState({path: 'chat_list.php'}, '', 'chat_list.php');
        };

        // Filter Logic
        window.filterChats = function(filter, btn) {
            // Update button styles
            document.querySelectorAll('.filter-btn').forEach(el => {
                el.classList.remove('bg-primary', 'text-slate-900', 'border-transparent', 'shadow-[0_2px_8px_rgb(249,231,31,0.3)]');
                el.classList.add('bg-slate-100/80', 'text-slate-600', 'font-semibold');
            });
            btn.classList.add('bg-primary', 'text-slate-900', 'border-transparent', 'shadow-[0_2px_8px_rgb(249,231,31,0.3)]');
            btn.classList.remove('bg-slate-100/80', 'text-slate-600', 'font-semibold');

            const items = document.querySelectorAll('.chat-list-item');
            items.forEach(item => {
                const role = item.getAttribute('data-role');
                const unread = item.getAttribute('data-unread') === 'true';
                
                let show = false;
                if (filter === 'all') show = true;
                else if (filter === 'buying' && role === 'buying') show = true;
                else if (filter === 'selling' && role === 'selling') show = true;
                else if (filter === 'unread' && unread) show = true;

                if (show) {
                    item.classList.remove('hidden');
                    // Also show the separator if any (it's the next sibling)
                    if (item.nextElementSibling && item.nextElementSibling.classList.contains('h-px')) {
                        item.nextElementSibling.classList.remove('hidden');
                    }
                } else {
                    item.classList.add('hidden');
                    if (item.nextElementSibling && item.nextElementSibling.classList.contains('h-px')) {
                        item.nextElementSibling.classList.add('hidden');
                    }
                }
            });
        };

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            // If the URL is chat_list.php (no params), close mobile view
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.get('request_id')) {
                window.closeMobileChat();
            } else {
                // Otherwise try to load the chat based on URL
                window.loadChat(urlParams.get('request_id'), urlParams.get('product_id'));
            }
        });

        // Auto-load if URL has params
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const rId = urlParams.get('request_id');
            const pId = urlParams.get('product_id');
            if (rId && pId) {
                // Find matching element in list to highlight it
                const item = document.querySelector(`.chat-list-item[href*="request_id=${rId}"]`);
                window.loadChat(rId, pId, item);
            }
        });
    </script>
</body>
</html>

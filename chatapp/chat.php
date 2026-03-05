<?php
require_once __DIR__ . '/api/chat_controller.php';
?>
<?php if (!$isPartial): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Marketplace</title>
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
                        customBg: "#fbfbfb", // Light cream page bg
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
        /* Prevent body scroll, layout handles it */
        body { overflow: hidden; }
    </style>
</head>
<body class="bg-customBg font-sans text-slate-800 h-screen w-screen flex flex-col relative">

    <!-- Navbar -->
    <header class="h-[72px] bg-white border-b border-slate-100 flex items-center px-4 md:px-8 flex-shrink-0 z-10 shadow-sm relative w-full">
        <a href="chat_list.php" 
           <?= $isPartial ? 'onclick="if(window.closeMobileChat) { window.closeMobileChat(); return false; }"' : '' ?>
           class="text-slate-500 hover:text-slate-900 p-2 -ml-2 rounded-lg hover:bg-slate-100 shrink-0 mr-4 md:hidden">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div class="flex items-center gap-4 flex-1 min-w-0">
<?php endif; // !$isPartial ?>

<div id="chat-partial-wrapper" class="flex flex-col h-full w-full bg-customBg relative">
    
    <!-- Chat Header -->
    <?php if ($isPartial): ?>
    <header class="h-[72px] bg-white border-b border-slate-100 flex items-center px-4 md:px-8 flex-shrink-0 z-10 shadow-sm relative w-full">
        <button onclick="closeMobileChat()" class="md:hidden text-slate-500 hover:text-slate-900 p-2 -ml-2 rounded-lg hover:bg-slate-100 shrink-0 mr-4">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <div class="flex items-center gap-4 flex-1 min-w-0">
    <?php endif; ?>
            <?php if ($requestId !== ''): ?>
            <div class="relative w-11 h-11 rounded-full bg-slate-200 overflow-hidden flex-shrink-0 border border-slate-100 ring-2 ring-white">
                <?php if ($otherImg): ?>
                    <img src="<?= htmlspecialchars($otherImg) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/default.png';">
                <?php else: ?>
                    <span class="absolute inset-0 flex items-center justify-center font-bold text-slate-500"><?= mb_substr($otherName, 0, 1) ?></span>
                <?php endif; ?>
                <!-- Active green dot -->
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full z-10"></div>
            </div>
            <div class="truncate">
                <h2 class="font-bold text-[17px] text-slate-900 leading-tight truncate"><?= htmlspecialchars($otherName) ?></h2>
                <p class="text-[12px] text-slate-400 font-medium">Active now</p>
            </div>
            <?php else: ?>
                <h2 class="font-bold text-[17px] text-slate-900">Chat</h2>
            <?php endif; ?>
        </div>

        <!-- Product Link Box (Right side of header) -->
        <?php if ($productId > 0): ?>
        <a href="../php/product_detail.php?id=<?= $productId ?>" class="hidden sm:flex items-center gap-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl p-1.5 pr-4 transition-colors max-w-[280px] shadow-sm ml-4">
            <?php if ($activeProductImgUrl): ?>
                <img src="<?= htmlspecialchars($activeProductImgUrl) ?>" class="w-10 h-10 object-cover rounded-lg shadow-sm border border-slate-100" onerror="this.onerror=null; this.src='/assets/default.png';">
            <?php else: ?>
                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center"><span class="material-symbols-outlined text-slate-300 text-sm">image</span></div>
            <?php endif; ?>
            <div class="flex-1 min-w-0 pr-2">
                <p class="text-[12px] font-bold text-slate-800 truncate mb-0.5"><?= htmlspecialchars($chatProductName) ?></p>
                <p class="text-[11px] font-black text-slate-900 bg-primary/20 px-1.5 py-0.5 rounded inline-block">฿<?= number_format($productPrice) ?></p>
            </div>
            <span class="material-symbols-outlined text-slate-400 text-[18px]">chevron_right</span>
        </a>
        <?php endif; ?>
    </header>

    <main class="flex-1 overflow-hidden flex flex-col relative z-0">
        <?php if ($requestId === ''): ?>
            <!-- EMPTY STATE -->
            <div class="m-auto flex flex-col items-center justify-center text-center px-4 h-full">
                <div class="relative w-48 h-48 flex items-center justify-center mb-8">
                    <div class="absolute inset-0 bg-yellow-50 rounded-full animate-pulse opacity-50"></div>
                    <div class="absolute inset-4 bg-yellow-100/50 rounded-full"></div>
                    <div class="absolute inset-8 bg-white rounded-full shadow-sm flex items-center justify-center border border-yellow-100/30">
                        <span class="material-symbols-outlined text-[80px] text-primary" style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
                    </div>
                </div>
                <h2 class="text-2xl font-black text-slate-900">Select a conversation</h2>
                <p class="text-[14px] text-slate-500 mt-2">Go back to Chat List and select a message.</p>
            </div>
        <?php else: ?>
            <!-- Messages List -->
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 relative">
                <!-- Loaded via JS -->
            </div>

            <!-- Payment Banner -->
            <?php if ($productId > 0): ?>
            <div id="payment-banner" class="px-4 sm:px-6 py-3 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 transition-all">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">payments</span>
                    <span id="order-status-text" class="text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <?php 
                        if (!$activeOrder) echo "ยังไม่มีคำสั่งซื้อ";
                        elseif ($activeOrder['status'] === 'pending') echo "รอชำระเงิน: ฿" . number_format($activeOrder['amount'], 2);
                        elseif ($activeOrder['status'] === 'paid') echo "ชำระเงินแล้ว (เงินพักอยู่ในระบบ)";
                        elseif ($activeOrder['status'] === 'released') echo "โอนเงินเข้าบัญชีผู้ขายแล้ว";
                        else echo "สถานะ: " . htmlspecialchars($activeOrder['status']);
                        ?>
                    </span>
                </div>
                
                <div class="flex gap-2">
                <?php if ($me === $buyerId): ?>
                    <?php if (!$activeOrder || $activeOrder['status'] === 'pending'): ?>
                        <button onclick="initiatePayment()" class="bg-primary text-slate-900 text-[12px] font-bold px-4 py-1.5 rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95">จ่ายด้วย MSUPAY</button>
                    <?php elseif ($activeOrder['status'] === 'paid'): ?>
                        <button onclick="confirmReceipt()" class="bg-green-500 text-white text-[12px] font-bold px-4 py-1.5 rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95">ฉันได้รับสินค้าแล้ว</button>
                    <?php elseif ($activeOrder['status'] === 'released' && empty($activeOrder['has_rated'])): ?>
                        <button onclick="openRatingModal()" class="bg-yellow-400 text-slate-900 text-[12px] font-bold px-4 py-1.5 rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">star</span>ให้คะแนนผู้ขาย
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Input Area -->
            <div class="p-4 sm:p-6 bg-white border-t border-slate-100 flex-shrink-0 z-10 w-full mb-0 h-auto flex items-center shadow-[0_-10px_30px_rgb(0,0,0,0.02)] pt-4 pb-4">
                <form id="chat-form" class="w-full flex items-center gap-2 sm:gap-3" onsubmit="sendMsg(event)">
                    
                    <div class="relative">
                        <button type="button" id="action-btn" onclick="toggleActionMenu(event)" class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors tooltip" title="Attach file">
                            <span class="material-symbols-outlined text-[20px]">add</span>
                        </button>

                        <!-- Action Menu Dropdown -->
                        <div id="floating-action-menu" class="hidden absolute bottom-full left-0 mb-3 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 transform origin-bottom-left transition-all animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <button type="button" onclick="document.getElementById('image-input-gallery').click()" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-blue-500">image</span>
                                <span class="text-sm font-medium">คลังรูปภาพ</span>
                            </button>
                            <button type="button" onclick="document.getElementById('image-input-camera').click()" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-green-500">photo_camera</span>
                                <span class="text-sm font-medium">กล้องถ่ายรูป</span>
                            </button>
                            <div class="h-px bg-slate-100 my-1"></div>
                            <button type="button" onclick="shareLocation()" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-red-500">location_on</span>
                                <span class="text-sm font-medium">แชร์ตำแหน่ง</span>
                            </button>
                        </div>
                    </div>

                    <!-- Hidden file inputs -->
                    <input type="file" id="image-input-gallery" accept="image/*" class="hidden" onchange="handleImageSelect(event)">
                    <input type="file" id="image-input-camera" accept="image/*" capture="environment" class="hidden" onchange="handleImageSelect(event)">

                    
                    <!-- Input Box -->
                    <input type="text" id="message" name="message" placeholder="Type a message..." 
                           class="flex-1 bg-slate-100/70 border-0 rounded-2xl px-5 py-3 sm:py-3.5 text-[14px] sm:text-[15px] text-slate-700 font-medium placeholder-slate-400 focus:ring-2 focus:ring-primary/50 focus:bg-white transition-all outline-none" required autocomplete="off">
                    
                    <!-- Send Button -->
                    <button type="submit" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-primary hover:bg-yellow-400 text-slate-900 flex items-center justify-center shrink-0 transition-all shadow-md hover:shadow-lg active:scale-95 ml-1">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">send</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>

</div> <!-- End #chat-partial-wrapper -->

<?php if ($requestId !== ''): ?>
<script>
    // Ensure we scope variables so multiple partial loads don't conflict
    (function(){
        const reqId = "<?= htmlspecialchars($requestId) ?>";
        const productId = <?= (int)$productId ?>;
        const myId  = <?= $me ?>;
        const msgContainer = document.getElementById('chat-messages');

        // Clear existing interval if any
        if (window.chatPollInterval) {
            clearInterval(window.chatPollInterval);
        }

        function formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
        }

        function buildMsg(m) {
            const isMe = (parseInt(m.sender_id) === myId);
            const timeStr = formatTime(m.sent_at || m.created_at);
            
            let container = document.createElement('div');
            container.className = `flex w-full mb-1 ${isMe ? 'justify-end' : 'justify-start'}`;
            
            let bubble = document.createElement('div');
            bubble.className = `inline-block text-left px-4 sm:px-5 py-2.5 sm:py-3 rounded-2xl relative shadow-sm leading-relaxed text-[14px] sm:text-[15px] font-medium whitespace-pre-wrap break-words \
                ${isMe 
                  ? 'bg-slate-900 text-white rounded-br-sm' 
                  : 'bg-white border border-slate-100 text-slate-800 rounded-bl-sm'}`;
            
            if (m.message && m.message.startsWith('LOCATION:')) {
                const coords = m.message.replace('LOCATION:', '').split(',');
                if (coords.length === 2) {
                    const lat = coords[0];
                    const lng = coords[1];
                    const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
                    
                    let locContent = document.createElement('div');
                    locContent.className = 'flex flex-col gap-2 min-w-[150px]';
                    
                    let head = document.createElement('div');
                    head.className = `flex items-center gap-2 ${isMe ? 'text-primary' : 'text-slate-900'}`;
                    head.innerHTML = `<span class="material-symbols-outlined ${!isMe ? 'text-red-500' : ''}">location_on</span><span class="font-bold">ตำแหน่งที่ตั้ง</span>`;
                    
                    let link = document.createElement('a');
                    link.href = mapsUrl;
                    link.target = '_blank';
                    link.className = `rounded-lg p-2 text-center text-xs transition-colors flex items-center justify-center gap-1 border ${
                        isMe ? 'bg-white/10 hover:bg-white/20 border-white/20 text-white' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600'
                    }`;
                    link.innerHTML = `<span class="material-symbols-outlined text-[16px]">map</span> ดูบน Google Maps`;
                    
                    locContent.appendChild(head);
                    locContent.appendChild(link);
                    bubble.appendChild(locContent);
                } else {
                    let t = document.createElement('span');
                    t.textContent = m.message;
                    bubble.appendChild(t);
                }
            } else if (m.message && m.message.startsWith('IMAGE:')) {
                const filename = m.message.replace('IMAGE:', '').trim();
                const imgUrl = `../uploads/chat/${filename}`;
                
                let img = document.createElement('img');
                img.src = imgUrl;
                img.className = 'max-w-full rounded-lg cursor-pointer hover:opacity-90 transition-opacity';
                img.style.maxHeight = '300px';
                img.onclick = () => window.viewImage(imgUrl);
                
                bubble.appendChild(img);
            } else {
                let t = document.createElement('span');
                t.textContent = m.message;
                bubble.appendChild(t);
            }

            let timeLabel = document.createElement('div');
            timeLabel.className = `text-[10px] uppercase font-bold text-slate-400 mt-1.5 ${isMe ? 'text-right' : 'text-left'}`;
            timeLabel.textContent = timeStr;

            let wrapper = document.createElement('div');
            wrapper.className = `max-w-[85%] sm:max-w-[70%] ${isMe ? 'text-right' : 'text-left'}`;
            
            wrapper.appendChild(bubble);
            wrapper.appendChild(timeLabel);

            if (!isMe) {
                let row = document.createElement('div');
                row.className = 'flex items-end gap-2.5';
                
                let av = document.createElement('div');
                av.className = 'w-8 h-8 rounded-full bg-slate-200 shrink-0 mb-4 overflow-hidden border border-slate-100 hidden sm:block';
                <?php if($otherImg): ?>
                    av.innerHTML = `<img src="<?= htmlspecialchars($otherImg) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/default.png';">`;
                <?php else: ?>
                     av.innerHTML = `<span class="w-full h-full flex items-center justify-center font-bold text-slate-500 text-xs"><?= mb_substr($otherName, 0, 1) ?></span>`;
                <?php endif; ?>
                
                row.appendChild(av);
                row.appendChild(wrapper);
                container.appendChild(row);
            } else {
                container.appendChild(wrapper);
            }

            return container;
        }

        let isScrolledToBottom = true;

        if (msgContainer) {
            msgContainer.addEventListener('scroll', () => {
                const threshold = 50;
                isScrolledToBottom = msgContainer.scrollHeight - msgContainer.clientHeight <= msgContainer.scrollTop + threshold;
            });
        }

        window.loadMessages = function() {
            if (!reqId || productId <= 0 || !msgContainer) return;
            fetch('api/fetch_messages.php?request_id=' + encodeURIComponent(reqId) + '&product_id=' + productId + '&_t=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (Array.isArray(data)) {
                    const wasAtBottom = isScrolledToBottom;
                    
                    msgContainer.innerHTML = '';
                    data.forEach(m => {
                        msgContainer.appendChild(buildMsg(m));
                    });

                    if (wasAtBottom) {
                        msgContainer.scrollTop = msgContainer.scrollHeight;
                    }
                }
            }).catch(err => console.error(err));
        }
        
        // Auto-load once loaded in partial
        window.loadMessages();
        window.chatPollInterval = setInterval(window.loadMessages, 3000); // Polling mechanism

        window.sendMsg = function(e) {
            e.preventDefault();
            const inp = document.getElementById('message');
            const txt = inp.value.trim();
            if(!txt || !reqId) return;

            // Optimistically add message to UI
            const optimisticMsg = {
                sender_id: myId,
                message: txt,
                sent_at: new Date().toISOString()
            };
            msgContainer.appendChild(buildMsg(optimisticMsg));
            msgContainer.scrollTop = msgContainer.scrollHeight;
            
            inp.value = '';
            inp.focus();

            const fd = new FormData();
            fd.append('request_id', reqId);
            fd.append('message', txt);

            fetch('api/api_chat_send.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'ok') {
                    window.loadMessages();
                    msgContainer.scrollTop = msgContainer.scrollHeight;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Error sending message',
                        confirmButtonColor: '#f9e71f'
                    });
                }
            }).catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Network error while sending message.',
                    confirmButtonColor: '#f9e71f'
                });
            });
        }

        window.shareLocation = function() {
            if (!navigator.geolocation) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่รองรับ',
                    text: 'เบราว์เซอร์ของคุณไม่รองรับการแชร์ตำแหน่ง',
                    confirmButtonColor: '#f9e71f'
                });
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const locMsg = `LOCATION:${lat},${lng}`;
                sendLocationMessage(locMsg);
            }, (error) => {
                let msg = "ไม่สามารถดึงตำแหน่งได้";
                if (error.code === 1) msg = "กรุณาอนุญาตการเข้าถึงตำแหน่งเพื่อแชร์";
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: msg,
                    confirmButtonColor: '#f9e71f'
                });
            });
        }

        function sendLocationMessage(txt) {
            if(!txt || !reqId) return;

            const fd = new FormData();
            fd.append('request_id', reqId);
            fd.append('message', txt);

            fetch('api/api_chat_send.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'ok') {
                    window.loadMessages();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Error sending location',
                        confirmButtonColor: '#f9e71f'
                    });
                }
            }).catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Network error while sending location.',
                    confirmButtonColor: '#f9e71f'
                });
            });
        }

        window.initiatePayment = async function() {
            const result = await Swal.fire({
                title: 'ยืนยันการชำระเงิน',
                text: "ยืนยันที่จะสร้างคำสั่งซื้อและชำระด้วย MSUPAY ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f9e71f',
                cancelButtonColor: '#94a3b8' ,
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                color: '#1e293b'
            });

            if (!result.isConfirmed) return;
            
            const { value: pwd } = await Swal.fire({
                title: 'กรุณากรอกรหัสผ่าน',
                input: 'password',
                inputLabel: 'รหัสผ่านของคุณเพื่อยืนยันการชำระเงิน',
                inputPlaceholder: 'Password',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                confirmButtonColor: '#f9e71f',
                confirmButtonText: 'ชำระเงิน',
                showCancelButton: true,
                cancelButtonColor: '#94a3b8',
                cancelButtonText: 'ยกเลิก'
            });

            if (!pwd) {
                if (pwd === "") Swal.fire('Error', 'กรุณากรอกรหัสผ่าน', 'error');
                return;
            }

            try {
                const resp = await fetch('api/api_msupay_pay.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        request_id: reqId,
                        product_id: productId,
                        password: pwd
                    })
                });
                const data = await resp.json();
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'ชำระเงินสำเร็จ!',
                        text: 'ยอดเงินถูกพักไว้ในระบบเรียบร้อยแล้ว',
                        confirmButtonColor: '#22c55e'
                    });
                    
                    // Trigger reload of right panel instead of full page
                    if(window.loadChat) {
                        window.loadChat(reqId, productId);
                    } else {
                        window.location.reload();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'การชำระเงินล้มเหลว',
                        text: data.error || 'เกิดข้อผิดพลาด',
                        confirmButtonColor: '#f9e71f'
                    });
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
            }
        }

        window.confirmReceipt = async function() {
            const result = await Swal.fire({
                title: 'ยืนยันการรับสินค้า?',
                text: "คุณได้รับสินค้าเรียบร้อยแล้วใช่หรือไม่? เงินจะถูกโอนให้ผู้ขายทันทีและไม่สามารถเรียกคืนได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ใช่, ฉันได้รับสินค้าแล้ว',
                cancelButtonText: 'ยกเลิก'
            });

            if (!result.isConfirmed) return;

            try {
                const resp = await fetch('api/release_escrow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        request_id: reqId,
                        product_id: productId
                    })
                });
                const data = await resp.json();
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'เรียบร้อย!',
                        text: 'ยืนยันการรับสินค้าสำเร็จ! เงินถูกโอนให้ผู้ขายแล้ว',
                        confirmButtonColor: '#22c55e'
                    });
                    if(window.loadChat) {
                        window.loadChat(reqId, productId);
                    } else {
                        window.location.reload();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ล้มเหลว',
                        text: data.error || 'เกิดข้อผิดพลาด',
                        confirmButtonColor: '#f9e71f'
                    });
                }
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error',
                    confirmButtonColor: '#f9e71f'
                });
            }
        }

    async function openRatingModal() {
        const orderId = <?= $activeOrder['order_id'] ?? 0 ?>;
        if (!orderId) return;

        const { value: formValues } = await Swal.fire({
            title: 'ให้คะแนนผู้ขาย',
            html: `
                <div class="flex flex-col gap-4 items-center">
                    <div class="flex flex-row-reverse justify-center gap-2 star-rating" style="font-size: 32px; color: #cbd5e1; cursor: pointer;">
                        <input type="radio" id="star5" name="rating" value="5" class="hidden peer/5" />
                        <label for="star5" class="hover:text-yellow-400 peer-checked/5:text-yellow-400 transition-colors" onclick="document.getElementById('star5').checked = true; updateStars(5)">★</label>
                        
                        <input type="radio" id="star4" name="rating" value="4" class="hidden peer/4" />
                        <label for="star4" class="hover:text-yellow-400 peer-checked/4:text-yellow-400 peer-checked/5:text-yellow-400 transition-colors" onclick="document.getElementById('star4').checked = true; updateStars(4)">★</label>
                        
                        <input type="radio" id="star3" name="rating" value="3" class="hidden peer/3" />
                        <label for="star3" class="hover:text-yellow-400 peer-checked/3:text-yellow-400 peer-checked/4:text-yellow-400 peer-checked/5:text-yellow-400 transition-colors" onclick="document.getElementById('star3').checked = true; updateStars(3)">★</label>
                        
                        <input type="radio" id="star2" name="rating" value="2" class="hidden peer/2" />
                        <label for="star2" class="hover:text-yellow-400 peer-checked/2:text-yellow-400 peer-checked/3:text-yellow-400 peer-checked/4:text-yellow-400 peer-checked/5:text-yellow-400 transition-colors" onclick="document.getElementById('star2').checked = true; updateStars(2)">★</label>
                        
                        <input type="radio" id="star1" name="rating" value="1" class="hidden peer/1" checked />
                        <label for="star1" class="text-yellow-400 transition-colors" onclick="document.getElementById('star1').checked = true; updateStars(1)">★</label>
                    </div>
                    <textarea id="swal-comment" class="swal2-textarea w-full text-base box-border" placeholder="เขียนรีวิวผู้ขาย (ไม่บังคับ)"></textarea>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'ส่งคะแนน',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#94a3b8',
            didOpen: () => {
                // Ensure helper function exists in window context for the HTML string
                window.updateStars = function(val) {
                    const stars = document.querySelectorAll('.star-rating label');
                    stars.forEach(s => s.style.color = '#cbd5e1'); // Reset all
                    for (let i = 5; i >= 1; i--) {
                        if (i <= val) {
                            document.querySelector(`label[for="star${i}"]`).style.color = '#facc15';
                        }
                    }
                };
                window.updateStars(5); // init default (always 5)
                document.getElementById('star5').checked = true;
            },
            preConfirm: () => {
                let score = 5;
                for (let i=5; i>=1; i--) {
                    const r = document.getElementById(`star${i}`);
                    if (r && r.checked) {
                        score = parseInt(r.value);
                        break;
                    }
                }
                const comment = document.getElementById('swal-comment').value;
                return { score, comment };
            }
        });

        if (formValues) {
            try {
                const resp = await fetch('api/api_rate_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        product_id: productId,
                        score: formValues.score,
                        comment: formValues.comment
                    })
                });
                const data = await resp.json();
                if (data.ok) {
                    await Swal.fire('สำเร็จ', 'บันทึกคะแนนผู้ขายเรียบร้อยแล้ว', 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
            }
        }
    }

    function toggleActionMenu(e) {
        if(e) e.stopPropagation();
        const menu = document.getElementById('floating-action-menu');
        const isHidden = menu.classList.contains('hidden');
        
        // Close all first if needed (future proofing)
        if (isHidden) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    }

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        const menu = document.getElementById('floating-action-menu');
        const btn = document.getElementById('action-btn');
        if (menu && !menu.classList.contains('hidden')) {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
            }
        }
    });

    function handleImageSelect(event) {
        const menu = document.getElementById('floating-action-menu');
        if(menu) menu.classList.add('hidden');

        const file = event.target.files[0];
        if (!file) return;

        // Reset input so the same file can be selected again
        event.target.value = '';

        uploadImage(file);
    }

        window.uploadImage = async function(file) {
           let formData = new FormData();
        formData.append('image', file);
        formData.append('request_id', '<?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?>');
        formData.append('product_id', <?= $productId ?>);
        formData.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>');

            Swal.fire({
                title: 'กำลังอัปโหลด...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const resp = await fetch('api/api_chat_image.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await resp.json();
                if (data.ok) {
                    Swal.close();
                    window.loadMessages();
                } else {
                    Swal.fire('อัปโหลดล้มเหลว', data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }

        window.viewImage = function(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Chat Image',
                showConfirmButton: false,
                showCloseButton: true,
                width: '90%',
                padding: '0',
                background: 'transparent',
                customClass: {
                    popup: 'bg-transparent shadow-none border-0 overflow-hidden',
                    image: 'max-h-[90vh] object-contain rounded-xl'
                }
            });
        }

        // Prevent body bounce on iOS / Mobile inside chat
        document.addEventListener('touchmove', function (e) {
            if (!e.target.closest('#chat-messages')) {
                e.preventDefault();
            }
        }, { passive: false });

        // Ensure scrolling to bottom on first load and resize
        window.addEventListener('load', () => {
            msgContainer.scrollTop = msgContainer.scrollHeight;
        });
        window.addEventListener('resize', () => {
            if (isScrolledToBottom) {
                msgContainer.scrollTop = msgContainer.scrollHeight;
            }
        });

    })(); // End IIFE
</script>
    <?php endif; // if requestId ?>
</div> <!-- End #chat-partial-wrapper -->

<?php if (!$isPartial): ?>
</body>
</html>
<?php endif; ?>

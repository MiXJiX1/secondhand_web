<?php
require_once __DIR__ . '/controllers/sell_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ลงขายสินค้าใหม่ | Marketplace</title>

<!-- Tailwind & Fonts -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#f9e71f",
                    "background-light": "#f8f8f5",
                    "background-dark": "#23210f",
                },
                fontFamily: {
                    "display": ["Prompt"]
                },
            },
        },
    }
</script>
<style>
  /* Custom Condition Radio Button styling */
  input[type="radio"]:checked + div {
      background-color: rgb(249 231 31 / 0.15); /* primary/15 */
      border-color: #f9e71f; /* primary */
      color: #0f172a; /* slate-900 */
      font-weight: 700;
  }
</style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 font-display text-slate-900 dark:text-slate-100 flex flex-col min-h-screen">

<!-- Navigation Header -->
<?php include __DIR__ . '/../includes/navbar_main.php'; ?>

<main class="flex-grow py-12 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto">
        <!-- Title area -->
        <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">ลงขายสินค้าใหม่</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">ระบุรายละเอียดด้านล่างเพื่อเข้าถึงผู้ซื้อจำนวนมาก</p>

        <?php if($errorMsg): ?>
            <div class="mt-6 p-4 rounded-lg bg-red-50 text-red-600 border border-red-200 font-medium flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= h($errorMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <form method="POST" action="sell.php" enctype="multipart/form-data" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm mt-8 overflow-hidden">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            
            <!-- Section 1: Item Photos -->
            <div class="p-6 sm:p-10">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">รูปภาพสินค้า</h2>
                
                <div class="border-2 border-dashed border-primary/40 dark:border-primary/20 rounded-2xl bg-[#FFFDF0] dark:bg-primary/5 py-16 px-6 flex flex-col items-center justify-center text-center hover:bg-[#FFFAD0] transition-colors cursor-pointer" onclick="document.getElementById('imgInp').click()">
                    
                    <div class="w-16 h-16 bg-primary/30 text-yellow-700 rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <span class="material-symbols-outlined text-4xl">cloud_upload</span>
                    </div>
                    
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg">อัปโหลดรูปภาพ</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md">ลากและวางหรือคลิกเพื่อเพิ่มรูปภาพสินค้า รูปภาพคุณภาพสูงช่วยให้ขายได้เร็วขึ้นถึง 3 เท่า!</p>
                    
                    <button type="button" class="mt-6 bg-primary text-slate-900 font-bold px-8 py-2.5 rounded-lg shadow-sm hover:bg-opacity-90 transition-all">เพิ่มรูปภาพ</button>
                    
                    <input type="file" id="imgInp" name="images[]" multiple style="display:none" onchange="previewImages(this)" accept="image/*">
                </div>

                <!-- Previews -->
                <div id="previews" class="flex flex-wrap gap-4 mt-6 empty:hidden"></div>
            </div>

            <!-- Divider -->
            <hr class="border-slate-100 dark:border-slate-800">

            <!-- Section 2: Item Details -->
            <div class="p-6 sm:p-10">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">รายละเอียดสินค้า</h2>
                
                <!-- Title -->
                <div class="flex flex-col gap-1.5 mb-6">
                    <label class="text-sm font-bold text-slate-700 dark:text-slate-300">ชื่อสินค้า</label>
                    <input type="text" name="product_name" placeholder="เช่น เสื้อแจ็คเก็ตหนังวินเทจ" required class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 outline-none w-full transition-all placeholder:text-slate-400">
                </div>

                <!-- Category & Price -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-bold text-slate-700 dark:text-slate-300">หมวดหมู่</label>
                        <select name="category" required class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 outline-none w-full transition-all cursor-pointer">
                            <option value="">เลือกหมวดหมู่</option>
                            <option value="electronics">อุปกรณ์อิเล็กทรอนิกส์</option>
                            <option value="fashion">แฟชั่น</option>
                            <option value="furniture">เฟอร์นิเจอร์</option>
                            <option value="vehicle">ยานพาหนะ</option>
                            <option value="gameandtoys">เกมและของเล่น</option>
                            <option value="household">ของใช้ในครัวเรือน</option>
                            <option value="sport">อุปกรณ์กีฬา</option>
                            <option value="music">เครื่องดนตรี</option>
                            <option value="others">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-bold text-slate-700 dark:text-slate-300">ราคา (฿)</label>
                        <input type="number" name="product_price" placeholder="0.00" step="0.01" required class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 outline-none w-full transition-all placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Condition -->
                <div class="flex flex-col gap-1.5 mb-6">
                    <label class="text-sm font-bold text-slate-700 dark:text-slate-300">สภาพสินค้า</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="item_condition" value="New" class="peer sr-only">
                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg py-3 text-center text-slate-600 dark:text-slate-400 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                ใหม่
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="item_condition" value="Like New" class="peer sr-only">
                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg py-3 text-center text-slate-600 dark:text-slate-400 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                เหมือนใหม่
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="item_condition" value="Used" class="peer sr-only" checked>
                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg py-3 text-center text-slate-600 dark:text-slate-400 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                มือสอง
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-1.5 mb-6">
                    <label class="text-sm font-bold text-slate-700 dark:text-slate-300">รายละเอียดสินค้า</label>
                    <textarea name="description" placeholder="อธิบายคุณสมบัติ จุดเด่น ตำหนิ และขนาดของสินค้า..." required class="h-32 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 outline-none w-full transition-all placeholder:text-slate-400 resize-y"></textarea>
                </div>

                <!-- Location Area Selection -->
                <div class="flex flex-col gap-1.5 mb-6">
                    <label class="text-sm font-bold text-slate-700 dark:text-slate-300">พื้นที่ (Area)</label>
                    <select name="location_area" required class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 outline-none w-full transition-all">
                        <option value="" disabled selected>เลือกพื้นที่สินค้า</option>
                        <option value="หน้ามอ">หน้ามอ</option>
                        <option value="หลังมอ">หลังมอ</option>
                        <option value="ขามเรียง">ขามเรียง</option>
                        <option value="ในเมือง">ในเมือง</option>
                        <option value="กันทรวิชัย">กันทรวิชัย</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>

                <!-- Location Map -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700 dark:text-slate-300">สถานที่นัดรับ (ระบุบนแผนที่)</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="mapSearchInput" placeholder="ค้นหา จังหวัด, อำเภอ, หอพัก..." class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary/50 outline-none flex-1 placeholder:text-slate-400 text-sm">
                        <button type="button" id="mapSearchBtn" class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold px-4 py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-sm border border-slate-200 dark:border-slate-700 border-b-2">ค้นหา</button>
                    </div>
                    <div id="map" class="w-full h-[300px] rounded-lg border border-slate-200 dark:border-slate-700 z-10 sticky shadow-inner"></div>
                    <small class="text-slate-500 mt-1">คลิกบนแผนที่เพื่อปักหมุด</small>
                    <input type="hidden" name="location_text" id="locationInput" value="">
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-6 sm:px-10 py-6 flex items-center justify-end gap-4">
                <a href="../index.php" class="text-slate-500 dark:text-slate-400 font-bold hover:text-slate-700 dark:hover:text-slate-200 px-4 py-2 transition-colors">ยกเลิก</a>
                <button type="submit" class="bg-primary text-slate-900 font-bold px-10 py-3 rounded-lg hover:shadow-md transition-all">ลงขายทันที</button>
            </div>
        </form>

        <div class="flex items-center justify-center gap-2 mt-8 text-sm text-slate-500 dark:text-slate-400 mb-12">
            <span class="material-symbols-outlined text-base">verified_user</span> 
            ประกาศของคุณจะถูกตรวจสอบตามแนวทางปฏิบัติของชุมชนก่อนที่จะเผยแพร่
        </div>
        
        <div class="text-center text-sm text-slate-400 mb-8 pb-8">
            © 2024 Marketplace Inc. สงวนลิขสิทธิ์
        </div>
    </div>
</main>

<script>
function previewImages(input) {
    const box = document.getElementById('previews');
    box.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        // Change text/icon styling subtly to indicate files selected
        const ctn = input.parentElement;
        ctn.classList.remove('bg-[#FFFDF0]');
        ctn.classList.add('bg-white');

        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-slate-200 shadow-sm';
                box.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}

// Map Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Default to Mahasarakham University (MSU)
    let initialLat = 16.2458;
    let initialLng = 103.2505;

    let map = L.map('map').setView([initialLat, initialLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker;
    let locInput = document.getElementById('locationInput');

    map.on('click', function(e) {
        let lat = e.latlng.lat;
        let lng = e.latlng.lng;

        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([lat, lng]).addTo(map);
        locInput.value = JSON.stringify({ lat: lat, lng: lng });
    });

    // Search Feature
    document.getElementById('mapSearchBtn').addEventListener('click', function() {
        let query = document.getElementById('mapSearchInput').value;
        if (!query) return;

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    let lat = data[0].lat;
                    let lng = data[0].lon;

                    map.setView([lat, lng], 16);

                    if (marker) map.removeLayer(marker);
                    marker = L.marker([lat, lng]).addTo(map);
                    locInput.value = JSON.stringify({ lat: lat, lng: lng });
                } else {
                    alert('ไม่พบสถานที่ที่คุณเลือก');
                }
            })
            .catch(err => {
                console.error('Error searching location:', err);
            });
    });

    document.getElementById('mapSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('mapSearchBtn').click();
        }
    });
});
</script>
</body>
</html>

<?php
require_once __DIR__ . '/controllers/edit_product_controller.php';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขสินค้า | Secondhand Market</title>
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/php-edit_product.css">
</head>
<body class="animate-up">

<?php 
$navTitle = 'แก้ไขสินค้า';
$backLink = 'product_detail.php?id=' . (int)$prod['product_id'];
$backText = 'กลับไปหน้ารายละเอียด';
include __DIR__ . '/../includes/navbar_back.php'; 
?>

<div class="wrap">
    <div class="card">
        <?php if($successMsg): ?>
            <div class="alert alert-success shadow-sm border-0 mb-4"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if($errorMsg): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="grid">
                <!-- Column 1: Basic Info -->
                <div class="column">
                    <label>ชื่อสินค้า</label>
                    <input type="text" name="product_name" value="<?= htmlspecialchars($prod['product_name']) ?>" required placeholder="ระบุชื่อสินค้า">

                    <label>ราคา (บาท)</label>
                    <input type="number" name="product_price" step="0.01" value="<?= htmlspecialchars((string)$prod['product_price']) ?>" min="0" required placeholder="0.00">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>หมวดหมู่</label>
                            <select name="category" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach($categories as $val=>$label): ?>
                                    <option value="<?= $val ?>" <?= ($prod['category'] === $val ? 'selected' : '') ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>สถานะสินค้า</label>
                            <select name="status">
                                <option value="active" <?= $prod['status']==='active'?'selected':'' ?>>เปิดขาย (Active)</option>
                                <option value="sold" <?= $prod['status']==='sold'?'selected':'' ?>>ขายแล้ว (Sold)</option>
                                <option value="hidden" <?= $prod['status']==='hidden'?'selected':'' ?>>ซ่อนสินค้า (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <label>รายละเอียดสินค้า</label>
                    <textarea name="description" placeholder="ระบุรายละเอียดสินค้า..."><?= htmlspecialchars($prod['description']) ?></textarea>

                    <label style="margin-top: 1rem;">พื้นที่ (Area)</label>
                    <select name="location_area" required style="width: 100%; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--bg-input); margin-bottom: 1rem;">
                        <option value="" disabled>เลือกพื้นที่สินค้า</option>
                        <option value="หน้ามอ" <?= ($prod['location_name'] ?? '') === 'หน้ามอ' ? 'selected' : '' ?>>หน้ามอ (Front of MSU)</option>
                        <option value="หลังมอ" <?= ($prod['location_name'] ?? '') === 'หลังมอ' ? 'selected' : '' ?>>หลังมอ (Back of MSU)</option>
                        <option value="ขามเรียง" <?= ($prod['location_name'] ?? '') === 'ขามเรียง' ? 'selected' : '' ?>>ขามเรียง (Kham Riang)</option>
                        <option value="ในเมือง" <?= ($prod['location_name'] ?? '') === 'ในเมือง' ? 'selected' : '' ?>>ในเมือง (City Center)</option>
                        <option value="กันทรวิชัย" <?= ($prod['location_name'] ?? '') === 'กันทรวิชัย' ? 'selected' : '' ?>>กันทรวิชัย (Kantarawichai)</option>
                        <option value="อื่นๆ" <?= ($prod['location_name'] ?? '') === 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ (Other)</option>
                    </select>

                    <label>ปักหมุดตำแหน่งที่นัดรับ</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" id="mapSearchInput" placeholder="ค้นหา จังหวัด, อำเภอ, หอพัก..." style="flex:1;">
                        <button type="button" id="mapSearchBtn" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">ค้นหา</button>
                    </div>
                    <div id="map" style="width: 100%; height: 250px; border-radius: var(--radius-sm); border: 1px solid var(--border); margin-bottom: 0.5rem; z-index: 1;"></div>
                    <small style="color:var(--text-muted); display:block; margin-bottom:1rem;">คลิกบนแผนที่เพื่อเปลี่ยนตำแหน่งนัดรับ</small>
                    <input type="hidden" name="location_text" id="locationInput" value="<?= htmlspecialchars($prod['location'] ?? '') ?>">
                </div>

                <!-- Column 2: Images -->
                <div class="column">
                    <label>รูปภาพปัจจุบัน</label>
                    <div class="current-images">
                        <?php
                        $imgs = allImagesFromField($prod['product_image']);
                        if (empty($imgs)): ?>
                            <div class="text-muted small">ไม่มีรูปภาพสินค้า</div>
                        <?php else: 
                            foreach ($imgs as $fn):
                                $fn = basename($fn);
                                $src = $baseUrl . '/uploads/' . rawurlencode($fn);
                        ?>
                            <div class="img-container">
                                <img src="<?= htmlspecialchars($src) ?>" alt="Product image" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <label>อัปโหลดรูปภาพใหม่</label>
                    <div class="fileBox" onclick="document.getElementById('imgInp').click()">
                        <input type="file" id="imgInp" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none" onchange="previewImages(this)">
                        <div style="font-size:2rem">📸</div>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem">คลิกเพื่อเพิ่มรูปภาพใหม่</div>
                        <div class="previews" id="previews"></div>
                    </div>

                    <label class="replace-toggle">
                        <input type="checkbox" name="replace_images" value="1">
                        <span>แทนที่รูปเดิมทั้งหมด (ลบรูปเก่า)</span>
                    </label>
                    <div class="small text-muted mb-4">* หากไม่ติ๊ก ระบบจะเพิ่มรูปภาพใหม่ต่อจากรูปเดิม</div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-warning py-3 fw-bold shadow-sm" style="flex:2">บันทึกการแก้ไข</button>
                <a href="<?= $baseUrl ?>/product/<?= (int)$prod['product_id'] ?>" class="btn btn-secondary py-3 d-flex align-items-center justify-content-center" style="flex:1">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImages(input) {
    const box = document.getElementById('previews');
    box.innerHTML = '';
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'thumb';
                box.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}

// Map Initialization
document.addEventListener('DOMContentLoaded', function() {
    let locInput = document.getElementById('locationInput');
    let initialLat = 16.2458; // MSU Default
    let initialLng = 103.2505;
    let hasSavedLocation = false;

    try {
        if (locInput.value) {
            let savedLoc = JSON.parse(locInput.value);
            if (savedLoc.lat && savedLoc.lng) {
                initialLat = parseFloat(savedLoc.lat);
                initialLng = parseFloat(savedLoc.lng);
                hasSavedLocation = true;
            }
        }
    } catch (e) {
        console.warn("Invalid location logic", e);
    }

    let map = L.map('map').setView([initialLat, initialLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker;
    if (hasSavedLocation) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

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

        // Use Nominatim API for geocoding
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
                    alert('ไม่พบสถานที่ที่คุณค้นหา กรุณาลองใหม่อีกครั้ง');
                }
            })
            .catch(err => {
                console.error('Error searching location:', err);
                alert('เกิดข้อผิดพลาดในการค้นหาสถานที่ระบบเครือข่าย');
            });
    });

    // Allow Enter key to trigger search
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

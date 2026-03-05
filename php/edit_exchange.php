<?php
require_once __DIR__ . '/controllers/edit_exchange_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isCreate ? 'ลงประกาศแลกเปลี่ยน' : 'แก้ไขประกาศแลกเปลี่ยน' ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Prompt', sans-serif; }
</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen pb-20">

<?php include __DIR__ . '/../includes/navbar_main.php'; ?>

<div class="max-w-4xl mx-auto px-4 mt-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= $isCreate ? 'ลงประกาศแลกเปลี่ยนใหม่' : 'แก้ไขประกาศแลกเปลี่ยน' ?></h1>
            <p class="text-slate-500 mt-1">กรอกข้อมูลสิ่งของที่คุณต้องการนำมาแลกเปลี่ยน</p>
        </div>
        <a href="exchange.php" class="text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            กลับหน้าหลัก
        </a>
    </div>

    <?php if ($successMsg): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <?= $successMsg ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <?= $errorMsg ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 shadow-sm rounded-3xl overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="p-8 space-y-8">
            <!-- Section 1: Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-2">ชื่อสินค้าที่นำมาแลก <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($prod['title']) ?>" required
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all placeholder:text-slate-400"
                           placeholder="เช่น iPhone 13 Pro 128GB สภาพดี">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">หมวดหมู่ <span class="text-red-500">*</span></label>
                    <select name="category" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all">
                        <?php foreach($CATS as $c): ?>
                            <option value="<?= $c ?>" <?= $prod['category']===$c ? 'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">สิ่งที่ต้องการแลก <span class="text-red-500">*</span></label>
                    <input type="text" name="want_text" value="<?= htmlspecialchars($prod['want_text']) ?>" required
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all placeholder:text-slate-400"
                           placeholder="เช่น iPad Air 5 หรือส่วนต่างเงินสด">
                </div>
            </div>

            <!-- Section 2: Details -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">สภาพสินค้า / ตำหนิ</label>
                <input type="text" name="condition_text" value="<?= htmlspecialchars($prod['condition_text']) ?>"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all placeholder:text-slate-400"
                       placeholder="เช่น ใช้งานมา 1 ปี, มีรอยขีดข่วนเล็กน้อย">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">รายละเอียดเพิ่มเติม</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all placeholder:text-slate-400"
                          placeholder="อธิบายเพิ่มเติมเกี่ยวกับสินค้าของคุณ..."><?= htmlspecialchars($prod['description']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">สถานที่นัดรับ</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($prod['location']) ?>"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all placeholder:text-slate-400"
                           placeholder="เช่น หน้า มมส., หอพักแถวหลังมอ">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">สถานะประกาศ</label>
                    <select name="status"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all">
                        <option value="available" <?= $prod['status']==='available' ? 'selected':'' ?>>พร้อมแลกเปลี่ยน (Available)</option>
                        <option value="pending" <?= $prod['status']==='pending' ? 'selected':'' ?>>กำลังเจรจา (Pending)</option>
                        <option value="swapped" <?= $prod['status']==='swapped' ? 'selected':'' ?>>แลกเปลี่ยนสำเร็จแล้ว (Swapped)</option>
                        <option value="cancelled" <?= $prod['status']==='cancelled' ? 'selected':'' ?>>ยกเลิก (Cancelled)</option>
                    </select>
                </div>
            </div>

            <!-- Section 3: Images -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-4">รูปภาพสินค้า</label>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-4 mb-6">
                    <?php 
                    $imgs = allImagesFromField($prod['images']);
                    foreach ($imgs as $img): ?>
                        <div class="relative group aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
                            <img src="../uploads/<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover">
                            <label class="absolute inset-0 bg-red-500/80 items-center justify-center flex opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                <input type="checkbox" name="remove_images[]" value="<?= htmlspecialchars($img) ?>" class="hidden">
                                <span class="text-white font-bold text-xs">ลบคลิกที่นี่</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    
                    <label class="aspect-square rounded-2xl border-2 border-dashed border-slate-300 hover:border-primary hover:bg-yellow-50 flex flex-col items-center justify-center cursor-pointer transition-all gap-2 text-slate-400 hover:text-primary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span class="text-xs font-bold">เพิ่มรูป</span>
                        <input type="file" name="images[]" multiple class="hidden" accept="image/*">
                    </label>
                </div>
                <p class="text-xs text-slate-400">เลือกรูปภาพเพื่อลบออก หรือคลิก (+) เพื่ออัปโหลดรูปใหม่ (ขนาดแนะนำ 800x600 px)</p>
            </div>
        </div>

        <div class="p-8 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <p class="text-xs text-slate-500 max-w-xs text-center sm:text-left">การบันทึกข้อมูลจะอัปเดตประกาศของคุณทันที ผู้ใช้อื่นจะเห็นข้อมูลที่แก้ไขแล้ว</p>
            <div class="flex gap-3 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-none px-8 py-3.5 bg-slate-900 text-white font-bold rounded-2xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-200 active:scale-95">
                    <?= $isCreate ? 'ลงประกาศเลย' : 'บันทึกการเปลี่ยนแปลง' ?>
                </button>
            </div>
        </div>
    </form>
</div>

</body>
</html>

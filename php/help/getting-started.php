<?php
// help/getting-started.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../config/database.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$userId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>ศูนย์ความช่วยเหลือ | Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Alpine.js for FAQ accordion -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#f9e71f" },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Navbar -->
    <div class="bg-white border-b border-slate-100 relative z-50">
        <?php 
        $current_page = 'help';
        include __DIR__ . '/../../includes/navbar_main.php'; 
        ?>
    </div>

    <!-- Hero Section -->
    <section class="bg-[#fefce8] pt-16 pb-20 px-4 sm:px-6 relative overflow-hidden">
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <h1 class="text-4xl sm:text-5xl font-black text-slate-900 mb-4 tracking-tight">เราจะช่วยอะไรคุณได้บ้างในวันนี้?</h1>
            <p class="text-slate-500 font-medium mb-10 max-w-2xl mx-auto text-[15px] sm:text-[16px]">
                ค้นหาคำตอบสำหรับคำถามของคุณเกี่ยวกับการซื้อ การขาย และการใช้งานอย่างปลอดภัยในชุมชนของเรา
            </p>
            
            <div class="bg-white p-2 rounded-2xl shadow-lg border border-slate-100 flex items-center max-w-2xl mx-auto transition-transform focus-within:scale-[1.02] duration-300">
                <span class="material-symbols-outlined text-slate-400 ml-3 mr-2">search</span>
                <input type="text" placeholder="ค้นหาความช่วยเหลือหรือหัวข้อ..." class="flex-1 bg-transparent border-none outline-none text-slate-700 font-medium placeholder:text-slate-400 placeholder:font-normal py-3 w-full">
                <button class="bg-primary hover:bg-yellow-400 text-slate-900 font-bold px-8 py-3 rounded-xl transition-colors shadow-sm ml-2">
                    ค้นหา
                </button>
            </div>
        </div>
    </section>

    <!-- Popular Categories -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 -mt-8 relative z-20 w-full mb-16">
        <div class="flex items-center gap-3 mb-6 ml-2">
            <div class="w-6 h-1 bg-primary rounded-full"></div>
            <h2 class="text-xl font-bold text-slate-900">หัวข้อยอดนิยม</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <a href="#" class="bg-white p-6 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-md transition-all group flex flex-col h-full">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">rocket_launch</span>
                </div>
                <h3 class="font-bold text-slate-900 text-[15px] mb-2">เริ่มต้นใช้งาน</h3>
                <p class="text-[13px] text-slate-500 font-medium mt-auto leading-relaxed">การตั้งค่าบัญชีและขั้นตอนแรกของคุณ</p>
            </a>

            <a href="#" class="bg-white p-6 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-md transition-all group flex flex-col h-full">
                <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">sell</span>
                </div>
                <h3 class="font-bold text-slate-900 text-[15px] mb-2">การซื้อและขาย</h3>
                <p class="text-[13px] text-slate-500 font-medium mt-auto leading-relaxed">เคล็ดลับสำหรับการซื้อขายและการลงประกาศที่ราบรื่น</p>
            </a>

            <a href="#" class="bg-white p-6 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-md transition-all group flex flex-col h-full">
                <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                </div>
                <h3 class="font-bold text-slate-900 text-[15px] mb-2">การชำระเงินและเครดิต</h3>
                <p class="text-[13px] text-slate-500 font-medium mt-auto leading-relaxed">ยอดเงินในวอลเล็ตและวิธีการถอนเงิน</p>
            </a>

            <a href="#" class="bg-white p-6 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-md transition-all group flex flex-col h-full">
                <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">security</span>
                </div>
                <h3 class="font-bold text-slate-900 text-[15px] mb-2">ความปลอดภัย</h3>
                <p class="text-[13px] text-slate-500 font-medium mt-auto leading-relaxed">การปกป้องตัวคุณเองและข้อมูลของคุณ</p>
            </a>

            <a href="#" class="bg-white p-6 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-md transition-all group flex flex-col h-full">
                <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">swap_horiz</span>
                </div>
                <h3 class="font-bold text-slate-900 text-[15px] mb-2">การแลกเปลี่ยนสินค้า</h3>
                <p class="text-[13px] text-slate-500 font-medium mt-auto leading-relaxed">นโยบายการคืนสินค้าและการแลกเปลี่ยน</p>
            </a>

        </div>
    </section>

    <!-- FAQ Section -->
    <section class="max-w-3xl mx-auto px-4 sm:px-6 w-full mb-20 text-center">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">คำถามที่พบบ่อย (FAQ)</h2>

        <div class="space-y-3 text-left">
            
            <div x-data="{ expanded: true }" class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300">
                <button @click="expanded = ! expanded" class="w-full flex items-center justify-between p-5 focus:outline-none">
                    <span class="font-bold text-[15px] text-slate-900">ฉันจะลงขายสินค้าได้อย่างไร?</span>
                    <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse>
                    <div class="px-5 pb-5 text-[14px] text-slate-500 font-medium leading-relaxed">
                        ในการลงขายสินค้า ให้คลิกปุ่ม "ลงขายสินค้า" ในแถบนำทาง คุณจะต้องอัปโหลดรูปภาพที่ชัดเจน เขียนรายละเอียดสินค้า ตั้งราคา และระบุพื้นที่นัดรับ โดยประกาศส่วนใหญ่จะแสดงผลเกือบจะทันที!
                    </div>
                </div>
            </div>

            <div x-data="{ expanded: false }" class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300">
                <button @click="expanded = ! expanded" class="w-full flex items-center justify-between p-5 focus:outline-none">
                    <span class="font-bold text-[15px] text-slate-900">การชำระเงินของฉันปลอดภัยหรือไม่?</span>
                    <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse>
                    <div class="px-5 pb-5 text-[14px] text-slate-500 font-medium leading-relaxed">
                        ใช่ เราใช้การเข้ารหัสมาตรฐานอุตสาหกรรมเพื่อปกป้องรายละเอียดการชำระเงินของคุณ นอกจากนี้เรายังมีระบบตรวจสอบการชำระเงินเพื่อให้แน่ใจว่าทั้งผู้ซื้อและผู้ขายได้รับความโปร่งใสที่สุด
                    </div>
                </div>
            </div>

            <div x-data="{ expanded: false }" class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300">
                <button @click="expanded = ! expanded" class="w-full flex items-center justify-between p-5 focus:outline-none">
                    <span class="font-bold text-[15px] text-slate-900">จะทำอย่างไรถ้าไม่ได้รับสินค้าตามที่ตกลง?</span>
                    <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse>
                    <div class="px-5 pb-5 text-[14px] text-slate-500 font-medium leading-relaxed">
                        หากคุณไม่ได้รับสินค้าภายในกรอบเวลาที่กำหนด หรือสินค้าไม่ตรงตามที่ตกลง คุณสามารถเปิดข้อโต้แย้งผ่านหน้า "คำสั่งซื้อของฉัน" ได้ ทีมสนับสนุนของเราจะตรวจสอบข้อมูลและช่วยเหลือในการคืนเงินหากตรวจสอบพบปัญหาจริง
                    </div>
                </div>
            </div>

            <div x-data="{ expanded: false }" class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300">
                <button @click="expanded = ! expanded" class="w-full flex items-center justify-between p-5 focus:outline-none">
                    <span class="font-bold text-[15px] text-slate-900">ฉันสามารถคืนสินค้ามือสองได้หรือไม่?</span>
                    <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse>
                    <div class="px-5 pb-5 text-[14px] text-slate-500 font-medium leading-relaxed">
                        โดยทั่วไปแล้วการคืนสินค้าจะทำได้ต่อเมื่อสินค้าไม่เป็นไปตามที่อธิบายไว้ในประกาศอย่างชัดเจน อย่างไรก็ตาม ผู้ขายบางรายอาจมีนโยบายการคืนสินค้าที่ยืดหยุ่นกว่านั้น ซึ่งจะระบุไว้ในรายละเอียดสินค้าแต่ละรายการ
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Contact Support CTA -->
    <section class="max-w-4xl mx-auto px-4 sm:px-6 w-full mb-20">
        <div class="bg-[#fefce8] rounded-3xl p-10 sm:p-14 text-center">
            <h2 class="text-2xl font-bold text-slate-900 mb-2">ยังต้องการความช่วยเหลืออยู่ใช่ไหม?</h2>
            <p class="text-slate-600 font-medium text-[15px] mb-8">ทีมสนับสนุนของเราพร้อมช่วยเหลือคุณตลอด 24 ชั่วโมงในทุกปัญหาที่คุณเจอ</p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <button class="w-full sm:w-auto flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold px-8 py-3.5 rounded-xl transition-colors shadow-md">
                    <span class="material-symbols-outlined text-[20px]">chat_bubble</span>
                    แชทสด (Live Chat)
                </button>
                <button class="w-full sm:w-auto flex items-center justify-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-900 font-bold px-8 py-3.5 rounded-xl transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    ส่งอีเมลถึงเรา
                </button>
            </div>
        </div>
    </section>

    <div class="mt-auto">
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

</body>
</html>

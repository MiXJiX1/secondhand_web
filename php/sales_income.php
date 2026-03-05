<?php
require_once __DIR__ . '/controllers/sales_income_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายรับจากการขาย | Midnight Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col">

<!-- Header -->
<div class="bg-slate-900 sticky top-0 z-50 text-white shadow-md">
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <button onclick="history.length > 1 ? history.back() : location.href='profile.php'" class="w-10 h-10 rounded-full flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors text-white">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </button>
            <h1 class="text-lg font-bold tracking-tight">รายรับจากการขาย</h1>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="flex-1 w-full max-w-7xl mx-auto px-6 py-8">
    
    <!-- Filter Section -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm mb-8">
        <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">calendar_month</span> ค้นหาตามช่วงเวลา
        </h3>
        <form method="get" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">จากวันที่</label>
                <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
            </div>
            <div class="w-full md:w-auto flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ถึงวันที่</label>
                <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
            </div>
            <div class="w-full md:w-auto flex gap-3">
                <button type="submit" class="flex-1 md:flex-none bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md active:scale-[0.98] flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">search</span> แสดงผล
                </button>
                <?php if (!empty($start) || !empty($end)): ?>
                <a href="sales_income.php" class="flex-1 md:flex-none bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2 text-center">
                    ล้างตัวกรอง
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 bg-primary/10 w-32 h-32 rounded-full blur-2xl group-hover:bg-primary/20 transition-all duration-500"></div>
            <div class="w-16 h-16 bg-primary/20 text-yellow-600 rounded-2xl flex items-center justify-center flex-shrink-0 relative z-10">
                <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
            </div>
            <div class="relative z-10">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">รวมทั้งช่วงที่เลือก</p>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">฿<?= number_format((float)$sum['total_sum'], 2) ?></h3>
            </div>
        </div>

        <!-- Today -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 bg-green-500/10 w-32 h-32 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all duration-500"></div>
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center flex-shrink-0 relative z-10">
                <span class="material-symbols-outlined text-3xl">today</span>
            </div>
            <div class="relative z-10">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">วันนี้</p>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">฿<?= number_format((float)$sum['today_sum'], 2) ?></h3>
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 bg-blue-500/10 w-32 h-32 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center flex-shrink-0 relative z-10">
                <span class="material-symbols-outlined text-3xl">date_range</span>
            </div>
            <div class="relative z-10">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">เดือนนี้</p>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">฿<?= number_format((float)$sum['month_sum'], 2) ?></h3>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 tracking-tight">
                <span class="material-symbols-outlined text-slate-400">receipt_long</span> รายละเอียดคำสั่งซื้อ
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">รายการสินค้า</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">ผู้ซื้อ</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">เลขออเดอร์</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">วันที่ชำระ</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap text-right">ยอดชำระ (฿)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($rows): foreach($rows as $r):
                        $img = !empty($r['product_image']) ? ('../uploads/'.basename($r['product_image'])) : '../assets/no-image.png';
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/default.png';">
                                </div>
                                <span class="font-bold text-sm text-slate-900 line-clamp-1 max-w-[250px]"><?= htmlspecialchars($r['product_name'] ?: '-') ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs text-slate-500 font-bold overflow-hidden">
                                    <span class="material-symbols-outlined text-[14px]">person</span>
                                </div>
                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($r['buyer_name'] ?: '-') ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded border border-slate-200 block text-center">#<?= htmlspecialchars($r['order_no']) ?></code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($r['paid_at']): ?>
                                <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars(date('d/m/Y', strtotime($r['paid_at']))) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars(date('H:i', strtotime($r['paid_at']))) ?> น.</p>
                            <?php else: ?>
                                <span class="text-sm text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <span class="font-bold text-slate-900 bg-green-100/50 text-green-700 px-3 py-1.5 rounded-lg text-sm whitespace-nowrap">+ ฿<?= number_format((float)$r['amount'], 2) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="py-12">
                            <div class="flex flex-col items-center justify-center text-center">
                                <span class="material-symbols-outlined text-6xl text-slate-200 mb-4">search_off</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">ไม่พบรายรับในช่วงเวลาที่เลือก</h3>
                                <p class="text-sm text-slate-500">ลองเปลี่ยนช่วงวันที่เพื่อค้นหาใหม่อีกครั้ง</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>

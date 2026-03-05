<?php
require_once __DIR__ . '/controllers/admin_stats_controller.php';

$pageTitle = 'สถิติระบบ';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">monitoring</span> 
            สถิติระบบ
        </h3>
        <p class="text-sm text-slate-500">ภาพรวมข้อมูลผู้ใช้งาน สินค้า และการทำธุรกรรม (30 วันล่าสุด)</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]">group</span>
        </div>
        <div>
            <div class="text-sm text-slate-500 font-medium mb-1">ผู้ใช้ทั้งหมด</div>
            <div class="text-2xl font-bold text-slate-900"><?= number_format($totUsers) ?></div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]">inventory_2</span>
        </div>
        <div>
            <div class="text-sm text-slate-500 font-medium mb-1">สินค้าทั้งหมด</div>
            <div class="text-2xl font-bold text-slate-900"><?= number_format($totProducts) ?></div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]">account_balance_wallet</span>
        </div>
        <div>
            <div class="text-sm text-slate-500 font-medium mb-1">ยอดเติมสะสม (บาท)</div>
            <div class="text-2xl font-bold text-slate-900"><?= number_format($totTopup, 2) ?></div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]">payments</span>
        </div>
        <div>
            <div class="text-sm text-slate-500 font-medium mb-1">ยอดถอนสะสม (บาท)</div>
            <div class="text-2xl font-bold text-slate-900"><?= number_format($totWithdraw, 2) ?></div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- User Line Chart -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h6 class="text-base font-bold text-slate-900">ผู้ใช้ใหม่ 30 วันล่าสุด</h6>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="userLine"></canvas>
        </div>
    </div>
    
    <!-- Withdraw Donut Chart -->
    <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h6 class="text-base font-bold text-slate-900">สถานะการถอน</h6>
        </div>
        <div class="relative h-72 w-full flex items-center justify-center">
            <canvas id="withdrawDonut"></canvas>
        </div>
    </div>

    <!-- Money Bar Chart -->
    <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h6 class="text-base font-bold text-slate-900">ยอดเติม vs ถอน (บาท) • 30 วันล่าสุด</h6>
        </div>
        <div class="relative h-80 w-full">
            <canvas id="moneyBar"></canvas>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Tailwind styling variables for charts
const fontFam = "'Prompt', sans-serif";
const gridColor = '#f1f5f9';
const textColor = '#64748b';

Chart.defaults.font.family = fontFam;
Chart.defaults.color = textColor;
Chart.defaults.scale.grid.color = gridColor;

const labels = <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE) ?>;

// Line: new users (แกน Y เริ่ม 0 และแสดงเฉพาะจำนวนเต็ม)
const userDaily = <?= json_encode(array_values($usersDaily), JSON_UNESCAPED_UNICODE) ?>;
new Chart(document.getElementById('userLine'), {
  type: 'line',
  data: { 
      labels, 
      datasets: [{ 
          label: 'ผู้ใช้ใหม่', 
          data: userDaily, 
          tension: 0.4,
          borderColor: '#4f46e5', // indigo-600
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#4f46e5',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: true
      }] 
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            titleFont: { size: 13, family: fontFam },
            bodyFont: { size: 14, family: fontFam, weight: 'bold' },
            displayColors: false,
            cornerRadius: 8
        }
    },
    scales: {
      x: { grid: { display: false } },
      y: {
        beginAtZero: true,
        border: { dash: [4, 4] },
        ticks: {
          precision: 0,           // บังคับเลขจำนวนเต็ม
          callback: (v) => Number.isInteger(v) ? v : '' // ซ่อนเลขทศนิยม
        }
      }
    }
  }
});

// Doughnut: withdraw status
const wdLabels = <?= json_encode(array_keys($wdStatusMap), JSON_UNESCAPED_UNICODE) ?>;
const wdData   = <?= json_encode(array_values($wdStatusMap), JSON_UNESCAPED_UNICODE) ?>;

// Map status to English and colors
const statusColors = {
    'requested': '#eab308', // yellow-500
    'approved': '#3b82f6',  // blue-500
    'paid': '#22c55e',      // green-500
    'rejected': '#ef4444'   // red-500
};
const bgColors = wdLabels.map(l => statusColors[l] || '#94a3b8');

new Chart(document.getElementById('withdrawDonut'), {
  type: 'doughnut',
  data: { 
      labels: wdLabels, 
      datasets: [{ 
          data: wdData,
          backgroundColor: bgColors,
          borderWidth: 0,
          hoverOffset: 4
      }] 
  },
  options: { 
      responsive: true,
      maintainAspectRatio: false, 
      cutout: '75%',
      plugins: { 
          legend: { 
              position: 'bottom',
              labels: { padding: 20, usePointStyle: true, boxWidth: 8 }
          },
          tooltip: {
              backgroundColor: '#1e293b',
              padding: 12,
              titleFont: { size: 13, family: fontFam },
              bodyFont: { size: 14, family: fontFam, weight: 'bold' },
              cornerRadius: 8
          }
      } 
  }
});

// Bar: topup vs withdraw
const topupDaily    = <?= json_encode(array_values($topupDaily), JSON_UNESCAPED_UNICODE) ?>;
const withdrawDaily = <?= json_encode(array_values($withdrawDaily), JSON_UNESCAPED_UNICODE) ?>;
new Chart(document.getElementById('moneyBar'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { 
          label: 'เติมเครดิต', 
          data: topupDaily,
          backgroundColor: '#22c55e', // green-500
          borderRadius: 4,
          borderSkipped: false
      },
      { 
          label: 'ถอนเครดิต', 
          data: withdrawDaily,
          backgroundColor: '#ef4444', // red-500
          borderRadius: 4,
          borderSkipped: false
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { 
        x: { grid: { display: false } },
        y: { 
            beginAtZero: true,
            border: { dash: [4, 4] }
        } 
    },
    plugins: { 
        legend: { 
            position: 'top',
            align: 'end',
            labels: { usePointStyle: true, boxWidth: 8 }
        },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            titleFont: { size: 13, family: fontFam },
            bodyFont: { size: 14, family: fontFam, weight: 'bold' },
            cornerRadius: 8
        }
    }
  }
});
</script>

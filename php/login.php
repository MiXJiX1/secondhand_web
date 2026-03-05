<?php
// login.php — หน้าเข้าสู่ระบบ (หน้าผู้ใช้)
require_once __DIR__ . '/controllers/login_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>เข้าสู่ระบบ | Midnight Premium</title>
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
                        customBg: "#f5f5f5" // Light grey background like the mockup
                    },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
</head>
<body class="bg-customBg font-sans text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">

    <!-- Container -->
    <div class="w-full max-w-md flex flex-col items-center">
        
        <!-- Logo and Heading -->
        <div class="mb-8 text-center flex flex-col items-center">
            <!-- Icon Box -->
            <div class="w-14 h-14 bg-primary text-slate-900 rounded-xl flex items-center justify-center mb-4 shadow-sm">
                <span class="material-symbols-outlined text-[32px]">local_mall</span>
            </div>
            
            <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-2">Marketplace</h1>
            <p class="text-sm font-medium text-slate-500">Welcome back! Please enter your details.</p>
        </div>

        <!-- Login Card -->
        <div class="w-full bg-white rounded-2xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 p-8">
            <form action="<?= $baseUrl ?>/login" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                
                <!-- Email / Username Field -->
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-bold text-slate-700">Email address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-slate-400 text-lg">mail</span>
                        </div>
                        <input type="text" id="username" name="username" placeholder="name@company.com" required autocomplete="username"
                               class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-bold text-slate-700">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-slate-400 text-lg">lock</span>
                        </div>
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password"
                               class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-bold tracking-widest placeholder-slate-400 placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                        <button type="button" id="togglePass" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="remember" class="peer hidden" id="rememberMe">
                            <div class="w-4 h-4 border border-slate-300 rounded bg-white peer-checked:bg-primary peer-checked:border-primary transition-all flex items-center justify-center shadow-sm group-hover:border-slate-400">
                                <span class="material-symbols-outlined text-[12px] text-slate-900 font-bold opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-slate-500 group-hover:text-slate-700 transition-colors">Remember me</span>
                    </label>
                    <a href="<?= $baseUrl ?>/forgot-password" class="text-xs font-bold text-slate-900 hover:text-slate-600 transition-colors">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-black py-3 rounded-xl transition-all shadow-sm text-sm">
                        Login
                    </button>
                </div>

                <!-- Divider -->
                <div class="relative flex items-center justify-center pt-2 pb-1">
                    <div class="absolute inset-x-0 h-px bg-slate-100"></div>
                    <span class="relative bg-white px-4 text-xs font-medium text-slate-400">Or login with</span>
                </div>

                <!-- Social Login Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="flex flex-col sm:flex-row items-center justify-center gap-2 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                        <!-- Simplified Google G inside a box to mimic SVG -->
                        <div class="w-4 h-4 rounded-sm flex items-center justify-center text-[10px] font-bold text-white shadow-inner overflow-hidden relative border border-slate-200 bg-white">
                           <span class="absolute inset-0 border-[3px] border-l-red-500 border-t-yellow-400 border-r-green-500 border-b-blue-500 rounded-full scale-125 opacity-70"></span>
                           <span class="text-slate-700 z-10" style="font-family: Arial, sans-serif;">G</span>
                        </div>
                        <span class="text-xs font-bold text-slate-700 mt-1 sm:mt-0">Google</span>
                    </button>
                    
                    <button type="button" class="flex flex-col sm:flex-row items-center justify-center gap-2 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                        <!-- Simple Facebook Logo via Material Icon or CSS -->
                        <span class="material-symbols-outlined text-[#1877F2] text-[18px]">facebook</span>
                        <span class="text-xs font-bold text-slate-700 mt-1 sm:mt-0">Facebook</span>
                    </button>
                </div>

            </form>
        </div>

        <!-- Sign Up Link -->
        <p class="text-sm font-medium text-slate-500 mt-8 mb-8 text-center">
            Don't have an account? <a href="<?= $baseUrl ?>/register" class="text-slate-900 font-black hover:underline underline-offset-4 decoration-2">Sign up</a>
        </p>

        <!-- Feature Icons (Bottom) -->
        <div class="flex justify-center gap-4 opacity-50">
            <div class="w-8 h-8 rounded-full bg-slate-200/50 border border-slate-200 flex items-center justify-center">
                <span class="material-symbols-outlined text-[16px] text-slate-500">local_shipping</span>
            </div>
            <div class="w-8 h-8 rounded-full bg-slate-200/50 border border-slate-200 flex items-center justify-center">
                <span class="material-symbols-outlined text-[16px] text-slate-500">verified_user</span>
            </div>
            <div class="w-8 h-8 rounded-full bg-slate-200/50 border border-slate-200 flex items-center justify-center">
                <span class="material-symbols-outlined text-[16px] text-slate-500">payments</span>
            </div>
        </div>
    </div>

<script>
    // Password toggle
    document.getElementById('togglePass').addEventListener('click', function() {
        const p = document.getElementById('password');
        const icon = this.querySelector('span');
        const isPwd = p.type === 'password';
        
        p.type = isPwd ? 'text' : 'password';
        icon.textContent = isPwd ? 'visibility_off' : 'visibility';
        
        // Toggle font tracking to avoid spread-out plain text
        if(isPwd) p.classList.remove('tracking-widest');
        else p.classList.add('tracking-widest');
    });

    // Error handling
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const err = urlParams.get('error');
        if (err === '1') {
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบล้มเหลว',
                text: 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง',
                confirmButtonColor: '#f9e71f',
                confirmButtonText: '<span class="text-slate-900 font-bold">ตกลง</span>'
            });
        } else if (err === 'banned') {
            Swal.fire({
                icon: 'error',
                title: 'บัญชีถูกระงับ',
                text: 'กรุณาติดต่อผู้ดูแลระบบเพื่อขอข้อมูลเพิ่มเติม',
                confirmButtonColor: '#f9e71f',
                confirmButtonText: '<span class="text-slate-900 font-bold">ตกลง</span>'
            });
        }
    }
</script>
</body>
</html>

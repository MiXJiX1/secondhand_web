<?php
require_once __DIR__ . '/controllers/register_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Create an account | Midnight Premium</title>
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
                        customBg: "#fbfbfb" // Very light cream/gray like mockup
                    },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
    <style>
        /* Decorative Background Pattern */
        .bg-pattern {
            background-color: #fbfbfb;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-pattern font-sans text-slate-800 antialiased min-h-screen flex flex-col items-center">

    <!-- Navbar -->
    <nav class="w-full bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 py-4 fixed top-0 z-50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-slate-900">local_mall</span>
            </div>
            <span class="text-xl font-black text-slate-900 tracking-tight">Marketplace</span>
        </div>
        
        <div class="hidden md:flex items-center gap-8">
            <a href="../index.php" class="text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">Shop</a>
            <a href="sell.php" class="text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">Sell</a>
            <a href="../help/getting-started.php" class="text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">About</a>
        </div>

        <div class="flex items-center gap-4">
            <a href="login.php" class="text-sm font-bold text-slate-900 hover:text-slate-600 transition-colors">Log In</a>
            <a href="register.php" class="text-sm font-bold bg-primary text-slate-900 px-5 py-2 rounded-lg hover:bg-yellow-400 transition-colors shadow-sm">Sign Up</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="w-full flex-grow flex items-center justify-center p-4 pt-28 pb-20 relative">
        
        <!-- Decorative Floating Elements (from mockup) -->
        <span class="material-symbols-outlined text-slate-200 text-6xl absolute top-32 left-32 hidden lg:block -rotate-12">shopping_cart</span>
        <span class="material-symbols-outlined text-slate-200 text-6xl absolute top-40 right-40 hidden lg:block rotate-12" style="font-variation-settings: 'FILL' 1;">favorite</span>
        <span class="material-symbols-outlined text-slate-200 text-6xl absolute bottom-32 right-32 hidden lg:block -rotate-45" style="font-variation-settings: 'FILL' 1;">sell</span>

        <!-- Signup Card -->
        <div class="w-full max-w-[500px] bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 sm:p-10 relative z-10">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-2">Create an account</h1>
                <p class="text-sm font-medium text-slate-500">Join thousands of buyers and sellers today.</p>
            </div>

            <!-- Social Logins -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <button type="button" class="flex items-center justify-center gap-2 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                    <div class="w-4 h-4 rounded-sm flex items-center justify-center text-[10px] font-bold text-white shadow-inner overflow-hidden relative border border-slate-200 bg-white">
                        <span class="absolute inset-0 border-[3px] border-l-red-500 border-t-yellow-400 border-r-green-500 border-b-blue-500 rounded-full scale-125 opacity-70"></span>
                        <span class="text-slate-700 z-10" style="font-family: Arial, sans-serif;">G</span>
                    </div>
                    <span class="text-xs font-bold text-slate-700">Google</span>
                </button>
                <button type="button" class="flex items-center justify-center gap-2 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[#1877F2] text-[18px]">facebook</span>
                    <span class="text-xs font-bold text-slate-700">Facebook</span>
                </button>
            </div>

            <div class="relative flex items-center justify-center mb-6">
                <div class="absolute inset-x-0 h-px bg-slate-100"></div>
                <span class="relative bg-white px-4 text-xs font-medium text-slate-400">Or continue with email</span>
            </div>

            <form id="regForm" class="space-y-4">
                
                <!-- Avatar Upload -->
                <div class="flex flex-col items-center pb-2">
                    <div class="relative group cursor-pointer" onclick="document.getElementById('avatarBtn').click()">
                        <div class="w-20 h-20 rounded-full border-2 border-slate-200 overflow-hidden bg-slate-50">
                            <img id="preview" src="../uploads/avatars/default.png" class="w-full h-full object-cover">
                        </div>
                        <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="material-symbols-outlined text-white text-[20px]">add_a_photo</span>
                        </div>
                    </div>
                    <input type="file" id="avatarBtn" name="avatar" class="hidden" accept="image/*" onchange="previewImg(this)">
                    <p class="text-[10px] text-center text-slate-400 mt-2 font-medium">รูปภาพโปรไฟล์ (ไม่บังคับ)</p>
                </div>

                <!-- Full Name (Split for DB but styled cohesively) -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name</label>
                    <div class="flex gap-3">
                        <input type="text" name="fname" placeholder="First Name" required
                               class="block w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                        <input type="text" name="lname" placeholder="Last Name" required
                               class="block w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                </div>

                <!-- Student ID (Username in DB) -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Student ID</label>
                    <input type="text" name="username" placeholder="650xxxxxxxxx" required pattern="\d{11}"
                           class="block w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                </div>

                <!-- Email Address -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" placeholder="name@msu.ac.th" required
                           class="block w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6"
                               class="block w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 font-bold tracking-widest placeholder-slate-400 placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                        <button type="button" id="togglePass" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Terms -->
                <div class="pt-2 flex items-start gap-2">
                    <div class="relative flex items-center mt-0.5">
                        <input type="checkbox" name="terms" required class="peer hidden" id="terms">
                        <div class="w-4 h-4 border border-slate-300 rounded bg-white peer-checked:bg-primary peer-checked:border-primary transition-all flex items-center justify-center shadow-sm cursor-pointer" onclick="document.getElementById('terms').click()">
                            <span class="material-symbols-outlined text-[12px] text-slate-900 font-bold opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                        </div>
                    </div>
                    <label for="terms" class="text-xs font-medium text-slate-500 leading-snug cursor-pointer select-none">
                        By signing up, I agree to the <a href="#" class="text-slate-700 underline underline-offset-2">Terms of Service</a> and <a href="#" class="text-slate-700 underline underline-offset-2">Privacy Policy</a>.
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" id="btnSubmit" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-black py-3.5 rounded-xl transition-all shadow-md text-sm">
                        Create Account
                    </button>
                </div>

                <div class="text-center pt-4">
                    <p class="text-sm font-medium text-slate-500">
                        Already have an account? <a href="login.php" class="text-slate-900 font-black underline underline-offset-4 decoration-2">Log in</a>
                    </p>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-slate-100 py-6 text-center text-xs font-medium text-slate-500 mt-auto">
        <div class="flex justify-between items-center max-w-6xl mx-auto px-6">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[16px]">local_mall</span>
                <span class="font-bold text-slate-900">Marketplace</span>
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-slate-900 transition-colors">Privacy</a>
                <a href="#" class="hover:text-slate-900 transition-colors">Terms</a>
                <a href="#" class="hover:text-slate-900 transition-colors">Cookies</a>
                <a href="#" class="hover:text-slate-900 transition-colors">Contact</a>
            </div>
            <div>
                © 2024 Marketplace Inc. All rights reserved.
            </div>
        </div>
    </footer>

<script>
    // Avatar Preview
    function previewImg(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Password Toggle
    document.getElementById('togglePass').addEventListener('click', function() {
        const p = document.getElementById('password');
        const icon = this.querySelector('span');
        const isPwd = p.type === 'password';
        
        p.type = isPwd ? 'text' : 'password';
        icon.textContent = isPwd ? 'visibility_off' : 'visibility';
        
        // Toggle font tracking
        if(isPwd) p.classList.remove('tracking-widest');
        else p.classList.add('tracking-widest');
    });

    // Form Submission (AJAX)
    document.getElementById('regForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const terms = document.getElementById('terms');
        if(!terms.checked) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณายอมรับเงื่อนไข',
                text: 'โปรดยอมรับ Terms of Service และ Privacy Policy',
                confirmButtonColor: '#f9e71f'
            });
            return;
        }

        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin align-middle mr-2">progress_activity</span>กำลังสร้างบัญชี...';

        const fd = new FormData(this);
        fetch('register.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: data.message,
                    confirmButtonColor: '#f9e71f',
                    confirmButtonText: '<span class="text-slate-900 font-bold">ไปหน้าเข้าสู่ระบบ</span>'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message,
                    confirmButtonColor: '#f9e71f'
                });
                btn.disabled = false;
                btn.innerHTML = 'Create Account';
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'ระบบผิดพลาด',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                confirmButtonColor: '#f9e71f'
            });
            btn.disabled = false;
            btn.innerHTML = 'Create Account';
        });
    });
</script>
</body>
</html>

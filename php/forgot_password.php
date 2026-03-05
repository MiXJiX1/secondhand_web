<?php
require_once __DIR__ . '/controllers/forgot_password_controller.php';
?>
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Forgot Password | Midnight Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 
                        primary: "#f9e71f",
                        customBg: "#fbfbfb"
                    },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
</head>
<body class="bg-customBg font-sans text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- Header (Mockup Style) -->
    <header class="w-full bg-transparent px-8 py-6 flex justify-between items-center fixed top-0 w-full z-50">
        <a href="../index.php" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-slate-900">local_mall</span>
            </div>
            <span class="text-xl font-black text-slate-900 tracking-tight">Marketplace</span>
        </a>
        <div class="hidden sm:block">
            <a href="help/getting-started.php" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-800 text-white hover:bg-slate-700 transition-colors">
                <span class="material-symbols-outlined text-[18px]">question_mark</span>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="w-full flex-grow flex items-center justify-center p-4">
        
        <?php if ($mode === 'request'): ?>
        <!-- ======================= 1. REQUEST STATE ======================= -->
        <div class="w-full max-w-md bg-white rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 sm:p-12 text-center">
            
            <div class="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-primary text-3xl">history_toggle_off</span>
            </div>
            
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-3">Forgot Password?</h1>
            <p class="text-sm font-medium text-slate-500 mb-8 px-4 leading-relaxed">
                Enter the email address associated with your account and we'll send you a link to reset your password.
            </p>

            <?php if ($errMsg): ?>
            <div class="text-xs text-red-500 font-bold mb-4 text-left">
                <?= h($errMsg) ?>
            </div>
            <?php endif; ?>

            <form method="post" class="space-y-6 text-left" novalidate>
                <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
                
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-900">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">mail</span>
                        </div>
                        <input type="email" id="email" name="email" placeholder="name@example.com" required autocomplete="email"
                               class="block w-full pl-10 pr-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-3.5 rounded-xl transition-all shadow-sm text-sm flex items-center justify-center gap-2">
                    Send Reset Link
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </form>

            <div class="mt-8 text-center pt-6 border-t border-slate-50">
                <a href="login.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Login
                </a>
            </div>
        </div>

        <?php elseif ($mode === 'sent'): ?>
        <!-- ======================= 2. EMAIL SENT STATE ======================= -->
        <div class="w-full max-w-md bg-white rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 sm:p-12 text-center relative overflow-hidden">
            
            <div class="w-16 h-16 bg-yellow-100/60 rounded-full flex items-center justify-center mx-auto mb-6">
                <!-- Multi-tone email icon based on mockup -->
                <span class="material-symbols-outlined text-yellow-500 text-3xl" style="font-variation-settings: 'FILL' 1;">mark_email_read</span>
            </div>
            
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-4">Email Sent!</h1>
            <p class="text-sm font-medium text-slate-500 mb-8 px-2 leading-relaxed">
                We've sent a password reset link to your email address. Please check your inbox and 
                <span class="font-bold text-slate-700">spam folder</span> to continue.
            </p>

            <a href="login.php" class="block w-full bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-3.5 rounded-xl transition-all shadow-sm text-sm mb-6">
                Back to Login
            </a>

            <div class="text-center">
                <p class="text-xs font-medium text-slate-500">
                    Didn't receive an email? 
                    <a href="forgot_password.php" class="text-slate-900 font-bold hover:underline">Resend Email</a>
                </p>
            </div>

            <!-- Decorative Yellow Gradient Block -->
            <div class="w-[85%] h-24 mx-auto mt-8 rounded-xl bg-gradient-to-tr from-yellow-100 via-primary/50 to-primary/80 opacity-60"></div>
            
            <!-- Debug Link if no mail server config -->
            <?php if(isset($_SESSION['debug_link'])): ?>
            <div class="absolute bottom-2 left-0 right-0 text-center">
                <a href="<?= h($_SESSION['debug_link']) ?>" class="text-[10px] text-blue-500 hover:underline">dev mode: jump to reset</a>
            </div>
            <?php unset($_SESSION['debug_link']); endif; ?>
        </div>

        <?php else: /* Reset Mode */ ?>
        <!-- ======================= 3. RESET PASSWORD STATE ======================= -->
        <div class="w-full max-w-md bg-white rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 sm:p-12 text-center">
            
            <?php if ($tokenInvalid): ?>
                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-red-500 text-3xl">error</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-3">Link Expired</h1>
                <p class="text-sm font-medium text-slate-500 mb-8 leading-relaxed">
                    This password reset link is invalid or has expired (30 minutes). Please request a new one.
                </p>
                <a href="forgot_password.php" class="block w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition-all shadow-sm text-sm">
                    Request New Link
                </a>
            <?php else: ?>
                <div class="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-primary text-3xl">lock_reset</span>
                </div>
                
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-3">Set New Password</h1>
                <p class="text-sm font-medium text-slate-500 mb-8 leading-relaxed">
                    Please enter your new password below. Make it strong and memorable.
                </p>

                <?php if ($errMsg): ?>
                <div class="text-xs text-red-500 font-bold mb-4 text-left">
                    <?= h($errMsg) ?>
                </div>
                <?php endif; ?>

                <form method="post" class="space-y-5 text-left" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
                    
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-900">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-slate-400 text-[18px]">lock</span>
                            </div>
                            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6" autocomplete="new-password"
                                   class="block w-full pl-10 pr-10 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 font-bold tracking-widest placeholder-slate-400 placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                            <button type="button" class="togglePass absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" data-target="password">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password2" class="block text-xs font-bold text-slate-900">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-slate-400 text-[18px]">check_circle</span>
                            </div>
                            <input type="password" id="password2" name="password2" placeholder="Repeat password" required minlength="6" autocomplete="new-password"
                                   class="block w-full pl-10 pr-10 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 font-bold tracking-widest placeholder-slate-400 placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                            <button type="button" class="togglePass absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" data-target="password2">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-3.5 rounded-xl transition-all shadow-sm text-sm mt-2">
                        Save Password
                    </button>
                </form>

                <div class="mt-8 text-center pt-6 border-t border-slate-50">
                    <a href="login.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                        Cancel
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="w-full text-center text-[11px] font-medium text-slate-400 py-6 mb-2">
        <p>
            © 2024 Secondhand Marketplace. All rights reserved. 
            <a href="#" class="hover:text-slate-600 transition-colors ml-2">Terms</a> 
            <a href="#" class="hover:text-slate-600 transition-colors ml-1">Privacy</a>
        </p>
    </footer>

    <?php if ($mode === 'reset' && !$tokenInvalid): ?>
    <script>
        document.querySelectorAll('.togglePass').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('span');
                
                const isPwd = input.type === 'password';
                input.type = isPwd ? 'text' : 'password';
                icon.textContent = isPwd ? 'visibility_off' : 'visibility';
                
                if(isPwd) input.classList.remove('tracking-widest');
                else input.classList.add('tracking-widest');
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>

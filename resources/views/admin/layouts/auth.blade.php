<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'LesGo Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="login-shell min-h-screen flex items-center justify-center px-4 py-8 sm:px-6">
    <div class="w-full max-w-5xl">
        <main class="auth-shell-card grid overflow-hidden rounded-3xl lg:grid-cols-[0.9fr_1.1fr]">
            <section class="auth-visual hidden min-h-[620px] flex-col justify-between p-10 text-white lg:flex">
                <div>
                    <a href="{{ route('admin.login') }}" class="block" aria-label="LesGo Courier Service admin login">
                        <span class="lesgo-logo-crop lesgo-logo-login" aria-hidden="true">
                            <img src="{{ asset('images/lesgo-brand.png') }}" alt="" class="lesgo-logo-source">
                        </span>
                        <span class="mt-2 block text-xs font-semibold uppercase tracking-[0.28em] text-purple-100/75">Admin Panel</span>
                    </a>

                    <div class="mt-14">
                        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.24em] text-purple-200">Operations command center</p>
                        <h1 class="max-w-sm text-4xl font-semibold leading-tight">Everything moving. All in one place.</h1>
                        <p class="mt-5 max-w-sm leading-relaxed text-white/68">Monitor users, drivers, partners, orders, payments, and support activity from one secure workspace.</p>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div class="auth-feature flex items-center gap-3 rounded-xl px-4 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/12"><i class="fas fa-shield-halved text-sm"></i></span>
                        <div><p class="text-sm font-semibold">Protected admin access</p><p class="text-xs text-white/55">Rate-limited and security monitored</p></div>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-white/45">
                        <i class="fas fa-lock text-[10px]"></i>
                        <span>Authorized LesGo personnel only</span>
                    </div>
                </div>
            </section>

            <section class="auth-form-panel flex min-h-[620px] flex-col justify-center bg-white px-6 py-10 sm:px-12 lg:px-16">
                <div class="mb-8 lg:hidden">
                    <span class="lesgo-logo-crop lesgo-logo-mobile" aria-hidden="true">
                        <img src="{{ asset('images/lesgo-brand.png') }}" alt="" class="lesgo-logo-source">
                    </span>
                    <p class="mt-2 text-center text-[10px] font-semibold uppercase tracking-[0.24em] text-purple-700">Admin Panel</p>
                </div>

                @yield('content')
            </section>
        </main>

        <p class="mt-5 text-center text-xs text-white/55">&copy; {{ date('Y') }} LesGo Courier Service. All rights reserved.</p>
    </div>

    @stack('scripts')
</body>
</html>

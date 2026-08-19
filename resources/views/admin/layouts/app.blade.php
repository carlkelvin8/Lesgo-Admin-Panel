<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LesGo Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-shell min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="admin-sidebar w-64 text-gray-300 flex flex-col fixed h-full">
            <div class="admin-brand border-b px-4 py-3">
                <a href="{{ route('admin.dashboard') }}" class="block" aria-label="LesGo Admin dashboard">
                    <span class="lesgo-logo-crop lesgo-logo-sidebar" aria-hidden="true">
                        <img src="{{ asset('images/lesgo-brand.png') }}" alt="" class="lesgo-logo-source">
                    </span>
                    <span class="mt-1 block text-center text-[10px] font-semibold uppercase tracking-[0.24em] text-purple-200/70">Admin Panel</span>
                </a>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i> Dashboard
                </a>

                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Management</div>

                <a href="{{ route('admin.users.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5"></i> Users
                </a>
                <a href="{{ route('admin.drivers.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <i class="fas fa-motorcycle w-5"></i> Drivers
                </a>
                <a href="{{ route('admin.partners.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                    <i class="fas fa-store w-5"></i> Partners
                </a>
                <a href="{{ route('admin.orders.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart w-5"></i> Orders
                </a>
                <a href="{{ route('admin.services.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <i class="fas fa-concierge-bell w-5"></i> Services
                </a>

                <a href="{{ route('admin.ratings.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
                    <i class="fas fa-star w-5"></i> Ratings & Reviews
                </a>

                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Operations</div>

                <a href="{{ route('admin.payments.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card w-5"></i> Payments
                </a>
                <a href="{{ route('admin.wallets.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet w-5"></i> Wallets
                </a>
                <a href="{{ route('admin.document-verifications.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.document-verifications.*') ? 'active' : '' }}">
                    <i class="fas fa-id-card w-5"></i> Verifications
                </a>
                <a href="{{ route('admin.notifications.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-bell w-5"></i> Notifications
                </a>

                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Support</div>

                <a href="{{ route('admin.tickets.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt w-5"></i> Support Tickets
                </a>
                <a href="{{ route('admin.faq.articles') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.faq.*') ? 'active' : '' }}">
                    <i class="fas fa-circle-question w-5"></i> FAQ Knowledge Base
                </a>

                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Insights & Security</div>

                <a href="{{ route('admin.analytics.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line w-5"></i> Analytics
                </a>
                <a href="{{ route('admin.reports.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-file-lines w-5"></i> Reports
                </a>
                <a href="{{ route('admin.security-events.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.security-events.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-halved w-5"></i> Security Events
                </a>
                <a href="{{ route('admin.audit-logs.index') }}"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left w-5"></i> Audit Logs
                </a>
            </nav>

            <div class="admin-user-panel p-4 border-t">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-400" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <header class="admin-topbar border-b px-6 py-4 flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-lg font-semibold text-gray-800">@yield('header', 'Dashboard')</h2>
                <div class="flex items-center gap-4">
                    @yield('actions')
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mx-6 mt-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mx-6 mt-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Page Content -->
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>

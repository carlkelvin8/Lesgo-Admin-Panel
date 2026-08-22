<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LesGo Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="admin-shell min-h-screen" x-data>
    <!-- Toast Notifications -->
    <div x-data="toastManager()" x-init="init()" class="fixed top-4 right-4 z-[100] space-y-2 max-w-sm">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" :class="{'bg-green-50 border-green-300 text-green-800': toast.type==='success','bg-red-50 border-red-300 text-red-800': toast.type==='error','bg-blue-50 border-blue-300 text-blue-800': toast.type==='info'}" class="border rounded-lg px-4 py-3 shadow-lg flex items-center gap-3 text-sm">
                <i :class="{'fas fa-check-circle text-green-500': toast.type==='success','fas fa-exclamation-circle text-red-500': toast.type==='error','fas fa-info-circle text-blue-500': toast.type==='info'}"></i>
                <span class="flex-1" x-text="toast.message"></span>
                <button @click="removeToast(toast.id)" class="opacity-60 hover:opacity-100"><i class="fas fa-times text-xs"></i></button>
            </div>
        </template>
    </div>

    <!-- Global Confirm Modal -->
    <div x-data="confirmModal()" x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancel()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="iconBg"><i class="fas" :class="iconClass"></i></div>
                <h3 class="text-lg font-semibold text-gray-800" x-text="title"></h3>
            </div>
            <p class="text-gray-600 text-sm mb-6" x-text="message"></p>
            <div class="flex justify-end gap-3">
                <button @click="cancel()" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition" x-text="cancelText"></button>
                <button @click="confirm()" class="px-4 py-2 text-sm rounded-lg text-white transition" :class="confirmClass" x-text="confirmText"></button>
            </div>
        </div>
    </div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="admin-sidebar" class="admin-sidebar w-64 text-gray-300 flex flex-col fixed h-full" aria-label="Admin navigation">
            <div class="admin-brand border-b px-4 py-3">
                <a href="{{ route('admin.dashboard') }}" class="block" aria-label="LesGo Admin dashboard">
                    <span class="lesgo-logo-crop lesgo-logo-sidebar" aria-hidden="true">
                        <img src="{{ asset('images/lesgo-brand.png') }}" alt="" class="lesgo-logo-source">
                    </span>
                    <span class="mt-1 block text-center text-[10px] font-semibold uppercase tracking-[0.24em] text-purple-200/70">Admin Panel</span>
                </a>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                @if(auth()->user()->hasAdminPermission('dashboard.view'))
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt w-5"></i> Dashboard
                    </a>
                @endif

                @if(auth()->user()->hasAdminPermission('users.view') || auth()->user()->hasAdminPermission('roles.manage') || auth()->user()->hasAdminPermission('drivers.manage') || auth()->user()->hasAdminPermission('partners.manage') || auth()->user()->hasAdminPermission('orders.view') || auth()->user()->hasAdminPermission('services.manage') || auth()->user()->hasAdminPermission('ratings.manage'))
                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Management</div>
                @endif
                @if(auth()->user()->hasAdminPermission('roles.manage'))
                <a href="{{ route('admin.roles.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield w-5"></i> Roles & Permissions
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('users.view'))
                <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5"></i> Users
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('drivers.manage'))
                <a href="{{ route('admin.drivers.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <i class="fas fa-motorcycle w-5"></i> Drivers
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('partners.manage'))
                <a href="{{ route('admin.partners.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                    <i class="fas fa-store w-5"></i> Partners
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('orders.view'))
                <a href="{{ route('admin.orders.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart w-5"></i> Orders
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('services.manage'))
                <a href="{{ route('admin.services.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <i class="fas fa-concierge-bell w-5"></i> Services
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('ratings.manage'))
                <a href="{{ route('admin.ratings.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
                    <i class="fas fa-star w-5"></i> Ratings & Reviews
                </a>
                @endif

                @if(auth()->user()->hasAdminPermission('payments.view') || auth()->user()->hasAdminPermission('wallets.view') || auth()->user()->hasAdminPermission('verifications.manage') || auth()->user()->hasAdminPermission('notifications.manage'))
                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Operations</div>
                @endif
                @if(auth()->user()->hasAdminPermission('payments.view'))
                <a href="{{ route('admin.payments.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card w-5"></i> Payments
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('wallets.view'))
                <a href="{{ route('admin.wallets.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet w-5"></i> Wallets
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('verifications.manage'))
                <a href="{{ route('admin.document-verifications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.document-verifications.*') ? 'active' : '' }}">
                    <i class="fas fa-id-card w-5"></i> Verifications
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('notifications.manage'))
                <a href="{{ route('admin.notifications.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-bell w-5"></i> Notifications
                </a>
                @endif

                @if(auth()->user()->hasAdminPermission('tickets.manage') || auth()->user()->hasAdminPermission('faq.manage'))
                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Support</div>
                @endif
                @if(auth()->user()->hasAdminPermission('tickets.manage'))
                <a href="{{ route('admin.tickets.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt w-5"></i> Support Tickets
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('faq.manage'))
                <a href="{{ route('admin.faq.articles') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.faq.*') ? 'active' : '' }}">
                    <i class="fas fa-circle-question w-5"></i> FAQ Knowledge Base
                </a>
                @endif

                @if(auth()->user()->hasAdminPermission('reports.view') || auth()->user()->hasAdminPermission('security.manage') || auth()->user()->hasAdminPermission('audit.view'))
                <div class="sidebar-section-label px-4 py-2 text-xs uppercase tracking-wider mt-4">Insights & Security</div>
                @endif
                @if(auth()->user()->hasAdminPermission('reports.view'))
                <a href="{{ route('admin.analytics.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line w-5"></i> Analytics
                </a>
                <a href="{{ route('admin.reports.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-file-lines w-5"></i> Reports
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('security.manage'))
                <a href="{{ route('admin.security-settings.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.security-settings.*') ? 'active' : '' }}">
                    <i class="fas fa-sliders w-5"></i> Security Center
                </a>
                <a href="{{ route('admin.security-events.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.security-events.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-halved w-5"></i> Security Events
                </a>
                @endif
                @if(auth()->user()->hasAdminPermission('audit.view'))
                <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left w-5"></i> Audit Logs
                </a>
                @endif
            </nav>

            <div class="admin-user-panel p-4 border-t">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <a href="{{ route('admin.profile.edit') }}" class="flex-1 min-w-0 hover:opacity-90" title="Open profile settings">
                        <p class="text-sm text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->adminRoleLabel() }}</p>
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-400" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </div>
        </aside>
        <button id="admin-sidebar-overlay" type="button" class="admin-sidebar-overlay" aria-label="Close navigation"></button>

        <!-- Main Content -->
        <main class="flex-1 ml-0 md:ml-64 min-w-0">
            <!-- Top Bar -->
            <header class="admin-topbar border-b px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="admin-menu-toggle" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-purple-100 bg-white text-purple-700 md:hidden" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
                    <div>
                        @if(isset($breadcrumbs))
                        <nav class="flex items-center gap-1 text-xs text-gray-400 mb-1">
                            @foreach($breadcrumbs as $i => $crumb)
                                @if($i > 0) <i class="fas fa-chevron-right text-[8px]"></i> @endif
                                @if(isset($crumb['url']))
                                    <a href="{{ $crumb['url'] }}" class="hover:text-blue-600 transition-colors">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="text-gray-600">{{ $crumb['label'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                        @endif
                        <h2 class="truncate text-lg font-semibold text-gray-800">@yield('header', 'Dashboard')</h2>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @yield('actions')
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="mx-6 mt-4 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button @click="show = false" class="ml-auto"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition class="mx-6 mt-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button @click="show = false" class="ml-auto"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @if($errors->any())
                <div x-data="{ show: true }" x-show="show" x-transition class="mx-6 mt-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <div class="flex items-center gap-2 mb-1"><i class="fas fa-exclamation-circle"></i> <strong>Please fix the following errors:</strong></div>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button @click="show = false" class="absolute top-3 right-3"><i class="fas fa-times"></i></button>
                </div>
            @endif

            <!-- Page Content -->
            <div class="p-4 sm:p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')

    <!-- Keyboard Shortcuts & Command Palette -->
    <x-keyboard-shortcuts />
    <x-command-palette />

    <script>
        function toastManager() {
            return {
                toasts: [],
                init() {
                    const self = this;
                    window.addEventListener('toast', (e) => self.add(e.detail));
                },
                add(detail) {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, message: detail.message, type: detail.type || 'info', visible: true });
                    setTimeout(() => this.removeToast(id), 5000);
                },
                removeToast(id) {
                    const t = this.toasts.find(t => t.id === id);
                    if (t) t.visible = false;
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
                }
            };
        }
        function confirmModal() {
            return {
                open: false, title: '', message: '', confirmText: 'Confirm', cancelText: 'Cancel',
                confirmClass: 'bg-red-600 hover:bg-red-700', iconBg: 'bg-red-100', iconClass: 'fas fa-exclamation-triangle text-red-600',
                _callback: null,
                show(title, message, opts = {}) {
                    this.title = title; this.message = message;
                    this.confirmText = opts.confirmText || 'Confirm';
                    this.cancelText = opts.cancelText || 'Cancel';
                    this.confirmClass = opts.confirmClass || 'bg-red-600 hover:bg-red-700';
                    this.iconBg = opts.iconBg || 'bg-red-100';
                    this.iconClass = opts.iconClass || 'fas fa-exclamation-triangle text-red-600';
                    this._callback = opts.onConfirm || null;
                    this.open = true;
                },
                confirm() { if (this._callback) this._callback(); this.open = false; },
                cancel() { this.open = false; }
            };
        }
        function confirmDelete(name, url, token) {
            const modal = document.querySelector('[x-data]').__x.$data;
            return `confirm('${name}', '${url}', '${token}')`;
        }
        ((sidebar, toggle, overlay) => {
            const setOpen = (open) => {
                sidebar?.classList.toggle('is-open', open);
                overlay?.classList.toggle('is-open', open);
                toggle?.setAttribute('aria-expanded', String(open));
                document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 768);
            };
            toggle?.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
            overlay?.addEventListener('click', () => setOpen(false));
            sidebar?.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
            document.addEventListener('keydown', (event) => event.key === 'Escape' && setOpen(false));
            window.addEventListener('resize', () => window.innerWidth >= 768 && setOpen(false));
        })(document.getElementById('admin-sidebar'), document.getElementById('admin-menu-toggle'), document.getElementById('admin-sidebar-overlay'));
    </script>
</body>
</html>

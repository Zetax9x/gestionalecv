<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Gestionale Protezione Civile</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css" rel="stylesheet">
    
    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        .sidebar-link {
            @apply flex items-center px-4 py-3 text-gray-300 hover:bg-blue-700 hover:text-white transition-all duration-200 rounded-lg mx-2;
        }
        .sidebar-link.active {
            @apply bg-blue-700 text-white;
        }
        .sidebar-link i {
            @apply w-5 h-5 mr-3;
        }
        .notification-badge {
            @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center;
        }
        .quick-action-card {
            @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200 cursor-pointer;
        }
        .stat-card {
            @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div id="app" class="min-h-screen">
        <!-- Sidebar -->
        <aside class="fixed top-0 left-0 z-40 w-64 h-screen bg-blue-900 shadow-lg">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 bg-blue-800 border-b border-blue-700">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                        <i data-lucide="shield" class="w-5 h-5 text-blue-900"></i>
                    </div>
                    <h1 class="text-xl font-bold text-white">Gestionale PC</h1>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-8 space-y-2">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="home"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('eventi.index') }}" class="sidebar-link {{ request()->routeIs('eventi.*') ? 'active' : '' }}">
                    <i data-lucide="calendar"></i>
                    <span>Eventi</span>
                </a>

                <a href="{{ route('volontari.index') }}" class="sidebar-link {{ request()->routeIs('volontari.*') ? 'active' : '' }}">
                    <i data-lucide="users"></i>
                    <span>Volontari</span>
                </a>

                @can('view', App\Models\Mezzo::class)
                <a href="{{ route('mezzi.index') }}" class="sidebar-link {{ request()->routeIs('mezzi.*') ? 'active' : '' }}">
                    <i data-lucide="truck"></i>
                    <span>Mezzi</span>
                </a>
                @endcan

                <a href="{{ route('tickets.index') }}" class="sidebar-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    <i data-lucide="ticket"></i>
                    <span>Tickets</span>
                    @if(auth()->user()->tickets()->whereIn('stato', ['aperto', 'in_lavorazione'])->count() > 0)
                        <span class="notification-badge">
                            {{ auth()->user()->tickets()->whereIn('stato', ['aperto', 'in_lavorazione'])->count() }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('magazzino.index') }}" class="sidebar-link {{ request()->routeIs('magazzino.*') ? 'active' : '' }}">
                    <i data-lucide="package"></i>
                    <span>Magazzino</span>
                </a>

                <a href="{{ route('dpi.index') }}" class="sidebar-link {{ request()->routeIs('dpi.*') ? 'active' : '' }}">
                    <i data-lucide="shield"></i>
                    <span>DPI</span>
                </a>

                <a href="{{ route('notifiche.index') }}" class="sidebar-link {{ request()->routeIs('notifiche.*') ? 'active' : '' }}">
                    <i data-lucide="bell"></i>
                    <span>Notifiche</span>
                    @if(auth()->user()->notifiche()->whereNull('read_at')->count() > 0)
                        <span class="notification-badge">
                            {{ auth()->user()->notifiche()->whereNull('read_at')->count() }}
                        </span>
                    @endif
                </a>

                @can('admin.access')
                <div class="border-t border-blue-700 mt-8 pt-4">
                    <a href="{{ route('configurazione.index') }}" class="sidebar-link {{ request()->routeIs('configurazione.*') ? 'active' : '' }}">
                        <i data-lucide="settings"></i>
                        <span>Configurazione</span>
                    </a>
                </div>
                @endcan
            </nav>

            <!-- User Info -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-blue-700">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-700 rounded-full flex items-center justify-center">
                        <i data-lucide="user" class="w-4 h-4 text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-blue-300 truncate">{{ ucfirst(auth()->user()->role) }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="ml-64">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <!-- Page Title -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        @yield('page-title', 'Dashboard')
                    </h2>
                    @hasSection('breadcrumbs')
                        <nav class="text-sm text-gray-500 mt-1">
                            @yield('breadcrumbs')
                        </nav>
                    @endif
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Quick Search -->
                    <div class="relative">
                        <input type="text" id="quickSearch" placeholder="Cerca..." 
                               class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                        
                        <!-- Search Results -->
                        <div id="searchResults" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
                            <!-- Results populated by JS -->
                        </div>
                    </div>

                    <!-- Notifications Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            @if(auth()->user()->notifiche()->whereNull('read_at')->count() > 0)
                                <span class="notification-badge">
                                    {{ auth()->user()->notifiche()->whereNull('read_at')->count() }}
                                </span>
                            @endif
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900">Notifiche</h3>
                                    <a href="{{ route('notifiche.index') }}" class="text-xs text-blue-600 hover:text-blue-800">Vedi tutte</a>
                                </div>
                            </div>
                            
                            <div id="recentNotifications" class="max-h-64 overflow-y-auto">
                                <!-- Notifiche caricate via AJAX -->
                                <div class="p-4 text-center text-gray-500">
                                    <i data-lucide="loader" class="w-4 h-4 animate-spin mx-auto mb-2"></i>
                                    Caricamento...
                                </div>
                            </div>
                            
                            <div class="p-3 border-t border-gray-200">
                                <button onclick="markAllNotificationsRead()" 
                                        class="w-full text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Segna tutte come lette
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-3 border-b border-gray-200">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <div class="py-1">
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>
                                    Profilo
                                </a>
                                <a href="{{ route('profile.security') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>
                                    Sicurezza
                                </a>
                            </div>
                            
                            <div class="border-t border-gray-200">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                        <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-2"></i>
                            {{ session('warning') }}
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-start">
                            <i data-lucide="alert-circle" class="w-5 h-5 mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-medium">Ci sono degli errori nel modulo:</p>
                                <ul class="mt-2 list-disc list-inside text-sm">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Main Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.js"></script>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Quick Search functionality
        let searchTimeout;
        document.getElementById('quickSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                hideSearchResults();
            }
        });

        function performSearch(query) {
            fetch(`/api/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    showSearchResults(data);
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }

        function showSearchResults(results) {
            const container = document.getElementById('searchResults');
            
            if (results.length === 0) {
                container.innerHTML = '<div class="p-4 text-gray-500 text-center">Nessun risultato trovato</div>';
            } else {
                container.innerHTML = results.map(result => `
                    <a href="${result.url}" class="block p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="${getIconForType(result.type)}" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${result.text}</p>
                                <p class="text-xs text-gray-500">${getTypeLabel(result.type)}</p>
                            </div>
                        </div>
                    </a>
                `).join('');
                
                // Re-initialize icons for new content
                lucide.createIcons();
            }
            
            container.classList.remove('hidden');
        }

        function hideSearchResults() {
            document.getElementById('searchResults').classList.add('hidden');
        }

        function getIconForType(type) {
            const icons = {
                'volontario': 'user',
                'evento': 'calendar',
                'mezzo': 'truck',
                'ticket': 'ticket'
            };
            return icons[type] || 'search';
        }

        function getTypeLabel(type) {
            const labels = {
                'volontario': 'Volontario',
                'evento': 'Evento',
                'mezzo': 'Mezzo',
                'ticket': 'Ticket'
            };
            return labels[type] || type;
        }

        // Load recent notifications
        function loadRecentNotifications() {
            fetch('/notifiche/api/recent')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recentNotifications');
                    
                    if (data.length === 0) {
                        container.innerHTML = '<div class="p-4 text-center text-gray-500">Nessuna notifica recente</div>';
                    } else {
                        container.innerHTML = data.map(notifica => `
                            <a href="${notifica.url}" class="block p-4 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 ${!notifica.read_at ? 'bg-blue-50' : ''}">
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="${notifica.icon}" class="w-4 h-4 text-blue-600"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">${notifica.titolo}</p>
                                        <p class="text-xs text-gray-500 mt-1">${notifica.messaggio}</p>
                                        <p class="text-xs text-gray-400 mt-1">${notifica.created_at}</p>
                                    </div>
                                    ${!notifica.read_at ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>' : ''}
                                </div>
                            </a>
                        `).join('');
                        
                        // Re-initialize icons
                        lucide.createIcons();
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('recentNotifications').innerHTML = 
                        '<div class="p-4 text-center text-red-500">Errore nel caricamento</div>';
                });
        }

        // Mark all notifications as read
        function markAllNotificationsRead() {
            fetch('/notifiche/mark-all-read', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh notifications
                    loadRecentNotifications();
                    // Update badge count
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
        }

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentNotifications();
        });

        // Click outside to close search results
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#quickSearch') && !e.target.closest('#searchResults')) {
                hideSearchResults();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
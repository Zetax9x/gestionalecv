<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Gestionale CV') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .navbar-brand {
            font-weight: 600;
        }
        .badge-notification {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <h4 class="text-white fw-bold">
                            <i class="bi bi-heart-pulse"></i>
                            Gestionale CV
                        </h4>
                        <small class="text-white-50">{{ auth()->user()->ruolo_label ?? 'Utente' }}</small>
                    </div>

                    <!-- Menu Navigation -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                               href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>

                        @can('permission', ['volontari', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('volontari.*') ? 'active' : '' }}" 
                               href="{{ route('volontari.index') }}">
                                <i class="bi bi-people"></i>
                                Volontari
                            </a>
                        </li>
                        @endcan

                        @can('access-mezzi')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('mezzi.*') ? 'active' : '' }}" 
                               href="{{ route('mezzi.index') }}">
                                <i class="bi bi-truck"></i>
                                Mezzi
                            </a>
                        </li>
                        @endcan

                        @can('permission', ['dpi', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dpi.*') ? 'active' : '' }}" 
                               href="{{ route('dpi.index') }}">
                                <i class="bi bi-shield-check"></i>
                                DPI
                            </a>
                        </li>
                        @endcan

                        @can('permission', ['magazzino', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('magazzino.*') ? 'active' : '' }}" 
                               href="{{ route('magazzino.index') }}">
                                <i class="bi bi-boxes"></i>
                                Magazzino
                            </a>
                        </li>
                        @endcan

                        @can('permission', ['eventi', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('eventi.*') ? 'active' : '' }}" 
                               href="{{ route('eventi.index') }}">
                                <i class="bi bi-calendar-event"></i>
                                Eventi
                            </a>
                        </li>
                        @endcan

                        @can('permission', ['tickets', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}" 
                               href="{{ route('tickets.index') }}">
                                <i class="bi bi-ticket-perforated"></i>
                                Tickets
                                @if(auth()->user()->countNotificheNonLette() > 0)
                                <span class="badge-notification ms-2">
                                    {{ auth()->user()->countNotificheNonLette() }}
                                </span>
                                @endif
                            </a>
                        </li>
                        @endcan

                        @can('permission', ['notifiche', 'visualizza'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notifiche.*') ? 'active' : '' }}" 
                               href="{{ route('notifiche.index') }}">
                                <i class="bi bi-bell"></i>
                                Notifiche
                            </a>
                        </li>
                        @endcan

                        @can('configure-acl')
                        <li class="nav-item mt-3">
                            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white-50">
                                <span>Amministrazione</span>
                            </h6>
                            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" 
                               href="{{ route('admin.permissions.index') }}">
                                <i class="bi bi-gear"></i>
                                Permessi ACL
                            </a>
                        </li>
                        @endcan
                    </ul>

                    <!-- User Profile -->
                    <div class="mt-auto pt-3 border-top border-white-50">
                        <div class="d-flex align-items-center text-white">
                            <img src="{{ auth()->user()->avatar_url }}" 
                                 alt="Avatar" 
                                 class="rounded-circle me-2" 
                                 width="32" height="32">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ auth()->user()->nome_completo }}</div>
                                <small class="text-white-50">{{ auth()->user()->email }}</small>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <a href="{{ route('profile.edit') }}" class="btn btn-outline-light btn-sm me-1">
                                <i class="bi bi-person"></i>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm">
                                    <i class="bi bi-box-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Top Navigation -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'Dashboard')</h1>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @yield('page-actions')
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Errori di validazione:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                <div class="content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Update ultimo accesso
        if (navigator.sendBeacon) {
            navigator.sendBeacon('{{ route("user.update-accesso") }}', new FormData());
        }
    </script>

    @stack('scripts')
</body>
</html>
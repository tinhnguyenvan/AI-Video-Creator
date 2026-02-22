<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Video Creator') - {{ config('app.name') }}</title>

    {{-- Bootstrap 5.3 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy-900: #1e293b;
            --navy-800: #1e293b;
            --navy-700: #131a3e;
            --navy-600: #1a2150;
            --navy-500: #232d63;
            --navy-400: #3a4580;
            --navy-300: #5a6499;
            --navy-200: #8b92b3;
            --navy-100: #c4c8db;
            --accent: #0b788e;
            --accent-hover: #119cbf;
            --accent-glow: rgba(8, 61, 71, 0.15);
            --accent-soft: rgba(0, 212, 255, 0.08);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-width: 260px;
            --topbar-height: 64px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            margin: 0;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--navy-900) 0%, var(--navy-800) 100%);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .sidebar-brand-text small {
            display: block;
            font-size: 0.65rem;
            font-weight: 400;
            color: var(--navy-300);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 1rem 0.75rem;
            flex: 1;
        }

        .sidebar-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--navy-400);
            padding: 0 0.75rem;
            margin-bottom: 0.5rem;
            margin-top: 1.25rem;
        }

        .sidebar-label:first-child {
            margin-top: 0;
        }

        .nav-item-sidebar {
            margin-bottom: 2px;
        }

        .nav-link-sidebar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 8px;
            color: var(--navy-200);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-link-sidebar i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-link-sidebar:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }

        .nav-link-sidebar.active {
            color: #fff;
            background: var(--accent-glow);
        }

        .nav-link-sidebar.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: var(--accent);
            border-radius: 0 3px 3px 0;
        }

        .nav-link-sidebar.active i {
            color: var(--accent);
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-cta {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(99, 102, 241, 0.1));
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }

        .sidebar-cta p {
            color: var(--navy-200);
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .btn-cta {
            background: linear-gradient(135deg, var(--accent), #6366f1);
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-cta:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== TOP BAR ===== */
        .topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .topbar-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--navy-900);
        }

        .topbar-breadcrumb {
            font-size: 0.8rem;
            color: var(--navy-300);
        }

        .topbar-breadcrumb a {
            color: var(--navy-400);
            text-decoration: none;
        }

        .topbar-breadcrumb a:hover {
            color: var(--accent);
        }

        .topbar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--navy-800);
            padding: 0.25rem;
            margin-right: 0.75rem;
            cursor: pointer;
        }

        /* ===== CONTENT AREA ===== */
        .content-area {
            flex: 1;
            padding: 1.75rem;
        }

        /* ===== CARDS ===== */
        .card-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .card-panel-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-panel-header h5 {
            font-weight: 700;
            font-size: 0.95rem;
            margin: 0;
            color: var(--navy-900);
        }

        .card-panel-body {
            padding: 1.5rem;
        }

        /* ===== STATS ===== */
        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(0, 212, 255, 0.08);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: rgba(0, 212, 255, 0.1); color: var(--accent); }
        .stat-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-icon.yellow { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-icon.red { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .stat-info .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stat-info .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--navy-900);
            line-height: 1;
            margin-top: 4px;
        }

        /* ===== VIDEO CARDS ===== */
        .video-grid-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .video-grid-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }

        .video-thumb {
            height: 180px;
            position: relative;
            overflow: hidden;
        }

        .video-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-thumb-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--navy-900), var(--navy-700));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--navy-400);
            font-size: 2.5rem;
        }

        .video-thumb-placeholder.processing {
            color: var(--accent);
        }

        .video-thumb-placeholder.failed {
            color: var(--danger);
        }

        .video-thumb-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .video-grid-card .card-body {
            padding: 1rem 1.25rem;
        }

        .video-grid-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--navy-900);
            margin-bottom: 0.35rem;
        }

        .video-grid-card .card-text {
            font-size: 0.8rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .video-grid-card .card-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid #f1f5f9;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ===== BUTTONS ===== */
        .btn-primary-dark {
            background: linear-gradient(135deg, var(--accent), #6366f1);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.25rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-primary-dark:hover {
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-navy {
            background: var(--navy-800);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.25rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-navy:hover {
            background: var(--navy-700);
            color: #fff;
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-weight: 500;
            border-radius: 8px;
            padding: 0.55rem 1.25rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-ghost:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--navy-900);
        }

        /* ===== HERO HEADER ===== */
        .page-hero {
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 50%, #1e1b4b 100%);
            border-radius: 16px;
            padding: 2rem 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.75rem;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
        }

        .page-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: 10%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
        }

        .page-hero > * { position: relative; z-index: 1; }

        .page-hero h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .page-hero p {
            color: var(--navy-200);
            font-size: 0.9rem;
            margin: 0;
        }

        /* ===== FORMS ===== */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 0.6rem 0.9rem;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--navy-800);
            margin-bottom: 0.35rem;
        }

        /* ===== BADGE ===== */
        .badge-status {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            letter-spacing: 0.02em;
        }

        .badge-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: var(--accent-soft);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1.25rem;
        }

        .empty-state h5 {
            font-weight: 700;
            color: var(--navy-900);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #94a3b8;
            max-width: 360px;
            margin: 0 auto 1.5rem;
            font-size: 0.9rem;
        }

        /* ===== SPINNER ===== */
        .spinner-accent {
            border-color: var(--accent);
            border-right-color: transparent;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }

        /* ===== TABLE ===== */
        .table-detail td {
            padding: 0.6rem 0;
            vertical-align: middle;
            font-size: 0.85rem;
        }

        .table-detail td:first-child {
            color: #94a3b8;
            font-weight: 500;
            width: 130px;
        }

        .table-detail td:last-child {
            color: var(--navy-900);
            font-weight: 600;
        }

        /* ===== FOOTER ===== */
        .main-footer {
            padding: 1rem 1.75rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.75rem;
            color: #94a3b8;
            text-align: center;
        }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1035;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .topbar-toggle {
                display: block;
            }

            .content-area {
                padding: 1.25rem;
            }
        }

        @media (max-width: 575.98px) {
            .content-area {
                padding: 1rem;
            }

            .page-hero {
                padding: 1.5rem;
            }

            .page-hero h1 {
                font-size: 1.25rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Sidebar Overlay (mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-camera-reels"></i>
            </div>
            <div class="sidebar-brand-text">
                AI Video Creator
                <small>Powered by Google Veo</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu chính</div>

            <div class="nav-item-sidebar">
                <a href="{{ route('videos.index') }}" class="nav-link-sidebar {{ request()->routeIs('videos.index') || request()->routeIs('home') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-item-sidebar">
                <a href="{{ route('videos.create') }}" class="nav-link-sidebar {{ request()->routeIs('videos.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-square-fill"></i>
                    <span>Tạo Video</span>
                </a>
            </div>

            <div class="sidebar-label">Hệ thống</div>

            <div class="nav-item-sidebar">
                <a href="{{ route('settings.index') }}" class="nav-link-sidebar {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>Cài đặt</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-cta">
                <p class="mb-2">Tạo video chuyên nghiệp chỉ với một đoạn mô tả</p>
                <a href="{{ route('videos.create') }}" class="btn-cta">
                    <i class="bi bi-plus-lg"></i> Tạo Video Mới
                </a>
            </div>
        </div>
    </aside>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">
        {{-- Top Bar --}}
        <header class="topbar">
            <button class="topbar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                <div class="topbar-breadcrumb">
                    @yield('breadcrumb', '<a href="' . route('home') . '">Trang chủ</a>')
                </div>
            </div>
        </header>

        {{-- Toast notifications --}}
        <div class="toast-container">
            @if(session('success'))
                <div class="toast align-items-center text-bg-success border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="5000">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="toast align-items-center text-bg-danger border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="8000">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Content --}}
        <main class="content-area">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="main-footer">
            &copy; {{ date('Y') }} AI Video Creator &mdash; Powered by Google AI Studio (Veo 3.1)
        </footer>
    </div>

    {{-- Bootstrap 5.3 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss toasts
            document.querySelectorAll('.toast.show').forEach(function(toast) {
                new bootstrap.Toast(toast).show();
            });

            // Sidebar toggle (mobile)
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (toggle) {
                toggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>

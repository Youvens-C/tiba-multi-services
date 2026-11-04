<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tableau de bord') - Tiba Multi-Services</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-tables.css') }}">
    @stack('styles')
</head>
<body>

    @php $user = auth()->user(); @endphp

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('images/logo_tiba.jpg') }}" alt="Tiba Multi-Services">
            <span>Tiba Multi-Services</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Tableau de bord">
                <i class="bi bi-speedometer2"></i><span>Tableau de bord</span>
            </a>
            <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" title="Transactions">
                <i class="bi bi-arrow-left-right"></i><span>Transactions</span>
            </a>
            <a href="{{ route('accessories.index') }}" class="nav-link {{ request()->routeIs('accessories.index') ? 'active' : '' }}" title="Accessoires">
                <i class="bi bi-phone"></i><span>Accessoires</span>
            </a>

            @if($user->role === 'directeur')
                <a href="{{ route('accessories.catalog') }}" class="nav-link {{ request()->routeIs('accessories.catalog') ? 'active' : '' }}" title="Catalogue">
                    <i class="bi bi-box-seam"></i><span>Catalogue</span>
                </a>
                <a href="{{ route('debts.index') }}" class="nav-link {{ request()->routeIs('debts.*') ? 'active' : '' }}" title="Dettes">
                    <i class="bi bi-cash-coin"></i><span>Dettes</span>
                </a>
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Rapports">
                    <i class="bi bi-file-earmark-pdf"></i><span>Rapports</span>
                </a>
                <a href="{{ route('admin.audit') }}" class="nav-link {{ request()->routeIs('admin.audit*') ? 'active' : '' }}" title="Journal d'audit">
                    <i class="bi bi-shield-check"></i><span>Journal d'audit</span>
                </a>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="Utilisateurs">
                    <i class="bi bi-people"></i><span>Utilisateurs</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div class="sidebar-user-info">
                    <div class="name">{{ $user->name }}</div>
                    <div class="role">{{ $user->role === 'directeur' ? 'Directeur' : 'Employé' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout-btn" title="Se déconnecter">
                    <i class="bi bi-box-arrow-right"></i><span>Se déconnecter</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-main" id="appMain">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Ouvrir ou fermer le menu">
                    <i class="bi bi-list" style="font-size:1.2rem;"></i>
                </button>
                <h1>@yield('title', 'Tableau de bord')</h1>
            </div>

            <div class="topbar-user">
                <div class="text-end d-none d-sm-block">
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="role-badge">{{ $user->role === 'directeur' ? 'Directeur' : 'Employé' }}</div>
                </div>
                <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            </div>
        </header>

        <main class="page-content">
            @yield('content')
        </main>
    </div>

    {{-- Fenêtre de confirmation réutilisable (remplace confirm() natif du navigateur) --}}
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <div class="modal-body text-center pt-4 pb-3 px-4">
                    <div class="mb-3" style="width:52px;height:52px;border-radius:50%;background:var(--danger-light);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="bi bi-exclamation-triangle-fill" style="color:var(--danger);font-size:1.4rem;"></i>
                    </div>
                    <h5 id="confirmActionTitle" class="mb-2"></h5>
                    <p id="confirmActionMessage" class="text-muted mb-0" style="font-size:0.9rem;"></p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-center gap-2">
                    <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Retour</button>
                    <button type="button" class="btn-tiba-danger" id="confirmActionButton" style="padding:0.55rem 1.4rem;">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.toasts')
        <script src="{{ asset('js/responsive-tables.js') }}"></script>
        <script src="{{ asset('js/custom-select.js') }}"></script>
        <script src="{{ asset('js/live-refresh.js') }}"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const appMain = document.getElementById('appMain');
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        const isMobile = () => window.innerWidth <= 900;

        function applyState() {
            const hidden = localStorage.getItem('tiba_sidebar_hidden') === '1';
            if (isMobile()) {
                // Sur mobile, le sidebar est toujours masqué par défaut (overlay),
                // indépendamment de l'état mémorisé côté ordinateur.
                sidebar.classList.remove('hidden');
                appMain.classList.remove('full');
            } else {
                sidebar.classList.toggle('hidden', hidden);
                appMain.classList.toggle('full', hidden);
            }
        }
        applyState();

        function openMobileSidebar() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('show');
        }
        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        }

        toggleBtn.addEventListener('click', () => {
            if (isMobile()) {
                sidebar.classList.contains('mobile-open') ? closeMobileSidebar() : openMobileSidebar();
            } else {
                const hidden = sidebar.classList.toggle('hidden');
                appMain.classList.toggle('full', hidden);
                localStorage.setItem('tiba_sidebar_hidden', hidden ? '1' : '0');
            }
        });

        // Clic en dehors (sur le fond assombri) → fermeture
        overlay.addEventListener('click', closeMobileSidebar);

        // Clic sur un lien ou le bouton de déconnexion → fermeture (mobile uniquement)
        sidebar.querySelectorAll('.nav-link, .sidebar-logout-btn').forEach(el => {
            el.addEventListener('click', () => { if (isMobile()) closeMobileSidebar(); });
        });

        // Recalculer l'état à chaque changement de taille (dans les deux sens)
        window.addEventListener('resize', () => {
            closeMobileSidebar();
            applyState();
        });
        // Fenêtre de confirmation personnalisée — remplace confirm() natif.
        // Usage : confirmAction(form, { title, message, confirmLabel })
        function confirmAction(form, { title = 'Confirmer', message = 'Êtes-vous sûr ?', confirmLabel = 'Confirmer' } = {}) {
            const modalEl = document.getElementById('confirmActionModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            document.getElementById('confirmActionTitle').textContent = title;
            document.getElementById('confirmActionMessage').textContent = message;
            const btn = document.getElementById('confirmActionButton');
            btn.textContent = confirmLabel;
            const handler = () => { modal.hide(); form.submit(); btn.removeEventListener('click', handler); };
            btn.addEventListener('click', handler);
            modal.show();
        }
    </script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Tiba Multi-Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-brand">
                <img src="{{ asset('images/logo_tiba.jpg') }}" alt="Tiba Multi-Services">
                <h2>Tiba Multi-Services</h2>
                <p>Moncash, Natcash, Zelle et accessoires téléphoniques - toutes vos opérations du jour, au même endroit.</p>
            </div>

            <div class="login-form-side">
                <div class="login-form-wrap">
                    <h1>Connexion</h1>
                    <p class="subtitle">Bienvenue ! Veuillez entrer vos identifiants.</p>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">Identifiant</label>
                            <input type="text" name="username" id="username" class="form-control w-100"
                                   value="{{ old('username') }}" autofocus required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="password-field">
                                <input type="password" name="password" id="password" class="form-control w-100" required>
                                <button type="button" class="password-toggle" id="togglePassword" tabindex="-1" aria-label="Afficher le mot de passe">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-tiba-primary w-100">Se connecter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const pwd = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        const icon = document.getElementById('toggleIcon');
        toggle.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        });
    </script>

    @include('partials.toasts')
</body>
</html>

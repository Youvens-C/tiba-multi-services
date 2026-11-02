<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('heading') - Tiba Multi-Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .error-card {
            width: 100%; max-width: 460px; background: var(--white);
            border-radius: 22px; box-shadow: 0 20px 50px rgba(10, 20, 28, 0.35);
            padding: 2.5rem 2rem; text-align: center;
        }
        .error-card img { width: 84px; margin-bottom: 1rem; }
        .error-code { font-family: var(--font-heading); font-size: 4rem; font-weight: 700; line-height: 1; color: var(--green); }
        .error-card h1 { font-size: 1.35rem; margin: .75rem 0 .5rem; }
        .error-card p { color: var(--text-muted); font-size: .92rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="error-card">
            <img src="{{ asset('images/logo_tiba.jpg') }}" alt="Tiba Multi-Services">
            <div class="error-code">@yield('code')</div>
            <h1>@yield('heading')</h1>
            <p>@yield('message')</p>
            @yield('actions')
        </div>
    </div>
</body>
</html>

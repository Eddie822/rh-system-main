<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de autorización de horas extras y descansos laborados de Silgan.">
    <title>Recursos Humanos | Silgan</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #30343b; background: #fff; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        a { color: #224f83; }
        a:focus-visible { outline: 3px solid #224f83; outline-offset: 5px; }
        .container { width: min(1000px, calc(100% - 48px)); margin-inline: auto; }
        header { border-top: 5px solid #224f83; border-bottom: 1px solid #e2e2e2; }
        .header-content { min-height: 100px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .logo { display: block; width: 200px; max-width: 100%; height: auto; }
        .department { font-size: 14px; color: #61666d; }
        main { flex: 1; padding-block: 90px 70px; }
        .welcome { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 80px; align-items: start; }
        .section-label { margin: 0 0 18px; color: #61666d; font-size: 14px; }
        h1 { margin: 0 0 24px; font-size: clamp(28px, 3.5vw, 38px); line-height: 1.25; font-weight: 600; }
        .description { margin: 0; max-width: 540px; color: #61666d; font-size: 16px; line-height: 1.75; }
        .access { border-left: 1px solid #ddd; padding-left: 28px; }
        .access h2 { margin: 0 0 16px; font-size: 18px; font-weight: 600; }
        .access p { margin: 0 0 22px; color: #61666d; font-size: 14px; line-height: 1.65; }
        .button { display: inline-block; padding: 13px 24px; background: #224f83; color: #fff; border-radius: 3px; font-size: 14px; text-decoration: none; }
        .button:hover { background: #193c64; }
        .recovery { display: block; width: fit-content; margin-top: 18px; font-size: 13px; text-underline-offset: 3px; }
        .information { margin-top: 70px; border-top: 1px solid #ddd; padding-top: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
        .information h2 { margin: 0 0 10px; font-size: 14px; font-weight: 600; }
        .information p { margin: 0; color: #61666d; font-size: 13px; line-height: 1.75; }
        footer { background: #f5f5f5; border-top: 1px solid #e2e2e2; }
        .footer-content { display: flex; justify-content: space-between; gap: 16px; padding-block: 20px; font-size: 12px; color: #73777d; }
        @media (max-width: 760px) {
            main { padding-block: 48px; }
            .welcome { grid-template-columns: 1fr; gap: 36px; }
            .access { border-left: 0; border-top: 1px solid #ddd; padding: 24px 0 0; }
            .information { margin-top: 40px; gap: 28px; }
        }
        @media (max-width: 480px) {
            .container { width: calc(100% - 32px); }
            .header-content { min-height: 88px; gap: 16px; }
            .logo { width: 155px; }
            .department { font-size: 12px; text-align: right; }
            .information { grid-template-columns: 1fr; }
            .footer-content { flex-direction: column; gap: 8px; }
        }
    </style>
</head>
<body>
    @php
        $portalUrl = auth()->check()
            ? route(auth()->user()->canAccessAdminPanel() ? 'admin.dashboard' : 'request')
            : route('login');
    @endphp

    <header>
        <div class="container header-content">
            <a href="{{ url('/') }}" aria-label="Silgan · Inicio">
                <img class="logo" src="{{ asset('img/silgan-dispensing.png') }}" alt="Silgan Dispensing" width="250" height="82">
            </a>
            <span class="department">Recursos Humanos</span>
        </div>
    </header>

    <main class="container">
        <div class="welcome">
            <section aria-labelledby="welcome-title">
                <p class="section-label">Sistema de solicitudes</p>
                <h1 id="welcome-title">Autorización de horas extras<br>y descansos laborados</h1>
                <p class="description">Registra tus solicitudes de tiempo extra y consulta su estado. Los responsables de cada área pueden ingresar para revisar y autorizar las solicitudes de su personal.</p>
            </section>

            <section class="access" aria-labelledby="access-title">
                <h2 id="access-title">Acceso al sistema</h2>
                @guest
                    <p>Ingresa con tu número de nómina y contraseña.</p>
                    <a class="button" href="{{ $portalUrl }}">Iniciar sesión</a>
                    @if (Route::has('password.request'))
                        <a class="recovery" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                    @endif
                @else
                    <p>Bienvenido, {{ auth()->user()->name }}.</p>
                    <a class="button" href="{{ $portalUrl }}">Entrar al sistema</a>
                @endguest
            </section>
        </div>

        <div class="information">
            <section aria-labelledby="requests-title">
                <h2 id="requests-title">Consulta de solicitudes</h2>
                <p>En la sección Mis solicitudes puedes revisar las fechas, las horas registradas y el estado de autorización.</p>
            </section>
            <section aria-labelledby="help-title">
                <h2 id="help-title">¿Necesitas ayuda para ingresar?</h2>
                <p>Si no cuentas con tus datos de acceso, comunícate con Recursos Humanos.</p>
            </section>
        </div>
    </main>

    <footer>
        <div class="container footer-content">
            <span>Silgan Dispensing · Recursos Humanos</span>
            <span>Uso interno · {{ date('Y') }}</span>
        </div>
    </footer>
</body>
</html>

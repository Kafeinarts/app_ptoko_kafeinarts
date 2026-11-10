<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b4332">
    <title>@yield('title', 'Masuk') · Kafeinarts POS</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('assets/icons/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=1.0.0">
</head>
<body class="auth-body">
@yield('content')

<script>
    window.__app = { flash: { success: @json(session('swal_success')), error: @json(session('swal_error')), validation: null } };
</script>
<script src="{{ asset('assets/vendor/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/auth.js') }}?v=1.0.0"></script>
</body>
</html>

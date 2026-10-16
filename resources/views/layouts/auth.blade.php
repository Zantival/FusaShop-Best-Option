<!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | FusaShop</title>
    
    {{-- <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script> --}}
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="{{ asset('assets/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}" defer></script>
    
    {{-- <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#006c47",
                        "primary-container": "#00b67a",
                        "on-primary": "#ffffff",
                        "surface": "#fcf9f8",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f6f3f2",
                        "surface-container": "#f0eded",
                        "surface-container-highest": "#e5e2e1",
                        "on-surface": "#1b1c1c",
                        "on-surface-variant": "#3c4a41",
                        "background": "#fcf9f8",
                        "secondary-container": "#feb700",
                        "error": "#ba1a1a"
                    },
                    fontFamily: {
                        headline: ["Manrope", "sans-serif"],
                        body: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script> --}}
    
    @yield('styles')
</head>
<body class="bg-surface text-on-surface min-h-screen">
    @yield('content')
    @yield('scripts')
</body>
</html>

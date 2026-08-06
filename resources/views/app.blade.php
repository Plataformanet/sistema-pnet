<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function () {
            try {
                const storedTheme = localStorage.getItem('vueuse-color-scheme') || localStorage.getItem('color-scheme') || localStorage.getItem('theme');
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (storedTheme === 'auto' && systemDark) || (!storedTheme && systemDark)) {
                    document.documentElement.classList.add('dark');
                } else if (storedTheme === 'light') {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite('resources/css/app.css')

    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>

<body class="font-sans antialiased bg-background text-foreground">
    @inertia
</body>

</html>

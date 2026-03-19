<!-- resources/views/errors/tenant-not-found.blade.php -->
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa não encontrada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #1e293b;
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }

        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #e2e8f0;
            line-height: 1;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1rem;
            color: #0f172a;
        }

        .description {
            margin-top: 0.75rem;
            color: #64748b;
            line-height: 1.6;
        }

        .domain {
            display: inline-block;
            background: #fef2f2;
            color: #ef4444;
            padding: 0.2rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-family: monospace;
            margin: 0.25rem 0;
        }

        .btn {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 1.75rem;
            background: #3b82f6;
            color: #fff;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="code">404</div>

        <h1 class="title">Empresa não encontrada</h1>

        <p class="description">
            O endereço
            <span class="domain">{{ $domain }}</span>
            não está associado a nenhuma empresa cadastrada em nossa plataforma.
        </p>

        <a href="{{ $central_url }}" class="btn">
            Voltar para a página inicial
        </a>
    </div>
</body>

</html>

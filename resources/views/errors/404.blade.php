<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>404 - Halaman Tidak Ditemukan</title>

    @include('partials.branding-icon')

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px 16px;
            background: #f6f7fb;
            color: #111827;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .error-page {
            width: min(720px, 100%);
            text-align: center;
        }

        .error-code {
            position: relative;
            display: inline-block;
            color: #1e3fb4;
            font-size: clamp(96px, 24vw, 210px);
            font-weight: 900;
            line-height: .8;
            letter-spacing: 0;
        }

        .error-code::before {
            position: absolute;
            inset: 8px 0 0;
            content: attr(data-text);
            color: rgba(30, 63, 180, .12);
            z-index: -1;
        }

        .error-title {
            margin: 28px 0 10px;
            font-size: clamp(24px, 5vw, 34px);
            line-height: 1.2;
        }

        .error-copy {
            max-width: 560px;
            margin: 0 auto;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <main class="error-page" aria-labelledby="error-title">
        <div class="error-code" data-text="404">404</div>
        <h1 id="error-title" class="error-title">Halaman tidak ditemukan</h1>
        <p class="error-copy">
            Link assessment tidak valid, sudah tidak tersedia, atau tidak sesuai dengan brand/company yang digunakan.
            Silakan periksa kembali tautan yang diberikan.
        </p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Upload Information</title>

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

        .information-panel {
            width: min(520px, 100%);
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 40px rgba(15, 23, 42, .08);
        }

        .information-title {
            margin: 0 0 10px;
            font-size: 26px;
            line-height: 1.25;
        }

        .information-copy {
            margin: 0;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.7;
        }

        .information-note {
            margin-top: 22px;
            padding: 14px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            background: #f0fdf4;
            color: #166534;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="information-panel" aria-labelledby="information-title">
        <h1 id="information-title" class="information-title">Assessment document received</h1>
        <p class="information-copy">
            Thank you{{ $applicantName ? ', '.$applicantName : '' }}. Your assessment document has been uploaded successfully.
        </p>
        <div class="information-note">
            If you need to revise or upload the document again, please contact the recruitment team.
        </div>
    </main>
</body>
</html>

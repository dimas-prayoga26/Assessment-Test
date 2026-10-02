<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Verifikasi Pelamar</title>

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

        .verify-panel {
            width: min(440px, 100%);
            padding: 28px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 40px rgba(15, 23, 42, .08);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
            line-height: 1.25;
        }

        p {
            margin: 0 0 20px;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .email-input {
            width: 100%;
            min-height: 44px;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
        }

        .field-group {
            margin-bottom: 20px;
        }

        .pin-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .pin-input {
            width: 100%;
            aspect-ratio: 1;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
        }

        .hint,
        .error {
            display: block;
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.45;
        }

        .hint {
            color: #6b7280;
        }

        .error {
            color: #dc2626;
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            min-height: 44px;
            width: 100%;
            border: 0;
            border-radius: 8px;
            background: #1e3fb4;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <main class="verify-panel">
        @if (! $isEmailVerified)
            <h1>Verifikasi Email Pelamar</h1>
            <p>Masukkan email pelamar sebelum membuka halaman upload.</p>

            <form method="POST" action="{{ route('upload-files.verify.applicant.check') }}">
                @csrf
                <input type="hidden" name="step" value="email">

                <div class="field-group">
                    <label for="email">Email</label>
                    <input id="email" class="email-input" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus>
                    <span class="hint">Dummy: {{ $dummyEmail }}</span>

                    @error('email')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit">Lanjut</button>
            </form>
        @else
            <h1>Masukkan 4 Digit Terakhir Nomor HP</h1>
            <p>Nomor HP terdaftar: <strong>{{ $maskedPhone }}</strong></p>

            <form method="POST" action="{{ route('upload-files.verify.applicant.check') }}">
                @csrf
                <input type="hidden" name="step" value="pin">

                <div class="pin-grid" aria-label="4 digit terakhir nomor HP">
                    @for ($index = 0; $index < 4; $index++)
                        <input class="pin-input" name="pin[]" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="one-time-code" aria-label="Digit {{ $index + 1 }}">
                    @endfor
                </div>

                @error('pin')
                    <span class="error">{{ $message }}</span>
                @enderror

                @error('pin.*')
                    <span class="error">{{ $message }}</span>
                @enderror

                <button type="submit">Lanjut</button>
            </form>
        @endif
    </main>

    <script>
        document.querySelectorAll('input[name="pin[]"]').forEach((input, index, inputs) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 1);

                if (input.value && inputs[index + 1]) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && inputs[index - 1]) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>

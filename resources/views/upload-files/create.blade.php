<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Upload Assessment Document</title>

    @include('partials.branding-icon')

    <link href="{{ asset('vendor/dropzone/dropzone.min.css') }}?v=3" rel="stylesheet">

    <style>
        :root {
            --page-bg: #f6f7fb;
            --panel-bg: #ffffff;
            --panel-border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --primary: #1e3fb4;
            --primary-hover: #173391;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--page-bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .upload-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }

        .upload-panel {
            width: min(720px, 100%);
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, .08);
            padding: 30px;
        }

        .upload-title {
            margin: 0 0 8px;
            font-size: 24px;
            line-height: 1.25;
            font-weight: 700;
        }

        .upload-copy {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .upload-dropzone {
            min-height: 260px;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease;
        }

        .upload-dropzone.is-dragover {
            border-color: var(--primary);
            background: #f7f9ff;
        }

        .upload-dropzone label {
            min-height: 260px;
            cursor: pointer;
        }

        .upload-input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .upload-dropzone-content {
            min-height: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px;
            text-align: center;
        }

        .upload-file-name {
            display: block;
            margin-top: 16px;
            font-weight: 700;
        }

        .upload-hint,
        .upload-error,
        .upload-status {
            display: block;
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .upload-error {
            color: #dc2626;
        }

        .upload-status {
            margin-bottom: 18px;
            color: #15803d;
        }

        .upload-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            min-height: 44px;
            padding: 0 22px;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .upload-button:hover {
            background: var(--primary-hover);
        }
    </style>
</head>
<body>
    <main class="upload-shell">
        <section class="upload-panel" aria-labelledby="upload-title">
            <h1 id="upload-title" class="upload-title">Upload Assessment Document</h1>
            <p class="upload-copy">Upload a PDF or DOCX document. Maximum file size is 5 MB.</p>

            @if (session('status'))
                <span class="upload-status">
                    {{ session('status') }}
                    @if (session('uploaded_file'))
                        File: {{ session('uploaded_file') }}
                    @endif
                </span>
            @endif

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf

                <div id="document-dropzone" class="dropzone upload-dropzone">
                    <label for="document" class="upload-dropzone-content">
                        <input id="document" class="upload-input" name="document" type="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        <svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M27.1666 26.6667L20.4999 20L13.8333 26.6667" stroke="#DADADA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M20.5 20V35" stroke="#DADADA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M34.4833 30.6501C36.1088 29.7638 37.393 28.3615 38.1331 26.6644C38.8731 24.9673 39.027 23.0721 38.5703 21.2779C38.1136 19.4836 37.0724 17.8926 35.6111 16.7558C34.1497 15.619 32.3514 15.0013 30.4999 15.0001H28.3999C27.8955 13.0488 26.9552 11.2373 25.6498 9.70171C24.3445 8.16614 22.708 6.94647 20.8634 6.1344C19.0189 5.32233 17.0142 4.93899 15.0001 5.01319C12.9861 5.0874 11.015 5.61722 9.23523 6.56283C7.45541 7.50844 5.91312 8.84523 4.7243 10.4727C3.53549 12.1002 2.73108 13.9759 2.37157 15.959C2.01205 17.9421 2.10678 19.9809 2.64862 21.9222C3.19047 23.8634 4.16534 25.6565 5.49994 27.1667" stroke="#DADADA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M27.1666 26.6667L20.4999 20L13.8333 26.6667" stroke="#DADADA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span id="document-file-name" class="upload-file-name">Choose or drag your document here</span>
                        <span class="upload-hint">PDF and DOCX only.</span>
                    </label>
                </div>

                @error('document')
                    <span class="upload-error">{{ $message }}</span>
                @enderror
                
                <button type="submit" class="upload-button">Submit Document</button>
            </form>
        </section>
    </main>

    <script src="{{ asset('vendor/dropzone/dropzone.min.js') }}?v=3"></script>
    <script>
        if (window.Dropzone) {
            Dropzone.autoDiscover = false;
        }

        const dropzone = document.getElementById('document-dropzone');
        const fileInput = document.getElementById('document');
        const fileName = document.getElementById('document-file-name');

        const updateFileName = () => {
            fileName.textContent = fileInput.files.length > 0
                ? fileInput.files[0].name
                : 'Choose or drag your document here';
        };

        fileInput.addEventListener('change', updateFileName);

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            fileInput.files = event.dataTransfer.files;
            updateFileName();
        });
    </script>
</body>
</html>

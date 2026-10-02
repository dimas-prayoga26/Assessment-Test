<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class UploadFileController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (! session('upload_file_verified')) {
            return redirect()->route('upload-files.verify.applicant');
        }

        return view('upload-files.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! session('upload_file_verified')) {
            return redirect()->route('upload-files.verify.applicant');
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);

        /** @var UploadedFile $image */
        $image = $validated['image'];
        $path = $image->store('uploaded-images');

        return redirect()
            ->route('upload-files.create')
            ->with('status', 'File uploaded successfully.')
            ->with('uploaded_file', basename($path));
    }
}

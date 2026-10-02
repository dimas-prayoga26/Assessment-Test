<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UploadFileVerificationController extends Controller
{
    private const DUMMY_EMAIL = 'demo@example.com';

    private const DUMMY_PHONE = '081234567890';

    public function applicant(): View
    {
        return view('upload-files.verify-applicant', [
            'dummyEmail' => self::DUMMY_EMAIL,
            'isEmailVerified' => session('upload_file_email_verified', false),
            'maskedPhone' => $this->maskedPhone(),
        ]);
    }

    public function checkApplicant(Request $request): RedirectResponse
    {
        $validatedStep = $request->validate([
            'step' => ['required', 'in:email,pin'],
        ]);

        if ($validatedStep['step'] === 'email') {
            return $this->checkEmail($request);
        }

        return $this->checkPin($request);
    }

    private function checkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (Str::lower($validated['email']) !== self::DUMMY_EMAIL) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email tidak cocok dengan data dummy.']);
        }

        $request->session()->put('upload_file_email_verified', true);
        $request->session()->forget('upload_file_verified');

        return redirect()->route('upload-files.verify.applicant');
    }

    private function checkPin(Request $request): RedirectResponse
    {
        if (! $request->session()->get('upload_file_email_verified')) {
            return redirect()->route('upload-files.verify.applicant');
        }

        $validated = $request->validate([
            'pin' => ['required', 'array', 'size:4'],
            'pin.*' => ['required', 'digits:1'],
        ]);

        $pin = implode('', $validated['pin']);

        if ($pin !== substr(self::DUMMY_PHONE, -4)) {
            return back()->withErrors(['pin' => '4 digit terakhir nomor HP tidak cocok.']);
        }

        $request->session()->put('upload_file_verified', true);
        $request->session()->forget('upload_file_email_verified');

        return redirect()->route('upload-files.create');
    }

    private function maskedPhone(): string
    {
        return substr(self::DUMMY_PHONE, 0, 4).' '.substr(self::DUMMY_PHONE, 4, -4).' ****';
    }
}

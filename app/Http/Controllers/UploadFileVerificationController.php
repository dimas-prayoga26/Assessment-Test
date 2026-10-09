<?php

namespace App\Http\Controllers;

use App\Support\AssessmentUploadAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadFileVerificationController extends Controller
{
    public function __construct(private AssessmentUploadAccess $assessmentUploadAccess) {}

    public function applicant(Request $request, string $applicant): View
    {
        $applicantRecord = $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        return view('upload-files.verify-applicant', [
            'applicantId' => $applicant,
            'applicantName' => $applicantRecord?->full_name,
            'formAction' => $this->verificationCheckRoute($applicant),
            'isEmailVerified' => $this->assessmentUploadAccess->isEmailVerified($request, $applicant),
            'maskedPhone' => $this->assessmentUploadAccess->maskedPhone($applicantRecord),
        ]);
    }

    public function checkApplicant(Request $request, string $applicant): RedirectResponse
    {
        $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        $validatedStep = $request->validate([
            'step' => ['required', 'in:email,pin'],
        ]);

        if ($validatedStep['step'] === 'email') {
            return $this->checkEmail($request, $applicant);
        }

        return $this->checkPin($request, $applicant);
    }

    private function checkEmail(Request $request, string $applicant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $applicantRecord = $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        if (! $this->assessmentUploadAccess->emailMatches($applicantRecord, $validated['email'])) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email tidak cocok dengan data pelamar.']);
        }

        $this->assessmentUploadAccess->markEmailVerified($request, $applicant);

        return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
    }

    private function checkPin(Request $request, string $applicant): RedirectResponse
    {
        if (! $this->assessmentUploadAccess->isEmailVerified($request, $applicant)) {
            return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
        }

        $validated = $this->validatePin($request);
        $applicantRecord = $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        if (! $this->assessmentUploadAccess->phonePinMatches($applicantRecord, implode('', $validated['pin']))) {
            return back()->withErrors(['pin' => '4 digit terakhir nomor HP tidak cocok.']);
        }

        $this->assessmentUploadAccess->markUploadVerified($request, $applicant);

        return redirect()->route('upload-files.generated.create', ['applicant' => $applicant]);
    }

    /**
     * @return array{pin: array<int, string>}
     */
    private function validatePin(Request $request): array
    {
        return $request->validate([
            'pin' => ['required', 'array', 'size:4'],
            'pin.*' => ['required', 'digits:1'],
        ]);
    }

    private function verificationCheckRoute(string $applicant): string
    {
        return route('upload-files.verify.applicant.generated.check', ['applicant' => $applicant]);
    }
}

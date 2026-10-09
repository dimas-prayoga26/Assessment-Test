<?php

namespace App\Http\Controllers;

use App\Support\AssessmentUploadAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class UploadFileController extends Controller
{
    public function __construct(private AssessmentUploadAccess $assessmentUploadAccess) {}

    public function create(Request $request, string $applicant): RedirectResponse|View
    {
        $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        if (! $this->assessmentUploadAccess->isUploadVerified($request, $applicant)) {
            return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
        }

        return view('upload-files.create', [
            'formAction' => route('upload-files.generated.store', ['applicant' => $applicant]),
        ]);
    }

    public function store(Request $request, string $applicant): RedirectResponse
    {
        $this->assessmentUploadAccess->applicantForRequest($applicant, $request);

        if (! $this->assessmentUploadAccess->isUploadVerified($request, $applicant)) {
            return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
        }

        $image = $this->validatedImage($request);
        $uploadedFile = $this->assessmentUploadAccess->storeAssessmentDocument($applicant, $image);

        return redirect()
            ->route('upload-files.generated.create', ['applicant' => $applicant])
            ->with('status', 'File uploaded successfully.')
            ->with('uploaded_file', $uploadedFile);
    }

    private function validatedImage(Request $request): UploadedFile
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);

        /** @var UploadedFile $image */
        $image = $validated['image'];

        return $image;
    }
}

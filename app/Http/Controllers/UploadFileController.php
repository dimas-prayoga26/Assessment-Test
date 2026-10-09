<?php

namespace App\Http\Controllers;

use App\Rules\SafeAssessmentDocument;
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
        $applicantRecord = $this->assessmentUploadAccess->applicantForRequest($applicant, $request, allowCompletedUpload: true);

        if ($this->assessmentUploadAccess->hasAssessmentDocument($applicant)) {
            return view('upload-files.information', [
                'applicantName' => $applicantRecord->full_name ?? null,
            ]);
        }

        if (! $this->assessmentUploadAccess->isUploadVerified($request, $applicant)) {
            return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
        }

        return view('upload-files.create', [
            'formAction' => route('upload-files.generated.store', ['applicant' => $applicant]),
        ]);
    }

    public function store(Request $request, string $applicant): RedirectResponse
    {
        $this->assessmentUploadAccess->applicantForRequest($applicant, $request, allowCompletedUpload: true);

        if ($this->assessmentUploadAccess->hasAssessmentDocument($applicant)) {
            return redirect()->route('upload-files.generated.create', ['applicant' => $applicant]);
        }

        if (! $this->assessmentUploadAccess->isUploadVerified($request, $applicant)) {
            return redirect()->route('upload-files.verify.applicant.generated', ['applicant' => $applicant]);
        }

        $document = $this->validatedDocument($request);
        $uploadedFile = $this->assessmentUploadAccess->storeAssessmentDocument($applicant, $document, $request);

        return redirect()
            ->route('upload-files.generated.create', ['applicant' => $applicant])
            ->with('status', 'File uploaded successfully.')
            ->with('uploaded_file', $uploadedFile);
    }

    private function validatedDocument(Request $request): UploadedFile
    {
        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'mimes:pdf,docx',
                'extensions:pdf,docx',
                'max:5120',
                new SafeAssessmentDocument,
            ],
        ], [
            'document.required' => 'Please upload your assessment document.',
            'document.file' => 'Please upload a valid assessment document.',
            'document.mimes' => 'The document must be a PDF or DOCX file.',
            'document.extensions' => 'The document file name must end in .pdf or .docx.',
            'document.max' => 'The document may not be greater than 5 MB.',
        ]);

        /** @var UploadedFile $document */
        $document = $validated['document'];

        return $document;
    }
}

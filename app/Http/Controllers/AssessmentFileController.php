<?php

namespace App\Http\Controllers;

use App\Support\AssessmentUploadAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentFileController extends Controller
{
    public function __construct(private AssessmentUploadAccess $assessmentUploadAccess) {}

    public function show(Request $request, string $applicant): StreamedResponse|BinaryFileResponse
    {
        $document = $this->assessmentUploadAccess->assessmentDocumentForRequest($applicant, $request);
        $filePath = $this->safeRelativePath((string) $document->file_path);
        $fileName = basename((string) ($document->original_name ?: $filePath));
        $headers = [
            'Content-Type' => (string) ($document->mime_type ?: 'application/octet-stream'),
        ];
        $disk = (string) config('assessment_upload.storage_disk', 'public');

        if (Storage::disk($disk)->exists($filePath)) {
            return Storage::disk($disk)->response($filePath, $fileName, $headers);
        }

        $publicFilePath = public_path($filePath);

        abort_unless(File::exists($publicFilePath), 404);

        return response()->file($publicFilePath, $headers);
    }

    private function safeRelativePath(string $filePath): string
    {
        $filePath = ltrim(str_replace('\\', '/', trim($filePath)), '/');

        abort_if(
            $filePath === ''
            || $filePath === '..'
            || Str::contains($filePath, '../')
            || preg_match('/^[A-Za-z]:/', $filePath) === 1,
            404,
        );

        return $filePath;
    }
}

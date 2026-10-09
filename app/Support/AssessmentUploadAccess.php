<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssessmentUploadAccess
{
    private const APPLICANTS_TABLE = 'applicants';

    private const UPLOAD_REQUESTS_TABLE = 'applicant_upload_requests';

    private const DOCUMENTS_TABLE = 'applicant_documents';

    private const EMAIL_VERIFIED_SESSION_KEY = 'upload_file_email_verified_applicant_id';

    private const UPLOAD_VERIFIED_SESSION_KEY = 'upload_file_verified_applicant_id';

    public function applicantForRequest(string $applicantId, Request $request): object
    {
        $applicant = $this->applicantRecord($applicantId);

        abort_if($applicant === null, 404);
        abort_unless($this->hostMatchesApplicantBrand($request->getHost(), $applicant), 404);
        abort_if($this->activeUploadRequest($applicantId) === null, 404);

        return $applicant;
    }

    public function isEmailVerified(Request $request, string $applicantId): bool
    {
        return (string) $request->session()->get(self::EMAIL_VERIFIED_SESSION_KEY) === $applicantId;
    }

    public function isUploadVerified(Request $request, string $applicantId): bool
    {
        return (string) $request->session()->get(self::UPLOAD_VERIFIED_SESSION_KEY) === $applicantId;
    }

    public function markEmailVerified(Request $request, string $applicantId): void
    {
        $request->session()->put(self::EMAIL_VERIFIED_SESSION_KEY, $applicantId);
        $request->session()->forget(self::UPLOAD_VERIFIED_SESSION_KEY);
    }

    public function markUploadVerified(Request $request, string $applicantId): void
    {
        $request->session()->put(self::UPLOAD_VERIFIED_SESSION_KEY, $applicantId);
        $request->session()->forget(self::EMAIL_VERIFIED_SESSION_KEY);
    }

    public function emailMatches(object $applicant, string $email): bool
    {
        return Str::lower(trim((string) $applicant->email)) === Str::lower(trim($email));
    }

    public function phonePinMatches(object $applicant, string $pin): bool
    {
        return $this->lastPhoneDigits($applicant) !== ''
            && $this->lastPhoneDigits($applicant) === $pin;
    }

    public function maskedPhone(object $applicant): string
    {
        $digits = $this->phoneDigits($applicant);

        if (Str::length($digits) <= 4) {
            return '****';
        }

        return trim(substr($digits, 0, 4).' '.substr($digits, 4, -4).' ****');
    }

    public function storeAssessmentDocument(string $applicantId, UploadedFile $image): string
    {
        $uploadRequest = $this->activeUploadRequest($applicantId);

        abort_if($uploadRequest === null, 404);

        $path = $image->store((string) config('assessment_upload.storage_directory', 'uploaded-images'));
        $documentType = (string) config('assessment_upload.document_type', 'assessment_test');
        $now = now();

        $this->connection()->transaction(function () use ($applicantId, $documentType, $image, $now, $path, $uploadRequest): void {
            $existingDocumentId = $this->connection()
                ->table(self::DOCUMENTS_TABLE)
                ->where('applicant_id', $applicantId)
                ->where('document_type', $documentType)
                ->value('id');

            $document = [
                'applicant_id' => $applicantId,
                'applicant_upload_request_id' => $uploadRequest->id,
                'document_type' => $documentType,
                'file_path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'mime_type' => $image->getMimeType(),
                'file_size' => $image->getSize(),
                'uploaded_at' => $now,
                'updated_at' => $now,
            ];

            if (is_string($existingDocumentId) && $existingDocumentId !== '') {
                $this->connection()
                    ->table(self::DOCUMENTS_TABLE)
                    ->where('id', $existingDocumentId)
                    ->update($document);
            } else {
                $this->connection()
                    ->table(self::DOCUMENTS_TABLE)
                    ->insert([
                        ...$document,
                        'id' => (string) Str::uuid(),
                        'created_at' => $now,
                    ]);
            }
        });

        return basename($path);
    }

    private function applicantRecord(string $applicantId): ?object
    {
        return $this->connection()
            ->table(self::APPLICANTS_TABLE)
            ->select(['id', 'full_name', 'email', 'phone', 'brand_key'])
            ->where('id', $applicantId)
            ->whereNull('deleted_at')
            ->first();
    }

    private function activeUploadRequest(string $applicantId): ?object
    {
        return $this->connection()
            ->table(self::UPLOAD_REQUESTS_TABLE)
            ->select(['id'])
            ->where('applicant_id', $applicantId)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    private function hostMatchesApplicantBrand(string $host, object $applicant): bool
    {
        $hostBrand = config('assessment_upload.host_brands.'.Str::lower($host));

        if (! is_string($hostBrand) || $hostBrand === '') {
            return false;
        }

        return $hostBrand === $this->brandKeyFor($applicant);
    }

    private function brandKeyFor(object $applicant): string
    {
        $brandKey = Str::lower(trim((string) $applicant->brand_key));
        $knownBrandKeys = array_values((array) config('assessment_upload.host_brands', []));

        if ($brandKey !== '' && in_array($brandKey, $knownBrandKeys, true)) {
            return $brandKey;
        }

        return (string) config('assessment_upload.default_brand', 'rnb');
    }

    private function lastPhoneDigits(object $applicant): string
    {
        $digits = $this->phoneDigits($applicant);

        if (Str::length($digits) < 4) {
            return '';
        }

        return substr($digits, -4);
    }

    private function phoneDigits(object $applicant): string
    {
        return preg_replace('/\D+/', '', (string) $applicant->phone) ?? '';
    }

    private function connection(): ConnectionInterface
    {
        return DB::connection((string) config('assessment_upload.connection', 'tms'));
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use ZipArchive;

class SafeAssessmentDocument implements ValidationRule
{
    private const SAFE_DOCUMENT_NAME_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9 ._\-()]{0,199}\.(pdf|docx|jpe?g|png|webp|zip|rar)\z/i';

    private const UNSAFE_PDF_PATTERN = '/\/(?:JavaScript|JS|OpenAction|AA|Launch|EmbeddedFile|RichMedia|SubmitForm|ImportData)\b|<script\b|<\?php/i';

    private const UNSAFE_IMAGE_PATTERN = '/<script\b|<\?php/i';

    private const UNSAFE_ARCHIVE_ENTRY_PATTERN = '/\.(?:exe|dll|com|scr|msi|bat|cmd|vbs|vbe|ps1|hta|lnk)\z/i';

    /**
     * Shorter input makes getimagesize() emit a read warning instead of returning false.
     */
    private const MIN_IMAGE_BYTES = 12;

    private const MAX_ARCHIVE_ENTRIES = 1000;

    private const MAX_ARCHIVE_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;

    /**
     * @var array<string, int>
     */
    private const IMAGE_TYPES_BY_EXTENSION = [
        'jpg' => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'webp' => IMAGETYPE_WEBP,
    ];

    /**
     * @var array<int, string>
     */
    private const RAR_SIGNATURES = [
        "Rar!\x1A\x07\x00",
        "Rar!\x1A\x07\x01\x00",
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('Please upload a valid assessment document.');

            return;
        }

        if (! $this->hasSafeDocumentName($value)) {
            $fail('Use a simple file name ending in .pdf, .docx, .jpg, .jpeg, .png, .webp, .zip, or .rar. Letters, numbers, spaces, dots, dashes, underscores, and parentheses are allowed.');

            return;
        }

        if (! $this->hasSafeDocumentContent($value)) {
            $fail('The file is damaged, does not match its extension, or contains active content or executable files. Upload a clean PDF, DOCX, JPG, PNG, WEBP, ZIP, or RAR file.');
        }
    }

    private function hasSafeDocumentName(UploadedFile $document): bool
    {
        $fileName = $document->getClientOriginalName();
        $normalizedFileName = str_replace('\\', '/', $fileName);

        return $fileName === basename($normalizedFileName)
            && ! Str::contains($fileName, '..')
            && preg_match('/[\x00-\x1F\x7F]/', $fileName) !== 1
            && preg_match(self::SAFE_DOCUMENT_NAME_PATTERN, $fileName) === 1;
    }

    private function hasSafeDocumentContent(UploadedFile $document): bool
    {
        $extension = Str::lower($document->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->hasSafePdfContent($document),
            'docx' => $this->hasSafeDocxContent($document),
            'jpg', 'jpeg', 'png', 'webp' => $this->hasSafeImageContent($document, self::IMAGE_TYPES_BY_EXTENSION[$extension]),
            'zip' => $this->hasSafeZipContent($document),
            'rar' => $this->hasRarSignature($document),
            default => false,
        };
    }

    private function hasSafePdfContent(UploadedFile $document): bool
    {
        $path = $document->getRealPath();

        if ($path === false) {
            return false;
        }

        $contents = file_get_contents($path);

        return is_string($contents)
            && str_starts_with($contents, '%PDF-')
            && preg_match(self::UNSAFE_PDF_PATTERN, $contents) !== 1;
    }

    private function hasSafeDocxContent(UploadedFile $document): bool
    {
        $path = $document->getRealPath();

        if ($path === false) {
            return false;
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            return $this->docxArchiveIsSafe($zip);
        } finally {
            $zip->close();
        }
    }

    private function docxArchiveIsSafe(ZipArchive $zip): bool
    {
        if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('word/document.xml') === false) {
            return false;
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (! is_string($entryName) || ! $this->docxEntryIsSafe($zip, $entryName)) {
                return false;
            }
        }

        return true;
    }

    private function docxEntryIsSafe(ZipArchive $zip, string $entryName): bool
    {
        $normalizedEntryName = Str::lower(str_replace('\\', '/', $entryName));

        if (
            Str::startsWith($normalizedEntryName, '/')
            || Str::contains($normalizedEntryName, '../')
            || preg_match('/(^|\/)(vbaproject\.bin|.*\.(?:exe|js|vbs|scr|bat|cmd|ps1))\z/i', $normalizedEntryName) === 1
            || Str::contains($normalizedEntryName, ['activex', 'embeddings', 'oleobject'])
        ) {
            return false;
        }

        if (! Str::endsWith($normalizedEntryName, ['.xml', '.rels'])) {
            return true;
        }

        $contents = $zip->getFromName($entryName);

        return is_string($contents)
            && preg_match('/targetmode=["\']external["\']|javascript:|<script\b|vbaproject|macroenabled|activex|oleobject/i', $contents) !== 1;
    }

    private function hasSafeImageContent(UploadedFile $document, int $expectedImageType): bool
    {
        $path = $document->getRealPath();

        if ($path === false) {
            return false;
        }

        $contents = file_get_contents($path);

        if (! is_string($contents) || strlen($contents) < self::MIN_IMAGE_BYTES) {
            return false;
        }

        $imageInfo = getimagesizefromstring($contents);

        return $imageInfo !== false
            && $imageInfo[2] === $expectedImageType
            && preg_match(self::UNSAFE_IMAGE_PATTERN, $contents) !== 1;
    }

    private function hasSafeZipContent(UploadedFile $document): bool
    {
        $path = $document->getRealPath();

        if ($path === false) {
            return false;
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            return $this->zipArchiveIsSafe($zip);
        } finally {
            $zip->close();
        }
    }

    private function zipArchiveIsSafe(ZipArchive $zip): bool
    {
        if ($zip->numFiles === 0 || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
            return false;
        }

        $uncompressedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);

            if ($entry === false || ! $this->zipEntryIsSafe($entry)) {
                return false;
            }

            $uncompressedBytes += $entry['size'];

            if ($uncompressedBytes > self::MAX_ARCHIVE_UNCOMPRESSED_BYTES) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{name: string, size: int, encryption_method: int}  $entry
     */
    private function zipEntryIsSafe(array $entry): bool
    {
        $normalizedEntryName = str_replace('\\', '/', $entry['name']);

        return $entry['encryption_method'] === ZipArchive::EM_NONE
            && ! Str::startsWith($normalizedEntryName, '/')
            && preg_match('/^[A-Za-z]:/', $normalizedEntryName) !== 1
            && ! in_array('..', explode('/', $normalizedEntryName), true)
            && preg_match(self::UNSAFE_ARCHIVE_ENTRY_PATTERN, $normalizedEntryName) !== 1;
    }

    private function hasRarSignature(UploadedFile $document): bool
    {
        $path = $document->getRealPath();

        if ($path === false) {
            return false;
        }

        $header = file_get_contents($path, length: 8);

        return is_string($header) && Str::startsWith($header, self::RAR_SIGNATURES);
    }
}

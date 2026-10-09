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
    private const SAFE_DOCUMENT_NAME_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9 ._\-()]{0,199}\.(pdf|docx)\z/i';

    private const UNSAFE_PDF_PATTERN = '/\/(?:JavaScript|JS|OpenAction|AA|Launch|EmbeddedFile|RichMedia|SubmitForm|ImportData)\b|<script\b|<\?php/i';

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
            $fail('Use a simple file name ending in .pdf or .docx. Letters, numbers, spaces, dots, dashes, underscores, and parentheses are allowed.');

            return;
        }

        if (! $this->hasSafeDocumentContent($value)) {
            $fail('The document contains active content or unsupported embedded objects. Upload a clean PDF or DOCX file.');
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
}

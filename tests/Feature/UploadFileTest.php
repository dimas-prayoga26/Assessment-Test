<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class UploadFileTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

    private const UNSAFE_CONTENT_MESSAGE = 'The file is damaged, does not match its extension, or contains active content or executable files. Upload a clean PDF, DOCX, JPG, PNG, WEBP, ZIP, or RAR file.';

    public function test_upload_file_page_renders(): void
    {
        $this->mockAssessmentAccessForUpload(verified: true);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->get($this->generatedUploadRoute());

        $response
            ->assertOk()
            ->assertSee('Upload Assessment Document')
            ->assertSee('PDF, DOCX, JPG, PNG, WEBP, ZIP, and RAR only.');
    }

    public function test_upload_file_page_shows_information_when_document_was_uploaded(): void
    {
        $this->mockAssessmentAccessForCompletedUpload();

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->get($this->generatedUploadRoute());

        $response
            ->assertOk()
            ->assertSee('Assessment document received')
            ->assertDontSee('For Your Information')
            ->assertDontSee('Upload Assessment Document');
    }

    public function test_upload_file_page_redirects_to_applicant_verification_when_unverified(): void
    {
        $this->mockAssessmentAccessForUpload(verified: false);

        $response = $this->get($this->generatedUploadRoute());

        $response->assertRedirect($this->generatedVerifyRoute());
    }

    public function test_pdf_document_upload_is_stored(): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'assessment.pdf');

        $document = $this->fakePdf();

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $document,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    public function test_docx_document_upload_is_stored(): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'assessment.docx');

        $document = $this->fakeDocx();

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $document,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    #[TestWith(['result.jpg'])]
    #[TestWith(['result.jpeg'])]
    #[TestWith(['result.png'])]
    #[TestWith(['result.webp'])]
    public function test_image_upload_is_stored(string $fileName): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: $fileName);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'document' => UploadedFile::fake()->image($fileName),
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    public function test_zip_archive_upload_is_stored(): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'assessment.zip');

        $archive = $this->fakeZip([
            'assessment/answers.docx' => 'answers',
            'assessment/screenshots/result.png' => 'screenshot',
        ]);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $archive,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    #[TestWith(["Rar!\x1A\x07\x00"])]
    #[TestWith(["Rar!\x1A\x07\x01\x00"])]
    public function test_rar_archive_upload_is_stored(string $signature): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'assessment.rar');

        $archive = UploadedFile::fake()->createWithContent('assessment.rar', $signature.str_repeat("\x00", 32));

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $archive,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    public function test_svg_image_upload_is_rejected(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $file = UploadedFile::fake()
            ->createWithContent('result.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')
            ->mimeType('image/svg+xml');

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $file,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors(['document' => 'The document must be a PDF, DOCX, JPG, PNG, WEBP, ZIP, or RAR file.']);

        Storage::disk('public')->assertMissing($file->hashName('uploaded-images'));
    }

    #[TestWith(['result.png'])]
    #[TestWith(['result.zip'])]
    #[TestWith(['result.rar'])]
    public function test_file_with_content_not_matching_its_extension_is_rejected(string $fileName): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $file = UploadedFile::fake()->createWithContent($fileName, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $file,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors(['document' => self::UNSAFE_CONTENT_MESSAGE]);
    }

    /**
     * @param  array<string, string>  $entries
     * @param  array<int, string>  $encryptedEntryNames
     */
    #[DataProvider('unsafeZipArchiveProvider')]
    public function test_zip_archive_with_unsafe_entry_is_rejected(array $entries, array $encryptedEntryNames): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $this->fakeZip($entries, $encryptedEntryNames),
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors(['document' => self::UNSAFE_CONTENT_MESSAGE]);
    }

    /**
     * @return array<string, array{array<string, string>, array<int, string>}>
     */
    public static function unsafeZipArchiveProvider(): array
    {
        return [
            'executable entry' => [['assessment/setup.exe' => 'binary'], []],
            'script entry' => [['assessment/run.BAT' => 'del *'], []],
            'path traversal entry' => [['../outside.txt' => 'escape'], []],
            'absolute path entry' => [['/etc/outside.txt' => 'escape'], []],
            'encrypted entry' => [['assessment/answers.txt' => 'secret'], ['assessment/answers.txt']],
            'too many entries' => [
                array_fill_keys(array_map(fn (int $number): string => "assessment/file-{$number}.txt", range(1, 1001)), 'x'),
                [],
            ],
        ];
    }

    public function test_text_file_upload_is_rejected(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $file = UploadedFile::fake()
            ->createWithContent('notes.txt', 'plain text')
            ->mimeType('text/plain');

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $file,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors('document');

        Storage::disk('public')->assertMissing($file->hashName('uploaded-images'));
    }

    public function test_pdf_with_active_content_is_rejected(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $document = $this->fakePdf('assessment.pdf', "%PDF-1.4\n/OpenAction << /S /JavaScript /JS (app.alert('x')) >>\n%%EOF");

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $document,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors('document');
    }

    public function test_docx_with_macro_content_is_rejected(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $document = $this->fakeDocx('assessment.docx', [
            'word/vbaProject.bin' => 'macro-content',
        ]);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $document,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors('document');
    }

    public function test_document_with_unsafe_original_name_is_rejected(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: true);

        $document = $this->fakePdf('assessment<script>.pdf');

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedUploadRoute())
            ->post($this->generatedUploadStoreRoute(), [
                'document' => $document,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors('document');
    }

    public function test_upload_is_rejected_when_unverified(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: false);

        $document = $this->fakePdf();

        $response = $this->post($this->generatedUploadStoreRoute(), [
            'document' => $document,
        ]);

        $response->assertRedirect($this->generatedVerifyRoute());

        Storage::disk('public')->assertMissing($document->hashName('uploaded-images'));
    }

    private function mockAssessmentAccessForUpload(bool $verified): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock) use ($verified): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isUploadVerified')->andReturn($verified);
        });
    }

    private function mockAssessmentAccessForStore(bool $verified, string $uploadedFile): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock) use ($uploadedFile, $verified): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isUploadVerified')->andReturn($verified);
            $mock->shouldReceive('storeAssessmentDocument')->andReturn($uploadedFile);
        });
    }

    private function mockAssessmentAccessForCompletedUpload(): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(true);
        });
    }

    private function applicantRecord(): object
    {
        return (object) [
            'id' => self::APPLICANT_ID,
            'full_name' => 'Assessment Applicant',
        ];
    }

    private function fakePdf(string $name = 'assessment.pdf', string $contents = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"): UploadedFile
    {
        return UploadedFile::fake()
            ->createWithContent($name, $contents)
            ->mimeType('application/pdf');
    }

    /**
     * @param  array<string, string>  $extraEntries
     */
    private function fakeDocx(string $name = 'assessment.docx', array $extraEntries = []): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'assessment-docx-');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Assessment</w:t></w:r></w:p></w:body></w:document>');

        foreach ($extraEntries as $entryName => $contents) {
            $zip->addFromString($entryName, $contents);
        }

        $zip->close();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }

    /**
     * @param  array<string, string>  $entries
     * @param  array<int, string>  $encryptedEntryNames
     */
    private function fakeZip(array $entries, array $encryptedEntryNames = [], string $name = 'assessment.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'assessment-zip-');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $entryName => $contents) {
            $zip->addFromString($entryName, $contents);
        }

        foreach ($encryptedEntryNames as $entryName) {
            $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256, 'password');
        }

        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }

    private function generatedVerifyRoute(): string
    {
        return route('upload-files.verify.applicant.generated', ['applicant' => self::APPLICANT_ID]);
    }

    private function generatedUploadRoute(): string
    {
        return route('upload-files.generated.create', ['applicant' => self::APPLICANT_ID]);
    }

    private function generatedUploadStoreRoute(): string
    {
        return route('upload-files.generated.store', ['applicant' => self::APPLICANT_ID]);
    }
}

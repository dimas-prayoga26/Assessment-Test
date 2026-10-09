<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFileTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

    public function test_upload_file_page_renders(): void
    {
        $this->mockAssessmentAccessForUpload(verified: true);

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->get($this->generatedUploadRoute());

        $response
            ->assertOk()
            ->assertSee('Upload File')
            ->assertSee('Hanya JPG dan PNG.');
    }

    public function test_upload_file_page_shows_information_when_document_was_uploaded(): void
    {
        $this->mockAssessmentAccessForCompletedUpload();

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->get($this->generatedUploadRoute());

        $response
            ->assertOk()
            ->assertSee('For Your Information')
            ->assertSee('Dokumen assessment sudah diterima')
            ->assertDontSee('Upload File');
    }

    public function test_upload_file_page_redirects_to_applicant_verification_when_unverified(): void
    {
        $this->mockAssessmentAccessForUpload(verified: false);

        $response = $this->get($this->generatedUploadRoute());

        $response->assertRedirect($this->generatedVerifyRoute());
    }

    public function test_jpg_image_upload_is_stored(): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'profile.jpg');

        $image = UploadedFile::fake()->image('profile.jpg');

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'image' => $image,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
    }

    public function test_png_image_upload_is_stored(): void
    {
        $this->mockAssessmentAccessForStore(verified: true, uploadedFile: 'profile.png');

        $image = UploadedFile::fake()->image('profile.png');

        $response = $this
            ->withSession(['upload_file_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedUploadStoreRoute(), [
                'image' => $image,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHas('status', 'File uploaded successfully.');
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
                'image' => $file,
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute())
            ->assertSessionHasErrors('image');

        Storage::disk('public')->assertMissing($file->hashName('uploaded-images'));
    }

    public function test_upload_is_rejected_when_unverified(): void
    {
        Storage::fake('local');
        $this->mockAssessmentAccessForUpload(verified: false);

        $image = UploadedFile::fake()->image('profile.jpg');

        $response = $this->post($this->generatedUploadStoreRoute(), [
            'image' => $image,
        ]);

        $response->assertRedirect($this->generatedVerifyRoute());

        Storage::disk('public')->assertMissing($image->hashName('uploaded-images'));
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

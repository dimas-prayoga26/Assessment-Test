<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentFileTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

    public function test_uploaded_assessment_image_can_be_served(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploaded-images/result.jpg', 'image-content');

        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('assessmentDocumentForRequest')
                ->once()
                ->andReturn((object) [
                    'file_path' => 'uploaded-images/result.jpg',
                    'original_name' => 'result.jpg',
                    'mime_type' => 'image/jpeg',
                ]);
        });

        $response = $this->get($this->assessmentFileRoute());

        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('content-type'));
    }

    public function test_missing_assessment_image_returns_not_found(): void
    {
        Storage::fake('public');

        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('assessmentDocumentForRequest')
                ->once()
                ->andReturn((object) [
                    'file_path' => 'uploaded-images/missing.jpg',
                    'original_name' => 'missing.jpg',
                    'mime_type' => 'image/jpeg',
                ]);
        });

        $response = $this->get($this->assessmentFileRoute());

        $response->assertNotFound();
    }

    public function test_uploaded_assessment_image_can_be_served_from_project_root_upload_directory(): void
    {
        Storage::fake('public');

        $filePath = base_path('uploaded-images/base-path-result.jpg');
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, 'image-content');

        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('assessmentDocumentForRequest')
                ->once()
                ->andReturn((object) [
                    'file_path' => 'uploaded-images/base-path-result.jpg',
                    'original_name' => 'base-path-result.jpg',
                    'mime_type' => 'image/jpeg',
                ]);
        });

        try {
            $response = $this->get($this->assessmentFileRoute());

            $response->assertOk();
            $this->assertSame('image/jpeg', $response->headers->get('content-type'));
        } finally {
            File::delete($filePath);
        }
    }

    public function test_unsafe_assessment_image_path_returns_not_found(): void
    {
        Storage::fake('public');

        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('assessmentDocumentForRequest')
                ->once()
                ->andReturn((object) [
                    'file_path' => '../secret.jpg',
                    'original_name' => 'secret.jpg',
                    'mime_type' => 'image/jpeg',
                ]);
        });

        $response = $this->get($this->assessmentFileRoute());

        $response->assertNotFound();
    }

    private function assessmentFileRoute(): string
    {
        return route('assessment-files.show', ['applicant' => self::APPLICANT_ID]);
    }
}

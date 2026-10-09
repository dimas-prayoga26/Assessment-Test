<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AssessmentUploadLinkAccessTest extends TestCase
{
    public function test_phone_and_email_verification_helpers_match_applicant_data(): void
    {
        $access = new AssessmentUploadAccess;
        $applicant = (object) [
            'email' => 'Candidate@Example.Test',
            'phone' => '0812-3456-7890',
        ];

        $this->assertTrue($access->emailMatches($applicant, 'candidate@example.test'));
        $this->assertTrue($access->phonePinMatches($applicant, '7890'));
        $this->assertSame('0812 3456 ****', $access->maskedPhone($applicant));
    }

    public function test_generated_applicant_upload_routes_and_brand_guard_are_registered(): void
    {
        $verifyRoute = Route::getRoutes()->getByName('upload-files.verify.applicant.generated');
        $verifyPostRoute = Route::getRoutes()->getByName('upload-files.verify.applicant.generated.check');
        $uploadRoute = Route::getRoutes()->getByName('upload-files.generated.create');
        $uploadPostRoute = Route::getRoutes()->getByName('upload-files.generated.store');
        $access = File::get(app_path('Support/AssessmentUploadAccess.php'));
        $verificationController = File::get(app_path('Http/Controllers/UploadFileVerificationController.php'));
        $uploadController = File::get(app_path('Http/Controllers/UploadFileController.php'));
        $verifyView = File::get(resource_path('views/upload-files/verify-applicant.blade.php'));
        $uploadView = File::get(resource_path('views/upload-files/create.blade.php'));

        $this->assertSame('{applicant}/upload-file/verify-applicant', $verifyRoute?->uri());
        $this->assertSame('{applicant}/upload-file/verify-applicant', $verifyPostRoute?->uri());
        $this->assertSame('{applicant}/upload-file', $uploadRoute?->uri());
        $this->assertSame('{applicant}/upload-file', $uploadPostRoute?->uri());
        $this->assertStringContainsString('hostMatchesApplicantBrand', $access);
        $this->assertStringContainsString("config('assessment_upload.host_brands.'", $access);
        $this->assertStringContainsString('storeAssessmentDocument', $access);
        $this->assertStringContainsString('applicant_upload_requests', $access);
        $this->assertStringContainsString('applicant_documents', $access);
        $this->assertStringContainsString('AssessmentUploadAccess', $verificationController);
        $this->assertStringContainsString('AssessmentUploadAccess', $uploadController);
        $this->assertStringContainsString('$formAction', $verifyView);
        $this->assertStringContainsString('$formAction', $uploadView);
    }
}

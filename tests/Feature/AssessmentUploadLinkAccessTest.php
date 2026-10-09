<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class AssessmentUploadLinkAccessTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

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

    public function test_applicant_lookup_uses_configured_connection_for_request_domain(): void
    {
        config([
            'assessment_upload.connection' => 'assessment',
            'assessment_upload.host_brands' => [
                'technical-test.rnb.co.id' => 'rnb',
                'technical-test.trah.co.id' => 'trah',
            ],
        ]);

        $connection = Mockery::mock(ConnectionInterface::class);
        $applicantQuery = Mockery::mock();
        $uploadRequestQuery = Mockery::mock();

        DB::shouldReceive('connection')
            ->twice()
            ->with('assessment')
            ->andReturn($connection);

        $connection->shouldReceive('table')
            ->once()
            ->with('applicants')
            ->andReturn($applicantQuery);

        $applicantQuery->shouldReceive('select')
            ->once()
            ->with(['id', 'full_name', 'email', 'phone', 'brand_key'])
            ->andReturnSelf();
        $applicantQuery->shouldReceive('where')
            ->once()
            ->with('id', self::APPLICANT_ID)
            ->andReturnSelf();
        $applicantQuery->shouldReceive('whereNull')
            ->once()
            ->with('deleted_at')
            ->andReturnSelf();
        $applicantQuery->shouldReceive('first')
            ->once()
            ->andReturn((object) [
                'id' => self::APPLICANT_ID,
                'full_name' => 'TRAH Applicant',
                'email' => 'candidate@example.test',
                'phone' => '081234567890',
                'brand_key' => 'trah',
            ]);

        $connection->shouldReceive('table')
            ->once()
            ->with('applicant_upload_requests')
            ->andReturn($uploadRequestQuery);

        $uploadRequestQuery->shouldReceive('select')
            ->once()
            ->with(['id'])
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('where')
            ->once()
            ->with('applicant_id', self::APPLICANT_ID)
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('whereNull')
            ->once()
            ->with('used_at')
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('whereNull')
            ->once()
            ->with('revoked_at')
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('where')
            ->once()
            ->with('expires_at', '>', Mockery::type(Carbon::class))
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('latest')
            ->once()
            ->with('created_at')
            ->andReturnSelf();
        $uploadRequestQuery->shouldReceive('first')
            ->once()
            ->andReturn((object) ['id' => 'request-id']);

        $request = Request::create(
            '/'.self::APPLICANT_ID.'/upload-file',
            server: ['HTTP_HOST' => 'technical-test.trah.co.id'],
        );
        $applicant = (new AssessmentUploadAccess)->applicantForRequest(self::APPLICANT_ID, $request);

        $this->assertSame('TRAH Applicant', $applicant->full_name);
    }

    public function test_generated_applicant_upload_routes_and_brand_guard_are_registered(): void
    {
        $verifyRoute = Route::getRoutes()->getByName('upload-files.verify.applicant.generated');
        $verifyPostRoute = Route::getRoutes()->getByName('upload-files.verify.applicant.generated.check');
        $uploadRoute = Route::getRoutes()->getByName('upload-files.generated.create');
        $uploadPostRoute = Route::getRoutes()->getByName('upload-files.generated.store');
        $assessmentFileRoute = Route::getRoutes()->getByName('assessment-files.show');
        $access = File::get(app_path('Support/AssessmentUploadAccess.php'));
        $verificationController = File::get(app_path('Http/Controllers/UploadFileVerificationController.php'));
        $uploadController = File::get(app_path('Http/Controllers/UploadFileController.php'));
        $assessmentFileController = File::get(app_path('Http/Controllers/AssessmentFileController.php'));
        $verifyView = File::get(resource_path('views/upload-files/verify-applicant.blade.php'));
        $uploadView = File::get(resource_path('views/upload-files/create.blade.php'));

        $this->assertSame('{applicant}/upload-file/verify-applicant', $verifyRoute?->uri());
        $this->assertSame('{applicant}/upload-file/verify-applicant', $verifyPostRoute?->uri());
        $this->assertSame('{applicant}/upload-file', $uploadRoute?->uri());
        $this->assertSame('{applicant}/upload-file', $uploadPostRoute?->uri());
        $this->assertSame('{applicant}/assessment-file', $assessmentFileRoute?->uri());
        $this->assertStringContainsString('brandKeyForHost', $access);
        $this->assertStringContainsString("config('assessment_upload.connection'", $access);
        $this->assertStringContainsString("config('assessment_upload.host_brands', [])", $access);
        $this->assertStringContainsString('storeAssessmentDocument', $access);
        $this->assertStringContainsString('assessmentDocumentForRequest', $access);
        $this->assertStringContainsString('applicant_upload_requests', $access);
        $this->assertStringContainsString('applicant_documents', $access);
        $this->assertStringContainsString('AssessmentUploadAccess', $verificationController);
        $this->assertStringContainsString('AssessmentUploadAccess', $uploadController);
        $this->assertStringContainsString('Storage::disk($disk)->response', $assessmentFileController);
        $this->assertStringContainsString('$formAction', $verifyView);
        $this->assertStringContainsString('$formAction', $uploadView);
    }
}

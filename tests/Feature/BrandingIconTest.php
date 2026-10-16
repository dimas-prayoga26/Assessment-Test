<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Tests\TestCase;

class BrandingIconTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

    public function test_branding_icon_uses_logo_for_current_host(): void
    {
        $this->mockVerifiedApplicantAccess();

        $response = $this
            ->withHeader('Host', 'assessment-test.rnb.co.id')
            ->get('http://assessment-test.rnb.co.id/'.self::APPLICANT_ID.'/upload-file/verify-applicant');

        $response
            ->assertOk()
            ->assertSee('images/Logo%20RNB.png', false);
    }

    public function test_branding_icon_does_not_brand_technical_test_host(): void
    {
        $this->mockVerifiedApplicantAccess();

        $response = $this
            ->withHeader('Host', 'technical-test.tims.co.id')
            ->get('http://technical-test.tims.co.id/'.self::APPLICANT_ID.'/upload-file/verify-applicant');

        $response
            ->assertOk()
            ->assertSee('images/images.png', false)
            ->assertDontSee('images/Logo%20TMS.png', false);
    }

    public function test_branding_icon_uses_default_logo_for_unknown_host(): void
    {
        $this->mockVerifiedApplicantAccess();

        $response = $this
            ->withHeader('Host', '127.0.0.1')
            ->get('http://127.0.0.1/'.self::APPLICANT_ID.'/upload-file/verify-applicant');

        $response
            ->assertOk()
            ->assertSee('images/images.png', false);
    }

    private function mockVerifiedApplicantAccess(): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('applicantForRequest')->andReturn((object) [
                'id' => self::APPLICANT_ID,
                'full_name' => 'Assessment Applicant',
            ]);
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isEmailVerified')->andReturn(false);
            $mock->shouldReceive('maskedPhone')->andReturn('0812 3456 ****');
        });
    }
}

<?php

namespace Tests\Feature;

use App\Support\AssessmentUploadAccess;
use Tests\TestCase;

class UploadFileVerificationTest extends TestCase
{
    private const APPLICANT_ID = '11111111-1111-8111-8111-111111111111';

    public function test_applicant_verification_page_renders_email_step(): void
    {
        $this->mockAssessmentAccessForEmailStep();

        $response = $this->get($this->generatedVerifyRoute());

        $response
            ->assertOk()
            ->assertSee('Verify Applicant Email')
            ->assertSee('Assessment Applicant')
            ->assertDontSee('demo@example.com')
            ->assertDontSee('0812 3456 ****')
            ->assertDontSee('7890');
    }

    public function test_applicant_verification_page_shows_information_when_document_was_uploaded(): void
    {
        $this->mockAssessmentAccessForCompletedUpload();

        $response = $this->get($this->generatedVerifyRoute());

        $response
            ->assertOk()
            ->assertSee('Assessment document received')
            ->assertSee('Assessment Applicant')
            ->assertDontSee('For Your Information')
            ->assertDontSee('Verify Applicant Email');
    }

    public function test_valid_email_redirects_back_to_applicant_verification_phone_step(): void
    {
        $this->mockAssessmentAccessForEmailCheck(emailMatches: true);

        $response = $this->post($this->generatedVerifyCheckRoute(), [
            'step' => 'email',
            'email' => 'candidate@example.test',
        ]);

        $response
            ->assertRedirect($this->generatedVerifyRoute());
    }

    public function test_phone_step_renders_after_email_verification(): void
    {
        $this->mockAssessmentAccessForPhoneStep();

        $response = $this
            ->withSession(['upload_file_email_verified_applicant_id' => self::APPLICANT_ID])
            ->get($this->generatedVerifyRoute());

        $response
            ->assertOk()
            ->assertSee('Enter the Last 4 Phone Digits')
            ->assertSee('0812 3456 ****')
            ->assertDontSee('Verify Applicant Email')
            ->assertDontSee('7890');
    }

    public function test_valid_phone_digits_grant_upload_access(): void
    {
        $this->mockAssessmentAccessForPinCheck(pinMatches: true);

        $response = $this
            ->withSession(['upload_file_email_verified_applicant_id' => self::APPLICANT_ID])
            ->post($this->generatedVerifyCheckRoute(), [
                'step' => 'pin',
                'pin' => ['7', '8', '9', '0'],
            ]);

        $response
            ->assertRedirect($this->generatedUploadRoute());
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->mockAssessmentAccessForEmailCheck(emailMatches: false);

        $response = $this
            ->from($this->generatedVerifyRoute())
            ->post($this->generatedVerifyCheckRoute(), [
                'step' => 'email',
                'email' => 'wrong@example.com',
            ]);

        $response
            ->assertRedirect($this->generatedVerifyRoute())
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_phone_digits_are_rejected(): void
    {
        $this->mockAssessmentAccessForPinCheck(pinMatches: false);

        $response = $this
            ->withSession(['upload_file_email_verified_applicant_id' => self::APPLICANT_ID])
            ->from($this->generatedVerifyRoute())
            ->post($this->generatedVerifyCheckRoute(), [
                'step' => 'pin',
                'pin' => ['0', '0', '0', '0'],
            ]);

        $response
            ->assertRedirect($this->generatedVerifyRoute())
            ->assertSessionHasErrors('pin');
    }

    public function test_phone_step_post_redirects_to_email_step_without_email_verification(): void
    {
        $this->mockAssessmentAccessForMissingEmailVerification();

        $response = $this->post($this->generatedVerifyCheckRoute(), [
            'step' => 'pin',
            'pin' => ['7', '8', '9', '0'],
        ]);

        $response->assertRedirect($this->generatedVerifyRoute());
    }

    private function mockAssessmentAccessForEmailStep(): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isEmailVerified')->andReturn(false);
            $mock->shouldReceive('maskedPhone')->andReturn('0812 3456 ****');
        });
    }

    private function mockAssessmentAccessForPhoneStep(): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isEmailVerified')->andReturn(true);
            $mock->shouldReceive('maskedPhone')->andReturn('0812 3456 ****');
        });
    }

    private function mockAssessmentAccessForEmailCheck(bool $emailMatches): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock) use ($emailMatches): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('emailMatches')->andReturn($emailMatches);
            $mock->shouldReceive('markEmailVerified')->zeroOrMoreTimes();
        });
    }

    private function mockAssessmentAccessForPinCheck(bool $pinMatches): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock) use ($pinMatches): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isEmailVerified')->andReturn(true);
            $mock->shouldReceive('phonePinMatches')->andReturn($pinMatches);
            $mock->shouldReceive('markUploadVerified')->zeroOrMoreTimes();
        });
    }

    private function mockAssessmentAccessForMissingEmailVerification(): void
    {
        $this->mock(AssessmentUploadAccess::class, function ($mock): void {
            $mock->shouldReceive('applicantForRequest')->andReturn($this->applicantRecord());
            $mock->shouldReceive('hasAssessmentDocument')->andReturn(false);
            $mock->shouldReceive('isEmailVerified')->andReturn(false);
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
            'email' => 'candidate@example.test',
            'phone' => '081234567890',
            'brand_key' => 'niskala',
        ];
    }

    private function generatedVerifyRoute(): string
    {
        return route('upload-files.verify.applicant.generated', ['applicant' => self::APPLICANT_ID]);
    }

    private function generatedVerifyCheckRoute(): string
    {
        return route('upload-files.verify.applicant.generated.check', ['applicant' => self::APPLICANT_ID]);
    }

    private function generatedUploadRoute(): string
    {
        return route('upload-files.generated.create', ['applicant' => self::APPLICANT_ID]);
    }
}

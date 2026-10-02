<?php

namespace Tests\Feature;

use Tests\TestCase;

class UploadFileVerificationTest extends TestCase
{
    public function test_applicant_verification_page_renders_email_step(): void
    {
        $response = $this->get(route('upload-files.verify.applicant'));

        $response
            ->assertOk()
            ->assertSee('Verifikasi Email Pelamar')
            ->assertSee('demo@example.com')
            ->assertDontSee('0812 3456 ****')
            ->assertDontSee('7890');
    }

    public function test_valid_email_redirects_back_to_applicant_verification_phone_step(): void
    {
        $response = $this->post(route('upload-files.verify.applicant.check'), [
            'step' => 'email',
            'email' => 'demo@example.com',
        ]);

        $response
            ->assertRedirect(route('upload-files.verify.applicant'))
            ->assertSessionHas('upload_file_email_verified', true);
    }

    public function test_phone_step_renders_after_email_verification(): void
    {
        $response = $this
            ->withSession(['upload_file_email_verified' => true])
            ->get(route('upload-files.verify.applicant'));

        $response
            ->assertOk()
            ->assertSee('Masukkan 4 Digit Terakhir Nomor HP')
            ->assertSee('0812 3456 ****')
            ->assertDontSee('Verifikasi Email Pelamar')
            ->assertDontSee('7890');
    }

    public function test_valid_phone_digits_grant_upload_access(): void
    {
        $response = $this
            ->withSession(['upload_file_email_verified' => true])
            ->post(route('upload-files.verify.applicant.check'), [
                'step' => 'pin',
                'pin' => ['7', '8', '9', '0'],
            ]);

        $response
            ->assertRedirect(route('upload-files.create'))
            ->assertSessionHas('upload_file_verified', true)
            ->assertSessionMissing('upload_file_email_verified');
    }

    public function test_invalid_email_is_rejected(): void
    {
        $response = $this
            ->from(route('upload-files.verify.applicant'))
            ->post(route('upload-files.verify.applicant.check'), [
                'step' => 'email',
                'email' => 'wrong@example.com',
            ]);

        $response
            ->assertRedirect(route('upload-files.verify.applicant'))
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_phone_digits_are_rejected(): void
    {
        $response = $this
            ->withSession(['upload_file_email_verified' => true])
            ->from(route('upload-files.verify.applicant'))
            ->post(route('upload-files.verify.applicant.check'), [
                'step' => 'pin',
                'pin' => ['0', '0', '0', '0'],
            ]);

        $response
            ->assertRedirect(route('upload-files.verify.applicant'))
            ->assertSessionHasErrors('pin');
    }

    public function test_phone_step_post_redirects_to_email_step_without_email_verification(): void
    {
        $response = $this->post(route('upload-files.verify.applicant.check'), [
            'step' => 'pin',
            'pin' => ['7', '8', '9', '0'],
        ]);

        $response->assertRedirect(route('upload-files.verify.applicant'));
    }
}

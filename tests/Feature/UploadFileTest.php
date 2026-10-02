<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFileTest extends TestCase
{
    public function test_upload_file_page_renders(): void
    {
        $response = $this
            ->withSession(['upload_file_verified' => true])
            ->get(route('upload-files.create'));

        $response
            ->assertOk()
            ->assertSee('Upload File')
            ->assertSee('Hanya JPG dan PNG.');
    }

    public function test_upload_file_page_redirects_to_applicant_verification_when_unverified(): void
    {
        $response = $this->get(route('upload-files.create'));

        $response->assertRedirect(route('upload-files.verify.applicant'));
    }

    public function test_jpg_image_upload_is_stored(): void
    {
        Storage::fake('local');

        $image = UploadedFile::fake()->image('profile.jpg');

        $response = $this
            ->withSession(['upload_file_verified' => true])
            ->post(route('upload-files.store'), [
                'image' => $image,
            ]);

        $response
            ->assertRedirect(route('upload-files.create'))
            ->assertSessionHas('status', 'File uploaded successfully.');

        Storage::disk('local')->assertExists($image->hashName('uploaded-images'));
    }

    public function test_png_image_upload_is_stored(): void
    {
        Storage::fake('local');

        $image = UploadedFile::fake()->image('profile.png');

        $response = $this
            ->withSession(['upload_file_verified' => true])
            ->post(route('upload-files.store'), [
                'image' => $image,
            ]);

        $response
            ->assertRedirect(route('upload-files.create'))
            ->assertSessionHas('status', 'File uploaded successfully.');

        Storage::disk('local')->assertExists($image->hashName('uploaded-images'));
    }

    public function test_text_file_upload_is_rejected(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()
            ->createWithContent('notes.txt', 'plain text')
            ->mimeType('text/plain');

        $response = $this
            ->withSession(['upload_file_verified' => true])
            ->from(route('upload-files.create'))
            ->post(route('upload-files.store'), [
                'image' => $file,
            ]);

        $response
            ->assertRedirect(route('upload-files.create'))
            ->assertSessionHasErrors('image');

        Storage::disk('local')->assertMissing($file->hashName('uploaded-images'));
    }

    public function test_upload_is_rejected_when_unverified(): void
    {
        Storage::fake('local');

        $image = UploadedFile::fake()->image('profile.jpg');

        $response = $this->post(route('upload-files.store'), [
            'image' => $image,
        ]);

        $response->assertRedirect(route('upload-files.verify.applicant'));

        Storage::disk('local')->assertMissing($image->hashName('uploaded-images'));
    }
}

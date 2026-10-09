<?php

use App\Http\Controllers\AssessmentFileController;
use App\Http\Controllers\UploadFileController;
use App\Http\Controllers\UploadFileVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404);
});

Route::get('/{applicant}/upload-file/verify-applicant', [UploadFileVerificationController::class, 'applicant'])
    ->whereUuid('applicant')
    ->name('upload-files.verify.applicant.generated');

Route::post('/{applicant}/upload-file/verify-applicant', [UploadFileVerificationController::class, 'checkApplicant'])
    ->whereUuid('applicant')
    ->name('upload-files.verify.applicant.generated.check');

Route::get('/{applicant}/upload-file', [UploadFileController::class, 'create'])
    ->whereUuid('applicant')
    ->name('upload-files.generated.create');

Route::post('/{applicant}/upload-file', [UploadFileController::class, 'store'])
    ->whereUuid('applicant')
    ->name('upload-files.generated.store');

Route::get('/{applicant}/assessment-file', [AssessmentFileController::class, 'show'])
    ->whereUuid('applicant')
    ->name('assessment-files.show');

Route::fallback(function () {
    abort(404);
});

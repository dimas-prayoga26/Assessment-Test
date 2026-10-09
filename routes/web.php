<?php

use App\Http\Controllers\UploadFileController;
use App\Http\Controllers\UploadFileVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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

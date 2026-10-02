<?php

use App\Http\Controllers\UploadFileController;
use App\Http\Controllers\UploadFileVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload-file/verify-applicant', [UploadFileVerificationController::class, 'applicant'])
    ->name('upload-files.verify.applicant');

Route::post('/upload-file/verify-applicant', [UploadFileVerificationController::class, 'checkApplicant'])
    ->name('upload-files.verify.applicant.check');

Route::get('/upload-file', [UploadFileController::class, 'create'])
    ->name('upload-files.create');

Route::post('/upload-file', [UploadFileController::class, 'store'])
    ->name('upload-files.store');

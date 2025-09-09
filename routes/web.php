<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\MailController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/')->group(function() {
    Route::view('home', 'home')->name('home');
    Route::post('/get-token', [OAuthController::class, 'doGenerateToken'])->name('generate.token');
    Route::get('/get-token', [OAuthController::class, 'doSuccessToken'])->name('token.success');
    Route::post('/send', [MailController::class, 'doSendEmail'])->name('send.email');
});
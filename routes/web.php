<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\MailController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/')->group(function() {
    Route::get('/home', function() {
        $oauthController = new \App\Http\Controllers\OAuthController();
        $oauthController->loadTokensFromDatabase();
        return view('home');
    })->name('home');
    Route::post('/get-token', [OAuthController::class, 'doGenerateToken'])->name('generate.token');
    Route::get('/get-token', [OAuthController::class, 'doSuccessToken'])->name('token.success');
    Route::post('/refresh-token', [OAuthController::class, 'refreshAccessToken'])->name('refresh.token');
    Route::post('/send', [MailController::class, 'doSendEmail'])->name('send.email');
});
@extends('layouts.app')

@section('content')
@php
    use App\Models\OAuthToken;
    $userEmail = session('user_email', 'jpcloudspot@gmail.com');
    $tokenRecord = OAuthToken::findByEmail($userEmail);
@endphp
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
    <h3>Token Management</h3>
    <p><strong>Generate Token:</strong> Click to redirect to Google Account for authentication and get new tokens.</p>
    <p><strong>Refresh Token:</strong> Click to refresh your existing access token (requires refresh token).</p>
    <p><strong>Clear Tokens:</strong> Click to clear all tokens and force fresh authorization (useful if no refresh token).</p>
</div>
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm flex justify-between flex-wrap gap-2">
    <div class="flex gap-2">
        <form action="{{ route('generate.token') }}" method="post">
            @csrf
            <button type="submit" class="cursor p-2 px-6 bg-gray-900 text-gray-600 font-semibold">Generate Token</button>
        </form>
        
        
        <form action="{{ route('refresh.token') }}" method="post">
            @csrf
            <button type="submit" class="cursor p-2 px-6 bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors">
                🔄 Refresh Token
            </button>
        </form>
        
        @if ( $tokenRecord )
        <form action="{{ route('clear.tokens') }}" method="post">
            @csrf
            <button type="submit" class="cursor p-2 px-6 bg-red-600 text-white font-semibold hover:bg-red-700 transition-colors">
                🗑️ Clear Tokens
            </button>
        </form>
        @endif
    </div>
    
    @if ( $tokenRecord && $tokenRecord->access_token )
    <form action="{{ route('send.email') }}" method="post">
        @csrf
        <input type="hidden" name="oauth_token" value="{{ $tokenRecord->access_token }}">
        <button type="submit" class="cursor p-2 px-6 bg-gray-100 text-gray-700 font-semibold">Send Laravel Mail</button>
    </form>
    @endif
</div>
@if ( session()->has('error') || $tokenRecord )
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
    <h4 style="margin-bottom: 5px;">Token Status</h4>
    @if ( session()->has('error') )
    <p class="font-semibold text-red-600">{{ session()->get('error') }}</p>
    @endif

    @if ( $tokenRecord )
        <div class="mt-2">
            <p class="font-semibold {{ $tokenRecord->isExpired() ? 'text-red-600' : 'text-green-600' }}">
                Token Status: {{ $tokenRecord->isExpired() ? 'EXPIRED' : 'VALID' }}
            </p>
            @if (!$tokenRecord->isExpired() && $tokenRecord->expires_at)
                <p class="text-sm">Expires in: {{ $tokenRecord->getFormattedTimeRemaining() }}</p>
            @endif
            <p class="text-sm text-gray-500">User Email: {{ $tokenRecord->user_email }}</p>
            <p class="font-semibold" style="font-size: 10px; margin: 0px; word-break: break-all;">
                Access Token: {{ substr($tokenRecord->access_token, 0, 50) }}...
            </p>
            @if ($tokenRecord->refresh_token)
                <p class="font-semibold" style="font-size: 10px; margin: 0px; word-break: break-all;">
                    Refresh Token: {{ substr($tokenRecord->refresh_token, 0, 50) }}...
                </p>
            @endif
        </div>
    @endif
</div>
@endif

@if ( session()->has('success') )
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
    <p class="font-semibold">{{ session()->get('success') }}</p>
</div>
@endif
@endsection
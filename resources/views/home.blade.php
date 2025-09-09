@extends('layouts.app')

@section('content')
@php
    use App\Models\OAuthToken;
    $userEmail = session('user_email', 'jpcloudspot@gmail.com');
    $tokenRecord = OAuthToken::findByEmail($userEmail);
@endphp
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
    <h3>Generate Token</h3>
    <p>Once you click on below button it will redirect you to Google Account for select. Returned that with Token. If refresh Token Not found then please click Generate Token Button to grab token instead.</p>
</div>
<div class="mt-2 text-gray-600 dark:text-gray-400 text-sm flex justify-between flex-wrap gap-2">
    <form action="{{ route('generate.token') }}" method="post" class="mr-2">
        @csrf
        <button type="submit" class="cursor p-2 px-6 bg-gray-900 text-gray-600 font-semibold">Generate Token</button>
    </form>
    
    @if ( $tokenRecord && $tokenRecord->refresh_token )
    <form action="{{ route('refresh.token') }}" method="post" class="mr-2">
        @csrf
        <button type="submit" class="cursor p-2 px-6 bg-blue-600 text-white font-semibold">Refresh Token</button>
    </form>
    @endif
    
    @if ( $tokenRecord && $tokenRecord->access_token )
    <form action="{{ route('send.email') }}" method="post">
        @csrf
        <input type="hidden" name="oauth_token" value="{{ $tokenRecord->access_token }}">
        <button type="submit" class="cursor p-2 px-6 bg-gray-100 text-gray-700 font-semibold">Send Test Email</button>
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
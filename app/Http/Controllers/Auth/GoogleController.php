<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SessionStarted;
use App\Models\ChargingSession;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class GoogleController extends Controller
{
    private const ALLOWED_DOMAIN   = 'spcc.edu.ph';
    private const SESSION_DURATION = 1200; // 20 minutes in seconds

    public function landing(): View
    {
        return view('auth.qr-scan');
    }

    public function redirect(): RedirectResponse
    {
        $query = http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.google.redirect'),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(MqttService $mqtt): RedirectResponse
{
    $code = request('code');

    if (! $code) {
        return redirect()->route('qr.landing')
            ->with('error', 'Google login was cancelled.');
    }

    $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => config('services.google.client_id'),
        'client_secret' => config('services.google.client_secret'),
        'redirect_uri'  => config('services.google.redirect'),
        'grant_type'    => 'authorization_code',
    ]);

    if ($tokenResponse->failed()) {
        return redirect()->route('qr.landing')
            ->with('error', 'Failed to authenticate with Google.');
    }

    $accessToken = $tokenResponse->json('access_token');

    $userInfo = Http::withToken($accessToken)
        ->get('https://www.googleapis.com/oauth2/v3/userinfo')
        ->json();

    $email = $userInfo['email'] ?? null;
    $name  = $userInfo['name']  ?? 'Unknown';

    if (! $email) {
        return redirect()->route('qr.landing')
            ->with('error', 'Could not retrieve your Google account info.');
    }

    if (! str_ends_with($email, '@' . self::ALLOWED_DOMAIN)) {
        return redirect()->route('qr.landing')
            ->with('error', 'Please use your SPCC school email to continue.');
    }

    $alreadyActive = ChargingSession::where('student_email', $email)
        ->whereNull('ended_at')
        ->exists();

    if ($alreadyActive) {
        return redirect()->route('qr.landing')
            ->with('error', 'You already have an active charging session.');
    }

    // ── Check for a resumed session (student charged before, paused) ──
    $cacheKey  = "piezo_remaining_{$email}";
    $remaining = Cache::get($cacheKey);
    $isResumed = $remaining !== null;

    // If remaining time is 0 or less, don't let them back in
    if ($isResumed && $remaining <= 0) {
        Cache::forget($cacheKey);
        return redirect()->route('qr.landing')
            ->with('error', 'Your session time has been fully used up.');
    }

    $timeLimit = $isResumed ? (int) $remaining : self::SESSION_DURATION;

    if ($isResumed) {
        Cache::forget($cacheKey); // consume the cached remaining time
    }

    $session = ChargingSession::create([
        'student_name'  => $name,
        'student_email' => $email,
        'started_at'    => now(),
        'ended_at'      => null,
        'time_limit'    => $timeLimit,
        'is_resumed'    => $isResumed,
    ]);

    $settings = SystemSetting::current();
    $settings->update([
        'is_tracking_on'       => true,
        'active_student_name'  => $name,
        'active_student_email' => $email,
        'tracking_started_at'  => now(),
    ]);

    $mqtt->publish('piezo/command', [
        'tracking_on'  => true,
        'student_name' => $name,
    ], retain: true);

    Session::put('charging_session_id', $session->id);

    dispatch(new \App\Jobs\StopChargingSession($session->id))
        ->delay(now()->addSeconds($timeLimit)); // ← uses remaining time, not always 1200

    Mail::to($email)->queue(new SessionStarted($session));

    return redirect()->route('qr.success')
        ->with([
            'student_name' => $name,
            'started_at'   => now()->format('h:i A'),
        ]);
}

    public function success(): View
    {
        $activeSession = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return view('auth.qr-success', compact('activeSession'));
    }
}
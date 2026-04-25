<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class GoogleController extends Controller
{
    private const ALLOWED_DOMAIN   = 'spcc.edu.ph';
    private const SESSION_DURATION = 1200; // 20 minutes in seconds

    // ── Show the QR landing page ──────────────────────────────────────
    public function landing(): View
    {
        return view('auth.qr-scan');
    }

    // ── Redirect to Google OAuth ──────────────────────────────────────
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

    // ── Handle Google OAuth callback ──────────────────────────────────
    public function callback(MqttService $mqtt): RedirectResponse
    {
        $code = request('code');

        if (! $code) {
            return redirect()->route('qr.landing')
                ->with('error', 'Google login was cancelled.');
        }

        // ── Exchange code for access token ────────────────────────────
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

        // ── Fetch user info from Google ───────────────────────────────
        $userInfo = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        $email = $userInfo['email']     ?? null;
        $name  = $userInfo['name']      ?? 'Unknown';

        if (! $email) {
            return redirect()->route('qr.landing')
                ->with('error', 'Could not retrieve your Google account info.');
        }

        // ── Enforce school domain ─────────────────────────────────────
        if (! str_ends_with($email, '@' . self::ALLOWED_DOMAIN)) {
            return redirect()->route('qr.landing')
                ->with('error', 'Please use your SPCC school email to continue.');
        }

        // ── Prevent double session (if already active) ────────────────
        $alreadyActive = ChargingSession::where('student_email', $email)
            ->whereNull('ended_at')
            ->exists();

        if ($alreadyActive) {
            return redirect()->route('qr.landing')
                ->with('error', 'You already have an active charging session.');
        }

        // ── Create session record ─────────────────────────────────────
        $session = ChargingSession::create([
            'student_name'  => $name,
            'student_email' => $email,
            'started_at'    => now(),
            'ended_at'      => null,
        ]);

        // ── Update SystemSetting ──────────────────────────────────────
        $settings = SystemSetting::current();
        $settings->update([
            'is_tracking_on'      => true,
            'active_student_name'  => $name,
            'active_student_email' => $email,
            'tracking_started_at'  => now(),
        ]);

        // ── Signal ESP32 via MQTT ─────────────────────────────────────
        $mqtt->publish('piezo/command', [
            'tracking_on'  => true,
            'student_name' => $name,
        ], retain: true);

        // ── Store session ID so the stop job can reference it ─────────
        Session::put('charging_session_id', $session->id);

        // ── Schedule auto-stop after 20 mins ─────────────────────────
        dispatch(new \App\Jobs\StopChargingSession($session->id))
            ->delay(now()->addSeconds(self::SESSION_DURATION));

        return redirect()->route('qr.success')
            ->with([
                'student_name' => $name,
                'started_at'   => now()->format('h:i A'),
            ]);
    }

    // ── Success page after session starts ────────────────────────────
public function success(): View
{
    $activeSession = ChargingSession::whereNull('ended_at')
        ->latest('started_at')
        ->first();

    return view('auth.qr-success', compact('activeSession'));
}
}
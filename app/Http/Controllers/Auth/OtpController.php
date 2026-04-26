<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class OtpController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const LOCAL_OTP = '000000';

    public function __construct(private readonly SmsOtpService $smsService) {}

    // ── Register ─────────────────────────────────────────────

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function sendRegisterOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
        ], [
            'phone.regex' => 'Số điện thoại không đúng định dạng (VD: 0912345678)',
        ]);

        if (User::where('phone', $request->phone)->exists()) {
            return back()->withErrors(['phone' => 'Số điện thoại đã được đăng ký. Vui lòng đăng nhập.'])->withInput();
        }

        if ($this->isRateLimited($request)) {
            return back()->withErrors(['phone' => 'Bạn gửi OTP quá nhiều lần. Vui lòng thử lại sau.'])->withInput();
        }

        $otp = $this->generateAndStoreOtp($request->phone);
        $this->sendOtp($request->phone, $otp);

        $request->session()->put('otp_action', 'register');
        $request->session()->put('otp_phone', $request->phone);
        $request->session()->put('otp_name', $request->name);

        return redirect()->route('otp.verify');
    }

    // ── Login ─────────────────────────────────────────────────

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function sendLoginOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
        ], [
            'phone.regex' => 'Số điện thoại không đúng định dạng (VD: 0912345678)',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'Số điện thoại chưa được đăng ký.'])->withInput();
        }

        if ($this->isRateLimited($request)) {
            return back()->withErrors(['phone' => 'Bạn gửi OTP quá nhiều lần. Vui lòng thử lại sau.'])->withInput();
        }

        $otp = $this->generateAndStoreOtp($request->phone);
        $this->sendOtp($request->phone, $otp);

        $request->session()->put('otp_action', 'login');
        $request->session()->put('otp_phone', $request->phone);

        return redirect()->route('otp.verify');
    }

    // ── Verify OTP ────────────────────────────────────────────

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_phone')) {
            return redirect()->route('login');
        }

        return view('auth.otp-verify', [
            'action' => $request->session()->get('otp_action'),
            'phone' => $request->session()->get('otp_phone'),
            'isLocal' => app()->environment('local'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'otp.digits' => 'Mã OTP gồm 6 chữ số.',
        ]);

        $phone  = $request->session()->get('otp_phone');
        $action = $request->session()->get('otp_action');
        $name   = $request->session()->get('otp_name');

        if (! $phone || ! $action) {
            return redirect()->route('login');
        }

        if (! $this->validateOtp($phone, $request->otp)) {
            return back()->withErrors(['otp' => 'Mã OTP không đúng hoặc đã hết hạn.']);
        }

        $this->clearOtp($phone);
        $request->session()->forget(['otp_phone', 'otp_action', 'otp_name']);

        if ($action === 'register') {
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'phone_verified_at' => now(),
            ]);
        } else {
            $user = User::where('phone', $phone)->firstOrFail();
            $user->update(['phone_verified_at' => now()]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // ── Private helpers ───────────────────────────────────────

    private function generateAndStoreOtp(string $phone): string
    {
        $otp = app()->environment('local')
            ? self::LOCAL_OTP
            : (string) random_int(100000, 999999);

        Cache::put("otp:{$phone}", $otp, now()->addMinutes(self::OTP_TTL_MINUTES));

        return $otp;
    }

    private function validateOtp(string $phone, string $input): bool
    {
        $stored = Cache::get("otp:{$phone}");
        return $stored !== null && $stored === $input;
    }

    private function clearOtp(string $phone): void
    {
        Cache::forget("otp:{$phone}");
    }

    private function sendOtp(string $phone, string $otp): void
    {
        if (app()->environment('local')) {
            Log::info("LOCAL OTP for {$phone}: {$otp}");
            return;
        }

        $this->smsService->send($phone, $otp);
    }

    private function isRateLimited(Request $request): bool
    {
        $phone = $request->input('phone');
        $ip = $request->ip();

        $phoneLimitKey = "otp_send_phone:{$phone}";
        $ipLimitKey = "otp_send_ip:{$ip}";

        if (RateLimiter::tooManyAttempts($phoneLimitKey, 3)) {
            return true;
        }

        if (RateLimiter::tooManyAttempts($ipLimitKey, 5)) {
            return true;
        }

        RateLimiter::hit($phoneLimitKey, 600);
        RateLimiter::hit($ipLimitKey, 3600);

        return false;
    }
}

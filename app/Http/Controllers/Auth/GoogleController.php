<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Đăng nhập Google thất bại. Vui lòng thử lại.']);
        }

        $wasNew = ! User::where('email', $googleUser->getEmail())->exists();

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            ['name' => $googleUser->getName(), 'phone_verified_at' => null]
        );

        // Tạo default family group cho user mới
        if ($wasNew) {
            $group = $user->familyGroups()->create([
                'name'       => 'Gia đình',
                'color'      => '#c026d3',
                'is_default' => true,
            ]);
            session(['active_family_group_id' => $group->id]);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

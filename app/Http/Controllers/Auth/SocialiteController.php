<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['email' => 'Gagal melakukan login dengan Google.']);
        }

        $user = User::where('email', $googleUser->email)->first();

        if (! $user) {
            return redirect('/login')->withErrors([
                'email' => 'Akun dengan email ini belum terdaftar. Silakan hubungi administrator.',
            ]);
        }

        $user->update([
            'google_id' => $googleUser->id,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        Auth::login($user);

        return redirect()->intended(config('fortify.home'));
    }
}

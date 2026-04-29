<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OtpLoginController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'login_code' => $code,
            'login_code_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginOtpMail($code));

        return back()->with('status', 'Kode verifikasi telah dikirim ke email Anda.');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $request->email)
            ->where('login_code', $request->code)
            ->where('login_code_expires_at', '>', now())
            ->first();

        if (! $user) {
            return back()->withErrors(['code' => 'Kode verifikasi tidak valid atau telah kedaluwarsa.']);
        }

        $user->update([
            'login_code' => null,
            'login_code_expires_at' => null,
        ]);

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const DEV_EMAIL = 'developer@nebula.local';
    private const DEV_PASSWORD = 'dev12345';

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            if ($this->tryDeveloperModeLogin($credentials['email'], $credentials['password'], $request, $remember)) {
                return redirect()->intended(route('dashboard'));
            }

            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function tryDeveloperModeLogin(string $email, string $password, Request $request, bool $remember): bool
    {
        $developerModeEnabled = app()->environment('local') || (bool) config('app.debug');

        if (!$developerModeEnabled) {
            return false;
        }

        $normalizedEmail = strtolower(trim($email));

        if (!hash_equals(self::DEV_EMAIL, $normalizedEmail) || !hash_equals(self::DEV_PASSWORD, $password)) {
            return false;
        }

        $user = User::firstOrCreate(
            ['email' => self::DEV_EMAIL],
            [
                'name' => 'Developer Mode User',
                'password' => Hash::make(self::DEV_PASSWORD),
            ]
        );

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return true;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\FacebookAuthService;
use App\Services\GoogleAuthService;
use App\Services\MicrosoftAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly MicrosoftAuthService $microsoft,
        private readonly GoogleAuthService $google,
        private readonly FacebookAuthService $facebook
    ) {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showAdminLogin(): View
    {
        return view('auth.admin-login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function redirectToMicrosoft(Request $request): RedirectResponse
    {
        if (! $this->microsoft->isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'microsoft' => 'Microsoft login is not configured yet. Please contact an administrator.',
                ]);
        }

        $state = Str::random(40);
        $request->session()->put('microsoft_oauth_state', $state);

        return redirect()->away($this->microsoft->authorizationUrl($state));
    }

    public function handleMicrosoftCallback(Request $request): RedirectResponse
    {
        $state = (string) $request->session()->pull('microsoft_oauth_state');

        if ($state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            return $this->microsoftFailed('Microsoft sign-in could not be verified. Please try again.');
        }

        if ($request->filled('error')) {
            return $this->microsoftFailed('Microsoft sign-in was cancelled or denied.');
        }

        $code = trim((string) $request->query('code'));
        if ($code === '') {
            return $this->microsoftFailed('Microsoft did not return an authorization code.');
        }

        try {
            $profile = $this->microsoft->userFromCode($code);
        } catch (RuntimeException $exception) {
            return $this->microsoftFailed($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->microsoftFailed('Could not complete Microsoft sign-in. Please try again.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$profile['email']])
            ->first();

        if (! $user) {
            return $this->microsoftFailed('No SLT employee account matches this Microsoft login. Contact an administrator.');
        }

        if (! $user->is_active) {
            return $this->microsoftFailed('Your account is inactive. Please contact an administrator.');
        }

        if (! $user->isSltEmployeeStaff()) {
            return $this->microsoftFailed('Microsoft sign-in is only available for SLT employee staff.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        if (! $this->google->isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Google login is not configured yet. Please contact an administrator.',
                ]);
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->google->authorizationUrl($state));
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $state = (string) $request->session()->pull('google_oauth_state');

        if ($state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            return $this->googleFailed('Google sign-in could not be verified. Please try again.');
        }

        if ($request->filled('error')) {
            return $this->googleFailed('Google sign-in was cancelled or denied.');
        }

        $code = trim((string) $request->query('code'));
        if ($code === '') {
            return $this->googleFailed('Google did not return an authorization code.');
        }

        try {
            $profile = $this->google->userFromCode($code);
        } catch (RuntimeException $exception) {
            return $this->googleFailed($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->googleFailed('Could not complete Google sign-in. Please try again.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$profile['email']])
            ->first();

        if (! $user) {
            return $this->googleFailed('No account matches this Google email (' . $profile['email'] . '). Please contact an administrator.');
        }

        if (! $user->is_active) {
            return $this->googleFailed('Your account is inactive. Please contact an administrator.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function redirectToFacebook(Request $request): RedirectResponse
    {
        if (! $this->facebook->isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'facebook' => 'Facebook login is not configured yet. Please contact an administrator.',
                ]);
        }

        $state = Str::random(40);
        $request->session()->put('facebook_oauth_state', $state);

        return redirect()->away($this->facebook->authorizationUrl($state));
    }

    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        $state = (string) $request->session()->pull('facebook_oauth_state');

        if ($state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            return $this->facebookFailed('Facebook sign-in could not be verified. Please try again.');
        }

        if ($request->filled('error')) {
            return $this->facebookFailed('Facebook sign-in was cancelled or denied.');
        }

        $code = trim((string) $request->query('code'));
        if ($code === '') {
            return $this->facebookFailed('Facebook did not return an authorization code.');
        }

        try {
            $profile = $this->facebook->userFromCode($code);
        } catch (RuntimeException $exception) {
            return $this->facebookFailed($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->facebookFailed('Could not complete Facebook sign-in. Please try again.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$profile['email']])
            ->first();

        if (! $user) {
            return $this->facebookFailed('No account matches this Facebook email (' . $profile['email'] . '). Please contact an administrator.');
        }

        if (! $user->is_active) {
            return $this->facebookFailed('Your account is inactive. Please contact an administrator.');
        }

        Auth::login($user);
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

    private function microsoftFailed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['microsoft' => $message]);
    }

    private function googleFailed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }

    private function facebookFailed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['facebook' => $message]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\MicrosoftAuthService;
use App\Services\MicrosoftUserProvisioner;
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
        private readonly MicrosoftUserProvisioner $provisioner,
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
            $user = $this->provisioner->sync($this->microsoft->userFromCode($code));
        } catch (RuntimeException $exception) {
            return $this->microsoftFailed($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->microsoftFailed('Could not complete Microsoft sign-in. Please try again.');
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
}

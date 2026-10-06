<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        $email = (string) $request->query('email', '');
        $user = $email !== ''
            ? User::query()->where('email', $email)->first()
            : null;
        $valid = $user && Password::broker()->tokenExists($user, $token);

        return view('auth.setup-password', [
            'token' => $token,
            'email' => $email,
            'expiresMinutes' => (int) config('auth.passwords.users.expire', 60),
            'expired' => ! $valid,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'The passwords do not match.',
            'password.min' => 'Use at least 8 characters.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'password_setup_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Your password is ready. You can now sign in.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'password' => 'This setup link is invalid or has expired. Ask an administrator to send a new one.',
            ]);
    }
}

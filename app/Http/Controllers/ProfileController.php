<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public const CAMPUSES = [
        'Nebula Institute of Technology - Welisara',
        'Nebula Institute of Technology - Moratuwa',
        'Nebula Institute of Technology - Peradeniya',
    ];

    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing(['role', 'roles']);
        $locations = self::CAMPUSES;

        if (filled($user->location) && ! in_array($user->location, $locations, true)) {
            array_unshift($locations, $user->location);
        }

        return view('profile.show', [
            'user' => $user,
            'locations' => $locations,
            'tab' => $this->activeTab($request),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->boolean('remove_avatar') && $user->user_profile) {
            $this->deleteAvatar($user->user_profile);
            $validated['user_profile'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->user_profile) {
                $this->deleteAvatar($user->user_profile);
            }
            $validated['user_profile'] = $request->file('avatar')->store('profiles', 'public');
        }

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->update($validated);

        return redirect()
            ->route('user.profile')
            ->with('status', 'profile-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $password = $request->validated('password');

        $request->user()->update([
            'password' => $password,
        ]);

        auth()->logoutOtherDevices($password);
        $request->session()->regenerate();

        return redirect()
            ->route('user.profile', ['tab' => 'security'])
            ->with('status', 'password-updated');
    }

    private function deleteAvatar(string $path): void
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function activeTab(Request $request): string
    {
        if ($request->session()->get('status') === 'password-updated') {
            return 'security';
        }

        $errors = $request->session()->get('errors');
        if ($errors && $errors->getBag('updatePassword')->any()) {
            return 'security';
        }

        return $request->query('tab') === 'security' ? 'security' : 'profile';
    }
}

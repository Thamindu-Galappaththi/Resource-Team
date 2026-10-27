<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ]);
    }

    /**
     * @return array{id: string, email: string, name: string}
     */
    public function userFromCode(string $code): array
    {
        $token = $this->accessToken($code);
        $profile = $this->fetchProfile($token);

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $name = trim((string) ($profile['name'] ?? ''));
        $id = trim((string) ($profile['sub'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Google did not provide a valid email address for this account.');
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ];
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.google.redirect'));

        return $configured !== '' ? $configured : route('login.google.callback');
    }

    private function accessToken(string $code): string
    {
        try {
            $client = Http::asForm()
                ->timeout(15)
                ->acceptJson();

            if (app()->environment('local', 'testing')) {
                $client = $client->withoutVerifying();
            }

            $response = $client->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ])->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not complete Google sign-in. Please try again.', 0, $exception);
        }

        $token = (string) $response->json('access_token');

        if ($token === '') {
            throw new RuntimeException('Could not obtain access token from Google.');
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchProfile(string $accessToken): array
    {
        try {
            $client = Http::withToken($accessToken)
                ->timeout(15)
                ->acceptJson();

            if (app()->environment('local', 'testing')) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get('https://www.googleapis.com/oauth2/v3/userinfo')->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not fetch user profile from Google. Please try again.', 0, $exception);
        }

        $profile = $response->json();

        if (! is_array($profile)) {
            throw new RuntimeException('Invalid profile data received from Google.');
        }

        return $profile;
    }
}

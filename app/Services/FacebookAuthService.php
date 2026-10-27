<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class FacebookAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('services.facebook.client_id'))
            && filled(config('services.facebook.client_secret'));
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
            'client_id' => config('services.facebook.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
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
        $id = trim((string) ($profile['id'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Facebook did not provide an email address. Please make sure your Facebook account has a verified primary email.');
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ];
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.facebook.redirect'));

        return $configured !== '' ? $configured : route('login.facebook.callback');
    }

    private function accessToken(string $code): string
    {
        try {
            $client = Http::timeout(15)->acceptJson();

            if (app()->environment('local', 'testing')) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
            ])->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not complete Facebook sign-in. Please try again.', 0, $exception);
        }

        $token = (string) $response->json('access_token');

        if ($token === '') {
            throw new RuntimeException('Could not obtain access token from Facebook.');
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

            $response = $client->get('https://graph.facebook.com/v19.0/me', [
                'fields' => 'id,name,email',
            ])->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not fetch user profile from Facebook. Please try again.', 0, $exception);
        }

        $profile = $response->json();

        if (! is_array($profile)) {
            throw new RuntimeException('Invalid profile data received from Facebook.');
        }

        return $profile;
    }
}

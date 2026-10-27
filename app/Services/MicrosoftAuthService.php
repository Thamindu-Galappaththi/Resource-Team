<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MicrosoftAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('services.microsoft.client_id'))
            && filled(config('services.microsoft.client_secret'));
    }

    public function authorizationUrl(string $state): string
    {
        return $this->authority().'/oauth2/v2.0/authorize?'.http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /**
     * @return array{id: string, email: string, name: string}
     */
    public function userFromCode(string $code): array
    {
        $token = $this->accessToken($code);
        $profile = $this->graphProfile($token);

        $email = strtolower(trim((string) ($profile['mail'] ?? $profile['userPrincipalName'] ?? '')));
        $name = trim((string) ($profile['displayName'] ?? ''));
        $id = trim((string) ($profile['id'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Microsoft did not provide an email address for this account.');
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ];
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.microsoft.redirect'));

        return $configured !== '' ? $configured : route('login.microsoft.callback');
    }

    private function authority(): string
    {
        $tenant = trim((string) config('services.microsoft.tenant', 'organizations')) ?: 'organizations';

        return 'https://login.microsoftonline.com/'.rawurlencode($tenant);
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

            $response = $client->post($this->authority().'/oauth2/v2.0/token', [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ])->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not complete Microsoft sign-in. Please try again.', 0, $exception);
        }

        $token = (string) $response->json('access_token');

        if ($token === '') {
            throw new RuntimeException('Could not complete Microsoft sign-in. Please try again.');
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function graphProfile(string $accessToken): array
    {
        try {
            $client = Http::withToken($accessToken)
                ->timeout(15)
                ->acceptJson();

            if (app()->environment('local', 'testing')) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get('https://graph.microsoft.com/v1.0/me')->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not complete Microsoft sign-in. Please try again.', 0, $exception);
        }

        $profile = $response->json();

        if (! is_array($profile)) {
            throw new RuntimeException('Could not complete Microsoft sign-in. Please try again.');
        }

        return $profile;
    }
}

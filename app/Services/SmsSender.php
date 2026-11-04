<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsSender
{
    public const SENT = 'sent';
    public const LOGGED = 'logged';
    public const FAILED = 'failed';

    public function send(string $phone, string $message): string
    {
        $to = $this->normalize($phone);
        if ($to === '') {
            return self::FAILED;
        }

        if (config('sms.driver') !== 'http') {
            Log::info('SMS (log driver)', ['to' => $to, 'message' => $message]);

            return self::LOGGED;
        }

        try {
            $response = Http::withToken((string) config('sms.http.token'))
                ->timeout(config('sms.http.timeout', 10))
                ->acceptJson()
                ->post((string) config('sms.http.url'), [
                    'to' => $to,
                    'message' => $message,
                    'sender_id' => config('sms.sender_id'),
                ]);

            if ($response->successful()) {
                return self::SENT;
            }

            Log::warning('SMS gateway rejected message', ['to' => $to, 'status' => $response->status(), 'body' => $response->body()]);
        } catch (Throwable $exception) {
            Log::warning('SMS gateway unreachable', ['to' => $to, 'error' => $exception->getMessage()]);
        }

        return self::FAILED;
    }

    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $country = (string) config('sms.country_code', '94');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = $country.substr($digits, 1);
        }

        return strlen($digits) >= 9 ? $digits : '';
    }
}

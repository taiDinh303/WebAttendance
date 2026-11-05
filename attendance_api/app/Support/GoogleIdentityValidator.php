<?php

namespace App\Support;

use App\Exceptions\ApiException;
use Google\Client;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

final class GoogleIdentityValidator
{
    public function __construct(private readonly Client $client) {}

    public function validate(#[\SensitiveParameter] string $credential, string $nonce): array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            throw new ApiException('GOOGLE_DISABLED', 'Google sign-in is not configured.', 503);
        }
        $this->client->setClientId($clientId);
        try {
            $claims = $this->client->verifyIdToken($credential);
        } catch (GuzzleException) {
            throw new ApiException('GOOGLE_UNAVAILABLE', 'Google verification is temporarily unavailable.', 503);
        } catch (Throwable) {
            $claims = false;
        }
        if (! is_array($claims) || ($claims['aud'] ?? null) !== $clientId
            || ! in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            || ! is_int($claims['exp'] ?? null) || $claims['exp'] <= time()
            || ! is_int($claims['iat'] ?? null) || $claims['iat'] > time() + 60
            || ! is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 255
            || ($claims['email_verified'] ?? null) !== true
            || ! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce'])
            || (isset($claims['azp']) && $claims['azp'] !== $clientId)
            || ! is_string($claims['email'] ?? null) || strlen($claims['email']) > 254 || ! filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('INVALID_GOOGLE_TOKEN', 'Google sign-in could not be verified. Please try again.', 422);
        }

        return ['subject' => $claims['sub'], 'email' => mb_strtolower($claims['email']),
            'name' => mb_substr(trim((string) ($claims['name'] ?? 'Google User')), 0, 120) ?: 'Google User',
            'authoritative' => str_ends_with(mb_strtolower($claims['email']), '@gmail.com') || ! empty($claims['hd'])];
    }
}

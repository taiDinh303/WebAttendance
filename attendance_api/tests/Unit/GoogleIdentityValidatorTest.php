<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Support\GoogleIdentityValidator;
use Firebase\JWT\JWT;
use Google\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GoogleIdentityValidatorTest extends TestCase
{
    private static \OpenSSLAsymmetricKey $key;

    private static array $jwk;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $windowsConfig = dirname(__DIR__, 3).'/.tools/php/extras/ssl/openssl.cnf';
        if (is_file($windowsConfig)) {
            $options['config'] = $windowsConfig;
        }
        self::$key = openssl_pkey_new($options);
        $details = openssl_pkey_get_details(self::$key);
        $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        self::$jwk = ['kty' => 'RSA', 'kid' => 'fixture', 'alg' => 'RS256', 'use' => 'sig',
            'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e'])];
    }

    private function claims(array $override = []): array
    {
        return array_replace(['sub' => 'signed-subject', 'email' => 'synthetic@gmail.com', 'email_verified' => true,
            'aud' => 'synthetic.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com',
            'exp' => time() + 3600, 'iat' => time(), 'nonce' => 'session-nonce'], $override);
    }

    private function validator(): GoogleIdentityValidator
    {
        config(['services.google.client_id' => 'synthetic.apps.googleusercontent.com']);
        $http = new HttpClient(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['keys' => [self::$jwk]])),
        ]))]);
        $client = new Client;
        $client->setHttpClient($http);

        return new GoogleIdentityValidator($client);
    }

    public function test_real_signature_is_verified_using_google_library_and_a_synthetic_key(): void
    {
        $identity = $this->validator()->validate(JWT::encode($this->claims(), self::$key, 'RS256', 'fixture'), 'session-nonce');
        $this->assertSame('signed-subject', $identity['subject']);
        $this->assertTrue($identity['authoritative']);
    }

    #[DataProvider('invalidClaims')]
    public function test_signed_tokens_with_invalid_claims_are_rejected(array $override): void
    {
        $this->expectException(ApiException::class);
        $this->validator()->validate(JWT::encode($this->claims($override), self::$key, 'RS256', 'fixture'), 'session-nonce');
    }

    public static function invalidClaims(): array
    {
        return ['audience' => [['aud' => 'another-app']], 'issuer' => [['iss' => 'https://attacker.example']],
            'expired' => [['exp' => 1]], 'future' => [['iat' => time() + 3600]], 'nonce' => [['nonce' => 'other-session']],
            'unverified' => [['email_verified' => false]], 'missing_subject' => [['sub' => '']],
            'missing_audience' => [['aud' => null]], 'presenter' => [['azp' => 'another-client']],
            'email' => [['email' => 'invalid']]];
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $token = JWT::encode($this->claims(), self::$key, 'RS256', 'fixture');
        $parts = explode('.', $token);
        $parts[2][0] = $parts[2][0] === 'a' ? 'b' : 'a';
        $this->expectException(ApiException::class);
        $this->validator()->validate(implode('.', $parts), 'session-nonce');
    }
}

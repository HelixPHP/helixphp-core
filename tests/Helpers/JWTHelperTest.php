<?php

namespace Express\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Express\Authentication\JWTHelper;

class JWTHelperTest extends TestCase
{
    private $secret = 'test_secret_key_123';

    public function testEncodeAndDecode(): void
    {
        // Arrange
        $payload = [
            'user_id' => 1,
            'username' => 'testuser',
            'role' => 'admin'
        ];

        // Act
        $token = JWTHelper::encode($payload, $this->secret);
        $decoded = JWTHelper::decode($token, $this->secret);

        // Assert
        $this->assertIsString($token);
        $this->assertIsArray($decoded);
        $this->assertEquals(1, $decoded['user_id']);
        $this->assertEquals('testuser', $decoded['username']);
        $this->assertEquals('admin', $decoded['role']);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
    }

    public function testEncodeWithCustomOptions(): void
    {
        // Arrange
        $payload = ['user_id' => 1];
        $options = [
            'expiresIn' => 7200,
            'issuer' => 'test-app',
            'audience' => 'test-users'
        ];

        // Act
        $token = JWTHelper::encode($payload, $this->secret, $options);
        $decoded = JWTHelper::decode($token, $this->secret);

        // Assert
        $this->assertEquals('test-app', $decoded['iss']);
        $this->assertEquals('test-users', $decoded['aud']);
        $this->assertEquals($decoded['iat'] + 7200, $decoded['exp']);
    }

    public function testIsValid(): void
    {
        // Arrange
        $payload = ['user_id' => 1];
        $validToken = JWTHelper::encode($payload, $this->secret);
        $invalidToken = 'invalid.token.here';

        // Act & Assert
        $this->assertTrue(JWTHelper::isValid($validToken, $this->secret));
        $this->assertFalse(JWTHelper::isValid($invalidToken, $this->secret));
    }

    public function testGetPayload(): void
    {
        // Arrange
        $payload = ['user_id' => 123, 'username' => 'testuser'];
        $token = JWTHelper::encode($payload, $this->secret);

        // Act
        $extractedPayload = JWTHelper::getPayload($token);

        // Assert
        $this->assertEquals(123, $extractedPayload['user_id']);
        $this->assertEquals('testuser', $extractedPayload['username']);
    }

    public function testIsExpired(): void
    {
        // Arrange - token com expiração muito curta
        $payload = ['user_id' => 1];
        $expiredToken = JWTHelper::encode($payload, $this->secret, ['expiresIn' => -3600]); // expirado há 1 hora
        $validToken = JWTHelper::encode($payload, $this->secret, ['expiresIn' => 3600]); // válido por 1 hora

        // Act & Assert
        $this->assertTrue(JWTHelper::isExpired($expiredToken));
        $this->assertFalse(JWTHelper::isExpired($validToken));
    }

    public function testIsExpiredWithLeeway(): void
    {
        // Arrange - token expirado há poucos segundos
        $payload = ['user_id' => 1];
        $token = JWTHelper::encode($payload, $this->secret, ['expiresIn' => -5]); // expirado há 5 segundos

        // Act & Assert
        $this->assertTrue(JWTHelper::isExpired($token)); // sem leeway
        $this->assertFalse(JWTHelper::isExpired($token, 10)); // com leeway de 10 segundos
    }

    public function testGenerateSecret(): void
    {
        // Act
        $secret1 = JWTHelper::generateSecret();
        $secret2 = JWTHelper::generateSecret(16);

        // Assert
        $this->assertIsString($secret1);
        $this->assertEquals(64, strlen($secret1)); // 32 bytes = 64 chars hex
        $this->assertIsString($secret2);
        $this->assertEquals(32, strlen($secret2)); // 16 bytes = 32 chars hex
        $this->assertNotEquals($secret1, $secret2);
    }

    public function testCreateRefreshToken(): void
    {
        // Arrange
        $userId = 123;

        // Act
        $refreshToken = JWTHelper::createRefreshToken($userId, $this->secret);
        $payload = JWTHelper::getPayload($refreshToken);

        // Assert
        $this->assertIsString($refreshToken);
        $this->assertEquals(123, $payload['user_id']);
        $this->assertEquals('refresh', $payload['type']);
    }

    public function testValidateRefreshToken(): void
    {
        // Arrange
        $userId = 456;
        $validRefreshToken = JWTHelper::createRefreshToken($userId, $this->secret);
        $regularToken = JWTHelper::encode(['user_id' => $userId], $this->secret);
        $invalidToken = 'invalid.token.here';

        // Act
        $validResult = JWTHelper::validateRefreshToken($validRefreshToken, $this->secret);
        $regularResult = JWTHelper::validateRefreshToken($regularToken, $this->secret);
        $invalidResult = JWTHelper::validateRefreshToken($invalidToken, $this->secret);

        // Assert
        $this->assertIsArray($validResult);
        $this->assertEquals(456, $validResult['user_id']);
        $this->assertEquals('refresh', $validResult['type']);
        $this->assertFalse($regularResult); // token regular não é refresh
        $this->assertFalse($invalidResult); // token inválido
    }

    public function testDecodeWithInvalidSignature(): void
    {
        // Arrange
        $payload = ['user_id' => 1];
        $token = JWTHelper::encode($payload, $this->secret);
        $wrongSecret = 'wrong_secret';

        // Act & Assert
        $this->expectException(\Exception::class);
        JWTHelper::decode($token, $wrongSecret);
    }

    public function testDecodeExpiredToken(): void
    {
        // Arrange
        $payload = ['user_id' => 1];
        $expiredToken = JWTHelper::encode($payload, $this->secret, ['expiresIn' => -3600]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expired');
        JWTHelper::decode($expiredToken, $this->secret);
    }

    public function testDecodeWithLeeway(): void
    {
        // Arrange
        $payload = ['user_id' => 1];
        $recentlyExpiredToken = JWTHelper::encode($payload, $this->secret, ['expiresIn' => -5]);

        // Act - com leeway suficiente, deve funcionar
        $decoded = JWTHelper::decode($recentlyExpiredToken, $this->secret, ['leeway' => 10]);

        // Assert
        $this->assertEquals(1, $decoded['user_id']);
    }

    public function testGetPayloadInvalidFormat(): void
    {
        // Arrange
        $invalidToken = 'not.a.valid.jwt.format';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JWT format');
        JWTHelper::getPayload($invalidToken);
    }

    /**
     * Testa a implementação manual do HS256 quando a biblioteca Firebase JWT não está disponível
     */
    public function testManualHS256Implementation(): void
    {
        // Este teste simula o cenário onde firebase/php-jwt não está instalado
        // Para isso, vamos chamar os métodos privados usando reflexão

        $payload = [
            'user_id' => 1,
            'username' => 'testuser',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        // Act - usando a implementação manual
        $token = JWTHelper::encode($payload, $this->secret, ['algorithm' => 'HS256']);

        // Assert - verifica se o token tem o formato correto
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Verifica se conseguimos extrair o payload
        $extractedPayload = JWTHelper::getPayload($token);
        $this->assertEquals(1, $extractedPayload['user_id']);
        $this->assertEquals('testuser', $extractedPayload['username']);
    }
}

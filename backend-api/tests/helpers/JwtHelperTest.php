<?php
// backend-api/tests/helpers/JwtHelperTest.php

namespace tests\helpers;

use PHPUnit\Framework\TestCase;
use helpers\JwtHelper;
use models\User;

class JwtHelperTest extends TestCase
{
    /**
     * Helper function to create a realistic-looking JWT string for tests.
     */
    private function createTestJwt(array $payload): string
    {
        $header = '{"alg":"RS256","typ":"JWT"}';
        $encodedHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        // The signature doesn't need to be valid for this unit test.
        $signature = 'fake-signature';
        return "{$encodedHeader}.{$encodedPayload}.{$signature}";
    }

    public function testGetClaimsSuccess()
    {
        // Arrange: Create a valid token and a mock User model
        $jti = 'valid-jti-123';
        $token = $this->createTestJwt([
            'sub' => 'user-abc',
            'exp' => time() + 3600, // Expires in 1 hour
            'jti' => $jti,
        ]);

        $userModelMock = $this->createMock(User::class);
        // Expect isTokenRevoked to be called with the correct JTI and return false
        $userModelMock->method('isTokenRevoked')->with($this->equalTo($jti))->willReturn(false);

        // Act: Call the method being tested
        $claims = JwtHelper::getClaims($token, $userModelMock);

        // Assert: Check that the returned claims are correct
        $this->assertIsObject($claims);
        $this->assertEquals('user-abc', $claims->sub);
        $this->assertEquals($jti, $claims->jti);
    }

    public function testGetClaimsThrowsExceptionForRevokedToken()
    {
        // Arrange: Expect an exception to be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token has been revoked.');

        $jti = 'revoked-jti-456';
        $token = $this->createTestJwt([
            'sub' => 'user-abc',
            'exp' => time() + 3600,
            'jti' => $jti,
        ]);
        
        // This time, the mock will return true, simulating a revoked token
        $userModelMock = $this->createMock(User::class);
        $userModelMock->method('isTokenRevoked')->with($this->equalTo($jti))->willReturn(true);

        // Act
        JwtHelper::getClaims($token, $userModelMock);
    }

    public function testGetClaimsThrowsExceptionForExpiredToken()
    {
        // Arrange
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token has expired.');

        $token = $this->createTestJwt([
            'sub' => 'user-abc',
            'exp' => time() - 3600, // Expired an hour ago
            'jti' => 'any-jti',
        ]);
        
        // The mock shouldn't even be called if expiration check fails first
        $userModelMock = $this->createMock(User::class);
        $userModelMock->expects($this->never())->method('isTokenRevoked');

        // Act
        JwtHelper::getClaims($token, $userModelMock);
    }
}
<?php
// backend-api/tests/controllers/ProfileTest.php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use controllers\Profile; // The class we are testing
use models\User;         // We will be mocking this class

/**
 * Tests for the Profile controller.
 * The annotations below are important: they tell PHPUnit to run each test
 * in a separate process, which is necessary because the controller uses
 * global functions like header() and http_response_code().
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProfileTest extends TestCase
{
    private $userModelMock;

    // This method is called before each test.
    protected function setUp(): void
    {
        // Create a mock (a "fake" version) of the User model.
        $this->userModelMock = $this->createMock(User::class);
    }

    private function createTestJwt(array $payload): string
    {
        $header = '{"alg":"RS256","typ":"JWT"}';
        $encodedHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        return "{$encodedHeader}.{$encodedPayload}.fake-signature";
    }

    public function testIndexReturnsUserDataOnSuccess()
    {
        // --- Arrange ---
        $userId = 'auth0|user123';
        $token = $this->createTestJwt(['sub' => $userId, 'exp' => time() + 3600, 'jti' => 'abc-123']);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

        // Configure the User model mock's behavior for this specific test
        $this->userModelMock->method('isTokenRevoked')->willReturn(false);
        $fakeUser = (object)['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com', 'created_at' => '2025-06-17 18:30:00'];
        $this->userModelMock->method('findUserByOAuthId')->with($userId)->willReturn($fakeUser);

        // Instantiate the controller and INJECT our mock User model
        $profileController = new Profile($this->userModelMock);
        
        // --- Act ---
        // Start output buffering to capture what the controller `echo`s
        ob_start();
        $profileController->index();
        $output = ob_get_clean();
        
        // --- Assert ---
        $this->assertEquals(200, http_response_code());
        $this->assertJsonStringEqualsJsonString(json_encode($fakeUser), $output);
    }

    public function testIndexReturns404WhenUserNotFound()
    {
        // --- Arrange ---
        $userId = 'auth0|user-not-found';
        $token = $this->createTestJwt(['sub' => $userId, 'exp' => time() + 3600, 'jti' => 'def-456']);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        
        $this->userModelMock->method('isTokenRevoked')->willReturn(false);
        // Configure the mock to find no user
        $this->userModelMock->method('findUserByOAuthId')->with($userId)->willReturn(null);

        $profileController = new Profile($this->userModelMock);
        
        // --- Act ---
        ob_start();
        $profileController->index();
        $output = ob_get_clean();
        
        // --- Assert ---
        $this->assertEquals(404, http_response_code());
        $this->assertStringContainsString('User not found', $output);
    }
    
    public function testIndexReturns401ForMissingToken()
    {
        // --- Arrange ---
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $profileController = new Profile($this->userModelMock);
        
        // --- Act ---
        ob_start();
        $profileController->index();
        $output = ob_get_clean();
        
        // --- Assert ---
        $this->assertEquals(401, http_response_code());
        $this->assertStringContainsString('Access token is missing', $output);
    }
}
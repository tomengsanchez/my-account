<?php
namespace controllers;

use helpers\JwtHelper;
use models\User;

class Profile {

    private $userModel;

    public function __construct(User $userModel = null) {
        $this->userModel = $userModel ?? new User();
    }

    public function index() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        try {
            $user = $this->getAuthenticatedUser();
            
            http_response_code(200);
            echo json_encode([
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ]);

        } catch (\Exception $e) {
            // --- THIS IS THE FIX ---
            // If a specific error code (like 404) has not already been set inside the 'try' block,
            // then we should default to 401 Unauthorized.
            // Status codes below 400 are non-errors (200 is the default, 'false' is returned if unset in tests).
            if (http_response_code() < 400) {
                http_response_code(401);
            }
            echo json_encode(['message' => 'Access Denied. ' . $e->getMessage()]);
        }
    }

    private function getAuthenticatedUser(): object {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new \Exception('Access token is missing or malformed.');
        }
        $token = $matches[1];

        $claims = JwtHelper::getClaims($token, $this->userModel);

        $userId = $claims->sub;
        
        $user = $this->userModel->findUserByOAuthId($userId);

        if (!$user) {
            http_response_code(404); 
            throw new \Exception('User not found.');
        }

        return $user;
    }
}
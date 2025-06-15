My Ecosys Account API Documentation
Base URL
/backend-api

Authentication
The backend API relies on a central OAuth 2.0 server for user authentication. The frontend is responsible for directing the user to the OAuth server. Once the user authenticates, the OAuth server provides an oauth_user_id. The backend API then uses this ID to create and manage a local user profile.

For development purposes, the password submitted during registration is hashed to simulate the oauth_user_id.

Endpoints
1. Register User
URL: /users/register

Method: POST

Description: Creates a new user profile in the application database.

Body (JSON):

{
  "username": "testuser",
  "email": "test@example.com",
  "password": "a_strong_password"
}

Success Response:

Code: 201 Created

Content:

{
  "message": "User registered successfully.",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "created_at": "2025-06-15 18:46:00"
  }
}

Error Responses:

Code: 400 Bad Request

Code: 409 Conflict (Email already exists)

2. User Login
URL: /users/login

Method: POST

Description: Authenticates a user with their email and password.

Body (JSON):

{
  "email": "test@example.com",
  "password": "a_strong_password"
}

Success Response:

Code: 200 OK

Content:

{
  "message": "Login successful.",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "created_at": "2025-06-15 18:46:00"
  }
}

Error Response:

Code: 401 Unauthorized

Content:

{
  "message": "Invalid credentials."
}

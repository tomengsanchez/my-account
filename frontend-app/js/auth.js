/**
 * This script handles the logic for the registration and login forms.
 */

function handleAuthForms() {
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const messageDiv = document.getElementById('message');

    // --- Registration Form Handler ---
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const registrationData = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            if (!registrationData.first_name || !registrationData.last_name || !registrationData.username || !registrationData.email || !registrationData.password) {
                messageDiv.innerHTML = `<p class="text-red-600">Please fill out all fields.</p>`;
                return;
            }

            try {
                messageDiv.innerHTML = `<p class="text-blue-600">Processing registration... Please wait.</p>`;
                
                const response = await fetch('/backend-api/users/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(registrationData),
                });

                const result = await response.json();

                if (response.ok) {
                    messageDiv.className = 'text-green-600';
                    messageDiv.innerHTML = `<p>${result.message} You can now <a href="#login" class="font-medium text-blue-600 hover:text-blue-500">log in</a>.</p>`;
                    registerForm.reset();
                } else {
                    messageDiv.className = 'text-red-600';
                    messageDiv.innerHTML = `<p>${result.message || 'An error occurred.'}</p>`;
                    if(result.details) {
                        messageDiv.innerHTML += `<p class="text-xs mt-1">${result.details}</p>`;
                    }
                }

            } catch (error) {
                console.error('Registration Fetch Error:', error);
                messageDiv.className = 'text-red-600';
                messageDiv.innerHTML = `<p>Could not connect to the server.</p>`;
            }
        });
    }

    // --- Login Form Handler (Updated to use secure backend proxy) ---
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                messageDiv.innerHTML = `<p class="text-red-600">Please enter username and password.</p>`;
                return;
            }

            try {
                // --- Step 1: Get Access Token via our Backend Proxy ---
                messageDiv.innerHTML = `<p class="text-blue-600">Step 1/3: Requesting access token...</p>`;
                
                // ** FIX: Call our own backend, which will securely handle the client_secret. **
                const tokenResponse = await fetch('/backend-api/users/token', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    // We only need to send the username and password.
                    body: JSON.stringify({ username, password })
                });

                const tokenData = await tokenResponse.json();

                if (!tokenResponse.ok) {
                    // Our proxy forwards the error from the auth server.
                    throw new Error(tokenData.message || tokenData.error_description || "Invalid credentials.");
                }

                const accessToken = tokenData.access_token;
                if (!accessToken) {
                     throw new Error("Login successful, but no access token was provided.");
                }

                // --- Step 2: Get User Info with the Access Token ---
                messageDiv.innerHTML = `<p class="text-blue-600">Step 2/3: Fetching user profile...</p>`;
                
                // !! IMPORTANT: This endpoint is still a placeholder. Please provide the correct one. !!
                const userResponse = await fetch('https://ithelp.ecosyscorp.ph/etc-backend/api/user', {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`
                    }
                });

                const userData = await userResponse.json();

                if (!userResponse.ok) {
                    throw new Error(userData.message || "Could not fetch user profile with the provided token.");
                }
                
                const oauth_user_id = userData.username; 
                if (!oauth_user_id) {
                    throw new Error("Could not find a username in the user profile response.");
                }

                // --- Step 3: Start a session with our local backend API ---
                messageDiv.innerHTML = `<p class="text-blue-600">Step 3/3: Starting application session...</p>`;
                const localSessionResponse = await fetch('/backend-api/users/session', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ oauth_user_id })
                });

                const localSessionResult = await localSessionResponse.json();

                if (!localSessionResponse.ok) {
                    throw new Error(localSessionResult.message || "Failed to start local session.");
                }

                // Success!
                localStorage.setItem('userInfo', JSON.stringify(localSessionResult.user));
                window.location.hash = '#myapplication';
                location.reload();

            } catch (error) {
                console.error('Login Flow Error:', error);
                messageDiv.className = 'text-red-600';
                messageDiv.innerHTML = `<p>${error.message}</p>`;
            }
        });
    }
}

// --- Script Initialization ---
handleAuthForms();

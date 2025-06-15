{{'<'}}?php // Using .php extension for consistency with other pages ?{{'>'}}

<div class="page-container">
    <h2>My Application Dashboard</h2>
    <p>Welcome to your personal dashboard. This page is only visible to authenticated users.</p>
    
    <!-- A container where we could potentially load user-specific data later -->
    <div id="user-profile-data" class="profile-box">
        <h4>Your Profile</h4>
        <p><strong>Username:</strong> <span id="profile-username"></span></p>
        <p><strong>Email:</strong> <span id="profile-email"></span></p>
        <p><strong>Member Since:</strong> <span id="profile-created-at"></span></p>
    </div>
</div>

<script>
    // This simple script will run when the page is loaded.
    // It gets the user info from localStorage and populates the profile box.
    (function() {
        const userInfo = localStorage.getItem('userInfo');
        if (userInfo) {
            const user = JSON.parse(userInfo);
            document.getElementById('profile-username').textContent = user.username;
            document.getElementById('profile-email').textContent = user.email;
            // Format the date for better readability
            const joinedDate = new Date(user.created_at);
            document.getElementById('profile-created-at').textContent = joinedDate.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
        }
    })();
</script>

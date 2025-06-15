<div class="bg-white rounded-lg shadow-xl p-8 lg:p-12 text-center">
    <h1 class="text-4xl lg:text-5xl font-bold text-gray-800 mb-4">Welcome to My Ecosys Account</h1>
    <p class="text-lg text-gray-600 mb-8">Your central hub for managing all your Ecosys applications and services.</p>
    <div id="home-cta-area">
        <!-- This area will be dynamically populated with buttons based on login status -->
    </div>
</div>

<script>
    // This script runs immediately when the home page is loaded.
    // It checks if the user is logged in and displays the appropriate call-to-action buttons.
    (function() {
        const ctaArea = document.getElementById('home-cta-area');
        const userInfo = localStorage.getItem('userInfo');

        if (userInfo) {
            // If user info is found in localStorage, they are logged in.
            const user = JSON.parse(userInfo);
            ctaArea.innerHTML = `
                <p class="text-lg text-gray-700 mb-4">Hello, <strong>${user.username}</strong>. What would you like to do today?</p>
                <a href="#myapplication" class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 shadow-md hover:shadow-lg">
                    Go to My Application
                </a>
            `;
        } else {
            // If no user info is found, they are a guest.
            ctaArea.innerHTML = `
                <a href="#register" class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 shadow-md hover:shadow-lg">
                    Create an Account
                </a>
                <a href="#login" class="ml-4 inline-block bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-lg hover:bg-gray-300 transition duration-300 shadow-md hover:shadow-lg">
                    Login
                </a>
            `;
        }
    })();
</script>

/**
 * This is the main script for the frontend application.
 * It handles client-side routing and dynamically loading page content.
 */
document.addEventListener('DOMContentLoaded', () => {
    const pageContent = document.getElementById('page-content');

    /**
     * Loads a script dynamically into the document.
     * Ensures that the same script is not loaded multiple times.
     * @param {string} src The source path of the script.
     */
    const loadScript = (src) => {
        // Check if a script with the same source already exists and remove it.
        // This is important for re-initializing logic when navigating between pages.
        const oldScript = document.querySelector(`script[src="${src}"]`);
        if (oldScript) {
            oldScript.remove();
        }
        
        // Create a new script element and append it to the body.
        const script = document.createElement('script');
        script.src = src;
        script.type = 'text/javascript';
        document.body.appendChild(script);
    }

    /**
     * Fetches and loads the content for a given page.
     * @param {string} page The name of the page to load (e.g., 'home', 'login').
     */
    const loadPage = async (page) => {
        try {
            // Fetch the content of the corresponding .php file.
            const response = await fetch(`pages/${page}.php`);
            if (!response.ok) {
                throw new Error(`Page not found: ${page}.php`);
            }
            const html = await response.text();
            pageContent.innerHTML = html;

            // After loading the page content, load any page-specific JavaScript.
            // The auth.js script handles both the login and register forms.
            if (page === 'register' || page === 'login') {
                loadScript('js/auth.js');
            }
        } catch (error) {
            console.error('Error loading page:', error);
            pageContent.innerHTML = `<p class="error">Error: ${error.message}. Please try again.</p>`;
        }
    };

    /**
     * The main router function. It reads the URL hash and loads the correct page.
     */
    const router = () => {
        // Get the hash from the URL, remove the '#', and default to 'home' if empty.
        const hash = window.location.hash.substring(1) || 'home';
        loadPage(hash);
    };

    // Event Listeners
    // Listen for changes in the URL hash to navigate between pages.
    window.addEventListener('hashchange', router);

    // Initial Setup
    // Load the initial page based on the URL when the document is first loaded.
    router();
    // Update the navigation bar based on the current login status.
    updateNav();
});

/**
 * Updates the navigation bar to show/hide links based on login status.
 * It checks for user information stored in localStorage.
 */
function updateNav() {
    const userInfo = localStorage.getItem('userInfo');
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const logoutLink = document.getElementById('logout-link');
    const userInfoDiv = document.getElementById('user-info');

    if (userInfo) {
        // User is logged in
        const user = JSON.parse(userInfo);
        loginLink.style.display = 'none';
        registerLink.style.display = 'none';
        logoutLink.style.display = 'block';
        userInfoDiv.style.display = 'block';
        userInfoDiv.textContent = `Welcome, ${user.username}`;

        // Add a click event listener to the logout button.
        document.getElementById('logout-button').addEventListener('click', (e) => {
            e.preventDefault();
            // Clear user data from localStorage
            localStorage.removeItem('userInfo');
            // Redirect to the home page
            window.location.hash = '#home';
            // Update the nav bar again to reflect the logged-out state.
            updateNav();
        });

    } else {
        // User is not logged in
        loginLink.style.display = 'block';
        registerLink.style.display = 'block';
        logoutLink.style.display = 'none';
        userInfoDiv.style.display = 'none';
        userInfoDiv.textContent = '';
    }
}

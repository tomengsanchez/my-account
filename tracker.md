Comprehensive Development Tracker: My Ecosys Account
This tracker outlines the key phases and tasks for building both the backend API and the frontend user portal.

Phase 1: Backend API Development (Completed)
[x] Task 1.1: Initial Setup

Set up a separated backend/frontend project structure.

Implemented a PHP front controller pattern (.htaccess, public/index.php).

Created a core URL router (Core.php).

[x] Task 1.2: Database Configuration

Created a config.php file for database credentials and application settings.

Built a reusable Database.php class using PDO.

[x] Task 1.3: Migration System

Developed a migrate.php script to handle version-controlled database schema changes.

Created the initial migration for the users table, which includes fields for local profile data and custom application-specific data (age, address, etc.).

[x] Task 1.4: User Creation Endpoint

- Built the User model (models/User.php) to interact with the database.
- Built the Users controller (controllers/Users.php) to handle API logic.
- Successfully created the POST /users endpoint for new user registration.
- Implemented OAuth server integration for user registration.
- Added proper error handling and logging for OAuth registration flow.
- Configured OAuth server settings in config.php.
- Documented the registration process and configuration requirements.

Phase 2: Frontend Core Setup and Layout
[ ] Task 2.1: Project Setup

Initialize project structure.

Integrate Tailwind CSS for styling.

[ ] Task 2.2: Main Application Shell

Create the main index.html file.

Implement a dynamic layout with placeholders for a header, main content, and footer.

[ ] Task 2.3: Client-Side Routing

Implement a basic JavaScript router to load different pages (e.g., Home, Login) into the main content area without a full page refresh.

Phase 3: Frontend User Authentication
[ ] Task 3.1: Login Page

Build the HTML form for the login page.

Write the JavaScript to handle form submission.

Implement the OAuth2 flow:

Request an access_token from the OAuth server (https://ithelp.ecosyscorp.ph/etc-backend).

Send the received token to our own backend for session validation.

[ ] Task 3.2: Registration Page

Build the HTML form for user registration.

Write the JavaScript to submit the new user data to our completed backend API endpoint.

[ ] Task 3.3: State Management

Implement logic to manage the user's login state (logged in / logged out).

Dynamically update the UI (e.g., navigation bar) based on the user's state.

Phase 4: Frontend User Profile & Account Management
(This phase requires the user to be logged in)

[ ] Task 4.1: Profile Management Page

Create a page to display user profile information.

Add functionality to edit and update this information via API calls to our backend.

[ ] Task 4.2: Account Settings Page

Design a page for account settings (e.g., password reset).

Phase 5: Application Features & Polish
[ ] Task 5.1: "My Application" Page

Build out the "My Application" page as specified in structure.json.

[ ] Task 5.2: Responsive Design

Ensure the entire application is fully responsive.

[ ] Task 5.3: Error Handling & Feedback

Improve user feedback for all API interactions and form submissions.

---
### Development Notes
*   **2025-06-15**: Per user request, frontend development was reset. All frontend tasks are marked as incomplete for a fresh start.
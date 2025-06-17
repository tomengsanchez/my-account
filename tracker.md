Comprehensive Development Tracker: My Ecosys Account
### Development Notes
* **2025-06-17**: Implemented a comprehensive unit testing suite for the backend API.
    * Added PHPUnit as a development dependency.
    * Configured the testing environment, including a bootstrap file for loading application settings.
    * Refactored the `JwtHelper` class and `Profile` controller to allow for dependency injection, a prerequisite for isolated testing.
    * Updated the `Users` controller to use the refactored `JwtHelper`.
    * Wrote unit tests for `JwtHelper` to validate token parsing, expiration, and revocation logic.
    * Wrote integration-style unit tests for the `Profile` controller to validate the user profile endpoint, including success and various failure scenarios (e.g., missing token, user not found).
    * Iteratively debugged and fixed issues in the application code that were revealed by the new tests.
* **2025-06-15**: Per user request, frontend development was reset. All frontend tasks are marked as incomplete for a fresh start.
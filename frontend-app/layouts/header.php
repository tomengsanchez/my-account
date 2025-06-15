<header class="bg-white shadow-md sticky top-0 z-50">
    <nav class="container mx-auto px-6 py-3">
        <div class="flex justify-between items-center">
            <a href="#home" class="text-xl font-bold text-gray-800 hover:text-gray-700">My Ecosys Account</a>
            <div class="flex items-center">
                <!-- Desktop Navigation Links -->
                <ul id="nav-links" class="hidden md:flex items-center space-x-2">
                    <li><a href="#home" class="px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md font-medium">Home</a></li>
                    
                    <!-- Authenticated User Links -->
                    <li id="myapplication-link" style="display: none;"><a href="#myapplication" class="px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md font-medium">My Application</a></li>
                    <li id="logout-link" style="display: none;"><a href="#" id="logout-button" class="px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md font-medium">Logout</a></li>

                    <!-- Guest Links -->
                    <li id="login-link"><a href="#login" class="px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md font-medium">Login</a></li>
                    <li id="register-link"><a href="#register" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition duration-200">Register</a></li>
                </ul>
                <div id="user-info" style="display: none;" class="ml-4 text-gray-800 font-semibold"></div>
                <!-- A mobile menu could be added here later -->
            </div>
        </div>
    </nav>
</header>

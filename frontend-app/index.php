<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ecosys Account</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Your Custom CSS (optional, can be used to override Tailwind or add custom styles) -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php // Include the site header
        include 'layouts/header.php'; 
    ?>

    <main class="container mx-auto px-4">
        <!-- The content for each page will be dynamically loaded here -->
        <div id="page-content"></div>
    </main>

    <?php // Include the site footer
        include 'layouts/footer.php'; 
    ?>

    <!-- Main JavaScript file for routing and dynamic content -->
    <script src="js/main.js"></script>
    
</body>
</html>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and get user data
$isLoggedIn = isset($_SESSION['user_id']);
$userData = $isLoggedIn ? $_SESSION['user_data'] : null;
$currentPage = basename($_SERVER['PHP_SELF']);

// Define base URL from config
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <title>GPD Reports - <?php echo ucfirst(str_replace('.php', '', $currentPage)); ?></title>
    <link rel="icon" type="image/webp" href="<?php echo $baseUrl; ?>/assets/images/logo.webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --header-height: 4rem;
            --mobile-nav-height: 3.5rem;
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0);
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        html {
            scroll-behavior: smooth;
            height: -webkit-fill-available;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('<?php echo $baseUrl; ?>/assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            padding-bottom: calc(var(--safe-area-inset-bottom) + 1rem);
        }

        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .touch-scroll {
                -webkit-overflow-scrolling: touch;
                overflow-x: auto;
            }
            
            .mobile-optimized {
                font-size: 16px; /* Prevent auto-zoom on iOS */
                touch-action: manipulation;
            }
        }

        /* Improved mobile table handling */
        @media (max-width: 640px) {
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table-responsive td {
                white-space: nowrap;
            }
        }

        /* Better touch targets for mobile */
        @media (max-width: 768px) {
            button, 
            [role="button"],
            .clickable {
                min-height: 44px;
                min-width: 44px;
                padding: 0.5rem 1rem;
            }
        }

        /* Preloader Styles */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        .preloader.fade-out {
            opacity: 0;
        }
        .preloader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* iOS Safe Area Support */
        @supports (padding: max(0px)) {
            .content-overlay {
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
                padding-bottom: max(1rem, env(safe-area-inset-bottom));
            }
            header {
                padding-top: env(safe-area-inset-top);
                height: calc(var(--header-height) + env(safe-area-inset-top));
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="preloader-spinner"></div>
    </div>

    <div class="content-overlay">
    <header class="bg-white bg-opacity-90 shadow-sm fixed w-full top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="<?php echo $baseUrl; ?>/dashboard.php" class="flex items-center">
                            <img class="h-8 w-auto" src="<?php echo $baseUrl; ?>/assets/images/logo.webp" alt="GPD Reports">
                            <span class="ml-2 text-lg font-semibold text-gray-900">GPD Reports</span>
                        </a>
                    </div>
                    <?php if ($isLoggedIn): ?>
                    <div class="hidden md:block ml-10">
                        <div class="flex items-baseline space-x-4">
                            <a href="<?php echo $baseUrl; ?>/dashboard.php" 
                               class="<?php echo $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600'; ?> px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-home mr-1"></i> Dashboard
                            </a>
                            <a href="<?php echo $baseUrl; ?>/pages/reports/list.php" 
                               class="<?php echo $currentPage === 'list.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600'; ?> px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-list mr-1"></i> Reports
                            </a>
                            <?php if (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                            <a href="<?php echo $baseUrl; ?>/admin/users.php" 
                               class="<?php echo strpos($currentPage, 'users.php') !== false ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600'; ?> px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-users mr-1"></i> User Management
                            </a>
                            <a href="<?php echo $baseUrl; ?>/pages/files/index.php" 
                               class="<?php echo $currentPage === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/files/') !== false ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600'; ?> px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-folder mr-1"></i> Files
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <div class="hidden md:flex items-center">
                            <span class="text-gray-700 text-sm mr-2">
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($userData['username'] ?? ''); ?>
                                <?php if (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                                    <span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Admin
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <button class="md:hidden text-gray-700 hover:text-blue-600" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <a href="<?php echo $baseUrl; ?>/logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $baseUrl; ?>/login.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-sign-in-alt mr-1"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isLoggedIn): ?>
            <!-- Mobile menu -->
            <div class="mobile-menu md:hidden pb-3">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="<?php echo $baseUrl; ?>/dashboard.php" 
                       class="<?php echo $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="<?php echo $baseUrl; ?>/pages/reports/list.php" 
                       class="<?php echo $currentPage === 'list.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-list mr-1"></i> Reports
                    </a>
                    <?php if (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/users.php" 
                       class="<?php echo strpos($currentPage, 'users.php') !== false ? 'bg-blue-50 text-blue-600' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-users mr-1"></i> User Management
                    </a>
                    <a href="<?php echo $baseUrl; ?>/pages/files/index.php" 
                       class="<?php echo $currentPage === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/files/') !== false ? 'bg-blue-50 text-blue-600' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-folder mr-1"></i> Files
                    </a>
                    <?php endif; ?>
                    <div class="px-3 py-2 text-gray-700">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($userData['username'] ?? ''); ?>
                        <?php if (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                            <span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                Admin
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <main class="pt-20 pb-8">

<script>
// Mobile menu toggle
function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    mobileMenu.classList.toggle('active');
}

// Preloader
window.addEventListener('load', function() {
    const preloader = document.getElementById('preloader');
    preloader.classList.add('fade-out');
    setTimeout(() => {
        preloader.style.display = 'none';
    }, 500);
});
</script>

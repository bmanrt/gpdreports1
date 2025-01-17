<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Include header
require_once '../../layouts/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">File Manager</h1>
            </div>

            <!-- Coming Soon Banner -->
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-6">
                    <i class="fas fa-tools text-indigo-600 text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Coming Soon</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    We're working on building a powerful file management system for you. 
                    This feature will be available soon with enhanced capabilities to manage your files efficiently.
                </p>
                <div class="mt-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

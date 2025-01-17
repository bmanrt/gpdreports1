<?php
session_start();

// Include database configuration
require_once __DIR__ . '/../includes/database.php';

// Base URL configuration
if (php_sapi_name() === 'cli') {
    define('BASE_URL', '/gpdreports');
} else {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . '/gpdreports');
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pagination configuration
define('ITEMS_PER_PAGE', 10);

// Region and Zone data
$REGIONS_ZONES = [
    "Region 1" => ["SA Zone 1", "Cape Town Zone 1", "SA Zone 5", "Cape Town Zone 2", "SA Zone 2", "BLW Southern Africa Region", "Middle East Asia", "CE India", "SA Zone 3", "Durban", "BLW Asia & North Africa Region"],
    "Region 2" => ["UK Zone 3 Region 2", "CE Amsterdam DSP", "BLW Europe Region", "Western Europe Zone 4", "UK Zone 3 Region 1", "USA Zone 2 Region 1", "Eastern Europe", "Australia Zone", "Toronto Zone", "Western Europe Zone 2", "USA Zone 1 Region 2/Pacific Islands Region/New Zealand", "USA Region 3", "BLW Canada Sub-Region", "Western Europe Zone 3", "Dallas Zone USA Region 2", "UK Zone 4 Region 1", "Western Europe Zone 1", "UK Zone 1 (Region 2)", "UK Zone 2 Region 1", "UK Zone 1 Region 1", "USA Zone 1 Region 1", "BLW USA Region 2", "Ottawa Zone", "UK Zone 4 Region 2", "Quebec Zone", "BLW USA Region 1"],
    "Region 3" => ["BLW West Africa Region", "Nigeria Zone 4", "Nigeria Zone 5", "Nigeria Zone 1", "Nigeria Zone 2", "Nigeria Zone 3"],
    "Region 4" => ["BLW Central Africa Region", "Ghana Zone 1", "Ghana Zone 2", "Ghana Zone 3", "Ghana Zone 4", "Ghana Zone 5"],
    "Region 5" => ["BLW East Africa Region", "Kenya Zone 1", "Kenya Zone 2", "Kenya Zone 3", "Kenya Zone 4", "Kenya Zone 5"],
    "Region 6" => ["Lagos Sub Zone C", "Benin Zone 2", "Aba Zone", "Benin Zone 1", "Loveworld Church Zone", "South East Zone 1", "BLW East & Central Africa Region", "South East Zone 3", "Edo North & Central", "BLW Nigeria Region"]
];

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Report statuses
define('STATUS_DRAFT', 'draft');
define('STATUS_SUBMITTED', 'submitted');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
?>

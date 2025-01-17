<?php
require_once 'config/config.php';
require_once 'includes/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $region = $_POST['region'] ?? '';
    $zone = $_POST['zone'] ?? '';

    if (empty($username) || empty($password) || empty($region) || empty($zone)) {
        $error = "All fields are required";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // Create new user
                $stmt = $conn->prepare("INSERT INTO users (username, password, region, zone, role, active) VALUES (?, ?, ?, ?, 'user', 1)");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$username, $hashedPassword, $region, $zone]);
                
                $success = "Registration successful! Please login.";
                header("refresh:2;url=" . BASE_URL . "/login.php");
            }
        } catch (Exception $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Define base URL from config
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GPD Reports</title>
    <link rel="icon" type="image/webp" href="<?php echo $baseUrl; ?>/assets/images/logo.webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('<?php echo $baseUrl; ?>/assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .auth-overlay {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 auth-overlay p-8">
        <div>
            <div class="flex justify-center">
                <img src="<?php echo $baseUrl; ?>/assets/images/logo.webp" alt="GPD Reports Logo" class="h-12 w-12">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Create your account</h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    sign in to your account
                </a>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Username">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
                <div>
                    <label for="region" class="sr-only">Region</label>
                    <select id="region" name="region" required onchange="updateZones()"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                        <option value="">Select Region</option>
                        <?php foreach ($REGIONS_ZONES as $region => $zones): ?>
                            <option value="<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="zone" class="sr-only">Zone</label>
                    <select id="zone" name="zone" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                        <option value="">Select Zone</option>
                    </select>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</body>
</html>

<script>
const regionsZones = <?php echo json_encode($REGIONS_ZONES); ?>;

function updateZones() {
    const regionSelect = document.getElementById('region');
    const zoneSelect = document.getElementById('zone');
    const selectedRegion = regionSelect.value;
    
    // Clear current options
    zoneSelect.innerHTML = '<option value="">Select Zone</option>';
    
    if (selectedRegion && regionsZones[selectedRegion]) {
        regionsZones[selectedRegion].forEach(zone => {
            const option = document.createElement('option');
            option.value = zone;
            option.textContent = zone;
            zoneSelect.appendChild(option);
        });
    }
}

// Initialize zones on page load
document.addEventListener('DOMContentLoaded', updateZones);
</script>

<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) || 
            empty($_POST['region']) || empty($_POST['zone'])) {
            throw new Exception('All fields are required');
        }

        if (!Security::validateEmail($_POST['email'])) {
            throw new Exception('Invalid email format');
        }

        $auth = new Auth();
        $auth->register(
            $_POST['username'],
            $_POST['password'],
            $_POST['email'],
            $_POST['region'],
            $_POST['zone'],
            'admin' // Set role as admin
        );

        $success = "Admin account created successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - GPD Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create Admin Account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Create a new administrator account for GPD Reports
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">
                        <a href="login.php" class="font-medium text-green-700 hover:text-green-600">
                            Click here to login
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input id="username" name="username" type="text" required 
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required 
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required 
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700">Region</label>
                        <select id="region" name="region" required 
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            onchange="updateZones()">
                            <option value="">Select Region</option>
                            <?php foreach ($REGIONS_ZONES as $region => $zones): ?>
                                <option value="<?php echo htmlspecialchars($region); ?>">
                                    <?php echo htmlspecialchars($region); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="zone" class="block text-sm font-medium text-gray-700">Zone</label>
                        <select id="zone" name="zone" required 
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select Zone</option>
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Admin Account
                    </button>
                </div>

                <div class="text-center">
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const regions = <?php echo json_encode($REGIONS_ZONES); ?>;
    
    function updateZones() {
        const regionSelect = document.getElementById('region');
        const zoneSelect = document.getElementById('zone');
        const selectedRegion = regionSelect.value;
        
        // Clear existing options
        zoneSelect.innerHTML = '<option value="">Select Zone</option>';
        
        if (selectedRegion && regions[selectedRegion]) {
            regions[selectedRegion].forEach(zone => {
                const option = document.createElement('option');
                option.value = zone;
                option.textContent = zone;
                zoneSelect.appendChild(option);
            });
        }
    }
    </script>
</body>
</html>

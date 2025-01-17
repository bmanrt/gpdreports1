<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);
if ($user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Get current zone filter
$currentZone = isset($_GET['zone']) ? $_GET['zone'] : 'all';

// Get zones from database
try {
    $conn = get_database_connection();
    $stmt = $conn->query("SELECT DISTINCT zone FROM users WHERE zone IS NOT NULL ORDER BY zone");
    $zones = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $zones = [];
}

// Function to get human readable file size
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
}

// Get files based on zone
function get_zone_files($zone = 'all') {
    $base_upload_dir = '../uploads/';
    $files = [];
    
    try {
        $conn = get_database_connection();
        
        $query = "SELECT r.id, r.testimony_file, r.innovation_file, r.report_month, u.zone, u.username 
                 FROM reports r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE (r.testimony_file IS NOT NULL OR r.innovation_file IS NOT NULL)";
                 
        if ($zone !== 'all') {
            $query .= " AND u.zone = :zone";
        }
        
        $query .= " ORDER BY r.report_month DESC";
        
        $stmt = $conn->prepare($query);
        if ($zone !== 'all') {
            $stmt->bindParam(':zone', $zone);
        }
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['testimony_file']) {
                $filepath = $base_upload_dir . 'testimonies/' . $row['testimony_file'];
                if (file_exists($filepath)) {
                    $files[] = [
                        'name' => $row['testimony_file'],
                        'type' => 'Testimony',
                        'size' => human_filesize(filesize($filepath)),
                        'date' => date("Y-m-d H:i:s", filemtime($filepath)),
                        'path' => $filepath,
                        'report_month' => $row['report_month'],
                        'zone' => $row['zone'],
                        'username' => $row['username']
                    ];
                }
            }
            
            if ($row['innovation_file']) {
                $filepath = $base_upload_dir . 'innovations/' . $row['innovation_file'];
                if (file_exists($filepath)) {
                    $files[] = [
                        'name' => $row['innovation_file'],
                        'type' => 'Innovation',
                        'size' => human_filesize(filesize($filepath)),
                        'date' => date("Y-m-d H:i:s", filemtime($filepath)),
                        'path' => $filepath,
                        'report_month' => $row['report_month'],
                        'zone' => $row['zone'],
                        'username' => $row['username']
                    ];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return $files;
}

$files = get_zone_files($currentZone);

// Include header
require_once '../layouts/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">File Manager</h1>
                
                <!-- Zone Filter -->
                <div class="flex items-center space-x-4">
                    <label for="zone" class="text-sm font-medium text-gray-700">Filter by Zone:</label>
                    <select id="zone" name="zone" onchange="window.location.href='?zone=' + this.value"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="all" <?php echo $currentZone === 'all' ? 'selected' : ''; ?>>All Zones</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo htmlspecialchars($zone); ?>" 
                                    <?php echo $currentZone === $zone ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zone); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- File Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($files as $file): ?>
                    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($file['name']); ?>">
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </h3>
                                <div class="mt-1">
                                    <p class="text-sm text-gray-500">
                                        Type: <span class="font-medium"><?php echo $file['type']; ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Zone: <span class="font-medium"><?php echo htmlspecialchars($file['zone']); ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        User: <span class="font-medium"><?php echo htmlspecialchars($file['username']); ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Month: <span class="font-medium"><?php echo date('F Y', strtotime($file['report_month'])); ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Size: <span class="font-medium"><?php echo $file['size']; ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Modified: <span class="font-medium"><?php echo date('M j, Y g:i A', strtotime($file['date'])); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="ml-4">
                                <?php
                                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $iconClass = 'fa-file-alt'; // default icon
                                
                                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $iconClass = 'fa-file-image';
                                } elseif ($fileExtension === 'pdf') {
                                    $iconClass = 'fa-file-pdf';
                                } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                                    $iconClass = 'fa-file-word';
                                }
                                ?>
                                <i class="fas <?php echo $iconClass; ?> text-gray-400 text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo str_replace('../', BASE_URL . '/', $file['path']); ?>" 
                               target="_blank"
                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-download mr-1.5"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($files)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-folder-open text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No files found<?php echo $currentZone !== 'all' ? ' for ' . htmlspecialchars($currentZone) . ' zone' : ''; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional JavaScript functionality here
});
</script>

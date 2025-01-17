<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Ensure user is logged in
Auth::requireLogin();

// Get user data
$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);

// Get report ID
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reportId) {
    header('Location: list.php');
    exit;
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Get report data with user information
$reportQuery = "SELECT r.*, u.username, u.region, u.zone 
                FROM reports r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.id = ?";
$stmt = $conn->prepare($reportQuery);
$stmt->execute([$reportId]);
$report = $stmt->fetch();

// Check if report exists and user has access
if (!$report || (!Auth::isAdmin() && $report['user_id'] != $_SESSION['user_id'])) {
    header('Location: list.php');
    exit;
}

// Get report images
$imagesQuery = "SELECT * FROM report_images WHERE report_id = ?";
$stmt = $conn->prepare($imagesQuery);
$stmt->execute([$reportId]);
$images = $stmt->fetchAll();

// Include header
require_once '../../layouts/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
    <!-- Back button and title -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 space-y-2 sm:space-y-0">
        <a href="list.php" class="inline-flex items-center text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i> Back to Reports
        </a>
        <div class="flex items-center space-x-2">
            <?php if ($report['user_id'] == $_SESSION['user_id'] || Auth::isAdmin()): ?>
                <a href="edit.php?id=<?php echo $reportId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            <?php endif; ?>
            <a href="download_pdf.php?id=<?php echo $reportId; ?>" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-download mr-1"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Report Status and Meta -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex flex-col">
                <span class="text-sm text-gray-600">Status</span>
                <span class="font-semibold <?php echo getStatusColor($report['status']); ?>"><?php echo ucfirst($report['status']); ?></span>
            </div>
            <div class="flex flex-col">
                <span class="text-sm text-gray-600">Submitted By</span>
                <span class="font-semibold"><?php echo htmlspecialchars($report['username']); ?></span>
            </div>
            <div class="flex flex-col">
                <span class="text-sm text-gray-600">Region</span>
                <span class="font-semibold"><?php echo htmlspecialchars($report['region']); ?></span>
            </div>
            <div class="flex flex-col">
                <span class="text-sm text-gray-600">Zone</span>
                <span class="font-semibold"><?php echo htmlspecialchars($report['zone']); ?></span>
            </div>
        </div>
    </div>

    <!-- Total Copies Report -->
    <div class="grid grid-cols-1 gap-4 sm:gap-6 mb-4 sm:mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Total Copies Report</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php 
                    $totalCopiesFields = [
                        'total_copies' => 'Total Copies',
                        'total_distribution' => 'Total Distribution',
                    ];
                    foreach ($totalCopiesFields as $field => $label): 
                ?>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-600 mb-1"><?php echo $label; ?></div>
                        <div class="text-xl font-semibold"><?php echo number_format($report[$field]); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Distribution Report -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribution Report on Sub Campaigns</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php 
                $distributionFields = [
                    'penetrating_truth' => 'Penetrating with Truth',
                    'penetrating_languages' => 'Penetrating with Languages',
                    'youth_aglow' => 'Youth Aglow',
                    'minister_campaign' => 'Every Minister Campaign',
                    'say_yes_kids' => 'Say Yes to Kids',
                    'no_one_left' => 'No one left Behind',
                    'teevolution' => 'Teevolution',
                    'subscriptions' => 'Subscriptions',
                ];
                foreach ($distributionFields as $field => $label): 
            ?>
                <div class="bg-gray-50 rounded p-4">
                    <div class="text-sm text-gray-600 mb-1"><?php echo $label; ?></div>
                    <div class="text-xl font-semibold"><?php echo number_format($report[$field]); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Reach and Impact -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Reach and Impact Report</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
                $reachFields = [
                    'total_distribution' => 'Total Distribution',
                    'souls_won' => 'Souls Won',
                    'rhapsody_outreaches' => 'Rhapsody Outreaches',
                    'rhapsody_cells' => 'Rhapsody Cells',
                    'new_churches' => 'New Churches',
                    'new_partners' => 'New Partners',
                    'lingual_cells' => 'Lingual Cells',
                    'language_churches' => 'Language Churches',
                    'languages_sponsored' => 'Languages Sponsored',
                    'distribution_centers' => 'Distribution Centers',
                    'external_ministers' => 'External Ministers',
                ];
                foreach ($reachFields as $field => $label): 
            ?>
                <div class="bg-gray-50 rounded p-4">
                    <div class="text-sm text-gray-600 mb-1"><?php echo $label; ?></div>
                    <div class="text-xl font-semibold"><?php echo number_format($report[$field]); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Attachments -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Attachments</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if ($report['testimonies_file']): ?>
                <div class="bg-gray-50 rounded p-4">
                    <h3 class="font-medium text-gray-900 mb-2">Testimonies</h3>
                    <a href="<?php echo BASE_URL; ?>/uploads/<?php echo $report['testimonies_file']; ?>" 
                       class="text-blue-600 hover:text-blue-800 inline-flex items-center" 
                       target="_blank">
                        <i class="fas fa-file-alt mr-2"></i>
                        View Testimonies
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($report['pictures_file']): ?>
                <div class="bg-gray-50 rounded p-4">
                    <h3 class="font-medium text-gray-900 mb-2">Pictures</h3>
                    <a href="<?php echo BASE_URL; ?>/uploads/<?php echo $report['pictures_file']; ?>" 
                       class="text-blue-600 hover:text-blue-800 inline-flex items-center" 
                       target="_blank">
                        <i class="fas fa-images mr-2"></i>
                        View Pictures
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pictures Gallery -->
    <?php if (!empty($images)): ?>
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Pictures Gallery</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($images as $image): ?>
                <div class="relative aspect-w-1 aspect-h-1">
                    <img src="../../uploads/pictures/<?php echo $image['image_path']; ?>" 
                         alt="Report Image" 
                         class="object-cover rounded-lg shadow-sm hover:opacity-75 transition-opacity cursor-pointer"
                         onclick="window.open('../../uploads/pictures/<?php echo $image['image_path']; ?>', '_blank')">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</main>
</body>
</html>
<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is admin
Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: list.php');
    exit;
}

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

// Get report images
$imagesQuery = "SELECT * FROM report_images WHERE report_id = ?";
$stmt = $conn->prepare($imagesQuery);
$stmt->execute([$reportId]);
$images = $stmt->fetchAll();

// Check if report exists
if (!$report) {
    header('Location: list.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update report data
        $updateQuery = "UPDATE reports SET 
            report_month = :report_month,
            total_copies = :total_copies,
            champions_league_groups = :champions_league_groups,
            groups_1m = :groups_1m,
            groups_500k = :groups_500k,
            groups_250k = :groups_250k,
            groups_100k = :groups_100k,
            monthly_copies = :monthly_copies,
            wonder_alerts = :wonder_alerts,
            kids_alerts = :kids_alerts,
            language_missions = :language_missions,
            penetrating_truth = :penetrating_truth,
            penetrating_languages = :penetrating_languages,
            youth_aglow = :youth_aglow,
            minister_campaign = :minister_campaign,
            say_yes_kids = :say_yes_kids,
            no_one_left = :no_one_left,
            teevolution = :teevolution,
            subscriptions = :subscriptions,
            prayer_programs = :prayer_programs,
            partner_programs = :partner_programs,
            total_distribution = :total_distribution,
            souls_won = :souls_won,
            rhapsody_outreaches = :rhapsody_outreaches,
            rhapsody_cells = :rhapsody_cells,
            new_churches = :new_churches,
            new_partners = :new_partners,
            lingual_cells = :lingual_cells,
            language_churches = :language_churches,
            languages_sponsored = :languages_sponsored,
            distribution_centers = :distribution_centers,
            external_ministers = :external_ministers
            WHERE id = :id";

        $stmt = $conn->prepare($updateQuery);
        
        // Bind parameters
        $params = [
            ':id' => $reportId,
            ':report_month' => date('Y-m-01', strtotime($_POST['report_month'] . '-01')),
            ':total_copies' => $_POST['total_copies'] ?? $report['total_copies'],
            ':champions_league_groups' => $_POST['champions_league_groups'] ?? $report['champions_league_groups'],
            ':groups_1m' => $_POST['groups_1m'] ?? $report['groups_1m'],
            ':groups_500k' => $_POST['groups_500k'] ?? $report['groups_500k'],
            ':groups_250k' => $_POST['groups_250k'] ?? $report['groups_250k'],
            ':groups_100k' => $_POST['groups_100k'] ?? $report['groups_100k'],
            ':monthly_copies' => $_POST['monthly_copies'] ?? $report['monthly_copies'],
            ':wonder_alerts' => $_POST['wonder_alerts'] ?? $report['wonder_alerts'],
            ':kids_alerts' => $_POST['kids_alerts'] ?? $report['kids_alerts'],
            ':language_missions' => $_POST['language_missions'] ?? $report['language_missions'],
            ':penetrating_truth' => $_POST['penetrating_truth'] ?? $report['penetrating_truth'],
            ':penetrating_languages' => $_POST['penetrating_languages'] ?? $report['penetrating_languages'],
            ':youth_aglow' => $_POST['youth_aglow'] ?? $report['youth_aglow'],
            ':minister_campaign' => $_POST['minister_campaign'] ?? $report['minister_campaign'],
            ':say_yes_kids' => $_POST['say_yes_kids'] ?? $report['say_yes_kids'],
            ':no_one_left' => $_POST['no_one_left'] ?? $report['no_one_left'],
            ':teevolution' => $_POST['teevolution'] ?? $report['teevolution'],
            ':subscriptions' => $_POST['subscriptions'] ?? $report['subscriptions'],
            ':prayer_programs' => $_POST['prayer_programs'] ?? $report['prayer_programs'],
            ':partner_programs' => $_POST['partner_programs'] ?? $report['partner_programs'],
            ':total_distribution' => $_POST['total_distribution'] ?? $report['total_distribution'],
            ':souls_won' => $_POST['souls_won'] ?? $report['souls_won'],
            ':rhapsody_outreaches' => $_POST['rhapsody_outreaches'] ?? $report['rhapsody_outreaches'],
            ':rhapsody_cells' => $_POST['rhapsody_cells'] ?? $report['rhapsody_cells'],
            ':new_churches' => $_POST['new_churches'] ?? $report['new_churches'],
            ':new_partners' => $_POST['new_partners'] ?? $report['new_partners'],
            ':lingual_cells' => $_POST['lingual_cells'] ?? $report['lingual_cells'],
            ':language_churches' => $_POST['language_churches'] ?? $report['language_churches'],
            ':languages_sponsored' => $_POST['languages_sponsored'] ?? $report['languages_sponsored'],
            ':distribution_centers' => $_POST['distribution_centers'] ?? $report['distribution_centers'],
            ':external_ministers' => $_POST['external_ministers'] ?? $report['external_ministers']
        ];

        $stmt->execute($params);

        // Handle file updates if new files are uploaded
        if (isset($_FILES['testimonies_file']) && $_FILES['testimonies_file']['error'] === UPLOAD_ERR_OK) {
            $testimonies_file = uploadFile($_FILES['testimonies_file'], '../../uploads/documents/');
            if ($testimonies_file) {
                // Delete old file if exists
                if ($report['testimonies_file'] && file_exists('../../uploads/documents/' . $report['testimonies_file'])) {
                    unlink('../../uploads/documents/' . $report['testimonies_file']);
                }
                $stmt = $conn->prepare("UPDATE reports SET testimonies_file = ? WHERE id = ?");
                $stmt->execute([$testimonies_file, $reportId]);
            }
        }
        
        if (isset($_FILES['innovations_file']) && $_FILES['innovations_file']['error'] === UPLOAD_ERR_OK) {
            $innovations_file = uploadFile($_FILES['innovations_file'], '../../uploads/documents/');
            if ($innovations_file) {
                // Delete old file if exists
                if ($report['innovations_file'] && file_exists('../../uploads/documents/' . $report['innovations_file'])) {
                    unlink('../../uploads/documents/' . $report['innovations_file']);
                }
                $stmt = $conn->prepare("UPDATE reports SET innovations_file = ? WHERE id = ?");
                $stmt->execute([$innovations_file, $reportId]);
            }
        }

        // Handle report images
        if (isset($_FILES['report_images'])) {
            $uploadDir = '../../uploads/pictures/';
            
            // Create directories if they don't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Process each uploaded image
            $uploadedFiles = reArrayFiles($_FILES['report_images']);
            foreach ($uploadedFiles as $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $filename = uploadFile($file, $uploadDir);
                    if ($filename) {
                        $stmt = $conn->prepare("INSERT INTO report_images (report_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$reportId, $filename]);
                    }
                }
            }
        }

        // Handle image deletions
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imageId) {
                // Get image path
                $stmt = $conn->prepare("SELECT image_path FROM report_images WHERE id = ? AND report_id = ?");
                $stmt->execute([$imageId, $reportId]);
                $image = $stmt->fetch();

                if ($image) {
                    // Delete file from server
                    $imagePath = '../../uploads/pictures/' . $image['image_path'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }

                    // Delete from database
                    $stmt = $conn->prepare("DELETE FROM report_images WHERE id = ? AND report_id = ?");
                    $stmt->execute([$imageId, $reportId]);
                }
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Report updated successfully";
        header("Location: view.php?id=" . $reportId);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error updating report: " . $e->getMessage();
    }
}

// Include header
require_once '../../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Report</h1>
            <a href="view.php?id=<?php echo $reportId; ?>" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-times"></i>
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-6">
                      <!-- Report Month -->
                      <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Report Details</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Report Month</label>
                    <div class="mt-1 block w-full py-2 px-3 bg-gray-50 text-gray-700 border border-gray-300 rounded-md">
                        <?php echo date('F Y', strtotime($report['report_month'])); ?>
                    </div>
                    <input type="hidden" 
                           name="report_month" 
                           value="<?php echo date('Y-m', strtotime($report['report_month'])); ?>">
                </div>
            </div>


            <!-- Total Copies Report -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Total Copies Report</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Copies</label>
                        <input type="number" name="total_copies" value="<?php echo $report['total_copies']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Champions League Groups</label>
                        <input type="number" name="champions_league_groups" value="<?php echo $report['champions_league_groups']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">1M Groups</label>
                        <input type="number" name="groups_1m" value="<?php echo $report['groups_1m']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">500K Groups</label>
                        <input type="number" name="groups_500k" value="<?php echo $report['groups_500k']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">250K Groups</label>
                        <input type="number" name="groups_250k" value="<?php echo $report['groups_250k']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">100K Groups</label>
                        <input type="number" name="groups_100k" value="<?php echo $report['groups_100k']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Monthly Copies and Alerts -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Copies and Alerts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Monthly Copies</label>
                        <input type="number" name="monthly_copies" value="<?php echo $report['monthly_copies']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Wonder Alerts</label>
                        <input type="number" name="wonder_alerts" value="<?php echo $report['wonder_alerts']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kids Alerts</label>
                        <input type="number" name="kids_alerts" value="<?php echo $report['kids_alerts']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Language and Truth -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Language and Truth</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Language Missions</label>
                        <input type="number" name="language_missions" value="<?php echo $report['language_missions']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Penetrating Truth</label>
                        <input type="number" name="penetrating_truth" value="<?php echo $report['penetrating_truth']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Campaigns and Programs -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Campaigns and Programs</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Minister Campaign</label>
                        <input type="number" name="minister_campaign" value="<?php echo $report['minister_campaign']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Say Yes Kids</label>
                        <input type="number" name="say_yes_kids" value="<?php echo $report['say_yes_kids']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Distribution and Impact -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribution and Impact</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Distribution</label>
                        <input type="number" name="total_distribution" value="<?php echo $report['total_distribution']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Souls Won</label>
                        <input type="number" name="souls_won" value="<?php echo $report['souls_won']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Supporting Documents -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h2>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Existing Documents -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Testimonies File</label>
                        <input type="file" name="testimonies_file" 
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <?php if ($report['testimonies_file']): ?>
                            <p class="mt-2 text-sm text-gray-500">Current file: <?php echo $report['testimonies_file']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Innovations File</label>
                        <input type="file" name="innovations_file" 
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <?php if ($report['innovations_file']): ?>
                            <p class="mt-2 text-sm text-gray-500">Current file: <?php echo $report['innovations_file']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Report Images -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Images</label>
                        
                        <!-- Existing Images -->
                        <?php if ($images): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <?php foreach ($images as $image): ?>
                            <div class="relative group">
                                <img src="../../uploads/pictures/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="Report Image" 
                                     class="w-full h-32 object-cover rounded-lg">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black bg-opacity-50 rounded-lg">
                                    <label class="flex items-center space-x-2 text-white cursor-pointer">
                                        <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" 
                                               class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                        <span>Delete</span>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Upload New Images -->
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Add New Images</label>
                            <input type="file" name="report_images[]" multiple accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">You can select multiple images</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../layouts/footer.php'; ?>

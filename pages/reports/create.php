<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get user data
$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);
$_SESSION['user_data'] = $user;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $conn->beginTransaction();

        // Insert report data
        $reportQuery = "INSERT INTO reports (
            user_id, report_month, total_copies, champions_league_groups, groups_1m, 
            groups_500k, groups_250k, groups_100k, monthly_copies, 
            wonder_alerts, kids_alerts, language_missions, penetrating_truth,
            penetrating_languages, youth_aglow, minister_campaign, say_yes_kids,
            no_one_left, teevolution, subscriptions, prayer_programs,
            partner_programs, total_distribution, souls_won, rhapsody_outreaches,
            rhapsody_cells, new_churches, new_partners, lingual_cells,
            language_churches, languages_sponsored, distribution_centers,
            external_ministers, testimonies_file, innovations_file
        ) VALUES (
            :user_id, :report_month, :total_copies, :champions_league_groups, :groups_1m,
            :groups_500k, :groups_250k, :groups_100k, :monthly_copies,
            :wonder_alerts, :kids_alerts, :language_missions, :penetrating_truth,
            :penetrating_languages, :youth_aglow, :minister_campaign, :say_yes_kids,
            :no_one_left, :teevolution, :subscriptions, :prayer_programs,
            :partner_programs, :total_distribution, :souls_won, :rhapsody_outreaches,
            :rhapsody_cells, :new_churches, :new_partners, :lingual_cells,
            :language_churches, :languages_sponsored, :distribution_centers,
            :external_ministers, :testimonies_file, :innovations_file
        )";

        $stmt = $conn->prepare($reportQuery);
        
        // Handle file uploads for testimonies and innovations
        $testimonies_file = '';
        $innovations_file = '';
        
        if (isset($_FILES['testimonies_file']) && $_FILES['testimonies_file']['error'] === UPLOAD_ERR_OK) {
            $testimonies_file = uploadFile($_FILES['testimonies_file'], '../../uploads/documents/');
        }
        
        if (isset($_FILES['innovations_file']) && $_FILES['innovations_file']['error'] === UPLOAD_ERR_OK) {
            $innovations_file = uploadFile($_FILES['innovations_file'], '../../uploads/documents/');
        }

        // Bind all the parameters
        $params = [
            ':user_id' => $_SESSION['user_id'],
            ':report_month' => date('Y-m-01', strtotime($_POST['report_month'] . '-01')),
            ':total_copies' => $_POST['total_copies'] ?? 0,
            ':champions_league_groups' => $_POST['champions_league_groups'] ?? 0,
            ':groups_1m' => $_POST['groups_1m'] ?? 0,
            ':groups_500k' => $_POST['groups_500k'] ?? 0,
            ':groups_250k' => $_POST['groups_250k'] ?? 0,
            ':groups_100k' => $_POST['groups_100k'] ?? 0,
            ':monthly_copies' => $_POST['monthly_copies'] ?? 0,
            ':wonder_alerts' => $_POST['wonder_alerts'] ?? 0,
            ':kids_alerts' => $_POST['kids_alerts'] ?? 0,
            ':language_missions' => $_POST['language_missions'] ?? 0,
            ':penetrating_truth' => $_POST['penetrating_truth'] ?? 0,
            ':penetrating_languages' => $_POST['penetrating_languages'] ?? 0,
            ':youth_aglow' => $_POST['youth_aglow'] ?? 0,
            ':minister_campaign' => $_POST['minister_campaign'] ?? 0,
            ':say_yes_kids' => $_POST['say_yes_kids'] ?? 0,
            ':no_one_left' => $_POST['no_one_left'] ?? 0,
            ':teevolution' => $_POST['teevolution'] ?? 0,
            ':subscriptions' => $_POST['subscriptions'] ?? 0,
            ':prayer_programs' => $_POST['prayer_programs'] ?? 0,
            ':partner_programs' => $_POST['partner_programs'] ?? 0,
            ':total_distribution' => $_POST['total_distribution'] ?? 0,
            ':souls_won' => $_POST['souls_won'] ?? 0,
            ':rhapsody_outreaches' => $_POST['rhapsody_outreaches'] ?? 0,
            ':rhapsody_cells' => $_POST['rhapsody_cells'] ?? 0,
            ':new_churches' => $_POST['new_churches'] ?? 0,
            ':new_partners' => $_POST['new_partners'] ?? 0,
            ':lingual_cells' => $_POST['lingual_cells'] ?? 0,
            ':language_churches' => $_POST['language_churches'] ?? 0,
            ':languages_sponsored' => $_POST['languages_sponsored'] ?? 0,
            ':distribution_centers' => $_POST['distribution_centers'] ?? 0,
            ':external_ministers' => $_POST['external_ministers'] ?? 0,
            ':testimonies_file' => $testimonies_file,
            ':innovations_file' => $innovations_file
        ];

        foreach ($params as $key => &$value) {
            if (is_numeric($value)) {
                $value = (int)$value;
            }
        }

        $stmt->execute($params);
        $reportId = $conn->lastInsertId();

        // Handle image uploads
        if (isset($_FILES['report_images'])) {
            $uploadDir = '../../uploads/pictures/';
            
            // Create directories if they don't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Process each uploaded image
            $totalFiles = count($_FILES['report_images']['name']);
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['report_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['report_images']['tmp_name'][$i];
                    $fileName = uniqid() . '_' . basename($_FILES['report_images']['name'][$i]);
                    $filePath = $uploadDir . $fileName;
                    
                    // Move uploaded file
                    if (move_uploaded_file($tmpName, $filePath)) {
                        // Insert image record into database
                        $imageQuery = "INSERT INTO report_images (report_id, image_path) VALUES (:report_id, :image_path)";
                        $imageStmt = $conn->prepare($imageQuery);
                        $imageStmt->execute([
                            ':report_id' => $reportId,
                            ':image_path' => $fileName
                        ]);
                    }
                }
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Report created successfully!";
        header('Location: view.php?id=' . $reportId);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error creating report: " . $e->getMessage();
    }
}

// Include header
require_once '../../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Report</h1>

        <form method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-6">
            <!-- Report Month -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Report Details</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Report Month</label>
                    <input type="month" 
                           name="report_month" 
                           value="<?php echo date('Y-m'); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           required>
                    <p class="mt-2 text-sm text-gray-500">Select the month for this report</p>
                </div>
            </div>

            <!-- Total Copies -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Total Copies</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Number of Copies</label>
                    <input type="number" name="total_copies" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Group Cycles -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Group Cycles</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Champions League Groups</label>
                        <input type="number" name="champions_league_groups" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">1M Groups</label>
                        <input type="number" name="groups_1m" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">500K Groups</label>
                        <input type="number" name="groups_500k" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">250K Groups</label>
                        <input type="number" name="groups_250k" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">100K Groups</label>
                        <input type="number" name="groups_100k" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Monthly Copies</label>
                        <input type="number" name="monthly_copies" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Wonder Alerts</label>
                        <input type="number" name="wonder_alerts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kids Alerts</label>
                        <input type="number" name="kids_alerts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Language and Truth -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Language and Truth</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Language Missions</label>
                        <input type="number" name="language_missions" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Penetrating Truth</label>
                        <input type="number" name="penetrating_truth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Penetrating Languages</label>
                        <input type="number" name="penetrating_languages" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Youth and Campaign -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Youth and Campaign</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Youth Aglow</label>
                        <input type="number" name="youth_aglow" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Minister Campaign</label>
                        <input type="number" name="minister_campaign" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Say Yes Kids</label>
                        <input type="number" name="say_yes_kids" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Programs -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Programs</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">No One Left</label>
                        <input type="number" name="no_one_left" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teevolution</label>
                        <input type="number" name="teevolution" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subscriptions</label>
                        <input type="number" name="subscriptions" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Prayer Programs</label>
                        <input type="number" name="prayer_programs" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Partner Programs</label>
                        <input type="number" name="partner_programs" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Distribution and Impact -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribution and Impact</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Distribution</label>
                        <input type="number" name="total_distribution" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Souls Won</label>
                        <input type="number" name="souls_won" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rhapsody Outreaches</label>
                        <input type="number" name="rhapsody_outreaches" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rhapsody Cells</label>
                        <input type="number" name="rhapsody_cells" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Growth Metrics -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Growth Metrics</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Churches</label>
                        <input type="number" name="new_churches" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Partners</label>
                        <input type="number" name="new_partners" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lingual Cells</label>
                        <input type="number" name="lingual_cells" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Language Churches</label>
                        <input type="number" name="language_churches" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Languages Sponsored</label>
                        <input type="number" name="languages_sponsored" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Distribution Centers</label>
                        <input type="number" name="distribution_centers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">External Ministers</label>
                        <input type="number" name="external_ministers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- File Uploads -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Testimonies File</label>
                        <input type="file" name="testimonies_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Innovations File</label>
                        <input type="file" name="innovations_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
            </div>

            <!-- Image Uploads -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Report Images</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Report Images</label>
                        <input type="file" name="report_images[]" multiple class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div id="image-preview"></div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Create Report
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.querySelector('input[name="report_images[]"]');
    const previewContainer = document.getElementById('image-preview');

    imageInput.addEventListener('change', function() {
        previewContainer.innerHTML = ''; // Clear existing previews
        
        if (this.files) {
            const files = Array.from(this.files);
            
            // Create preview grid
            const grid = document.createElement('div');
            grid.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-4';
            
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'relative aspect-square';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover rounded-lg';
                        
                        previewDiv.appendChild(img);
                        grid.appendChild(previewDiv);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            previewContainer.appendChild(grid);
        }
    });
});
</script>
<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure user is logged in
Auth::requireLogin();

// Get report ID
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reportId) {
    header('Location: ../pages/reports/list.php');
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
    header('Location: ../pages/reports/list.php');
    exit;
}

// Calculate distribution rate
$distributionRate = ($report['total_copies'] > 0) 
    ? round(($report['total_distribution'] / $report['total_copies']) * 100, 2)
    : 0;

// Set headers for HTML download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="report_' . $reportId . '_' . date('Y-m-d') . '.html"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report #<?php echo $reportId; ?> - <?php echo date('Y-m-d', strtotime($report['created_at'])); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Report Header -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Report #<?php echo $reportId; ?></h1>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Submitted By</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($report['username']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Region</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($report['region']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Zone</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($report['zone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Total Copies Report -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Total Copies Report</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Copies</span>
                            <span class="font-semibold text-xl text-blue-600"><?php echo number_format($report['total_copies']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Distribution</span>
                            <span class="font-semibold text-xl text-green-600"><?php echo number_format($report['total_distribution']); ?></span>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-500">Distribution Rate</div>
                            <div class="mt-1 flex justify-between items-center">
                                <div class="text-lg font-semibold text-indigo-600"><?php echo $distributionRate; ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Champions League Groups -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Champions League Groups</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">1M Groups</span>
                            <span class="font-semibold text-xl"><?php echo number_format($report['groups_1m']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">500K Groups</span>
                            <span class="font-semibold text-xl"><?php echo number_format($report['groups_500k']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">250K Groups</span>
                            <span class="font-semibold text-xl"><?php echo number_format($report['groups_250k']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">100K Groups</span>
                            <span class="font-semibold text-xl"><?php echo number_format($report['groups_100k']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reach and Impact -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Reach and Impact Report</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">Souls Won</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['souls_won']); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">New Churches</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['new_churches']); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">New Partners</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['new_partners']); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">Lingual Cells</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['lingual_cells']); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">Language Churches</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['language_churches']); ?></div>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">Languages Sponsored</div>
                        <div class="mt-1 text-xl font-semibold"><?php echo number_format($report['languages_sponsored']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add print functionality
        if (window.opener === null) {
            window.print();
        }
    </script>
</body>
</html>

<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get user data
$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);
$_SESSION['user_data'] = $user;

// Get reports count and recent reports
$db = new Database();
$conn = $db->getConnection();

// Initialize variables
$reportsCount = 0;
$recentReports = [];
$monthlyTotal = 0;
$totalCopies = 0;

try {
    // Get reports count
    if ($user['role'] === 'admin') {
        $reportsQuery = "SELECT COUNT(*) as count FROM reports";
        $stmt = $conn->query($reportsQuery);
    } else {
        $reportsQuery = "SELECT COUNT(*) as count FROM reports WHERE user_id = ?";
        $stmt = $conn->prepare($reportsQuery);
        $stmt->execute([$_SESSION['user_id']]);
    }
    $reportsCount = $stmt->fetch()['count'];

    // Get total copies
    if ($user['role'] === 'admin') {
        $stmt = $conn->query("SELECT SUM(total_copies) as total FROM reports");
    } else {
        $stmt = $conn->prepare("SELECT SUM(total_copies) as total FROM reports WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $totalCopies = $stmt->fetch()['total'] ?? 0;

    // Get recent reports
    if ($user['role'] === 'admin') {
        $recentReportsQuery = "SELECT r.*, u.username FROM reports r 
                              LEFT JOIN users u ON r.user_id = u.id 
                              ORDER BY r.created_at DESC LIMIT 5";
        $stmt = $conn->query($recentReportsQuery);
        $recentReports = $stmt->fetchAll();
    } else {
        $recentReportsQuery = "SELECT r.*, u.username FROM reports r 
                              LEFT JOIN users u ON r.user_id = u.id 
                              WHERE r.user_id = ? 
                              ORDER BY r.created_at DESC LIMIT 5";
        $stmt = $conn->prepare($recentReportsQuery);
        $stmt->execute([$_SESSION['user_id']]);
        $recentReports = $stmt->fetchAll();
    }

    // Get monthly total - only if the column exists
    try {
        if ($user['role'] === 'admin') {
            $monthlyQuery = "SELECT SUM(monthly_copies) as total FROM reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
            $stmt = $conn->query($monthlyQuery);
        } else {
            $monthlyQuery = "SELECT SUM(monthly_copies) as total FROM reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND user_id = ?";
            $stmt = $conn->prepare($monthlyQuery);
            $stmt->execute([$_SESSION['user_id']]);
        }
        $monthlyTotal = $stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        // Column doesn't exist yet, keep default value
        $monthlyTotal = 0;
    }
} catch (PDOException $e) {
    // Table doesn't exist yet or other database error
    error_log("Database error: " . $e->getMessage());
}

// Include header
require_once 'layouts/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Zone Information -->
    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <i class="fas fa-map-marker-alt text-white"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Your Zone</dt>
                        <dd class="text-xl font-semibold text-gray-900">
                            <?php echo htmlspecialchars($user['zone'] ?? 'Not Set'); ?>
                            <span class="text-sm text-gray-500 ml-2">(<?php echo htmlspecialchars($user['region'] ?? 'Not Set'); ?>)</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Reports Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-file-alt text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Reports</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?php echo $reportsCount; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="pages/reports/list.php" class="font-medium text-blue-600 hover:text-blue-500">
                        View all reports <span class="ml-1">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>

            <!-- Total Copies -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                        <i class="fas fa-copy text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Copies</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?php echo number_format($totalCopies); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month's Copies -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">This Month's Copies</dt>
                            <dd class="text-3xl font-semibold text-gray-900">
                                <?php echo number_format($monthlyTotal); ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
        <!-- Total Users Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                            <dd class="text-3xl font-semibold text-gray-900">
                                <?php
                                    try {
                                        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                                        echo $stmt->fetch()['count'];
                                    } catch (PDOException $e) {
                                        echo '0';
                                    }
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="admin/users.php" class="font-medium text-blue-600 hover:text-blue-500">
                        View all users <span class="ml-1">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="pages/reports/create.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Submit Report
                </a>
                <a href="pages/reports/list.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-list mr-2"></i> View All Reports
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-chart-bar mr-2"></i> Analytics Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Reports</h3>
                <a href="pages/reports/create.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Submit Report
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Copies</th>
                        <?php if ($user['role'] === 'admin'): ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                        <?php endif; ?>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recentReports)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No reports found. <a href="pages/reports/create.php" class="text-blue-600 hover:text-blue-500">Create your first report</a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recentReports as $report): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($report['total_copies']); ?>
                            </td>
                            <?php if ($user['role'] === 'admin'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($report['username']); ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="pages/reports/view.php?id=<?php echo $report['id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>
</body>
</html>

<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

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

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get current month and year
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

try {
    // Get reports count
    $reportsQuery = "SELECT COUNT(*) as count FROM reports WHERE YEAR(report_month) = ? AND MONTH(report_month) = ?";
    $stmt = $conn->prepare($reportsQuery);
    $stmt->execute([$currentYear, $currentMonth]);
    $reportsCount = $stmt->fetch()['count'];

    // Get total copies and other metrics for current month
    $totalQuery = "SELECT 
        COALESCE(SUM(total_copies), 0) as total_copies,
        COALESCE(SUM(monthly_copies), 0) as monthly_copies,
        COALESCE(SUM(total_distribution), 0) as total_distribution,
        COALESCE(SUM(souls_won), 0) as total_souls,
        COALESCE(SUM(rhapsody_outreaches), 0) as total_outreaches,
        COALESCE(SUM(champions_league_groups), 0) as total_champions,
        COALESCE(SUM(wonder_alerts), 0) as total_alerts
    FROM reports 
    WHERE YEAR(report_month) = ? AND MONTH(report_month) = ?";
    
    $stmt = $conn->prepare($totalQuery);
    $stmt->execute([$currentYear, $currentMonth]);
    $monthStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthStats['total_reports'] = $reportsCount;

    // Get zone performance for selected month
    $zoneStatsQuery = "SELECT 
        u.zone,
        COUNT(r.id) as total_reports,
        COALESCE(SUM(r.total_copies), 0) as total_copies,
        COALESCE(SUM(r.monthly_copies), 0) as monthly_copies,
        COALESCE(SUM(r.total_distribution), 0) as total_distribution,
        COALESCE(SUM(r.souls_won), 0) as total_souls,
        COALESCE(SUM(r.rhapsody_outreaches), 0) as total_outreaches
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE YEAR(r.report_month) = ? AND MONTH(r.report_month) = ?
    GROUP BY u.zone
    ORDER BY total_copies DESC";
    
    $stmt = $conn->prepare($zoneStatsQuery);
    $stmt->execute([$currentYear, $currentMonth]);
    $zoneStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top performing users for selected month
    $topUsersQuery = "SELECT 
        u.username,
        u.zone,
        COUNT(r.id) as total_reports,
        COALESCE(SUM(r.total_copies), 0) as total_copies,
        COALESCE(SUM(r.monthly_copies), 0) as monthly_copies,
        COALESCE(SUM(r.total_distribution), 0) as total_distribution,
        COALESCE(SUM(r.souls_won), 0) as total_souls,
        COALESCE(SUM(r.rhapsody_outreaches), 0) as total_outreaches
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE YEAR(r.report_month) = ? AND MONTH(r.report_month) = ?
    GROUP BY u.id, u.username, u.zone
    ORDER BY total_copies DESC
    LIMIT 10";
    
    $stmt = $conn->prepare($topUsersQuery);
    $stmt->execute([$currentYear, $currentMonth]);
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get yearly trend
    $yearlyTrendQuery = "SELECT 
        MONTH(report_month) as month,
        COUNT(*) as total_reports,
        COALESCE(SUM(total_copies), 0) as total_copies,
        COALESCE(SUM(monthly_copies), 0) as monthly_copies,
        COALESCE(SUM(total_distribution), 0) as total_distribution,
        COALESCE(SUM(souls_won), 0) as total_souls,
        COALESCE(SUM(rhapsody_outreaches), 0) as total_outreaches
    FROM reports 
    WHERE YEAR(report_month) = ?
    GROUP BY MONTH(report_month)
    ORDER BY MONTH(report_month)";
    
    $stmt = $conn->prepare($yearlyTrendQuery);
    $stmt->execute([$currentYear]);
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $monthStats = [
        'total_reports' => 0,
        'total_copies' => 0,
        'monthly_copies' => 0,
        'total_distribution' => 0,
        'total_souls' => 0,
        'total_outreaches' => 0,
        'total_champions' => 0,
        'total_alerts' => 0
    ];
    $zoneStats = [];
    $topUsers = [];
    $monthlyStats = [];
}

// Include header
require_once '../layouts/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-indigo-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-indigo-800">Analytics Dashboard Coming Soon</h3>
                <p class="mt-2 text-indigo-700">
                    We're working on bringing you comprehensive analytics and insights. Check back soon for detailed reports and statistics.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="../pages/reports/list.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-list-ul mr-2"></i>
                    View All Reports
                </a>
                <a href="users.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-users mr-2"></i>
                    Manage Users
                </a>
                <a href="files.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                    <i class="fas fa-folder mr-2"></i>
                    File Manager
                </a>
                <a href="../pages/reports/create.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Create New Report
                </a>
            </div>
        </div>
    </div>

    <!-- Monthly Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Reports</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900"><?php echo number_format((int)$monthStats['total_reports']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Copies</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900"><?php echo number_format((int)$monthStats['total_copies']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Distribution</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900"><?php echo number_format((int)$monthStats['total_distribution']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-medium text-gray-500">Souls Won</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900"><?php echo number_format((int)$monthStats['total_souls']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-medium text-gray-500">Outreaches</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900"><?php echo number_format((int)$monthStats['total_outreaches']); ?></p>
        </div>
    </div>

    <!-- Monthly Overview Chart -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Overview</h2>
            <canvas id="monthlyChart" class="w-full" height="300"></canvas>
        </div>
    </div>

    <!-- Zone Performance -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Zone Performance</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reports</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Copies</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Distribution</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Souls Won</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outreaches</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($zoneStats as $zone): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($zone['zone']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$zone['total_reports']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$zone['total_copies']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$zone['total_distribution']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$zone['total_souls']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$zone['total_outreaches']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php 
                                    $rate = $zone['total_copies'] > 0 
                                        ? round(($zone['total_distribution'] / $zone['total_copies']) * 100, 1) 
                                        : 0;
                                    echo $rate . '%';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Performing Users -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Users</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reports</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Copies</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Distribution</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Souls Won</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outreaches</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($topUsers as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['zone']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$user['total_reports']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$user['total_copies']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$user['total_distribution']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$user['total_souls']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php echo number_format((int)$user['total_outreaches']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                <?php 
                                    $rate = $user['total_copies'] > 0 
                                        ? round(($user['total_distribution'] / $user['total_copies']) * 100, 1) 
                                        : 0;
                                    echo $rate . '%';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Monthly Overview Chart
const monthlyData = <?php echo json_encode($monthlyStats); ?>;
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const copies = Array(12).fill(0);
const distribution = Array(12).fill(0);
const souls = Array(12).fill(0);
const outreaches = Array(12).fill(0);

monthlyData.forEach(data => {
    const monthIndex = data.month - 1;
    copies[monthIndex] = parseInt(data.total_copies);
    distribution[monthIndex] = parseInt(data.total_distribution);
    souls[monthIndex] = parseInt(data.total_souls);
    outreaches[monthIndex] = parseInt(data.total_outreaches);
});

const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Total Copies',
                data: copies,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            },
            {
                label: 'Distribution',
                data: distribution,
                backgroundColor: 'rgba(16, 185, 129, 0.5)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1
            },
            {
                label: 'Souls Won',
                data: souls,
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            },
            {
                label: 'Outreaches',
                data: outreaches,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

</main>
</body>
</html>

<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Ensure user is logged in
Auth::requireLogin();

// Get user data
$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);

// Handle delete request
if (Auth::isAdmin() && isset($_POST['delete']) && isset($_POST['report_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Delete report images first
        $deleteImagesQuery = "DELETE FROM report_images WHERE report_id = :report_id";
        $stmt = $conn->prepare($deleteImagesQuery);
        $stmt->bindValue(':report_id', $_POST['report_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete report
        $deleteReportQuery = "DELETE FROM reports WHERE id = :report_id";
        $stmt = $conn->prepare($deleteReportQuery);
        $stmt->bindValue(':report_id', $_POST['report_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to refresh the page
        header("Location: list.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error deleting report: " . $e->getMessage();
    }
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Get total copies
if (Auth::isAdmin()) {
    $totalCopiesQuery = "SELECT SUM(total_copies) as total_copies FROM reports";
    $stmt = $conn->query($totalCopiesQuery);
} else {
    $totalCopiesQuery = "SELECT SUM(total_copies) as total_copies FROM reports WHERE user_id = :user_id";
    $stmt = $conn->prepare($totalCopiesQuery);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
}
$totalCopies = $stmt->fetch(PDO::FETCH_ASSOC)['total_copies'] ?? 0;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : '';
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : '';

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!Auth::isAdmin()) {
    $whereConditions[] = "r.user_id = :user_id";
    $params[':user_id'] = $_SESSION['user_id'];
}

if ($search) {
    $whereConditions[] = "(u.username LIKE :search OR u.region LIKE :search OR u.zone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($startDate) {
    $whereConditions[] = "r.report_month >= :start_date";
    $params[':start_date'] = $startDate;
}

if ($endDate) {
    $whereConditions[] = "r.report_month <= :end_date";
    $params[':end_date'] = $endDate;
}

if ($filterMonth && $filterYear) {
    $whereConditions[] = "MONTH(r.report_month) = :filter_month AND YEAR(r.report_month) = :filter_year";
    $params[':filter_month'] = $filterMonth;
    $params[':filter_year'] = $filterYear;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Update queries with filters
if (Auth::isAdmin()) {
    $countQuery = "SELECT COUNT(*) as total FROM reports r 
                   LEFT JOIN users u ON r.user_id = u.id 
                   $whereClause";
    $reportsQuery = "SELECT r.*, u.username, u.region, u.zone 
                     FROM reports r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     $whereClause 
                     ORDER BY r.report_month DESC, r.created_at DESC 
                     LIMIT :limit OFFSET :offset";
} else {
    $countQuery = "SELECT COUNT(*) as total FROM reports r 
                   LEFT JOIN users u ON r.user_id = u.id 
                   $whereClause";
    $reportsQuery = "SELECT r.*, u.username, u.region, u.zone 
                     FROM reports r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     $whereClause 
                     ORDER BY r.report_month DESC, r.created_at DESC 
                     LIMIT :limit OFFSET :offset";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$countStmt = $conn->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get reports with filters
$stmt = $conn->prepare($reportsQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();

// Include header
require_once '../../layouts/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reports Overview</h1>
            <p class="mt-1 text-sm text-gray-600">
                Total Copies: <span class="font-semibold"><?php echo number_format($totalCopies); ?></span>
                <?php if (Auth::isAdmin()): ?>
                <span class="text-gray-500">(All Users)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex space-x-4">
            <a href="../../includes/export_reports.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-download mr-2"></i> Export All Reports
            </a>
            <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-plus mr-2"></i> Submit Report
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6">
        <div class="flex flex-wrap -mx-3">
            <div class="w-full md:w-1/2 xl:w-1/3 px-3 mb-6">
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" id="search" name="search" value="<?php echo $search; ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="w-full md:w-1/2 xl:w-1/3 px-3 mb-6">
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="w-full md:w-1/2 xl:w-1/3 px-3 mb-6">
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="w-full md:w-1/2 xl:w-1/3 px-3 mb-6">
                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                <select id="month" name="month" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Select Month</option>
                    <option value="1" <?php echo $filterMonth === 1 ? 'selected' : ''; ?>>January</option>
                    <option value="2" <?php echo $filterMonth === 2 ? 'selected' : ''; ?>>February</option>
                    <option value="3" <?php echo $filterMonth === 3 ? 'selected' : ''; ?>>March</option>
                    <option value="4" <?php echo $filterMonth === 4 ? 'selected' : ''; ?>>April</option>
                    <option value="5" <?php echo $filterMonth === 5 ? 'selected' : ''; ?>>May</option>
                    <option value="6" <?php echo $filterMonth === 6 ? 'selected' : ''; ?>>June</option>
                    <option value="7" <?php echo $filterMonth === 7 ? 'selected' : ''; ?>>July</option>
                    <option value="8" <?php echo $filterMonth === 8 ? 'selected' : ''; ?>>August</option>
                    <option value="9" <?php echo $filterMonth === 9 ? 'selected' : ''; ?>>September</option>
                    <option value="10" <?php echo $filterMonth === 10 ? 'selected' : ''; ?>>October</option>
                    <option value="11" <?php echo $filterMonth === 11 ? 'selected' : ''; ?>>November</option>
                    <option value="12" <?php echo $filterMonth === 12 ? 'selected' : ''; ?>>December</option>
                </select>
            </div>
            <div class="w-full md:w-1/2 xl:w-1/3 px-3 mb-6">
                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                <input type="number" id="year" name="year" value="<?php echo $filterYear; ?>" min="2000" max="2099" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="w-full px-3 mb-6 flex space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
                <a href="list.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
        </div>
    </form>

    <?php if (empty($reports)): ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500">No reports found. Start by creating your first report.</p>
        <a href="create.php" class="inline-block mt-4 text-blue-600 hover:text-blue-500">Create Report</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($reports as $report): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium">
                            <a href="view.php?id=<?php echo $report['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                Report #<?php echo $report['id']; ?>
                            </a>
                        </h3>
                        <div class="mt-1 text-sm text-gray-500">
                            <span class="font-medium"><?php echo htmlspecialchars($report['username']); ?></span>
                            <span class="mx-1">•</span>
                            <span><?php echo htmlspecialchars($report['region']); ?></span>
                            <span class="mx-1">•</span>
                            <span><?php echo htmlspecialchars($report['zone']); ?></span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            <span class="font-medium">Report Month:</span>
                            <span><?php echo date('F Y', strtotime($report['report_month'])); ?></span>
                            <span class="mx-1">•</span>
                            <span>Submitted: <?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <a href="view.php?id=<?php echo $report['id']; ?>" 
                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            View Details
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                        <form method="POST" class="inline-block ml-3" onsubmit="return confirm('Are you sure you want to delete this report? This action cannot be undone.');">
                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                            <button type="submit" name="delete" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Key Metrics -->
                    <div class="bg-gray-50 rounded p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Total Copies</h4>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($report['total_copies']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Total Distribution</h4>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($report['total_distribution']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Souls Won</h4>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($report['souls_won']); ?></p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($report['testimonies_file']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700">
                        <i class="fas fa-file-alt mr-2"></i> Has Testimonies
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($report['innovations_file']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
                        <i class="fas fa-lightbulb mr-2"></i> Has Innovations
                    </span>
                    <?php endif; ?>

                    <?php
                    // Get pictures for this report
                    $picturesQuery = "SELECT image_path FROM report_images WHERE report_id = ?";
                    $stmt = $conn->prepare($picturesQuery);
                    $stmt->execute([$report['id']]);
                    $pictures = $stmt->fetchAll();
                    if (!empty($pictures)):
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                        <i class="fas fa-images mr-2"></i> <?php echo count($pictures); ?> Pictures
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $filterMonth ? '&month=' . urlencode($filterMonth) : ''; ?><?php echo $filterYear ? '&year=' . urlencode($filterYear) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Previous</span>
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $filterMonth ? '&month=' . urlencode($filterMonth) : ''; ?><?php echo $filterYear ? '&year=' . urlencode($filterYear) : ''; ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $page ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $filterMonth ? '&month=' . urlencode($filterMonth) : ''; ?><?php echo $filterYear ? '&year=' . urlencode($filterYear) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Next</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</main>
</body>
</html>
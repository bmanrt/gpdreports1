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

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="report_' . $reportId . '_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
$headers = [
    'Report ID', 'Submitted By', 'Region', 'Zone', 'Date Submitted',
    'Total Copies', 'Total Distribution', 'Distribution Rate',
    'Champions League Groups', 'Groups 1M', 'Groups 500K', 'Groups 250K', 'Groups 100K',
    'Monthly Copies', 'Wonder Alerts', 'Kids Alerts', 'Language Missions',
    'Penetrating Truth', 'Penetrating Languages', 'Youth Aglow', 'Minister Campaign',
    'Say Yes Kids', 'No One Left', 'Teevolution', 'Subscriptions',
    'Prayer Programs', 'Partner Programs', 'Souls Won',
    'Rhapsody Outreaches', 'Rhapsody Cells', 'New Churches', 'New Partners',
    'Lingual Cells', 'Language Churches', 'Languages Sponsored',
    'Distribution Centers', 'External Ministers'
];
fputcsv($output, $headers);

// Calculate distribution rate
$distributionRate = ($report['total_copies'] > 0) 
    ? round(($report['total_distribution'] / $report['total_copies']) * 100, 2)
    : 0;

// Prepare data row
$data = [
    $report['id'],
    $report['username'],
    $report['region'],
    $report['zone'],
    date('Y-m-d', strtotime($report['created_at'])),
    $report['total_copies'],
    $report['total_distribution'],
    $distributionRate . '%',
    $report['champions_league_groups'],
    $report['groups_1m'],
    $report['groups_500k'],
    $report['groups_250k'],
    $report['groups_100k'],
    $report['monthly_copies'],
    $report['wonder_alerts'],
    $report['kids_alerts'],
    $report['language_missions'],
    $report['penetrating_truth'],
    $report['penetrating_languages'],
    $report['youth_aglow'],
    $report['minister_campaign'],
    $report['say_yes_kids'],
    $report['no_one_left'],
    $report['teevolution'],
    $report['subscriptions'],
    $report['prayer_programs'],
    $report['partner_programs'],
    $report['souls_won'],
    $report['rhapsody_outreaches'],
    $report['rhapsody_cells'],
    $report['new_churches'],
    $report['new_partners'],
    $report['lingual_cells'],
    $report['language_churches'],
    $report['languages_sponsored'],
    $report['distribution_centers'],
    $report['external_ministers']
];

// Write data
fputcsv($output, $data);

// Close the output stream
fclose($output);
exit;

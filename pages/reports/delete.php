<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is admin
Auth::requireLogin();
if (!Auth::isAdmin()) {
    header("Location: list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get report details for file deletion
        $reportQuery = "SELECT testimonies_file, innovations_file FROM reports WHERE id = :report_id";
        $stmt = $conn->prepare($reportQuery);
        $stmt->bindValue(':report_id', $_POST['report_id'], PDO::PARAM_INT);
        $stmt->execute();
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get image paths
        $imagesQuery = "SELECT image_path FROM report_images WHERE report_id = :report_id";
        $stmt = $conn->prepare($imagesQuery);
        $stmt->bindValue(':report_id', $_POST['report_id'], PDO::PARAM_INT);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete physical files
        if ($report) {
            if ($report['testimonies_file']) {
                $path = "../../uploads/testimonies/" . $report['testimonies_file'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            if ($report['innovations_file']) {
                $path = "../../uploads/innovations/" . $report['innovations_file'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        
        // Delete image files
        foreach ($images as $image) {
            $path = "../../uploads/pictures/" . $image;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // Delete report images from database
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
        
        $_SESSION['success'] = "Report deleted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting report: " . $e->getMessage();
    }
}

// Redirect back to list page
header("Location: list.php");
exit;

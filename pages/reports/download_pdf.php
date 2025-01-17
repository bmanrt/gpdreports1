<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';

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

// Check if report ID is provided
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

// Get report data
$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("
        SELECT r.*, u.username, u.zone, u.region 
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $report = $stmt->fetch();

    if (!$report) {
        header('Location: list.php');
        exit;
    }

    // Custom PDF class with header and footer
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo
            $image_file = '../../assets/images/logo.png';
            if (file_exists($image_file)) {
                $this->Image($image_file, 15, 10, 30);
            }
            
            // Set font
            $this->SetFont('helvetica', 'B', 20);
            // Title
            $this->SetTextColor(44, 62, 80); // Dark blue color
            $this->Cell(0, 30, 'GPD Report Details', 0, true, 'C');
            
            // Line separator
            $this->SetLineStyle(array('width' => 0.5, 'color' => array(52, 73, 94)));
            $this->Line(15, 45, 195, 45);
        }

        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Text color
            $this->SetTextColor(128);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
        }

        // Colored table
        public function ColoredTable($header, $data) {
            // Colors, line width and bold font
            $this->SetFillColor(52, 152, 219); // Blue header
            $this->SetTextColor(255);
            $this->SetDrawColor(44, 62, 80);
            $this->SetLineWidth(0.3);
            $this->SetFont('', 'B');
            
            // Header
            $w = array(70, 50, 65);
            for($i = 0; $i < count($header); $i++)
                $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
            $this->Ln();
            
            // Color and font restoration
            $this->SetFillColor(224, 235, 255);
            $this->SetTextColor(44, 62, 80);
            $this->SetFont('');
            
            // Data
            $fill = false;
            foreach($data as $row) {
                $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $row[1], 'LR', 0, 'R', $fill);
                $this->Cell($w[2], 6, $row[2], 'LR', 0, 'R', $fill);
                $this->Ln();
                $fill = !$fill;
            }
            $this->Cell(array_sum($w), 0, '', 'T');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('GPD Reports System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Report Details - ' . $report['id']);

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetHeaderMargin(20);
    $pdf->SetFooterMargin(10);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage();

    // Report Meta Information
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(41, 128, 185); // Blue
    $pdf->Cell(0, 10, 'GPD Report Details', 0, 1, 'C');
    $pdf->Ln(5);

    // Report Information Section
    $pdf->Cell(0, 10, 'Report Information', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    // Meta Information Table
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(44, 62, 80);
    
    // Create info box with light blue background
    $pdf->SetFillColor(236, 240, 241);
    $pdf->Rect(15, $pdf->GetY(), 180, 40, 'F');
    $pdf->SetXY(20, $pdf->GetY() + 5);
    
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Report ID:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 7, $report['id'], 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Submitted By:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, $report['username'], 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Zone:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 7, $report['zone'], 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Region:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, $report['region'], 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Report Month:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, date('F Y', strtotime($report['report_month'])), 0, 1);

    $pdf->Ln(15);

    // Total Copies Report
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Total Copies Report', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $header = array('Metric', 'Value', 'Percentage');
    $data = array(
        array('Total Copies', number_format($report['total_copies']), '100%'),
        array('Total Distribution', number_format($report['total_distribution']), 
            round(($report['total_distribution'] / $report['total_copies']) * 100, 1) . '%')
    );
    $pdf->ColoredTable($header, $data);

    // Strategic Income Alerts
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Strategic Income Alerts', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $header = array('Metric', 'Value');
    $alerts = array(
        array('Monthly Copies', number_format($report['monthly_copies'])),
        array('Wonder Alerts', number_format($report['wonder_alerts'])),
        array('Say Yes to Kids Alerts', number_format($report['kids_alerts'])),
        array('Language Redemption Missions', number_format($report['language_missions']))
    );
    $pdf->ColoredTable($header, $alerts);

    // Sub Campaigns
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Distribution Report on Sub Campaigns', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $header = array('Campaign', 'Copies');
    $campaigns = array(
        array('Penetrating with Truth', number_format($report['penetrating_truth'])),
        array('Penetrating with Languages', number_format($report['penetrating_languages'])),
        array('Youth Aglow', number_format($report['youth_aglow'])),
        array('Teevolution', number_format($report['teevolution'])),
        array('Subscriptions', number_format($report['subscriptions']))
    );
    $pdf->ColoredTable($header, $campaigns);

    // Program Report
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Program Report', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $header = array('Program', 'Count');
    $programs = array(
        array('Partners Prayer Programs', number_format($report['prayer_programs'])),
        array('Partners Programs', number_format($report['partner_programs']))
    );
    $pdf->ColoredTable($header, $programs);

    // Reach and Impact Report
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Reach and Impact Report', 0, 1, 'L');
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $header = array('Metric', 'Count');
    $impact = array(
        array('Total Distribution', number_format($report['total_distribution'])),
        array('Souls Won', number_format($report['souls_won'])),
        array('Rhapsody Outreaches', number_format($report['rhapsody_outreaches'])),
        array('Rhapsody Cells', number_format($report['rhapsody_cells'])),
        array('New Churches', number_format($report['new_churches'])),
        array('New Partners', number_format($report['new_partners'])),
        array('Lingual Cells', number_format($report['lingual_cells'])),
        array('Language Churches', number_format($report['language_churches'])),
        array('Languages Sponsored', number_format($report['languages_sponsored'])),
        array('Distribution Centers', number_format($report['distribution_centers'])),
        array('External Ministers', number_format($report['external_ministers']))
    );
    $pdf->ColoredTable($header, $impact);

    // Images Gallery
    $imagesQuery = "SELECT * FROM report_images WHERE report_id = ?";
    $stmt = $conn->prepare($imagesQuery);
    $stmt->execute([$_GET['id']]);
    $images = $stmt->fetchAll();

    if (!empty($images)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Pictures Gallery', 0, 1, 'L');
        $pdf->Line($pdf->GetX(), $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        $x = 15;
        $y = $pdf->GetY();
        $maxHeight = 0;

        foreach ($images as $image) {
            $imagePath = '../../uploads/pictures/' . $image['image_path'];
            if (file_exists($imagePath)) {
                if ($x > 150) { // New row
                    $x = 15;
                    $y += $maxHeight + 10;
                    $maxHeight = 0;
                }
                
                $imageSize = getimagesize($imagePath);
                $width = 80;
                $height = ($imageSize[1] * $width) / $imageSize[0];
                if ($height > $maxHeight) $maxHeight = $height;

                $pdf->Image($imagePath, $x, $y, $width);
                $x += $width + 10;
            }
        }
    }

    // Output the PDF
    $pdf->Output('Report_' . $report['id'] . '.pdf', 'D');

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

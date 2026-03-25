<?php
// Prevent any layout inclusion and ensure clean output
define('DIRECT_DOWNLOAD', true);

// Enable output buffering for better cross-browser compatibility
ob_start();

// Get database connection
require_once __DIR__ . '/../app/db.php';
$pdo = DB::conn();
$company = get_company();

// Get and validate date parameters
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Validate dates
if (!DateTime::createFromFormat('Y-m-d', $from) || !DateTime::createFromFormat('Y-m-d', $to)) {
    die('Invalid date format');
}

// 1. Fetch production stocks
$stmt = $pdo->prepare('SELECT s.date, yt.name as yarn_type_name, s.stock_type, s.total_bags, s.bag_weight, s.sold_bags, s.sold_weight, s.notes
                     FROM stocks s
                     LEFT JOIN yarn_types yt ON s.yarn_type_id = yt.id
                     WHERE DATE(s.date) BETWEEN ? AND ?
                     ORDER BY s.stock_type ASC, s.date ASC');
$stmt->execute([$from, $to]);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch purchased stocks
$purchase_stmt = $pdo->prepare('SELECT ps.*, yt.name as yarn_name 
                                FROM purchased_stocks ps 
                                JOIN yarn_types yt ON ps.yarn_type_id = yt.id 
                                WHERE DATE(ps.date_purchased) BETWEEN ? AND ? 
                                ORDER BY ps.date_purchased ASC');
$purchase_stmt->execute([$from, $to]);
$purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch summary data
$summary = get_yarn_production_summary($company['id']);

// Separate stocks by type
$bag_stocks = [];
$chippam_stocks = [];
foreach ($stocks as $stock) {
    if ($stock['stock_type'] === 'bag') { $bag_stocks[] = $stock; } 
    else { $chippam_stocks[] = $stock; }
}

// Generate clean HTML content
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stocks Management Report</title>
    <style>
        body { font-family: sans-serif; margin: 20px; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 30px; }
        .main-title { font-size: 24px; font-weight: bold; border: 2px solid black; padding: 10px; display: inline-block; }
        .company-name { font-size: 18px; font-weight: bold; margin-top: 10px; }
        .section-title { text-align: center; font-weight: bold; padding: 8px; margin: 20px 0 10px 0; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid black; padding: 6px; text-align: left; font-size: 13px; }
        th { background-color: #f2f2f2; }
        .number-cell { text-align: right; }
        .total-row { background-color: #f9f9f9; font-weight: bold; }
        .text-orange-600 { color: #ea580c; }
        .text-green-600 { color: #16a34a; }
    </style>
</head>
<body>

<div class="header">
    <div class="main-title">STOCKS MANAGEMENT REPORT</div>
    <div class="company-name">' . e($company['name'] ?? 'BALAJI TEX') . '</div>
    <div class="date-range">Period: ' . htmlspecialchars($from . ' to ' . $to) . '</div>
</div>';

// 1. SUMMARY SECTION
if (!empty($summary)) {
    $html .= '<div class="section-title" style="background-color: #f3f4f6;">PRODUCTION VS PURCHASED SUMMARY (OVERALL)</div>';
    $html .= '<table>
        <tr>
            <th>Yarn Type</th>
            <th>Total Purchased (kg)</th>
            <th>Total Finished (kg)</th>
            <th>Pending Quota (kg)</th>
            <th>Status</th>
        </tr>';
    foreach ($summary as $s) {
        $status = $s['pending_kg'] > 0 ? "Pending" : "Completed";
        $color = $s['pending_kg'] > 0 ? "text-orange-600" : "text-green-600";
        $html .= '<tr>
            <td>' . e($s['yarn_name']) . '</td>
            <td class="number-cell">' . number_format($s['purchased_kg'], 3) . '</td>
            <td class="number-cell">' . number_format($s['finished_kg'], 3) . '</td>
            <td class="number-cell" style="font-weight:bold;">' . number_format($s['pending_kg'], 3) . '</td>
            <td class="' . $color . '">' . $status . '</td>
        </tr>';
    }
    $html .= '</table>';
}

// 2. PURCHASED STOCKS SECTION
if (!empty($purchases)) {
    $html .= '<div class="section-title" style="background-color: #e0e7ff;">PURCHASED STOCK HISTORY</div>';
    $html .= '<table>
        <tr>
            <th>Date</th>
            <th>Supplier</th>
            <th>Yarn Type</th>
            <th>Bags</th>
            <th>Weight/Bag</th>
            <th>Total Weight</th>
        </tr>';
    foreach ($purchases as $p) {
        $html .= '<tr>
            <td>' . e($p['date_purchased']) . '</td>
            <td>' . e($p['supplier_name']) . '</td>
            <td>' . e($p['yarn_name']) . '</td>
            <td class="number-cell">' . (int)$p['bag_count'] . '</td>
            <td class="number-cell">' . number_format($p['weight_per_bag'], 3) . '</td>
            <td class="number-cell">' . number_format($p['total_weight'], 3) . ' kg</td>
        </tr>';
    }
    $html .= '</table>';
}

// 3. CHIPPAM STOCKS Section
if (!empty($chippam_stocks)) {
    $html .= '<div class="section-title" style="background-color: #E6F3FF;">CHIPPAM PRODUCTION STOCKS</div>';
    $html .= '<table>
        <tr>
            <th>Date</th>
            <th>Yarn Type</th>
            <th>Weight (kg)</th>
            <th>Total Chippam</th>
            <th>Net Weight (after 0.4kg wastage)</th>
            <th>Available</th>
        </tr>';
    foreach ($chippam_stocks as $stock) {
        $net_weight = max(0, $stock['bag_weight'] - ($stock['total_bags'] * 0.400));
        $avail_bags = $stock['total_bags'] - $stock['sold_bags'];
        $html .= '<tr>
            <td>' . e($stock['date']) . '</td>
            <td>' . e($stock['yarn_type_name']) . '</td>
            <td class="number-cell">' . number_format($stock['bag_weight'], 3) . '</td>
            <td class="number-cell">' . (int)$stock['total_bags'] . '</td>
            <td class="number-cell">' . number_format($net_weight, 3) . '</td>
            <td class="number-cell">' . (int)$avail_bags . '</td>
        </tr>';
    }
    $html .= '</table>';
}

// 4. CONE STOCKS Section
if (!empty($bag_stocks)) {
    $html .= '<div class="section-title" style="background-color: #FFE6E6;">CONE PRODUCTION STOCKS (BAGS)</div>';
    $html .= '<table>
        <tr>
            <th>Date</th>
            <th>Yarn Type</th>
            <th>Weight/Bag</th>
            <th>Total Bags</th>
            <th>Total Weight</th>
            <th>Available</th>
        </tr>';
    foreach ($bag_stocks as $stock) {
        $total_w = $stock['bag_weight'] * $stock['total_bags'];
        $avail_bags = $stock['total_bags'] - $stock['sold_bags'];
        $html .= '<tr>
            <td>' . e($stock['date']) . '</td>
            <td>' . e($stock['yarn_type_name']) . '</td>
            <td class="number-cell">' . number_format($stock['bag_weight'], 3) . '</td>
            <td class="number-cell">' . (int)$stock['total_bags'] . '</td>
            <td class="number-cell">' . number_format($total_w, 3) . '</td>
            <td class="number-cell">' . (int)$avail_bags . '</td>
        </tr>';
    }
    $html .= '</table>';
}

if (empty($stocks) && empty($purchases)) {
    $html .= '<div style="text-align:center; color:red; margin:20px;">No data found for the selected period.</div>';
}

$html .= '</body></html>';

ob_end_clean();
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="balajitex_report_'.date('Ymd').'.html"');
echo $html;
exit;

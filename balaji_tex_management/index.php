<?php
require __DIR__ . '/app/db.php';
session_start();
$hadSessionCompany = isset($_SESSION['company_id']);
$company = get_company();
$page = $_GET['page'] ?? null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $pdo = DB::conn();
        if ($action === 'company_create') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Company name required');
            create_company($name);
            header('Location: index.php');
            exit;
        }
        if ($action === 'company_select') {
            $id = (int)($_POST['company_id'] ?? 0);
            if (!select_company($id)) throw new Exception('Invalid company');
            header('Location: index.php');
            exit;
        }
        if (!$company) throw new Exception('Company not initialized');

        // Yarn type add/delete
        if ($action === 'yarn_type_add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Yarn type name required');
            
            // Check if yarn type already exists
            $stmt = $pdo->prepare('SELECT id FROM yarn_types WHERE company_id=? AND name=?');
            $stmt->execute([$company['id'], $name]);
            $existing = $stmt->fetchColumn();
            
            if (!$existing) {
                $stmt = $pdo->prepare('INSERT INTO yarn_types(company_id, name) VALUES(?, ?)');
                $stmt->execute([$company['id'], $name]);
                $ytId = (int)$pdo->lastInsertId();
            } else {
                $ytId = (int)$existing;
            }
            
            header('Location: index.php?page=yarn_types');
            exit;
        }
        if ($action === 'yarn_type_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM yarn_types WHERE id=?');
            $stmt->execute([$id]);
            header('Location: index.php?page=yarn_types');
            exit;
        }
        if ($action === 'yarn_type_edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($id <= 0 || $name === '') throw new Exception('Invalid input');
            
            // Check if yarn type name already exists (excluding current one)
            $stmt = $pdo->prepare('SELECT id FROM yarn_types WHERE company_id=? AND name=? AND id!=?');
            $stmt->execute([$company['id'], $name, $id]);
            $existing = $stmt->fetchColumn();
            
            if (!$existing) {
                $stmt = $pdo->prepare('UPDATE yarn_types SET name=? WHERE id=?');
                $stmt->execute([$name, $id]);
            }
            
            header('Location: index.php?page=yarn_types');
            exit;
        }

        // Stock management actions
        if ($action === 'add_stock') {
            $yarn_type_id = (int)($_POST['yarn_type_id'] ?? 0);
            $stock_type = trim($_POST['stock_type'] ?? '');
            $stock_notes = trim($_POST['stock_notes'] ?? '');
            
            if ($yarn_type_id <= 0 || $stock_type === '') {
                throw new Exception('All fields are required');
            }
            
            // Get yarn type name
            $stmt = $pdo->prepare('SELECT name FROM yarn_types WHERE id=?');
            $stmt->execute([$yarn_type_id]);
            $yarn_name = $stmt->fetchColumn();
            
            if ($stock_type === 'chippam') {
                $total_chippam_weight_raw = trim($_POST['total_chippam_weight'] ?? '');
                $total_chippam_number_raw = trim($_POST['total_chippam_number'] ?? '');
                $stock_date = trim($_POST['stock_date'] ?? date('Y-m-d'));
                
                if ($total_chippam_weight_raw === '') throw new Exception('Total chippam weight is required');
                if ($total_chippam_number_raw === '') throw new Exception('Total chippam number is required');
                
                // Parse multiple values separated by comma
                $weight_values = array_map('floatval', array_map('trim', explode(',', $total_chippam_weight_raw)));
                $number_values = array_map('intval', array_map('trim', explode(',', $total_chippam_number_raw)));
                
                // Validate all values
                foreach ($weight_values as $weight) {
                    if ($weight <= 0) throw new Exception('All chippam weights must be greater than 0');
                }
                foreach ($number_values as $number) {
                    if ($number <= 0) throw new Exception('All chippam numbers must be greater than 0');
                }
                
                // Handle mismatched counts by using totals or averages
                if (count($weight_values) !== count($number_values)) {
                    // If counts don't match, check if we have multiple weights and single quantity
                    if (count($weight_values) > 1 && count($number_values) === 1) {
                        // Multiple weights, single quantity - use sum of weights
                        $total_chippam_weight = array_sum($weight_values);
                        $total_chippam_number = array_sum($number_values);
                    } elseif (count($weight_values) === 1 && count($number_values) > 1) {
                        // Single weight, multiple quantities - multiply weight by total quantity
                        $total_chippam_weight = $weight_values[0] * array_sum($number_values);
                        $total_chippam_number = array_sum($number_values);
                    } else {
                        // Other mismatched cases - use average weight
                        $avg_weight = array_sum($weight_values) / count($weight_values);
                        $total_chippam_weight = $avg_weight * array_sum($number_values);
                        $total_chippam_number = array_sum($number_values);
                    }
                }
                
                // Create separate entries for chippam stocks (each entry = 1 bag)
                if (count($weight_values) !== count($number_values)) {
                    // Handle mismatched counts
                    if (count($weight_values) > 1 && count($number_values) === 1) {
                        // Multiple weights, single quantity - create separate entries for each weight (each = 1 bag)
                        foreach ($weight_values as $weight) {
                            $stmt = $pdo->prepare('INSERT INTO stocks(yarn_type_id, cotton_type, bag_weight, total_bags, stock_type, date, notes) VALUES(?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute([$yarn_type_id, $yarn_name, $weight, 1, 'chippam', $stock_date, $stock_notes]); // 1 bag per entry
                        }
                    } elseif (count($weight_values) === 1 && count($number_values) > 1) {
                        // Single weight, multiple quantities - create separate entries for each quantity (each = 1 bag)
                        foreach ($number_values as $number) {
                            $stmt = $pdo->prepare('INSERT INTO stocks(yarn_type_id, cotton_type, bag_weight, total_bags, stock_type, date, notes) VALUES(?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute([$yarn_type_id, $yarn_name, $weight_values[0], 1, 'chippam', $stock_date, $stock_notes]); // 1 bag per entry
                        }
                    } else {
                        // Other mismatched cases - create one entry with totals
                        $avg_weight = array_sum($weight_values) / count($weight_values);
                        $total_chippam_weight = $avg_weight * array_sum($number_values);
                        $total_chippam_number = array_sum($number_values);
                        $stmt = $pdo->prepare('INSERT INTO stocks(yarn_type_id, cotton_type, bag_weight, total_bags, stock_type, date, notes) VALUES(?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$yarn_type_id, $yarn_name, $total_chippam_weight, $total_chippam_number, 'chippam', $stock_date, $stock_notes]);
                    }
                } else {
                    // Matching counts - create separate entries for each weight/quantity pair (each = 1 bag)
                    foreach ($weight_values as $index => $weight) {
                        $stmt = $pdo->prepare('INSERT INTO stocks(yarn_type_id, cotton_type, bag_weight, total_bags, stock_type, date, notes) VALUES(?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$yarn_type_id, $yarn_name, $weight, 1, 'chippam', $stock_date, $stock_notes]); // 1 bag per entry
                    }
                }
                $_SESSION['flash_error'] = 'Chippam stock added successfully';
                
            } elseif ($stock_type === 'bag') {
                $total_bags_raw = trim($_POST['total_bags'] ?? '');
                $stock_date = trim($_POST['stock_date'] ?? date('Y-m-d'));
                
                if ($total_bags_raw === '') {
                    throw new Exception('Total bags is required');
                }
                
                // Parse multiple values separated by comma
                $bag_values = array_map('intval', array_map('trim', explode(',', $total_bags_raw)));
                
                // Validate all values
                foreach ($bag_values as $bags) {
                    if ($bags <= 0) throw new Exception('All bag quantities must be greater than 0');
                }
                
                // Calculate totals
                $total_bags = array_sum($bag_values);
                
                // Create separate entries for each bag quantity with fixed 50kg per bag
                foreach ($bag_values as $index => $bags) {
                    $bag_weight = 50.000; // Fixed 50kg per bag
                    $stmt = $pdo->prepare('INSERT INTO stocks(yarn_type_id, cotton_type, bag_weight, total_bags, stock_type, date, notes) VALUES(?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$yarn_type_id, $yarn_name, $bag_weight, $bags, 'bag', $stock_date, $stock_notes]);
                }
                
                $_SESSION['flash_error'] = 'Bag stock added successfully';
            }
            
            header('Location: index.php?page=stocks');
            exit;
        }
        
        if ($action === 'sell_stock') {
            $stock_id = (int)($_POST['stock_id'] ?? 0);
            $sell_type = trim($_POST['sell_type'] ?? '');
            
            if ($stock_id <= 0 || $sell_type === '') {
                throw new Exception('All fields are required');
            }
            
            // Get stock details
            $stmt = $pdo->prepare('SELECT * FROM stocks WHERE id=?');
            $stmt->execute([$stock_id]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stock) {
                throw new Exception('Stock not found');
            }
            
            $current_total_bags = $stock['total_bags'];
            $current_sold_bags = $stock['sold_bags'] ?? 0;
            $current_sold_cones = $stock['sold_cones'] ?? 0;
            $current_sold_weight = $stock['sold_weight'] ?? 0;
            
            if ($sell_type === 'full_bag') {
                $sell_bags = (int)($_POST['sell_bags'] ?? 0);
                if ($sell_bags <= 0) throw new Exception('Number of bags must be greater than 0');
                if ($sell_bags > ($current_total_bags - $current_sold_bags)) throw new Exception('Not enough bags available');
                
                // Calculate total weight for full bags (using actual bag weight from database)
                $weight_per_bag = $stock['bag_weight']; // Use actual weight per bag from database
                $total_weight_sold = $sell_bags * $weight_per_bag;
                
                // Reduce total bags and update sold weight
                $new_total_bags = $current_total_bags - $sell_bags;
                $new_sold_bags = $current_sold_bags + $sell_bags;
                $new_sold_weight = $current_sold_weight + $total_weight_sold;
                
                $stmt = $pdo->prepare('UPDATE stocks SET total_bags=?, sold_bags=?, sold_weight=? WHERE id=?');
                $stmt->execute([$new_total_bags, $new_sold_bags, $new_sold_weight, $stock_id]);
                
                $_SESSION['flash_error'] = "Sold $sell_bags bags ({$total_weight_sold} kg) successfully";
                
            } elseif ($sell_type === 'cones') {
                $sell_bags = (int)($_POST['sell_bags'] ?? 0);
                if ($sell_bags <= 0) throw new Exception('Number of bags must be greater than 0');
                if ($sell_bags > ($current_total_bags - $current_sold_bags)) throw new Exception('Not enough bags available');
                
                // For cones selling, use 50kg per bag
                $weight_per_bag = 50.000; // 50 kg per bag
                $total_weight_sold = $sell_bags * $weight_per_bag;
                
                // Reduce total bags and update sold weight
                $new_total_bags = $current_total_bags - $sell_bags;
                $new_sold_bags = $current_sold_bags + $sell_bags;
                $new_sold_weight = $current_sold_weight + $total_weight_sold;
                
                $stmt = $pdo->prepare('UPDATE stocks SET total_bags=?, sold_bags=?, sold_weight=? WHERE id=?');
                $stmt->execute([$new_total_bags, $new_sold_bags, $new_sold_weight, $stock_id]);
                
                $_SESSION['flash_error'] = "Sold $sell_bags bags ({$total_weight_sold} kg) successfully";
            }
            
            header('Location: index.php?page=stocks');
            exit;
        }
        
        if ($action === 'delete_all_stocks') {
            // Delete all stocks from the database
            $stmt = $pdo->prepare('DELETE FROM stocks');
            $stmt->execute();
            
            $_SESSION['flash_error'] = 'All stocks have been deleted successfully';
            header('Location: index.php?page=stocks');
            exit;
        }
        
        if ($action === 'delete_stock') {
            $stock_id = (int)($_POST['stock_id'] ?? 0);
            if ($stock_id <= 0) throw new Exception('Invalid stock ID');
            
            $stmt = $pdo->prepare('DELETE FROM stocks WHERE id=?');
            $stmt->execute([$stock_id]);
            $_SESSION['flash_error'] = 'Stock deleted successfully';
            header('Location: index.php?page=stocks');
            exit;
        }

        // Worker add/delete
        if ($action === 'worker_add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new Exception('Worker name required');
            $stmt = $pdo->prepare('INSERT INTO workers(name) VALUES(?)');
            $stmt->execute([$name]);
            header('Location: index.php?page=workers');
            exit;
        }
        if ($action === 'worker_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM workers WHERE id=?');
            $stmt->execute([$id]);
            header('Location: index.php?page=workers');
            exit;
        }

        // Work log add/delete
        if ($action === 'worklog_add') {
            $worker_id = (int)($_POST['worker_id'] ?? 0);
            $work_date = $_POST['work_date'] ?? date('Y-m-d');
            $warps_count = (int)($_POST['warps_count'] ?? 0);
            $rate = (float)($_POST['rate_per_warp'] ?? 0);
            $amount = $warps_count * $rate;
            if ($worker_id <= 0 || $warps_count <= 0 || $rate <= 0) throw new Exception('Invalid work log');
            $pdo->prepare('INSERT INTO work_logs(company_id, worker_id, work_date, warps_count, rate_per_warp, amount) VALUES(?,?,?,?,?,?)')
                ->execute([$company['id'], $worker_id, $work_date, $warps_count, $rate, $amount]);
            header('Location: index.php?page=work_logs');
            exit;
        }
        if ($action === 'worklog_update') {
            $id = (int)($_POST['id'] ?? 0);
            $work_date = $_POST['work_date'] ?? date('Y-m-d');
            $warps_count = (int)($_POST['warps_count'] ?? 0);
            $rate = (float)($_POST['rate_per_warp'] ?? 0);
            if ($id <= 0 || $warps_count <= 0 || $rate <= 0) throw new Exception('Invalid update');
            $amount = $warps_count * $rate;
            $stmt = $pdo->prepare('UPDATE work_logs SET work_date=?, warps_count=?, rate_per_warp=?, amount=? WHERE company_id=? AND id=?');
            $stmt->execute([$work_date, $warps_count, $rate, $amount, $company['id'], $id]);
            $_SESSION['flash_error'] = 'Work log updated';
            $redir_week = $_POST['week_start'] ?? '';
            $to = 'index.php?page=work_logs' . ($redir_week ? ('&week_start=' . urlencode($redir_week)) : '');
            header('Location: ' . $to);
            exit;
        }
        if ($action === 'worklog_update_total') {
            // Replace the entire day's total for a worker: delete existing rows for that day and insert one with provided warps and rate
            $worker_id = (int)($_POST['worker_id'] ?? 0);
            $work_date = $_POST['work_date'] ?? date('Y-m-d');
            $warps_count = (int)($_POST['warps_count'] ?? 0);
            $rate = (float)($_POST['rate_per_warp'] ?? 0);
            if ($worker_id <= 0 || $warps_count < 0 || $rate <= 0) throw new Exception('Invalid update');
            $amount = $warps_count * $rate;
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('DELETE FROM work_logs WHERE company_id=? AND worker_id=? AND work_date=?');
                $stmt->execute([$company['id'], $worker_id, $work_date]);
                if ($warps_count > 0) {
                    $stmt = $pdo->prepare('INSERT INTO work_logs(company_id, worker_id, work_date, warps_count, rate_per_warp, amount) VALUES(?,?,?,?,?,?)');
                    $stmt->execute([$company['id'], $worker_id, $work_date, $warps_count, $rate, $amount]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            $_SESSION['flash_error'] = 'Day total updated';
            $redir_week = $_POST['week_start'] ?? '';
            $to = 'index.php?page=work_logs' . ($redir_week ? ('&week_start=' . urlencode($redir_week)) : '');
            header('Location: ' . $to);
            exit;
        }
        if ($action === 'worklog_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM work_logs WHERE company_id=? AND id=?')->execute([$company['id'], $id]);
            header('Location: index.php?page=work_logs');
            exit;
        }

        // Advances: add and settle
        if ($action === 'advance_add') {
            $worker_id = (int)($_POST['worker_id'] ?? 0);
            $advance_date = $_POST['advance_date'] ?? date('Y-m-d');
            $amount = (float)($_POST['amount'] ?? 0);
            $note = trim($_POST['note'] ?? '');
            if ($worker_id <= 0 || $amount <= 0) throw new Exception('Invalid advance');
            $stmt = $pdo->prepare('INSERT INTO advances(company_id, worker_id, advance_date, amount, note, settled) VALUES(?,?,?,?,?,0)');
            $stmt->execute([$company['id'], $worker_id, $advance_date, $amount, $note]);
            header('Location: index.php?page=work_logs');
            exit;
        }
        if ($action === 'advance_settle') {
            $id = (int)($_POST['id'] ?? 0);
            $settle_amount = isset($_POST['settle_amount']) ? (float)$_POST['settle_amount'] : 0.0;
            $stmt = $pdo->prepare('SELECT amount, paid_amount FROM advances WHERE company_id=? AND id=?');
            $stmt->execute([$company['id'], $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Advance not found');
            $amount = (float)$row['amount'];
            $paid = (float)$row['paid_amount'];
            $remaining = max(0.0, $amount - $paid);
            if ($settle_amount <= 0 || $settle_amount > $remaining) {
                $settle_amount = $remaining; // default to remaining
            }
            $new_paid = min($amount, $paid + $settle_amount);
            $settled = ($new_paid >= $amount) ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE advances SET paid_amount=?, settled=? WHERE company_id=? AND id=?');
            $stmt->execute([$new_paid, $settled, $company['id'], $id]);
            $_SESSION['flash_error'] = 'Settled ₹ ' . number_format($settle_amount,2) . ' (remaining ₹ ' . number_format(max(0,$amount-$new_paid),2) . ')';
            header('Location: index.php?page=work_logs');
            exit;
        }

        if ($action === 'advance_update') {
            $id = (int)($_POST['id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $note = trim($_POST['note'] ?? '');
            if ($amount <= 0) throw new Exception('Invalid amount');
            // clamp paid_amount if it exceeds new amount
            $stmt = $pdo->prepare('SELECT paid_amount FROM advances WHERE company_id=? AND id=?');
            $stmt->execute([$company['id'], $id]);
            $paid = (float)($stmt->fetchColumn() ?: 0);
            $new_paid = min($paid, $amount);
            $settled = ($new_paid >= $amount) ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE advances SET amount=?, note=?, paid_amount=?, settled=? WHERE company_id=? AND id=?');
            $stmt->execute([$amount, $note, $new_paid, $settled, $company['id'], $id]);
            $_SESSION['flash_error'] = 'Advance updated';
            header('Location: index.php?page=work_logs');
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }
}

// Check for direct download pages first (before including layout)
if ($page === 'stock_log') {
    require __DIR__ . '/pages/stock_log.php';
    exit;
}
if ($page === 'stocks_download') {
    require __DIR__ . '/pages/stocks_download.php';
    exit;
}
if ($page === 'stocks_download_new') {
    require __DIR__ . '/pages/stocks_download_new.php';
    exit;
}

// Don't include layout for direct downloads
if (!defined('DIRECT_DOWNLOAD')) {
    require __DIR__ . '/app/layout.php';
}

if (!$company) {
    $page = 'onboarding';
}
// If there are companies but this is a fresh session and no explicit page was requested, show Companies page first
if ($company && !$hadSessionCompany && $page === null) {
    $page = 'companies';
}

switch ($page ?? 'dashboard') {
    case 'onboarding':
        require __DIR__ . '/pages/onboarding.php';
        break;
    case 'companies':
        require __DIR__ . '/pages/companies.php';
        break;
    case 'dashboard':
        require __DIR__ . '/pages/dashboard.php';
        break;
    case 'yarn_types':
        require __DIR__ . '/pages/yarn_types.php';
        break;
    case 'stocks':
        require __DIR__ . '/pages/stocks.php';
        break;
    case 'workers':
        require __DIR__ . '/pages/workers.php';
        break;
    case 'work_logs':
        require __DIR__ . '/pages/work_logs.php';
        break;
    default:
        require __DIR__ . '/pages/dashboard.php';
}

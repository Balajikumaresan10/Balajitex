<?php
// MySQL PDO connection for XAMPP and Railway
class DB {
    private static $pdo = null;

    public static function conn() {
        if (self::$pdo === null) {
            // Railway environment variables
            $host = getenv('MYSQLHOST');
            $port = getenv('MYSQLPORT') ?: '3306';
            $dbname = getenv('MYSQLDATABASE');
            $username = getenv('MYSQLUSER');
            $password = getenv('MYSQLPASSWORD');

            // Fallback for local development (XAMPP) if Railway vars are missing
            if (!$host) {
                $host = getenv('DB_HOST') ?: '127.0.0.1';
                $dbname = getenv('DB_NAME') ?: 'balaji_tex';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: '';
                $port = getenv('DB_PORT') ?: '3306';
            }

            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                self::$pdo = new PDO($dsn, $username, $password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed for host $host: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

function get_company() {
    $pdo = DB::conn();
    $id = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : 0;
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, company_name as name, created_date as created_at FROM companies WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }
    $stmt = $pdo->query('SELECT id, company_name as name, created_date as created_at FROM companies ORDER BY id LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($row) {
        $_SESSION['company_id'] = (int)$row['id'];
    }
    return $row;
}

function list_companies() {
    $pdo = DB::conn();
    $stmt = $pdo->query('SELECT id, company_name as name, created_date as created_at FROM companies ORDER BY company_name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function select_company($id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('SELECT id FROM companies WHERE id=?');
    $stmt->execute([(int)$id]);
    if ($stmt->fetchColumn()) {
        $_SESSION['company_id'] = (int)$id;
        return true;
    }
    return false;
}

function create_company($name) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('INSERT INTO companies(company_name, created_date) VALUES(:name, CURDATE())');
    $stmt->execute([':name' => trim($name)]);
    $_SESSION['company_id'] = (int)$pdo->lastInsertId();
}

// Worker functions for existing table structure
function list_workers($company_id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('SELECT * FROM workers');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_worker($company_id, $name) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('INSERT INTO workers(name) VALUES(?)');
    $stmt->execute([trim($name)]);
    return $pdo->lastInsertId();
}

function delete_worker($company_id, $id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('DELETE FROM workers WHERE id=?');
    $stmt->execute([$id]);
}

// Stock functions for existing table structure
function list_stocks($company_id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('SELECT * FROM stocks ORDER BY date DESC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function add_stock($company_id, $cotton_type, $bag_weight, $total_bags) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('INSERT INTO stocks(cotton_type, bag_weight, total_bags, date) VALUES(?, ?, ?, CURDATE())');
    $stmt->execute([$cotton_type, $bag_weight, $total_bags]);
    return $pdo->lastInsertId();
}

// Yarn types functions
function list_yarn_types($company_id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('SELECT * FROM yarn_types WHERE company_id=? ORDER BY name');
    $stmt->execute([$company_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_yarn_type($company_id, $name) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('INSERT INTO yarn_types(company_id, name) VALUES(?, ?)');
    $stmt->execute([$company_id, trim($name)]);
    return $pdo->lastInsertId();
}

// Work logs functions
function list_work_logs($company_id, $worker_id = null, $start_date = null, $end_date = null) {
    $pdo = DB::conn();
    $sql = 'SELECT wl.*, w.name as worker_name FROM work_logs wl LEFT JOIN workers w ON wl.worker_id = w.id WHERE wl.company_id=?';
    $params = [$company_id];
    
    if ($worker_id) {
        $sql .= ' AND wl.worker_id=?';
        $params[] = $worker_id;
    }
    if ($start_date) {
        $sql .= ' AND wl.work_date >= ?';
        $params[] = $start_date;
    }
    if ($end_date) {
        $sql .= ' AND wl.work_date <= ?';
        $params[] = $end_date;
    }
    
    $sql .= ' ORDER BY wl.work_date DESC, wl.id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_work_log($company_id, $worker_id, $work_date, $warps_count, $rate_per_warp) {
    $pdo = DB::conn();
    $amount = $warps_count * $rate_per_warp;
    $stmt = $pdo->prepare('INSERT INTO work_logs(company_id, worker_id, work_date, warps_count, rate_per_warp, amount) VALUES(?, ?, ?, ?, ?, ?)');
    $stmt->execute([$company_id, $worker_id, $work_date, $warps_count, $rate_per_warp, $amount]);
    return $pdo->lastInsertId();
}

// Advances functions
function list_advances($company_id, $worker_id = null) {
    $pdo = DB::conn();
    $sql = 'SELECT a.*, w.name as worker_name FROM advances a LEFT JOIN workers w ON a.worker_id = w.id WHERE a.company_id=?';
    $params = [$company_id];
    
    if ($worker_id) {
        $sql .= ' AND a.worker_id=?';
        $params[] = $worker_id;
    }
    
    $sql .= ' ORDER BY a.advance_date DESC, a.id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_advance($company_id, $worker_id, $advance_date, $amount, $note = '') {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('INSERT INTO advances(company_id, worker_id, advance_date, amount, note) VALUES(?, ?, ?, ?, ?)');
    $stmt->execute([$company_id, $worker_id, $advance_date, $amount, $note]);
    return $pdo->lastInsertId();
}

// Purchased Stocks structure
function list_purchased_stocks($company_id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('SELECT cur.*, yt.name as yarn_name 
                            FROM purchased_stocks cur 
                            JOIN yarn_types yt ON cur.yarn_type_id = yt.id 
                            WHERE cur.company_id = ? 
                            ORDER BY cur.date_purchased DESC, cur.id DESC');
    $stmt->execute([$company_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_purchased_stock($company_id, $id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare('DELETE FROM purchased_stocks WHERE id = ? AND company_id = ?');
    $stmt->execute([$id, $company_id]);
}

// Global Summary: Comparison of Purchased vs Production
function get_yarn_production_summary($company_id) {
    $pdo = DB::conn();
    $sql = "SELECT yt.id as yarn_id, yt.name as yarn_name,
            COALESCE((SELECT SUM(bag_count * weight_per_bag) FROM purchased_stocks ps WHERE ps.yarn_type_id = yt.id AND ps.company_id = ?), 0) as purchased_kg,
            COALESCE((SELECT SUM(total_bags * bag_weight) FROM stocks s WHERE s.yarn_type_id = yt.id), 0) as finished_kg
            FROM yarn_types yt
            WHERE yt.company_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id, $company_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as &$r) {
        $r['pending_kg'] = max(0, $r['purchased_kg'] - $r['finished_kg']);
    }
    return $rows;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

<?php
// db_connect.php
// Database connection file

function finpay_db_exec_ddl(mysqli $dbc, string $sql): void
{
    try {
        mysqli_query($dbc, $sql);
    } catch (Throwable $e) {
        // Keep runtime alive even if DDL fails (e.g., limited grants).
    }
}

function finpay_db_ensure_core_schema(mysqli $dbc): void
{
    finpay_db_exec_ddl($dbc, "CREATE TABLE IF NOT EXISTS wallets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        symbol VARCHAR(16) NOT NULL,
        balance DECIMAL(20,8) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_symbol (user_id, symbol),
        KEY idx_user_symbol (user_id, symbol)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    finpay_db_exec_ddl($dbc, "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(120) NOT NULL,
        value TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_key_name (key_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    finpay_db_exec_ddl($dbc, "CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(20,8) NOT NULL,
        currency VARCHAR(10) NOT NULL DEFAULT 'GBP',
        status VARCHAR(30) NOT NULL DEFAULT 'completed',
        method VARCHAR(50) DEFAULT 'contact_payment',
        reference VARCHAR(80) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_withdrawals_user_created (user_id, created_at),
        INDEX idx_withdrawals_status_currency (status, currency)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    finpay_db_exec_ddl($dbc, "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(40) NOT NULL,
        symbol VARCHAR(16) NOT NULL,
        amount DECIMAL(20,8) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'completed',
        description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_transactions_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    finpay_db_exec_ddl($dbc, "CREATE TABLE IF NOT EXISTS user_stakes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id INT NOT NULL,
        amount DECIMAL(20,8) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        next_payout DATETIME NULL,
        end_date DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_stakes_user_status (user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$default_db_host = 'localhost';
$default_db_user = 'root';
$default_db_pass = '';
$default_db_name = 'ledgercore_db';
$default_db_port = 3306;

// Use environment variables when provided (cloud/deployment),
// otherwise keep local machine defaults.
$db_host = (string)(getenv('DB_HOST') ?: $default_db_host);
$db_name = (string)(getenv('DB_NAME') ?: $default_db_name);
$db_port = (int)(getenv('DB_PORT') ?: $default_db_port);

// Support both DB_USERNAME/DB_PASSWORD and DB_USER/DB_PASS naming.
$db_user = (string)(getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: $default_db_user));
$db_pass = (string)(getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: $default_db_pass));

// Create connection
$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if (!$dbc) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for full unicode support
mysqli_set_charset($dbc, "utf8mb4");

// Auto-repair minimal schema required by main app flows.
finpay_db_ensure_core_schema($dbc);
?>

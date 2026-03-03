<?php
/**
 * become/includes/db.php — MySQL Connection
 * 
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $db = Database::getInstance();
 *   $stmt = $db->prepare("SELECT * FROM folders");
 *   $stmt->execute();
 *   $rows = $stmt->fetchAll();
 */

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = require __DIR__ . '/../../config.php';

        $this->pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['db_host'] ?? 'localhost',
                $config['db_port'] ?? 3306,
                $config['db_name'] ?? ''
            ),
            $config['db_user'] ?? '',
            $config['db_pass'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function prepare($sql)     { return $this->pdo->prepare($sql); }
    public function query($sql)       { return $this->pdo->query($sql); }
    public function lastInsertId()    { return $this->pdo->lastInsertId(); }
    public function beginTransaction(){ return $this->pdo->beginTransaction(); }
    public function commit()          { return $this->pdo->commit(); }
    public function rollBack()        { return $this->pdo->rollBack(); }
}

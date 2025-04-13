<?php
class Database {
    private static $instance = null;
    private $pdo;
    private $logger;

    private function __construct() {
        $this->logger = Logger::getInstance();
        
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->logger->info('Database connection established');
        } catch (PDOException $e) {
            $this->logger->critical('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception('Database query failed');
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );

        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $params = []) {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    public function transaction($callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function count($table, $where = '1', $params = []) {
        $sql = sprintf("SELECT COUNT(*) as count FROM %s WHERE %s", $table, $where);
        return (int) $this->fetch($sql, $params)['count'];
    }

    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }

    public function paginate($sql, $page = 1, $perPage = 10, $params = []) {
        $offset = ($page - 1) * $perPage;
        $countSql = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) as count FROM', $sql);
        $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
        
        $total = (int) $this->fetch($countSql, $params)['count'];
        $totalPages = ceil($total / $perPage);
        
        $sql .= sprintf(" LIMIT %d OFFSET %d", $perPage, $offset);
        $items = $this->fetchAll($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }

    public function search($table, $fields, $query, $where = '1', $params = []) {
        $searchConditions = array_map(function($field) {
            return "$field LIKE ?";
        }, $fields);

        $searchParams = array_fill(0, count($fields), "%$query%");
        $sql = sprintf(
            "SELECT * FROM %s WHERE (%s) AND (%s)",
            $table,
            implode(' OR ', $searchConditions),
            $where
        );

        return $this->fetchAll($sql, array_merge($searchParams, $params));
    }
} 
<?php
namespace Traits;

trait ModelTrait {
    protected $pdo;
    protected static $table;
    protected $id;
    protected $data = [];

    public function __construct($pdo, $id = null) {
        $this->pdo = $pdo;
        if ($id) {
            $this->id = $id;
            $this->loadData();
        }
    }

    protected function loadData() {
        if (!static::$table) {
            throw new \Exception("Table name not defined for " . get_class($this));
        }
        $stmt = $this->pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$this->id]);
        $this->data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$this->data) {
            throw new \Exception("Record not found");
        }
    }

    protected function saveData() {
        if (!static::$table) {
            throw new \Exception("Table name not defined for " . get_class($this));
        }
        if ($this->id) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    protected function create() {
        $fields = array_keys($this->data);
        $values = array_values($this->data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO " . static::$table . " (" . implode(',', $fields) . ") VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute($values)) {
            $this->id = $this->pdo->lastInsertId();
            return true;
        }
        return false;
    }

    protected function update() {
        $fields = array_keys($this->data);
        $values = array_values($this->data);
        $set = implode('=?,', $fields) . '=?';
        
        $sql = "UPDATE " . static::$table . " SET $set WHERE id = ?";
        $values[] = $this->id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    protected function removeData() {
        if (!static::$table) {
            throw new \Exception("Table name not defined for " . get_class($this));
        }
        if (!$this->id) {
            throw new \Exception("Cannot remove unsaved record");
        }
        $stmt = $this->pdo->prepare("DELETE FROM " . static::$table . " WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function all($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("SELECT * FROM " . static::$table . " LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findBy($field, $value) {
        $stmt = $this->pdo->prepare("SELECT * FROM " . static::$table . " WHERE $field = ?");
        $stmt->execute([$value]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findAllBy($field, $value, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("SELECT * FROM " . static::$table . " WHERE $field = ? LIMIT ? OFFSET ?");
        $stmt->execute([$value, $perPage, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) FROM " . static::$table;
        if ($where) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function exists($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }

    public function query($sql, $params = [], $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function queryOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getRelated($table, $foreignKey, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE $foreignKey = ? LIMIT ? OFFSET ?");
        $stmt->execute([$this->id, $perPage, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRelatedOne($table, $foreignKey) {
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE $foreignKey = ? LIMIT 1");
        $stmt->execute([$this->id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function countRelated($table, $foreignKey) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE $foreignKey = ?");
        $stmt->execute([$this->id]);
        return $stmt->fetchColumn();
    }

    public function hasRelated($table, $foreignKey) {
        return $this->countRelated($table, $foreignKey) > 0;
    }

    public function getAttributeNames() {
        return array_keys($this->data);
    }

    public function hasAttribute($name) {
        return isset($this->data[$name]);
    }

    public function getAttribute($name, $default = null) {
        return $this->data[$name] ?? $default;
    }

    public function setAttribute($name, $value) {
        $this->data[$name] = $value;
    }

    public function getAttributes(array $names) {
        $result = [];
        foreach ($names as $name) {
            if ($this->hasAttribute($name)) {
                $result[$name] = $this->getAttribute($name);
            }
        }
        return $result;
    }

    public function setAttributes(array $values) {
        foreach ($values as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    public function toArray() {
        return $this->data;
    }

    public function toJson() {
        return json_encode($this->data);
    }
} 
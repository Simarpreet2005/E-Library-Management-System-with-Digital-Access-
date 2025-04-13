<?php
require_once 'Model.php';

class User extends Model {
    protected $id;
    protected $username;
    protected $email;
    protected $role;
    protected $created_at;
    
    public function __construct($pdo, $id = null) {
        parent::__construct($pdo);
        $this->id = $id;
        if ($id) {
            $this->load();
        }
    }
    
    public function load() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $userData = $stmt->fetch();
            
            if ($userData) {
                $this->username = $userData['username'];
                $this->email = $userData['email'];
                $this->role = $userData['role'];
                $this->created_at = $userData['created_at'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error loading user: " . $e->getMessage());
            return false;
        }
    }
    
    public function save() {
        try {
            if ($this->id) {
                // Update existing user
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                return $stmt->execute([$this->username, $this->email, $this->id]);
            } else {
                // Create new user
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                return $stmt->execute([$this->username, $this->email, $this->password, $this->role]);
            }
        } catch (PDOException $e) {
            error_log("Error saving user: " . $e->getMessage());
            return false;
        }
    }
    
    public function remove() {
        if (!$this->id) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$this->id]);
        } catch (PDOException $e) {
            error_log("Error removing user: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function setUsername($username) {
        $this->username = $username;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function setPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function setRole($role) {
        $this->role = $role;
    }
    
    public function updateProfile($newUsername, $newEmail) {
        $this->username = $newUsername;
        $this->email = $newEmail;
        return $this->save();
    }
    
    public function authenticate($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $this->id = $user['id'];
                $this->username = $user['username'];
                $this->email = $user['email'];
                $this->role = $user['role'];
                $this->created_at = $user['created_at'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBorrowedBooks($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, br.borrow_date, br.due_date, br.status 
                FROM books b
                JOIN borrowings br ON b.id = br.book_id
                WHERE br.user_id = ? AND br.status = 'borrowed'
                ORDER BY br.borrow_date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$this->id, $perPage, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting borrowed books: " . $e->getMessage());
            return [];
        }
    }
    
    public function getReservedBooks($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, r.reservation_date, r.status 
                FROM books b
                JOIN reservations r ON b.id = r.book_id
                WHERE r.user_id = ? AND r.status = 'pending'
                ORDER BY r.reservation_date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$this->id, $perPage, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting reserved books: " . $e->getMessage());
            return [];
        }
    }
    
    public function getFines($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, b.title as book_title
                FROM fines f
                JOIN borrowings br ON f.borrowing_id = br.id
                JOIN books b ON br.book_id = b.id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$this->id, $perPage, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting fines: " . $e->getMessage());
            return [];
        }
    }

    protected function validateData($data, $isUpdate = false) {
        $required = ['username', 'email', 'password'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
        }

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (isset($data['username'])) {
            $existingUser = $this->db->fetch(
                "SELECT id FROM users WHERE username = ? AND id != ?",
                [$data['username'], $this->id ?? 0]
            );
            if ($existingUser) {
                throw new Exception('Username already exists');
            }
        }

        if (isset($data['email'])) {
            $existingUser = $this->db->fetch(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $this->id ?? 0]
            );
            if ($existingUser) {
                throw new Exception('Email already exists');
            }
        }

        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
    }

    public function getBorrowings($page = 1, $perPage = 10) {
        return $this->db->paginate(
            "SELECT b.*, bk.title as book_title 
             FROM borrowings b 
             LEFT JOIN books bk ON b.book_id = bk.id 
             WHERE b.user_id = ? 
             ORDER BY b.borrow_date DESC",
            $page,
            $perPage,
            [$this->id]
        );
    }

    public function getUnpaidFines() {
        return $this->db->fetchAll(
            "SELECT f.*, b.title as book_title 
             FROM fines f 
             LEFT JOIN borrowings br ON f.borrowing_id = br.id 
             LEFT JOIN books b ON br.book_id = b.id 
             WHERE f.user_id = ? AND f.status = 'pending'",
            [$this->id]
        );
    }

    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isActive() {
        return $this->data['status'] === 'active';
    }
} 
<?php
namespace App\Models;

use Exception;
use App\Core\Model;

class Book extends Model {
    protected static $table = 'books';

    protected function validateData($data, $isUpdate = false) {
        $required = ['title', 'author', 'category_id'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
        }

        if (isset($data['category_id'])) {
            $category = $this->queryOne(
                "SELECT id FROM categories WHERE id = ?",
                [$data['category_id']]
            );
            if (!$category) {
                throw new Exception('Invalid category');
            }
        }

        if (isset($data['isbn']) && !empty($data['isbn'])) {
            if (!preg_match('/^[0-9]{10,13}$/', $data['isbn'])) {
                throw new Exception('Invalid ISBN format');
            }
            
            $existingBook = $this->queryOne(
                "SELECT id FROM books WHERE isbn = ? AND id != ?",
                [$data['isbn'], $this->id ?? 0]
            );
            if ($existingBook) {
                throw new Exception('ISBN already exists');
            }
        }

        if (isset($data['publication_year'])) {
            $year = (int) $data['publication_year'];
            if ($year < 1000 || $year > date('Y')) {
                throw new Exception('Invalid publication year');
            }
        }

        if (isset($data['total_copies']) && $data['total_copies'] < 0) {
            throw new Exception('Total copies cannot be negative');
        }

        if (isset($data['status']) && !in_array($data['status'], ['available', 'borrowed', 'reserved', 'maintenance'])) {
            throw new Exception('Invalid book status');
        }
    }

    public function search($query, $page = 1, $perPage = 10) {
        $searchQuery = "%{$query}%";
        return $this->query(
            "SELECT b.*, c.name as category_name 
             FROM books b 
             LEFT JOIN categories c ON b.category_id = c.id 
             WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? 
             ORDER BY b.title",
            [$searchQuery, $searchQuery, $searchQuery],
            $page,
            $perPage
        );
    }

    public function getCategory() {
        return $this->getRelatedOne('categories', 'category_id');
    }

    public function getBorrowings($page = 1, $perPage = 10) {
        return $this->query(
            "SELECT b.*, u.username 
             FROM borrowings b 
             LEFT JOIN users u ON b.user_id = u.id 
             WHERE b.book_id = ? 
             ORDER BY b.borrow_date DESC",
            [$this->id],
            $page,
            $perPage
        );
    }

    public function getCurrentBorrowing() {
        return $this->queryOne(
            "SELECT b.*, u.username 
             FROM borrowings b 
             LEFT JOIN users u ON b.user_id = u.id 
             WHERE b.book_id = ? AND b.status = 'borrowed'",
            [$this->id]
        );
    }

    public function isAvailable() {
        return $this->status === 'available';
    }

    public function isBorrowed() {
        return $this->status === 'borrowed';
    }

    public function isReserved() {
        return $this->status === 'reserved';
    }

    public function isUnderMaintenance() {
        return $this->status === 'maintenance';
    }

    public static function findByIsbn($isbn) {
        $instance = new static();
        $bookData = $instance->queryOne(
            "SELECT b.*, c.name as category_name 
             FROM books b 
             LEFT JOIN categories c ON b.category_id = c.id 
             WHERE b.isbn = ?",
            [$isbn]
        );

        if ($bookData) {
            $book = new static();
            $book->id = $bookData['id'];
            $book->data = $bookData;
            return $book;
        }

        return null;
    }

    public function save($data = null) {
        if ($data !== null) {
            $this->setAttributes($data);
        }

        $this->validateData($this->data, isset($this->id));

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            if (isset($this->id) && $this->cover_image) {
                $this->deleteFile($this->cover_image);
            }
            $this->cover_image = $this->handleFileUpload($_FILES['cover_image']);
        }

        return parent::save();
    }

    public function delete() {
        if ($this->cover_image) {
            $this->deleteFile($this->cover_image);
        }
        return parent::delete();
    }

    public function borrow($userId) {
        if ($this->available_copies <= 0) {
            throw new Exception('No copies available for borrowing');
        }

        $borrowedCount = $this->count(
            'borrowings',
            'user_id = ? AND status = ?',
            [$userId, 'borrowed']
        );

        if ($borrowedCount >= MAX_BORROW_BOOKS) {
            throw new Exception('Maximum borrow limit reached');
        }

        $alreadyBorrowed = $this->exists(
            'borrowings',
            'user_id = ? AND book_id = ? AND status = ?',
            [$userId, $this->id, 'borrowed']
        );

        if ($alreadyBorrowed) {
            throw new Exception('You have already borrowed this book');
        }

        $dueDate = date('Y-m-d H:i:s', strtotime('+' . MAX_BORROW_DAYS . ' days'));

        $this->pdo->beginTransaction();
        try {
            $this->query(
                "INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status) 
                 VALUES (?, ?, ?, ?, ?)",
                [$userId, $this->id, date('Y-m-d H:i:s'), $dueDate, 'borrowed']
            );

            $this->available_copies--;
            $this->save();

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function return($userId) {
        $borrowing = $this->queryOne(
            "SELECT * FROM borrowings 
             WHERE user_id = ? AND book_id = ? AND status = ?",
            [$userId, $this->id, 'borrowed']
        );

        if (!$borrowing) {
            throw new Exception('No active borrowing found for this book');
        }

        $returnDate = date('Y-m-d H:i:s');
        $fineAmount = 0;

        // Calculate fine if overdue
        if (strtotime($returnDate) > strtotime($borrowing['due_date'])) {
            $daysOverdue = floor((strtotime($returnDate) - strtotime($borrowing['due_date'])) / 86400);
            $fineAmount = min($daysOverdue * FINE_PER_DAY, MAX_FINE);
        }

        $this->pdo->beginTransaction();
        try {
            $this->query(
                "UPDATE borrowings SET return_date = ?, status = ?, fine_amount = ? 
                 WHERE id = ?",
                [$returnDate, 'returned', $fineAmount, $borrowing['id']]
            );

            // Create fine record if applicable
            if ($fineAmount > 0) {
                $this->query(
                    "INSERT INTO fines (borrowing_id, user_id, amount, reason) 
                     VALUES (?, ?, ?, ?)",
                    [$borrowing['id'], $borrowing['user_id'], $fineAmount, 'late_return']
                );
            }

            $this->available_copies++;
            $this->save();

            $this->pdo->commit();
            return $fineAmount;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function reserve($userId) {
        if ($this->available_copies > 0) {
            throw new Exception('Book is available for borrowing');
        }

        // Check if user has already reserved this book
        $alreadyReserved = $this->exists(
            'reservations',
            'user_id = ? AND book_id = ? AND status = ?',
            [$userId, $this->id, 'pending']
        );

        if ($alreadyReserved) {
            throw new Exception('You have already reserved this book');
        }

        $this->query(
            "INSERT INTO reservations (user_id, book_id, reservation_date, status) 
             VALUES (?, ?, ?, ?)",
            [$userId, $this->id, date('Y-m-d H:i:s'), 'pending']
        );

        return true;
    }

    private function handleFileUpload($file) {
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds limit');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_FILE_TYPES)) {
            throw new Exception('Invalid file type');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . '/books/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload file');
        }

        return $filename;
    }

    private function deleteFile($filename) {
        $filepath = UPLOAD_PATH . '/books/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // Magic getter for easier property access
    public function __get($name) {
        return $this->getAttribute($name);
    }

    // Magic setter for easier property access
    public function __set($name, $value) {
        $this->setAttribute($name, $value);
    }
} 
<?php
class Borrowing extends Model {
    protected $table = 'borrowings';

    protected function validateData($data, $isUpdate = false) {
        $required = ['book_id', 'user_id'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['borrowed', 'returned', 'overdue'])) {
            throw new Exception('Invalid borrowing status');
        }

        if (isset($data['book_id'])) {
            $book = $this->db->fetch(
                "SELECT id, status FROM books WHERE id = ?",
                [$data['book_id']]
            );
            if (!$book) {
                throw new Exception('Book not found');
            }
            if ($book['status'] !== 'available') {
                throw new Exception('Book is not available for borrowing');
            }
        }

        if (isset($data['user_id'])) {
            $user = $this->db->fetch(
                "SELECT id, status FROM users WHERE id = ?",
                [$data['user_id']]
            );
            if (!$user) {
                throw new Exception('User not found');
            }
            if ($user['status'] !== 'active') {
                throw new Exception('User is not active');
            }

            // Check for unpaid fines
            $unpaidFines = $this->db->fetch(
                "SELECT COUNT(*) as count FROM fines 
                 WHERE user_id = ? AND status = 'pending'",
                [$data['user_id']]
            );
            if ($unpaidFines['count'] > 0) {
                throw new Exception('User has unpaid fines');
            }
        }
    }

    public function create($data) {
        $this->validateData($data);

        // Set default values
        $data['status'] = 'borrowed';
        $data['borrow_date'] = date('Y-m-d H:i:s');
        $data['due_date'] = date('Y-m-d H:i:s', strtotime('+14 days'));

        // Update book status
        $this->db->update('books', 
            ['status' => 'borrowed'], 
            'id = ?', 
            [$data['book_id']]
        );

        return parent::create($data);
    }

    public function return($returnDate = null) {
        if ($this->data['status'] !== 'borrowed') {
            throw new Exception('Book is not currently borrowed');
        }

        $returnDate = $returnDate ?? date('Y-m-d H:i:s');
        $dueDate = strtotime($this->data['due_date']);
        $returnDate = strtotime($returnDate);

        $data = [
            'status' => 'returned',
            'return_date' => date('Y-m-d H:i:s', $returnDate)
        ];

        if ($returnDate > $dueDate) {
            $data['status'] = 'overdue';
        }

        $this->update($data);

        // Update book status
        $this->db->update('books', 
            ['status' => 'available'], 
            'id = ?', 
            [$this->data['book_id']]
        );

        return $this;
    }

    public function renew() {
        if ($this->data['status'] !== 'borrowed') {
            throw new Exception('Book is not currently borrowed');
        }

        // Check if book is already overdue
        if (strtotime($this->data['due_date']) < time()) {
            throw new Exception('Cannot renew an overdue book');
        }

        // Check if book has been renewed before
        if ($this->data['renewal_count'] >= MAX_RENEWALS) {
            throw new Exception('Maximum renewals reached');
        }

        $newDueDate = date('Y-m-d H:i:s', strtotime('+14 days'));
        
        $this->update([
            'due_date' => $newDueDate,
            'renewal_count' => $this->data['renewal_count'] + 1
        ]);

        return $this;
    }

    public function getUserBorrowings($userId, $page = 1, $perPage = 10) {
        return $this->db->paginate(
            "SELECT b.*, bk.title as book_title 
             FROM borrowings b 
             LEFT JOIN books bk ON b.book_id = bk.id 
             WHERE b.user_id = ? 
             ORDER BY b.borrow_date DESC",
            $page,
            $perPage,
            [$userId]
        );
    }

    public function getActiveBorrowings($userId) {
        return $this->db->fetchAll(
            "SELECT b.*, bk.title as book_title 
             FROM borrowings b 
             LEFT JOIN books bk ON b.book_id = bk.id 
             WHERE b.user_id = ? AND b.status = 'borrowed'",
            [$userId]
        );
    }

    public function isBorrowed() {
        return $this->data['status'] === 'borrowed';
    }

    public function isReturned() {
        return $this->data['status'] === 'returned';
    }

    public function isOverdue() {
        return $this->data['status'] === 'overdue';
    }

    public function isOverdueNow() {
        return $this->isBorrowed() && strtotime($this->data['due_date']) < time();
    }
} 
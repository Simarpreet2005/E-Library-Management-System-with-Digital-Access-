<?php
class Fine extends Model {
    protected $table = 'fines';

    protected function validateData($data, $isUpdate = false) {
        $required = ['borrowing_id', 'user_id', 'amount', 'reason'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
        }

        if (isset($data['amount']) && $data['amount'] < 0) {
            throw new Exception('Fine amount cannot be negative');
        }

        if (isset($data['reason']) && !in_array($data['reason'], ['late_return', 'damage', 'loss'])) {
            throw new Exception('Invalid fine reason');
        }

        if (isset($data['status']) && !in_array($data['status'], ['pending', 'paid', 'waived'])) {
            throw new Exception('Invalid fine status');
        }

        if (isset($data['borrowing_id'])) {
            $borrowing = $this->db->fetch(
                "SELECT id FROM borrowings WHERE id = ?",
                [$data['borrowing_id']]
            );
            if (!$borrowing) {
                throw new Exception('Borrowing record not found');
            }
        }

        if (isset($data['user_id'])) {
            $user = $this->db->fetch(
                "SELECT id FROM users WHERE id = ?",
                [$data['user_id']]
            );
            if (!$user) {
                throw new Exception('User not found');
            }
        }
    }

    public static function calculateFine($borrowingId) {
        $db = Database::getInstance();
        $borrowing = $db->fetch(
            "SELECT * FROM borrowings WHERE id = ?",
            [$borrowingId]
        );

        if (!$borrowing) {
            throw new Exception('Borrowing record not found');
        }

        if ($borrowing['status'] !== 'overdue') {
            return 0;
        }

        $returnDate = $borrowing['return_date'] ?? date('Y-m-d H:i:s');
        $dueDate = strtotime($borrowing['due_date']);
        $returnDate = strtotime($returnDate);

        if ($returnDate <= $dueDate) {
            return 0;
        }

        $daysOverdue = floor(($returnDate - $dueDate) / 86400);
        $fineAmount = min($daysOverdue * FINE_PER_DAY, MAX_FINE);

        return $fineAmount;
    }

    public function getUserFines($userId, $page = 1, $perPage = 10) {
        return $this->db->paginate(
            "SELECT f.*, b.title as book_title 
             FROM fines f 
             LEFT JOIN borrowings br ON f.borrowing_id = br.id 
             LEFT JOIN books b ON br.book_id = b.id 
             WHERE f.user_id = ? 
             ORDER BY f.created_at DESC",
            $page,
            $perPage,
            [$userId]
        );
    }

    public function getUnpaidFines($userId) {
        return $this->db->fetchAll(
            "SELECT f.*, b.title as book_title 
             FROM fines f 
             LEFT JOIN borrowings br ON f.borrowing_id = br.id 
             LEFT JOIN books b ON br.book_id = b.id 
             WHERE f.user_id = ? AND f.status = 'pending'",
            [$userId]
        );
    }

    public function isPaid() {
        return $this->data['status'] === 'paid';
    }

    public function isPending() {
        return $this->data['status'] === 'pending';
    }

    public function isWaived() {
        return $this->data['status'] === 'waived';
    }
} 
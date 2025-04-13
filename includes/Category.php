<?php
class Category extends Model {
    protected $table = 'categories';

    protected function validateData($data, $isUpdate = false) {
        if (!isset($data['name']) || empty($data['name'])) {
            throw new Exception('Category name is required');
        }

        $existingCategory = $this->db->fetch(
            "SELECT id FROM categories WHERE name = ? AND id != ?",
            [$data['name'], $this->id ?? 0]
        );
        if ($existingCategory) {
            throw new Exception('Category name already exists');
        }

        if (isset($data['parent_id'])) {
            if ($data['parent_id'] == $this->id) {
                throw new Exception('Category cannot be its own parent');
            }
            
            $parent = $this->db->fetch(
                "SELECT id FROM categories WHERE id = ?",
                [$data['parent_id']]
            );
            if (!$parent) {
                throw new Exception('Parent category not found');
            }
        }
    }

    public function getBooks($page = 1, $perPage = 10) {
        return $this->db->paginate(
            "SELECT b.*, c.name as category_name 
             FROM books b 
             LEFT JOIN categories c ON b.category_id = c.id 
             WHERE b.category_id = ? 
             ORDER BY b.title",
            $page,
            $perPage,
            [$this->id]
        );
    }

    public function getSubcategories() {
        return $this->db->fetchAll(
            "SELECT c.*, p.name as parent_name 
             FROM categories c 
             LEFT JOIN categories p ON c.parent_id = p.id 
             WHERE c.parent_id = ? 
             ORDER BY c.name",
            [$this->id]
        );
    }

    public function getParent() {
        if (!$this->data['parent_id']) {
            return null;
        }
        return new self($this->data['parent_id']);
    }

    public function hasChildren() {
        $count = $this->db->count('categories', 'parent_id = ?', [$this->id]);
        return $count > 0;
    }

    public function hasBooks() {
        $count = $this->db->count('books', 'category_id = ?', [$this->id]);
        return $count > 0;
    }
} 
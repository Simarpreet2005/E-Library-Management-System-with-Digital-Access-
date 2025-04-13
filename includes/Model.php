<?php
namespace App\Core;

require_once __DIR__ . '/traits/ModelTrait.php';

use Traits\ModelTrait;

abstract class Model {
    use ModelTrait;

    public function getId() {
        return $this->id;
    }

    public function getData() {
        return $this->data;
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }
} 
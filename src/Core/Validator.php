<?php
namespace App\Core;

class Validator {
    private $data;
    private $errors = [];
    private $currentField;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function field($fieldName) {
        $this->currentField = $fieldName;
        return $this;
    }
    
    public function required($fieldName = null) {
        $field = $fieldName ?? $this->currentField;
        
        if (!isset($this->data[$field]) || $this->isEmpty($this->data[$field])) {
            $this->errors[$field][] = ucfirst($field) . ' is required';
        }
        
        if ($fieldName) {
            $this->currentField = $fieldName;
        }
        
        return $this;
    }
    
    public function string() {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && !is_string($this->data[$field])) {
            $this->errors[$field][] = ucfirst($field) . ' must be a string';
        }
        
        return $this;
    }
    
    public function min($length) {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) < $length) {
            $this->errors[$field][] = ucfirst($field) . ' must be at least ' . $length . ' characters';
        }
        
        return $this;
    }
    
    public function max($length) {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) > $length) {
            $this->errors[$field][] = ucfirst($field) . ' must not exceed ' . $length . ' characters';
        }
        
        return $this;
    }
    
    public function email() {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = ucfirst($field) . ' must be a valid email address';
        }
        
        return $this;
    }
    
    public function numeric() {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = ucfirst($field) . ' must be numeric';
        }
        
        return $this;
    }
    
    public function integer() {
        $field = $this->currentField;
        
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = ucfirst($field) . ' must be an integer';
        }
        
        return $this;
    }
    
    public function passes() {
        return empty($this->errors);
    }
    
    public function fails() {
        return !$this->passes();
    }
    
    public function errors() {
        return $this->errors;
    }
    
    public function validated() {
        if ($this->fails()) {
            return null;
        }
        
        // Return only the fields that were validated
        $validated = [];
        foreach (array_keys($this->errors) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = trim($this->data[$field]);
            }
        }
        
        return $validated;
    }
    
    private function isEmpty($value) {
        if (is_string($value)) {
            return trim($value) === '';
        }
        return empty($value);
    }
}

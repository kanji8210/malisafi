<?php
/**
 * Input Validator for Malisafi MLS
 * 
 * Provides validation utilities for form inputs before sanitization
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Validator class
 */
class Validator {
    
    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = array();
    
    /**
     * Validated data
     *
     * @var array
     */
    private $validated = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->errors = array();
        $this->validated = array();
    }
    
    /**
     * Validate email address
     *
     * @param string $email Email to validate
     * @param string $field_name Field name for error messages
     * @param bool $required Whether field is required
     * @return bool
     */
    public function email($email, $field_name = 'email', $required = true) {
        if (empty($email)) {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        if (!is_email($email)) {
            $this->errors[$field_name] = __('Please provide a valid email address.', 'malisafi-mls');
            return false;
        }
        
        $this->validated[$field_name] = sanitize_email($email);
        return true;
    }
    
    /**
     * Validate phone number
     *
     * @param string $phone Phone number to validate
     * @param string $field_name Field name for error messages
     * @param bool $required Whether field is required
     * @return bool
     */
    public function phone($phone, $field_name = 'phone', $required = true) {
        if (empty($phone)) {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        // Remove common separators
        $clean_phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);
        
        // Kenya phone format: +254XXXXXXXXX or 07XXXXXXXX or 01XXXXXXXX
        if (!preg_match('/^(\+254|0)?[17]\d{8}$/', $clean_phone)) {
            $this->errors[$field_name] = __('Please provide a valid Kenyan phone number (e.g., +254712345678 or 0712345678).', 'malisafi-mls');
            return false;
        }
        
        $this->validated[$field_name] = sanitize_text_field($phone);
        return true;
    }
    
    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @param string $field_name Field name for error messages
     * @param bool $required Whether field is required
     * @return bool
     */
    public function url($url, $field_name = 'url', $required = false) {
        if (empty($url)) {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[$field_name] = __('Please provide a valid URL.', 'malisafi-mls');
            return false;
        }
        
        $this->validated[$field_name] = esc_url_raw($url);
        return true;
    }
    
    /**
     * Validate text field
     *
     * @param string $text Text to validate
     * @param string $field_name Field name for error messages
     * @param int $min_length Minimum length
     * @param int $max_length Maximum length
     * @param bool $required Whether field is required
     * @return bool
     */
    public function text($text, $field_name = 'text', $min_length = 0, $max_length = 255, $required = true) {
        if (empty($text)) {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        $length = strlen($text);
        
        if ($min_length > 0 && $length < $min_length) {
            $this->errors[$field_name] = sprintf(
                __('%s must be at least %d characters long.', 'malisafi-mls'),
                ucfirst($field_name),
                $min_length
            );
            return false;
        }
        
        if ($max_length > 0 && $length > $max_length) {
            $this->errors[$field_name] = sprintf(
                __('%s must not exceed %d characters.', 'malisafi-mls'),
                ucfirst($field_name),
                $max_length
            );
            return false;
        }
        
        $this->validated[$field_name] = sanitize_text_field($text);
        return true;
    }
    
    /**
     * Validate number (integer or float)
     *
     * @param mixed $number Number to validate
     * @param string $field_name Field name for error messages
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param bool $required Whether field is required
     * @return bool
     */
    public function number($number, $field_name = 'number', $min = null, $max = null, $required = true) {
        if (empty($number) && $number !== 0 && $number !== '0') {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        if (!is_numeric($number)) {
            $this->errors[$field_name] = sprintf(__('%s must be a valid number.', 'malisafi-mls'), ucfirst($field_name));
            return false;
        }
        
        $number = floatval($number);
        
        if ($min !== null && $number < $min) {
            $this->errors[$field_name] = sprintf(
                __('%s must be at least %s.', 'malisafi-mls'),
                ucfirst($field_name),
                $min
            );
            return false;
        }
        
        if ($max !== null && $number > $max) {
            $this->errors[$field_name] = sprintf(
                __('%s must not exceed %s.', 'malisafi-mls'),
                ucfirst($field_name),
                $max
            );
            return false;
        }
        
        $this->validated[$field_name] = $number;
        return true;
    }
    
    /**
     * Validate price (must be positive)
     *
     * @param mixed $price Price to validate
     * @param string $field_name Field name for error messages
     * @param bool $required Whether field is required
     * @return bool
     */
    public function price($price, $field_name = 'price', $required = true) {
        return $this->number($price, $field_name, 0, null, $required);
    }
    
    /**
     * Validate integer
     *
     * @param mixed $integer Integer to validate
     * @param string $field_name Field name for error messages
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param bool $required Whether field is required
     * @return bool
     */
    public function integer($integer, $field_name = 'integer', $min = null, $max = null, $required = true) {
        if (empty($integer) && $integer !== 0 && $integer !== '0') {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        if (!is_numeric($integer) || intval($integer) != $integer) {
            $this->errors[$field_name] = sprintf(__('%s must be a valid integer.', 'malisafi-mls'), ucfirst($field_name));
            return false;
        }
        
        $integer = intval($integer);
        
        if ($min !== null && $integer < $min) {
            $this->errors[$field_name] = sprintf(
                __('%s must be at least %d.', 'malisafi-mls'),
                ucfirst($field_name),
                $min
            );
            return false;
        }
        
        if ($max !== null && $integer > $max) {
            $this->errors[$field_name] = sprintf(
                __('%s must not exceed %d.', 'malisafi-mls'),
                ucfirst($field_name),
                $max
            );
            return false;
        }
        
        $this->validated[$field_name] = $integer;
        return true;
    }
    
    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @param string $field_name Field name for error messages
     * @param int $min_length Minimum password length
     * @return bool
     */
    public function password($password, $field_name = 'password', $min_length = 8) {
        if (empty($password)) {
            $this->errors[$field_name] = __('Password is required.', 'malisafi-mls');
            return false;
        }
        
        if (strlen($password) < $min_length) {
            $this->errors[$field_name] = sprintf(
                __('Password must be at least %d characters long.', 'malisafi-mls'),
                $min_length
            );
            return false;
        }
        
        // Check for at least one letter and one number
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->errors[$field_name] = __('Password must contain at least one letter and one number.', 'malisafi-mls');
            return false;
        }
        
        $this->validated[$field_name] = $password; // Don't sanitize passwords
        return true;
    }
    
    /**
     * Validate that value is in allowed list
     *
     * @param mixed $value Value to validate
     * @param array $allowed Allowed values
     * @param string $field_name Field name for error messages
     * @param bool $required Whether field is required
     * @return bool
     */
    public function in_array($value, $allowed, $field_name = 'field', $required = true) {
        if (empty($value)) {
            if ($required) {
                $this->errors[$field_name] = sprintf(__('%s is required.', 'malisafi-mls'), ucfirst($field_name));
                return false;
            }
            return true;
        }
        
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field_name] = sprintf(
                __('%s has an invalid value.', 'malisafi-mls'),
                ucfirst($field_name)
            );
            return false;
        }
        
        $this->validated[$field_name] = $value;
        return true;
    }
    
    /**
     * Validate checkbox (boolean)
     *
     * @param mixed $value Value to validate
     * @param string $field_name Field name
     * @return bool
     */
    public function checkbox($value, $field_name = 'checkbox') {
        $this->validated[$field_name] = !empty($value) ? true : false;
        return true;
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get validated data
     *
     * @return array
     */
    public function validated() {
        return $this->validated;
    }
    
    /**
     * Get first error message
     *
     * @return string|null
     */
    public function first_error() {
        if (empty($this->errors)) {
            return null;
        }
        return reset($this->errors);
    }
    
    /**
     * Get all errors as HTML list
     *
     * @return string
     */
    public function errors_html() {
        if (empty($this->errors)) {
            return '';
        }
        
        $html = '<ul class="malisafi-validation-errors">';
        foreach ($this->errors as $error) {
            $html .= '<li>' . esc_html($error) . '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
}

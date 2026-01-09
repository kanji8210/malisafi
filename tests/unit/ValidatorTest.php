<?php
/**
 * Test case for Validator class
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Unit;

use MalisafiMLS\Validator;
use WP_UnitTestCase;

/**
 * Validator test case
 */
class ValidatorTest extends WP_UnitTestCase {

    /**
     * Test email validation - valid email
     */
    public function test_email_validation_valid() {
        $validator = new Validator();
        $result = $validator->email('test@example.com', 'email', true);
        
        $this->assertTrue($result);
        $this->assertTrue($validator->passes());
        $this->assertEquals('test@example.com', $validator->validated()['email']);
    }

    /**
     * Test email validation - invalid email
     */
    public function test_email_validation_invalid() {
        $validator = new Validator();
        $result = $validator->email('not-an-email', 'email', true);
        
        $this->assertFalse($result);
        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->get_errors());
    }

    /**
     * Test email validation - empty when required
     */
    public function test_email_validation_required_empty() {
        $validator = new Validator();
        $result = $validator->email('', 'email', true);
        
        $this->assertFalse($result);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('required', $validator->first_error());
    }

    /**
     * Test email validation - empty when optional
     */
    public function test_email_validation_optional_empty() {
        $validator = new Validator();
        $result = $validator->email('', 'email', false);
        
        $this->assertTrue($result);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test phone validation - valid Kenya phone
     */
    public function test_phone_validation_valid_formats() {
        $validator = new Validator();
        
        $valid_phones = [
            '+254712345678',
            '0712345678',
            '0112345678',
            '254712345678'
        ];
        
        foreach ($valid_phones as $phone) {
            $validator = new Validator(); // Reset
            $result = $validator->phone($phone, 'phone', true);
            $this->assertTrue($result, "Phone $phone should be valid");
        }
    }

    /**
     * Test phone validation - invalid formats
     */
    public function test_phone_validation_invalid_formats() {
        $validator = new Validator();
        
        $invalid_phones = [
            '12345',
            '123456789012345',
            'abc123456789',
            '+1234567890'
        ];
        
        foreach ($invalid_phones as $phone) {
            $validator = new Validator(); // Reset
            $result = $validator->phone($phone, 'phone', true);
            $this->assertFalse($result, "Phone $phone should be invalid");
        }
    }

    /**
     * Test text validation - valid text
     */
    public function test_text_validation_valid() {
        $validator = new Validator();
        $result = $validator->text('John Doe', 'name', 2, 50, true);
        
        $this->assertTrue($result);
        $this->assertEquals('John Doe', $validator->validated()['name']);
    }

    /**
     * Test text validation - too short
     */
    public function test_text_validation_too_short() {
        $validator = new Validator();
        $result = $validator->text('J', 'name', 2, 50, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('at least 2 characters', $validator->first_error());
    }

    /**
     * Test text validation - too long
     */
    public function test_text_validation_too_long() {
        $validator = new Validator();
        $long_text = str_repeat('a', 300);
        $result = $validator->text($long_text, 'name', 2, 255, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('not exceed 255 characters', $validator->first_error());
    }

    /**
     * Test number validation - valid number
     */
    public function test_number_validation_valid() {
        $validator = new Validator();
        $result = $validator->number(42.5, 'price', 0, 1000, true);
        
        $this->assertTrue($result);
        $this->assertEquals(42.5, $validator->validated()['price']);
    }

    /**
     * Test number validation - below minimum
     */
    public function test_number_validation_below_min() {
        $validator = new Validator();
        $result = $validator->number(-5, 'price', 0, 1000, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('at least 0', $validator->first_error());
    }

    /**
     * Test number validation - above maximum
     */
    public function test_number_validation_above_max() {
        $validator = new Validator();
        $result = $validator->number(1500, 'price', 0, 1000, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('not exceed 1000', $validator->first_error());
    }

    /**
     * Test number validation - non-numeric
     */
    public function test_number_validation_non_numeric() {
        $validator = new Validator();
        $result = $validator->number('not-a-number', 'price', null, null, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('valid number', $validator->first_error());
    }

    /**
     * Test price validation - positive price
     */
    public function test_price_validation_positive() {
        $validator = new Validator();
        $result = $validator->price(99.99, 'price', true);
        
        $this->assertTrue($result);
        $this->assertEquals(99.99, $validator->validated()['price']);
    }

    /**
     * Test price validation - negative price
     */
    public function test_price_validation_negative() {
        $validator = new Validator();
        $result = $validator->price(-10, 'price', true);
        
        $this->assertFalse($result);
    }

    /**
     * Test integer validation - valid integer
     */
    public function test_integer_validation_valid() {
        $validator = new Validator();
        $result = $validator->integer(42, 'age', 18, 100, true);
        
        $this->assertTrue($result);
        $this->assertEquals(42, $validator->validated()['age']);
    }

    /**
     * Test integer validation - float rejected
     */
    public function test_integer_validation_float_rejected() {
        $validator = new Validator();
        $result = $validator->integer(42.5, 'age', 18, 100, true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('valid integer', $validator->first_error());
    }

    /**
     * Test password validation - strong password
     */
    public function test_password_validation_strong() {
        $validator = new Validator();
        $result = $validator->password('StrongPass123', 'password', 8);
        
        $this->assertTrue($result);
        $this->assertEquals('StrongPass123', $validator->validated()['password']);
    }

    /**
     * Test password validation - too short
     */
    public function test_password_validation_too_short() {
        $validator = new Validator();
        $result = $validator->password('Pass1', 'password', 8);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('at least 8 characters', $validator->first_error());
    }

    /**
     * Test password validation - no numbers
     */
    public function test_password_validation_no_numbers() {
        $validator = new Validator();
        $result = $validator->password('StrongPassword', 'password', 8);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('letter and one number', $validator->first_error());
    }

    /**
     * Test password validation - no letters
     */
    public function test_password_validation_no_letters() {
        $validator = new Validator();
        $result = $validator->password('12345678', 'password', 8);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('letter and one number', $validator->first_error());
    }

    /**
     * Test URL validation - valid URL
     */
    public function test_url_validation_valid() {
        $validator = new Validator();
        $result = $validator->url('https://example.com', 'website', false);
        
        $this->assertTrue($result);
        $this->assertEquals('https://example.com', $validator->validated()['website']);
    }

    /**
     * Test URL validation - invalid URL
     */
    public function test_url_validation_invalid() {
        $validator = new Validator();
        $result = $validator->url('not-a-url', 'website', false);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('valid URL', $validator->first_error());
    }

    /**
     * Test in_array validation - valid value
     */
    public function test_in_array_validation_valid() {
        $validator = new Validator();
        $allowed = ['agent', 'owner', 'developer'];
        $result = $validator->in_array('agent', $allowed, 'role', true);
        
        $this->assertTrue($result);
        $this->assertEquals('agent', $validator->validated()['role']);
    }

    /**
     * Test in_array validation - invalid value
     */
    public function test_in_array_validation_invalid() {
        $validator = new Validator();
        $allowed = ['agent', 'owner', 'developer'];
        $result = $validator->in_array('hacker', $allowed, 'role', true);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('invalid value', $validator->first_error());
    }

    /**
     * Test checkbox validation
     */
    public function test_checkbox_validation() {
        $validator = new Validator();
        
        // Checked
        $validator->checkbox('1', 'terms');
        $this->assertTrue($validator->validated()['terms']);
        
        // Unchecked
        $validator = new Validator();
        $validator->checkbox('', 'terms');
        $this->assertFalse($validator->validated()['terms']);
    }

    /**
     * Test multiple validations
     */
    public function test_multiple_validations() {
        $validator = new Validator();
        
        $validator->text('John', 'first_name', 2, 50, true);
        $validator->text('Doe', 'last_name', 2, 50, true);
        $validator->email('john@example.com', 'email', true);
        $validator->integer(30, 'age', 18, 100, true);
        
        $this->assertTrue($validator->passes());
        
        $validated = $validator->validated();
        $this->assertEquals('John', $validated['first_name']);
        $this->assertEquals('Doe', $validated['last_name']);
        $this->assertEquals('john@example.com', $validated['email']);
        $this->assertEquals(30, $validated['age']);
    }

    /**
     * Test multiple validations with failure
     */
    public function test_multiple_validations_with_failure() {
        $validator = new Validator();
        
        $validator->text('John', 'first_name', 2, 50, true);
        $validator->email('invalid-email', 'email', true);
        $validator->integer('not-a-number', 'age', 18, 100, true);
        
        $this->assertTrue($validator->fails());
        $this->assertCount(2, $validator->get_errors());
    }

    /**
     * Test XSS protection in validation
     */
    public function test_xss_protection() {
        $validator = new Validator();
        $xss_attempt = '<script>alert("XSS")</script>';
        
        $validator->text($xss_attempt, 'name', 0, 100, true);
        
        $validated = $validator->validated();
        // sanitize_text_field should strip the script tags
        $this->assertStringNotContainsString('<script>', $validated['name']);
    }
}

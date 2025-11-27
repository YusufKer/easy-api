<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utils\StringHelper;

class StringHelperTest extends TestCase {
    
    /**
     * Test that sanitizeProteinName properly cleans and formats protein names
     */
    public function test_sanitize_protein_name_converts_to_title_case(): void {
        // Arrange: Set up test data
        $input = "chicken breast";
        $expected = "Chicken Breast";
        
        // Act: Call the method we're testing
        $result = StringHelper::sanitizeProteinName($input);
        
        // Assert: Verify the result is what we expect
        $this->assertEquals($expected, $result);
    }
    
    public function test_sanitize_protein_name_trims_whitespace(): void {
        $input = "  beef  ";
        $expected = "Beef";
        
        $result = StringHelper::sanitizeProteinName($input);
        
        $this->assertEquals($expected, $result);
    }
    
    public function test_is_valid_protein_name_accepts_valid_names(): void {
        $validNames = [
            "Chicken",
            "Chicken Breast",
            "Grass-Fed Beef"
        ];
        
        foreach ($validNames as $name) {
            $result = StringHelper::isValidProteinName($name);
            $this->assertTrue($result, "Expected '$name' to be valid");
        }
    }
    
    public function test_is_valid_protein_name_rejects_invalid_names(): void {
        $invalidNames = [
            "",           // empty string
            "   ",        // only whitespace
            "Chicken123", // contains numbers
            "Beef@Home"   // contains special characters
        ];
        
        foreach ($invalidNames as $name) {
            $result = StringHelper::isValidProteinName($name);
            $this->assertFalse($result, "Expected '$name' to be invalid");
        }
    }
}

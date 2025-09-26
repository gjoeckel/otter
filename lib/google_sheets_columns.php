<?php

/**
 * GoogleSheetsColumns - Centralized column index constants
 * 
 * This class provides a single source of truth for all Google Sheets column mappings,
 * eliminating hardcoded column indices scattered across 15+ files.
 * 
 * @package Otter\Lib
 */
class GoogleSheetsColumns {
    
    /**
     * Registrants sheet column mappings
     * Maps logical names to zero-based column indices
     */
    const REGISTRANTS = [
        'DAYS_TO_CLOSE' => 0,    // Column A
        'INVITED' => 1,          // Column B  
        'ENROLLED' => 2,         // Column C
        'COHORT' => 3,           // Column D
        'YEAR' => 4,             // Column E
        'FIRST' => 5,            // Column F
        'LAST' => 6,             // Column G
        'EMAIL' => 7,            // Column H
        'ROLE' => 8,             // Column I
        'ORGANIZATION' => 9,     // Column J
        'CERTIFICATE' => 10,     // Column K
        'ISSUED' => 11,          // Column L
        'CLOSING_DATE' => 12,    // Column M
        'COMPLETED' => 13,       // Column N
        'ID' => 14,              // Column O
        'SUBMITTED' => 15,       // Column P
        'STATUS' => 16           // Column Q
    ];
    
    /**
     * Submissions sheet column mappings
     * Currently identical to registrants, but separated for future flexibility
     */
    const SUBMISSIONS = [
        'DAYS_TO_CLOSE' => 0,    // Column A
        'INVITED' => 1,          // Column B  
        'ENROLLED' => 2,         // Column C
        'COHORT' => 3,           // Column D
        'YEAR' => 4,             // Column E
        'FIRST' => 5,            // Column F
        'LAST' => 6,             // Column G
        'EMAIL' => 7,            // Column H
        'ROLE' => 8,             // Column I
        'ORGANIZATION' => 9,     // Column J
        'CERTIFICATE' => 10,     // Column K
        'ISSUED' => 11,          // Column L
        'CLOSING_DATE' => 12,    // Column M
        'COMPLETED' => 13,       // Column N
        'ID' => 14,              // Column O
        'SUBMITTED' => 15,       // Column P
        'STATUS' => 16           // Column Q
    ];
    
    /**
     * Get column index for registrants sheet
     * 
     * @param string $columnName The logical column name
     * @return int|null The zero-based column index, or null if not found
     */
    public static function getRegistrantsIndex($columnName) {
        return self::REGISTRANTS[$columnName] ?? null;
    }
    
    /**
     * Get column index for submissions sheet
     * 
     * @param string $columnName The logical column name
     * @return int|null The zero-based column index, or null if not found
     */
    public static function getSubmissionsIndex($columnName) {
        return self::SUBMISSIONS[$columnName] ?? null;
    }
    
    /**
     * Get all registrants column mappings
     * 
     * @return array All registrants column mappings
     */
    public static function getRegistrantsColumns() {
        return self::REGISTRANTS;
    }
    
    /**
     * Get all submissions column mappings
     * 
     * @return array All submissions column mappings
     */
    public static function getSubmissionsColumns() {
        return self::SUBMISSIONS;
    }
}

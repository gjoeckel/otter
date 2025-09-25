<?php

class InputValidator {
    public static function validateEnterpriseCode($code) {
        if (!is_string($code) || !preg_match('/^[a-z]{3,4}$/', $code)) {
            throw new InvalidArgumentException('Enterprise code must be 3-4 lowercase letters');
        }
        return $code;
    }
    
    public static function validatePassword($password) {
        if (!is_string($password) || !preg_match('/^\d{4}$/', $password)) {
            throw new InvalidArgumentException('Password must be exactly 4 digits');
        }
        return $password;
    }
    
    public static function validateDateFormat($date) {
        if (!preg_match('/^\d{2}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Date must be in MM-DD-YY format');
        }
        
        $dateObj = DateTime::createFromFormat('m-d-y', $date);
        if (!$dateObj || $dateObj->format('m-d-y') !== $date) {
            throw new InvalidArgumentException('Invalid date provided');
        }
        
        return $date;
    }
    
    public static function validateDateRange($startDate, $endDate) {
        $start = self::validateDateFormat($startDate);
        $end = self::validateDateFormat($endDate);
        
        $startObj = DateTime::createFromFormat('m-d-y', $start);
        $endObj = DateTime::createFromFormat('m-d-y', $end);
        
        if ($startObj > $endObj) {
            throw new InvalidArgumentException('Start date must be before end date');
        }
        
        return [$start, $end];
    }
    
    public static function validateMode($mode) {
        $validModes = ['date', 'cohort'];
        if (!in_array($mode, $validModes)) {
            throw new InvalidArgumentException('Mode must be: ' . implode(', ', $validModes));
        }
        return $mode;
    }
}

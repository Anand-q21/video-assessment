<?php

namespace App\Service;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    /**
     * Validate email format
     */
    public function validateEmail(string $email): array
    {
        $errors = [];
        
        if (empty(trim($email))) {
            $errors[] = 'Email is required';
            return $errors;
        }

        $constraint = new Assert\Email(['message' => 'Invalid email format']);
        $violations = $this->validator->validate($email, $constraint);
        
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }
        
        return $errors;
    }

    /**
     * Validate username format (alphanumeric and underscore only, 3-50 characters)
     */
    public function validateUsername(string $username): array
    {
        $errors = [];
        
        if (empty(trim($username))) {
            $errors[] = 'Username is required';
            return $errors;
        }

        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }
        
        if (strlen($username) > 50) {
            $errors[] = 'Username must not exceed 50 characters';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }
        
        return $errors;
    }

    /**
     * Validate password strength (minimum 8 characters)
     */
    public function validatePassword(string $password): array
    {
        $errors = [];
        
        if (empty($password)) {
            $errors[] = 'Password is required';
            return $errors;
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        return $errors;
    }

    /**
     * Validate string length
     */
    public function validateStringLength(string $value, string $fieldName, int $min = null, int $max = null): array
    {
        $errors = [];
        $length = strlen($value);
        
        if ($min !== null && $length < $min) {
            $errors[] = "{$fieldName} must be at least {$min} characters long";
        }
        
        if ($max !== null && $length > $max) {
            $errors[] = "{$fieldName} must not exceed {$max} characters";
        }
        
        return $errors;
    }

    /**
     * Validate file type
     */
    public function validateFileType(string $mimeType, array $allowedTypes, string $fieldName = 'File'): array
    {
        $errors = [];
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "{$fieldName} type not allowed. Allowed types: " . implode(', ', $allowedTypes);
        }
        
        return $errors;
    }

    /**
     * Validate file size (size in bytes)
     */
    public function validateFileSize(int $size, int $maxSize, string $fieldName = 'File'): array
    {
        $errors = [];
        
        if ($size > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            $errors[] = "{$fieldName} size exceeds maximum allowed size of {$maxSizeMB}MB";
        }
        
        return $errors;
    }

    /**
     * Sanitize string input (prevent XSS)
     */
    public function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate required fields in request data
     */
    public function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        
        return $errors;
    }

    /**
     * Validate request body is not empty
     */
    public function validateRequestBody($data): array
    {
        $errors = [];
        
        if ($data === null || (is_array($data) && empty($data))) {
            $errors[] = 'Request body cannot be empty';
        }
        
        return $errors;
    }
}

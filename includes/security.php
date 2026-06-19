<?php
function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken(string $token): bool {
    return !empty($_SESSION[CSRF_TOKEN_NAME]) &&
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizeInput(string $value): string {
    return trim(strip_tags($value));
}

function sanitizeEmail(string $email): string|false {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

function sanitizeInt(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int|false {
    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max]
    ]);
}

function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

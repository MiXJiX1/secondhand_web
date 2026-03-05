<?php
/**
 * includes/helpers.php
 * Centralized helper functions for the Secondhand Marketplace
 */

/**
 * Global Helper for safe HTML output (XSS Protection)
 */
if (!function_exists('h')) {
    function h($s) {
        if ($s === null) return '';
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Format price to Thai Baht
 */
if (!function_exists('formatPrice')) {
    function formatPrice($amount) {
        return '฿' . number_format((float)$amount, 0);
    }
}

/**
 * Format currency with decimals
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format((float)$amount, 2) . ' บาท';
    }
}

/**
 * Check if user is logged in
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

/**
 * Check if user is admin
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

/**
 * Redirect to a specific URL
 */
if (!function_exists('redirect')) {
    function redirect($path) {
        header("Location: $path");
        exit;
    }
}

/**
 * Get first image from a comma/pipe/json field
 */
if (!function_exists('firstImageFromField')) {
    function firstImageFromField(?string $s): ?string {
        if (!$s) return null;
        $s = trim($s);
        if ($s !== '' && $s[0] === '[') {
            $arr = json_decode($s, true);
            if (is_array($arr) && !empty($arr)) return basename((string)$arr[0]);
        }
        $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts && isset($parts[0])) return basename(trim($parts[0]));
        return basename($s);
    }
}
/**
 * Get all images from a field
 */
if (!function_exists('allImagesFromField')) {
    function allImagesFromField(?string $s): array {
        if (!$s) return [];
        $s = trim($s);
        if ($s !== '' && $s[0] === '[') {
            $arr = json_decode($s, true);
            if (is_array($arr)) {
                return array_values(array_filter(array_map(fn($x)=>basename((string)$x), $arr)));
            }
        }
        $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts) return array_values(array_filter(array_map(fn($x)=>basename(trim($x)), $parts)));
        return [basename($s)];
    }
}

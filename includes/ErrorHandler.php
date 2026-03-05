<?php

class ErrorHandler {
    private static $logFile = __DIR__ . '/../logs/app.log';

    public static function initialize() {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e) {
        self::log($e->getMessage() . "\n" . $e->getTraceAsString(), 'EXCEPTION');
        self::renderError($e->getMessage(), $e->getCode() ?: 500);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) return false;
        $message = "$errstr in $errfile on line $errline";
        self::log($message, 'ERROR');
        if ($errno === E_USER_ERROR) {
            self::renderError($errstr, 500);
        }
        return true;
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "{$error['message']} in {$error['file']} on line {$error['line']}";
            self::log($message, 'FATAL');
            self::renderError("A fatal error occurred.", 500);
        }
    }

    public static function log($message, $level = 'INFO') {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }

    private static function renderError($message, $code) {
        if (!headers_sent()) {
            http_response_code($code === 0 ? 500 : $code);
        }

        $isApi = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                 (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                 (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $message]);
        } else {
            // Simple user-friendly error page
            ?>
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <title>เกิดข้อผิดพลาด</title>
                <style>
                    body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .card { background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-width: 500px; width: 100%; border-radius: 1rem; border: 1px solid #e2e8f0; }
                    h1 { color: #dc2626; font-size: 1.5rem; margin-top: 0; }
                    p { line-height: 1.6; }
                    .btn { display: inline-block; background: #0f172a; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.5rem; margin-top: 1rem; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>ขออภัย เกิดข้อผิดพลาดขึ้น</h1>
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <a href="/" class="btn">กลับหน้าแรก</a>
                </div>
            </body>
            </html>
            <?php
        }
        exit;
    }
}

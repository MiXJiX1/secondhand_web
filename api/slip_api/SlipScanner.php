<?php
namespace SlipAPI;

require_once __DIR__ . '/../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;

class SlipScanner {
    // ... (rest of class remains, I need to be careful with the replacement chunk to not break the class structure)

    private $allowedBanks = []; // e.g., ['004', '014']

    public function __construct(array $allowedBanks = []) {
        $this->allowedBanks = $allowedBanks;
    }

    /**
     * Parses EMVCo (TLVs) Data
     */
    private function parseEMVCo($data) {
        $pos = 0;
        $result = [];
        while ($pos < strlen($data)) {
            if ($pos + 4 > strlen($data)) break; // Prevent out of bounds
            $tag = substr($data, $pos, 2);
            $len = (int)substr($data, $pos + 2, 2);
            if ($pos + 4 + $len > strlen($data)) break;
            $val = substr($data, $pos + 4, $len);
            $result[$tag] = $val;
            $pos += 4 + $len;
        }
        return $result;
    }

    /**
     * Decode the raw QR string into an array of meaningful slip data.
     */
    public function decodeRawQR($qrRaw) {
        $tags = $this->parseEMVCo($qrRaw);
        
        $amount = 0;
        $ref = '';

        // Format 1: Standard Thai QR Payment (contains 000201)
        if (isset($tags['54'])) {
            $amount = (float)$tags['54'];
        }
        if (isset($tags['62'])) {
            $subTags = $this->parseEMVCo($tags['62']);
            $ref = $subTags['07'] ?? $subTags['01'] ?? '';
        }

        // Format 2: Mini QR Bank Slip (e.g. KBank starts with 0041...)
        // The data is encapsulated inside tag '00'
        if (empty($ref) && isset($tags['00'])) {
            $subTags = $this->parseEMVCo($tags['00']);
            if (isset($subTags['02'])) {
                $ref = $subTags['02']; // Transaction Reference
            }
        }

        // If we still didn't find a reference, treat it as invalid
        if (empty($ref)) {
            return null;
        }

        // Note: For Mini QR codes (amount=0), you MUST implement a real 3rd party API 
        // to fetch the amount using the $ref, otherwise the topup amount validation will fail.
        return [
            'amount' => $amount,
            'transRef' => $ref,
            'raw' => $qrRaw
        ];
    }

    /**
     * Pre-process image to improve QR reading (grayscale + contrast)
     */
    private function preProcessImage($filePath) {
        $img = null;
        $info = getimagesize($filePath);
        if (!$info) return false;

        switch ($info[2]) {
            case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($filePath); break;
            case IMAGETYPE_PNG: $img = imagecreatefrompng($filePath); break;
            case IMAGETYPE_WEBP: $img = imagecreatefromwebp($filePath); break;
        }

        if (!$img) return false;

        // Convert to grayscale
        imagefilter($img, IMG_FILTER_GRAYSCALE);
        // Increase contrast (-100 to 100, negative increases contrast in GD)
        imagefilter($img, IMG_FILTER_CONTRAST, -30);

        $tempFile = sys_get_temp_dir() . '/processed_qr_' . uniqid() . '.jpg';
        imagejpeg($img, $tempFile, 100);
        imagedestroy($img);

        return $tempFile;
    }

    /**
     * Scans an image file and returns the verification result.
     */
    public function scanFile($filePath) {
        if (!file_exists($filePath)) {
            return ['ok' => false, 'message' => 'file_not_found'];
        }

        try {
            $options = new QROptions([
                'readerUseImagickIfAvailable' => false,
            ]);
            $qrcode = new QRCode($options);

            // First attempt: original image
            $text = null;
            try {
                $result = $qrcode->readFromFile($filePath);
                $text = (string)$result;
            } catch (\Throwable $e) {
                // Ignore and try pre-processed
            }

            // Second attempt: pre-processed image if original failed
            $tempFile = null;
            if (!$text) {
                $tempFile = $this->preProcessImage($filePath);
                if ($tempFile) {
                    try {
                        $result = $qrcode->readFromFile($tempFile);
                        $text = (string)$result;
                    } catch (\Throwable $e) {}
                }
            }

            // Cleanup temp file if created
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (!$text) {
                return ['ok' => false, 'message' => 'could_not_read_qr'];
            }

            $slipInfo = $this->decodeRawQR($text);

            if (!$slipInfo) {
                return ['ok' => false, 'message' => 'invalid_slip_format. Raw data: ' . $text];
            }


            return [
                'ok' => true,
                'data' => [
                    'amount' => ['amount' => $slipInfo['amount']],
                    'transRef' => $slipInfo['transRef'],
                    'payload' => $slipInfo['raw']
                ]
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'scanner_error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }
}

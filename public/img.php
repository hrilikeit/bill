<?php
// Robust image endpoint for Library images.
// Keeps warnings/notices out of image responses and safely serves resized thumbnails.

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../private/config.php';
require '../private/staysail/Staysail.php';
require '../private/interfaces/interface.AccountType.php';
StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Never render PHP warnings/notices into an image response.
ini_set('display_errors', '0');

$library_id = StaysailIO::getInt('id');
$web = StaysailIO::getInt('w');

if (!$library_id) {
    showNullImage();
}

$Library = new Library($library_id);
if (!$Library->id) {
    showNullImage();
}

$member_id = StaysailIO::session('Member.id');
$Member = ($member_id) ? new Member($member_id) : null;

$hasAccess = false;
try {
    $hasAccess = (bool)$Library->hasAccess($Member);
} catch (Throwable $e) {
    $hasAccess = false;
}

if (!$hasAccess) {
    showNoAccessImage();
}

serveLibraryImage($Library, (bool)$web);
exit;

function clearOutputBuffer(): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
}

function sendFile(string $path, string $mime): void
{
    clearOutputBuffer();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function showNullImage(): void
{
    $path = DATAROOT . '/public/site_img/null.png';
    if (is_file($path)) {
        sendFile($path, 'image/png');
    }

    clearOutputBuffer();
    header('Status: 404 Not Found');
    exit;
}

function showNoAccessImage(): void
{
    $path = DATAROOT . '/public/site_img/no_access.jpg';
    if (is_file($path)) {
        sendFile($path, 'image/jpeg');
    }

    showNullImage();
}

function detectMimeType(string $path, string $fallback = 'image/jpeg'): string
{
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($path);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    return $fallback;
}

function serveLibraryImage($Library, bool $web = false): void
{
    $filename = isset($Library->image) ? (string)$Library->image : '';
    if ($filename === '') {
        showNullImage();
    }

    $path = DATAROOT . '/private/library/' . $filename;
    if (!is_file($path)) {
        showNullImage();
    }

    if ($web && renderResizedImage($Library, $path, 360)) {
        exit;
    }

    $mime = isset($Library->mime_type) && is_string($Library->mime_type) && $Library->mime_type !== ''
        ? $Library->mime_type
        : detectMimeType($path);

    sendFile($path, $mime);
}

function renderResizedImage($Library, string $path, int $newWidth): bool
{
    if (!function_exists('getimagesize')) {
        return false;
    }

    $size = @getimagesize($path);
    if (!is_array($size) || empty($size[0]) || empty($size[1])) {
        return false;
    }

    // No need to resize small images; just send the original file.
    if ((int)$size[0] <= $newWidth) {
        return false;
    }

    $mime = isset($size['mime']) ? (string)$size['mime'] : '';
    $src = createImageResource($path, $mime);
    if (!$src) {
        return false;
    }

    $newHeight = max(1, (int)round(($newWidth / (int)$size[0]) * (int)$size[1]));
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    if (!$dst) {
        imagedestroy($src);
        return false;
    }

    preserveTransparency($dst, $mime);

    if (!@imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, (int)$size[0], (int)$size[1])) {
        imagedestroy($src);
        imagedestroy($dst);
        return false;
    }

    maybeApplySaleWatermark($dst, $Library);

    clearOutputBuffer();
    outputImageResource($dst, $mime);

    imagedestroy($src);
    imagedestroy($dst);
    return true;
}

function createImageResource(string $path, string $mime)
{
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false;
        case 'image/png':
            return function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false;
        case 'image/gif':
            return function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false;
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
        default:
            return false;
    }
}

function preserveTransparency($image, string $mime): void
{
    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}

function maybeApplySaleWatermark($image, $Library): void
{
    $placement = isset($Library->placement) ? (string)$Library->placement : '';
    if ($placement !== 'sale') {
        return;
    }

    $ownerId = 0;
    if (isset($Library->Member) && is_object($Library->Member) && isset($Library->Member->id)) {
        $ownerId = (int)$Library->Member->id;
    }

    $viewerId = (int)StaysailIO::session('Member.id');
    if ($ownerId !== 0 && $ownerId === $viewerId) {
        return;
    }

    $stamp = imagecreatetruecolor(180, 70);
    if (!$stamp) {
        return;
    }

    $white = imagecolorallocate($stamp, 255, 255, 255);
    $black = imagecolorallocate($stamp, 0, 0, 0);
    imagefilledrectangle($stamp, 0, 0, 180, 70, $white);
    imagestring($stamp, 5, 10, 30, 'YourFansLive.com', $black);

    $sx = imagesx($stamp);
    $sy = imagesy($stamp);
    $dstW = imagesx($image);
    $dstH = imagesy($image);
    imagecopymerge($image, $stamp, $dstW - $sx - 10, $dstH - $sy - 10, 0, 0, $sx, $sy, 50);
    imagedestroy($stamp);
}

function outputImageResource($image, string $mime): void
{
    switch ($mime) {
        case 'image/png':
            header('Content-Type: image/png');
            imagepng($image);
            break;
        case 'image/gif':
            header('Content-Type: image/gif');
            imagegif($image);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                header('Content-Type: image/webp');
                imagewebp($image, null, 85);
                break;
            }
            // fall through to jpeg if webp output is unavailable
        default:
            header('Content-Type: image/jpeg');
            imagejpeg($image, null, 90);
            break;
    }
}

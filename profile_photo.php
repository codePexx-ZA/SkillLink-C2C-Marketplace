<?php
const PROFILE_PHOTO_MAX_BYTES = 2097152;

function ensureProfilePhotoDirectory(): string
{
    $dir = __DIR__ . "/uploads/profile-photos";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function processProfilePhotoUpload(int $userId, ?string $existingRelativePath): array
{
    if (!isset($_FILES["profile_photo"]) || (int) $_FILES["profile_photo"]["error"] === UPLOAD_ERR_NO_FILE) {
        return ["path" => null, "error" => null];
    }

    $file = $_FILES["profile_photo"];
    if ((int) $file["error"] !== UPLOAD_ERR_OK) {
        return ["path" => null, "error" => "invalid"];
    }

    if ((int) $file["size"] > PROFILE_PHOTO_MAX_BYTES) {
        return ["path" => null, "error" => "too_large"];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);
    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp",
    ];

    if (!isset($allowed[$mime])) {
        return ["path" => null, "error" => "invalid"];
    }

    if (@getimagesize($file["tmp_name"]) === false) {
        return ["path" => null, "error" => "invalid"];
    }

    $uploadDir = ensureProfilePhotoDirectory();
    $filename = "user_" . $userId . "_" . bin2hex(random_bytes(8)) . "." . $allowed[$mime];
    $absolutePath = $uploadDir . "/" . $filename;
    $relativePath = "uploads/profile-photos/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $absolutePath)) {
        return ["path" => null, "error" => "save_failed"];
    }

    deleteProfilePhotoFile($existingRelativePath);

    return ["path" => $relativePath, "error" => null];
}

function deleteProfilePhotoFile(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === "") {
        return;
    }

    $relativePath = str_replace("\\", "/", $relativePath);
    if (strpos($relativePath, "..") !== false || !str_starts_with($relativePath, "uploads/profile-photos/")) {
        return;
    }

    $absolute = __DIR__ . "/" . $relativePath;
    if (is_file($absolute)) {
        unlink($absolute);
    }
}

function isSafeProfilePhotoPath(?string $relativePath): bool
{
    if ($relativePath === null || $relativePath === "") {
        return false;
    }

    $relativePath = str_replace("\\", "/", $relativePath);
    return strpos($relativePath, "..") === false
        && str_starts_with($relativePath, "uploads/profile-photos/")
        && is_file(__DIR__ . "/" . $relativePath);
}

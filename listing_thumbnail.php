<?php
const LISTING_THUMBNAIL_MAX_UPLOAD_BYTES = 8388608;
const LISTING_THUMBNAIL_WIDTH = 800;
const LISTING_THUMBNAIL_HEIGHT = 600;
const LISTING_THUMBNAIL_JPEG_QUALITY = 85;

function ensureListingThumbnailDirectory(): string
{
    $dir = __DIR__ . "/uploads/listing-photos";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function processListingThumbnailUpload(
    int $listingId,
    int $userId,
    ?string $existingRelativePath,
    bool $requireFile = false
): array {
    if (!isset($_FILES["listing_thumbnail"]) || (int) $_FILES["listing_thumbnail"]["error"] === UPLOAD_ERR_NO_FILE) {
        if ($requireFile) {
            return ["path" => null, "error" => "required"];
        }
        return ["path" => null, "error" => null];
    }

    $file = $_FILES["listing_thumbnail"];
    if ((int) $file["error"] !== UPLOAD_ERR_OK) {
        return ["path" => null, "error" => "invalid"];
    }

    if ((int) $file["size"] > LISTING_THUMBNAIL_MAX_UPLOAD_BYTES) {
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

    $imageInfo = @getimagesize($file["tmp_name"]);
    if ($imageInfo === false) {
        return ["path" => null, "error" => "invalid"];
    }

    $uploadDir = ensureListingThumbnailDirectory();
    $prefix = $listingId > 0 ? "listing_" . $listingId : "user_" . $userId;
    $filename = $prefix . "_" . bin2hex(random_bytes(8)) . "." . $allowed[$mime];
    $absolutePath = $uploadDir . "/" . $filename;
    $relativePath = "uploads/listing-photos/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $absolutePath)) {
        return ["path" => null, "error" => "save_failed"];
    }

    if (!optimizeListingThumbnailFile($absolutePath, $mime, (int) $imageInfo[0], (int) $imageInfo[1])) {
        @unlink($absolutePath);
        return ["path" => null, "error" => "save_failed"];
    }

    deleteListingThumbnailFile($existingRelativePath);

    return ["path" => $relativePath, "error" => null];
}

function loadListingImageResource(string $absolutePath, string $mime)
{
    return match ($mime) {
        "image/jpeg" => @imagecreatefromjpeg($absolutePath),
        "image/png" => @imagecreatefrompng($absolutePath),
        "image/gif" => @imagecreatefromgif($absolutePath),
        "image/webp" => function_exists("imagecreatefromwebp") ? @imagecreatefromwebp($absolutePath) : false,
        default => false,
    };
}

function saveListingImageResource($image, string $absolutePath, string $mime): bool
{
    return match ($mime) {
        "image/jpeg" => imagejpeg($image, $absolutePath, LISTING_THUMBNAIL_JPEG_QUALITY),
        "image/png" => imagepng($image, $absolutePath, 8),
        "image/gif" => imagegif($image, $absolutePath),
        "image/webp" => function_exists("imagewebp")
            ? imagewebp($image, $absolutePath, LISTING_THUMBNAIL_JPEG_QUALITY)
            : false,
        default => false,
    };
}

function optimizeListingThumbnailFile(string $absolutePath, string $mime, int $width, int $height): bool
{
    if (!function_exists("imagecreatetruecolor")) {
        return is_file($absolutePath);
    }

    $source = loadListingImageResource($absolutePath, $mime);
    if ($source === false) {
        return is_file($absolutePath);
    }

    $targetRatio = LISTING_THUMBNAIL_WIDTH / LISTING_THUMBNAIL_HEIGHT;
    $sourceRatio = $width / max(1, $height);

    if ($sourceRatio > $targetRatio) {
        $cropHeight = $height;
        $cropWidth = (int) round($height * $targetRatio);
        $cropX = (int) round(($width - $cropWidth) / 2);
        $cropY = 0;
    } else {
        $cropWidth = $width;
        $cropHeight = (int) round($width / $targetRatio);
        $cropX = 0;
        $cropY = (int) round(($height - $cropHeight) / 2);
    }

    $canvas = imagecreatetruecolor(LISTING_THUMBNAIL_WIDTH, LISTING_THUMBNAIL_HEIGHT);
    if ($canvas === false) {
        imagedestroy($source);
        return false;
    }

    if ($mime === "image/png" || $mime === "image/gif") {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, LISTING_THUMBNAIL_WIDTH, LISTING_THUMBNAIL_HEIGHT, $transparent);
    } else {
        $fill = imagecolorallocate($canvas, 232, 246, 238);
        imagefilledrectangle($canvas, 0, 0, LISTING_THUMBNAIL_WIDTH, LISTING_THUMBNAIL_HEIGHT, $fill);
    }

    if (!imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        $cropX,
        $cropY,
        LISTING_THUMBNAIL_WIDTH,
        LISTING_THUMBNAIL_HEIGHT,
        $cropWidth,
        $cropHeight
    )) {
        imagedestroy($source);
        imagedestroy($canvas);
        return false;
    }

    imagedestroy($source);

    $saved = saveListingImageResource($canvas, $absolutePath, $mime);
    imagedestroy($canvas);

    return $saved;
}

function deleteListingThumbnailFile(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === "") {
        return;
    }

    $relativePath = str_replace("\\", "/", $relativePath);
    if (strpos($relativePath, "..") !== false || !str_starts_with($relativePath, "uploads/listing-photos/")) {
        return;
    }

    $absolute = __DIR__ . "/" . $relativePath;
    if (is_file($absolute)) {
        unlink($absolute);
    }
}

function isSafeListingThumbnailPath(?string $relativePath): bool
{
    if ($relativePath === null || $relativePath === "") {
        return false;
    }

    $relativePath = str_replace("\\", "/", $relativePath);
    return strpos($relativePath, "..") === false
        && str_starts_with($relativePath, "uploads/listing-photos/")
        && is_file(__DIR__ . "/" . $relativePath);
}

function listingThumbnailSrc(?string $thumbnailUrl): ?string
{
    if ($thumbnailUrl === null || $thumbnailUrl === "") {
        return null;
    }

    if (isSafeListingThumbnailPath($thumbnailUrl)) {
        return $thumbnailUrl;
    }

    if (filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
        return $thumbnailUrl;
    }

    return null;
}

function listingThumbnailFilenameLabel(?string $relativePath): string
{
    if (!isSafeListingThumbnailPath($relativePath)) {
        return "No file selected";
    }

    return basename($relativePath);
}

function listingThumbnailUploadErrorMessage(string $code): string
{
    $messages = [
        "required" => "Please upload a listing image.",
        "invalid" => "Listing image must be a JPG, PNG, GIF, or WebP image.",
        "too_large" => "Listing image must be 8 MB or smaller.",
        "save_failed" => "Could not save listing image. Please try again.",
    ];

    return $messages[$code] ?? "Could not upload listing image.";
}

function renderListingThumbnailField(string $inputId, ?string $existingPath = null, bool $required = false): void
{
    $hasExisting = isSafeListingThumbnailPath($existingPath);
    $filenameLabel = listingThumbnailFilenameLabel($existingPath);
    $optionalLabel = $required ? "" : ' <span class="photo-optional-label">(optional — keep current if blank)</span>';
    ?>
    <div class="form-group">
        <label for="<?php echo htmlspecialchars($inputId); ?>">Listing image<?php echo $optionalLabel; ?></label>
        <?php if ($hasExisting): ?>
            <div class="listing-thumbnail-preview-wrap">
                <img
                    src="<?php echo htmlspecialchars($existingPath); ?>"
                    alt="Current listing image"
                    class="listing-thumbnail-preview"
                >
            </div>
        <?php endif; ?>
        <div class="photo-dropzone photo-dropzone-compact" data-thumbnail-dropzone>
            <input
                type="file"
                id="<?php echo htmlspecialchars($inputId); ?>"
                name="listing_thumbnail"
                accept="image/jpeg,image/png,image/gif,image/webp"
                <?php echo $required ? "required" : ""; ?>
            >
            <p class="photo-filename" data-thumbnail-filename><?php echo htmlspecialchars($filenameLabel); ?></p>
            <p class="photo-placeholder">Click to choose an image</p>
        </div>
        <p class="listing-upload-hint">JPG, PNG, GIF, or WebP — cropped to a standard 4:3 market card size on upload.</p>
    </div>
    <?php
}

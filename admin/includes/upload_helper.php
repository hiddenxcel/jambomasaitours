<?php
/**
 * Handle a single image file upload.
 * Returns ['url' => '...'] on success or if no file was chosen (returns $existingUrl).
 * Returns ['error' => '...'] on failure.
 */
function handleImageUpload(string $fieldName, string $existingUrl = ''): array {
    if (empty($_FILES[$fieldName]['name'])) {
        return ['url' => $existingUrl];
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [1=>'File too large (server limit)',2=>'File too large',3=>'Partial upload',4=>'No file',6=>'No temp folder',7=>'Cannot write to disk'];
        return ['error' => $msgs[$file['error']] ?? 'Upload error ' . $file['error']];
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        return ['error' => 'Image too large. Maximum 8MB allowed.'];
    }
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        return ['error' => 'Only JPG, PNG, WebP or GIF images are allowed.'];
    }
    $ext      = $allowed[$mime];
    $filename = uniqid('img_', true) . '.' . $ext;
    $destPath = UPLOADS_PATH . $filename;
    if (!is_dir(UPLOADS_PATH)) {
        mkdir(UPLOADS_PATH, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Could not save the uploaded file. Check folder permissions.'];
    }
    return ['url' => UPLOADS_URL . $filename];
}

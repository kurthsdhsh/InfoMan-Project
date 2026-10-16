<?php
// Saves one uploaded image.
// Returns the saved path (e.g. 'uploads/profiles/photo_abc.jpg'), or '' when nothing was chosen.
// If something is wrong, $error gets a message and '' is returned.
function saveImage(array $file, string $folder, ?string &$error): string
{
    $error = null;

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';                       // no file chosen: that's fine, photo is optional
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > 2 * 1024 * 1024) {
        $error = 'Photo must be 2 MB or smaller.';
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Photo upload failed. Please try again.';
        return '';
    }

    // getimagesize() returns false when the file is not a real image
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($types[$info[2]])) {
        $error = 'Photo must be a JPG, PNG or WEBP image.';
        return '';
    }

    $dir = __DIR__ . '/../' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);         // creates uploads/profiles the first time
    }

    $newName = uniqid('photo_') . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $newName)) {
        $error = 'Could not save the photo.';
        return '';
    }
    return $folder . '/' . $newName;
}
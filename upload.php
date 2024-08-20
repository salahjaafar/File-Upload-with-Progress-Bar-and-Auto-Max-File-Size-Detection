<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Générer un nom de fichier unique en utilisant un UUID
        $fileExt = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $uniqueFileName = uniqid('upload_', true) . '.' . $fileExt;
        $uploadFile = $uploadDir . $uniqueFileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            echo $uploadFile;
        } else {
            http_response_code(500);
            echo 'Failed to move uploaded file.';
        }
    } else {
        http_response_code(400);
        echo 'No file uploaded or file upload error.';
    }
} else {
    http_response_code(405);
    echo 'Invalid request method.';
}
?>

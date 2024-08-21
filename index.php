<?php
$forbiddenExtensions = ['php', 'exe', 'js', 'sh', 'bat', 'cmd', 'com', 'phtml', 'pl', 'py', 'rb'];
$uploadMaxFileSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

function convertToBytes($size_str) {
    switch (substr($size_str, -1)) {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}

$maxFileSize = min(convertToBytes($uploadMaxFileSize), convertToBytes($postMaxSize));
$maxFileSizeFormatted = formatSize($maxFileSize);

function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

function getExpirationTime($uploadTime) {
    return date("Y-m-d H:i:s", strtotime($uploadTime . ' +1 day'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $uploadTime = date("Y-m-d H:i:s");

        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $forbiddenExtensions)) {
            $errorMsg = "File type not allowed.";
        } elseif ($fileError !== UPLOAD_ERR_OK) {
            $errorMsg = "There was an error uploading your file.";
        } elseif ($fileSize > $maxFileSize) {
            $errorMsg = "File size exceeds the maximum limit of $maxFileSizeFormatted.";
        } else {
            $newFileName = uniqid('', true) . "." . $fileExtension;
            $destination = 'uploads/' . $newFileName;

            if (move_uploaded_file($fileTmpName, $destination)) {
                $expirationTime = getExpirationTime($uploadTime);

                // Enregistrer la date de téléchargement et d'expiration
                file_put_contents('uploads/' . $newFileName . '.json', json_encode([
                    'uploadTime' => $uploadTime,
                    'expirationTime' => $expirationTime
                ]));

                $successMsg = "File uploaded successfully! It will be deleted on $expirationTime.";
            } else {
                $errorMsg = "Failed to move uploaded file.";
            }
        }
    } else {
        $errorMsg = "No file was uploaded.";
    }
}

// Script pour supprimer les fichiers expirés
$files = glob('uploads/*');
$currentTime = time();

foreach ($files as $file) {
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) != 'json') {
        $jsonFile = $file . '.json';
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            $expirationTime = strtotime($data['expirationTime']);
            if ($currentTime > $expirationTime) {
                unlink($file);
                unlink($jsonFile);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Upload with Expiration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Secure File Upload with Expiration</h1>

    <div class="alert alert-info" role="alert">
        The maximum file size allowed for upload is <strong><?php echo $maxFileSizeFormatted; ?></strong>.
    </div>

    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <?php if (isset($successMsg)): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="fileInput" class="form-label">Select a file to upload</label>
            <input class="form-control" type="file" id="fileInput" name="file" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>

    <div class="mt-4">
        <button id="uploadAnotherBtn" class="btn btn-secondary mt-3" onclick="location.reload();">Upload Another File</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

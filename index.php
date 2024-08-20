<?php
// Récupérer la taille maximale de fichier téléchargeable depuis php.ini
$uploadMaxFileSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

// Convertir la taille en octets pour comparaison
function convertToBytes($size_str) {
    switch (substr($size_str, -1)) {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}

// Utiliser la plus petite taille entre upload_max_filesize et post_max_size
$maxFileSize = min(convertToBytes($uploadMaxFileSize), convertToBytes($postMaxSize));

// Convertir la taille maximale en format lisible
function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

$maxFileSizeFormatted = formatSize($maxFileSize);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Demo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">File Upload with Progress Bar</h1>

    <!-- Affichage de la taille maximale de fichier -->
    <div class="alert alert-info" role="alert">
        The maximum file size allowed for upload is <strong><?php echo $maxFileSizeFormatted; ?></strong>.
    </div>

    <div id="uploadForm">
        <div class="mb-3">
            <label for="fileInput" class="form-label">Select a file to upload</label>
            <input class="form-control" type="file" id="fileInput">
        </div>
        <button id="uploadBtn" class="btn btn-primary">Upload</button>
    </div>

    <div class="mt-4">
        <div id="progressContainer" class="progress" style="display: none;">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        <div id="uploadStatus" class="mt-3"></div>
        <button id="uploadAnotherBtn" class="btn btn-secondary mt-3" style="display: none;">Upload Another File</button>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<!-- JavaScript for handling file upload -->
<script>
document.getElementById('uploadBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files.length === 0) {
        alert('Please select a file.');
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('file', file);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = percentComplete + '%';
            progressBar.setAttribute('aria-valuenow', percentComplete);
            progressBar.textContent = Math.round(percentComplete) + '%';
            document.getElementById('progressContainer').style.display = 'block';
        }
    });

    xhr.addEventListener('load', function() {
        const uploadStatus = document.getElementById('uploadStatus');
        if (xhr.status === 200) {
            uploadStatus.innerHTML = `<div class="alert alert-success">Upload complete! <a href="${xhr.responseText}" target="_blank">Download file</a></div>`;
        } else {
            uploadStatus.innerHTML = `<div class="alert alert-danger">Upload failed! ${xhr.statusText}</div>`;
        }

        // Show "Upload Another File" button
        document.getElementById('uploadAnotherBtn').style.display = 'block';
    });

    xhr.send(formData);

    // Hide the upload button and form
    document.getElementById('uploadForm').style.display = 'none';
});

document.getElementById('uploadAnotherBtn').addEventListener('click', function() {
    // Reset the form and progress bar for another upload
    document.getElementById('fileInput').value = '';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressBar').textContent = '0%';
    document.getElementById('progressContainer').style.display = 'none';
    document.getElementById('uploadStatus').innerHTML = '';

    // Show the upload form again and hide this button
    document.getElementById('uploadForm').style.display = 'block';
    document.getElementById('uploadAnotherBtn').style.display = 'none';
});
</script>
</body>
</html>


<?php

require '../private/config.php';
require '../private/staysail/Staysail.php';

$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = isset($_POST['id']) ? $_POST['id'] : null;
    $job = isset($_POST['job']) ? $_POST['job'] : null;

    $folderPath = DATAROOT . '/private/documents/' . $userId;

    if ($job === 'delete') {

        $files = glob($folderPath . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($folderPath);

    } elseif ($job === 'archive') {

        $zipFileName = DATAROOT . '/private/documents/' . $userId . '/' . $userId . '.zip';


        $zip = new ZipArchive();

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {


                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            exit;
        }
    }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {

      $userId = isset($_GET['id']) ? $_GET['id'] : null;

        $fileName = $userId . '.zip';
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $fileName);

        $filePath = DATAROOT . '/private/documents/' . $userId . '/' . $userId . '.zip';

        if (file_exists($filePath)) {
            readfile($filePath);
            unlink($filePath);
            exit;
        }
}

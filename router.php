<?php
chdir(__DIR__);
$filePath = realpath('./src/'.ltrim($_SERVER['REQUEST_URI'], '/'));
if ($filePath && is_dir($filePath)){
    // attempt to find an index file
    foreach (['index.php', 'index.html'] as $indexFile) {
        if ($filePath = realpath($filePath . DIRECTORY_SEPARATOR . $indexFile)){
            break;
        }
    }
}

if ($filePath && is_file($filePath)) {
    if (strpos($filePath, __DIR__ . DIRECTORY_SEPARATOR) === 0 &&
        $filePath !== __DIR__ . DIRECTORY_SEPARATOR . 'router.php' &&
        substr(basename($filePath), 0, 1) !== '.'
    ) {
        if (strtolower(substr($filePath, -4)) === '.php') {
            include $filePath;
        } else {
            if (strtolower(substr($filePath, -3)) === '.js') {
                header('Content-Type: text/javascript');
            }
            readfile ($filePath);
        }
    } else {
        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
    }
} else {
    include  'src' . DIRECTORY_SEPARATOR . 'index.php';
}

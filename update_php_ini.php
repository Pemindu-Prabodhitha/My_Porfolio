<?php
$file = '/Applications/XAMPP/xamppfiles/etc/php.ini';
$content = file_get_contents($file);

// Replace post_max_size
$content = preg_replace('/^post_max_size\s*=\s*[0-9]+[KMGT]?/m', 'post_max_size = 500M', $content);

// Replace upload_max_filesize
$content = preg_replace('/^upload_max_filesize\s*=\s*[0-9]+[KMGT]?/m', 'upload_max_filesize = 500M', $content);

file_put_contents($file, $content);
echo "php.ini updated successfully.\n";

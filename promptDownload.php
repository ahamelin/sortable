<?php

$path = 'output.txt';
$browserFilename = 'output.txt';
$mimeType = 'text/plain';


if (!file_exists($path) || !is_readable($path)) {

return null;
}
header("Content-Type: " . $mimeType);
header("Content-Disposition: attachment; filename=\"$browserFilename\"");
header('Expires: ' . gmdate('D, d M Y H:i:s', gmmktime() - 3600) . ' GMT');
header("Content-Length: " . filesize($path));
// If you wish you can add some code here to track or log the download

// Special headers for IE 6
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
$fp = fopen($path, "r");
fpassthru($fp);

?>
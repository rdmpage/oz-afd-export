<?php

require_once(dirname(__FILE__) . '/fuseki/fuseki.php');

$filename = 'names.nt';
$filename = 'junk/bibliography.nt';
$filename = 'tx.nt';

upload_from_file_chunks($filename, 100000);

?>
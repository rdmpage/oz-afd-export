<?php

// Break a big triples file into arbitrary sized chunks for easier uplaoding.

$graph_uri = '';

$triples_filename = 'junk/bibliography.nt';
$basename = 'bibliography';

$triples_filename = 'taxa.nt';
$basename = 'taxa';

$triples_filename = 'junk/names.nt';
$basename = 'names';
$graph_uri = 'https://biodiversity.org.au/afd/publication';

$count = 0;
$total = 0;
$triples = '';

$chunks= 10000;


$handle = null;
$output_filename = '';

$chunk_files = array();

$file_handle = fopen($triples_filename, "r");
while (!feof($file_handle)) 
{
	if ($count == 0)
	{
		$output_filename = $basename . '-' . $total . '.nt';
		$chunk_files[] = $output_filename;
		$handle = fopen($output_filename, 'a');
	}

	$line = fgets($file_handle);
	
	fwrite($handle, $line);
	
	if (!(++$count < $chunks))
	{
		fclose($handle);
		
		$total += $count;
		
		echo $total . "\n";
		$count = 0;
		
	}
}

fclose($handle);


echo "--- curl upload.sh ---\n";
$curl = "#!/bin/sh\n\n";
foreach ($chunk_files as $filename)
{
	$curl .= "echo '$filename'\n";
	
	$url = 'http://130.209.46.63/blazegraph/sparql';
	
	if ($graph_uri != '')
	{
		$url .= '?context-uri=' . $graph_uri;
	}
	
	$curl .= "curl $url -H 'Content-Type: text/rdf+n3' --data-binary '@$filename'\n";
	$curl .= "echo ''\n";
	$curl .= "sleep 10\n";
}
file_put_contents(dirname(__FILE__) . '/upload.sh', $curl);



	
?>	

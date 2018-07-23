<?php

// Break a big triples file into arbitrary sized chunks for easier uplaoding.

$triples_filename = 'junk/bibliography.nt';
$basename = 'bibliography';

$triples_filename = 'taxa.nt';
$basename = 'taxa';

//$triples_filename = 'names.nt';
//$basename = 'names';

$count = 0;
$total = 0;
$triples = '';

$chunks= 500000;


$handle = null;
$output_filename = '';

$file_handle = fopen($triples_filename, "r");
while (!feof($file_handle)) 
{
	if ($count == 0)
	{
		$output_filename = $basename . '-' . $total . '.nt';
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


	
?>	

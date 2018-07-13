<?php

require_once(dirname(__FILE__) . '/sparql.php');


//----------------------------------------------------------------------------------------

// Directory where bundled RDF files live
$basedir = dirname(dirname(__FILE__)) . '/chunks';
$basedir = dirname(dirname(__FILE__)) . '/chunks2';


$files = scandir($basedir);


// Local Docker
$sparql_url = 'http://localhost:32791/blazegraph/sparql';

$sparql_url = 'http://localhost:9999/blazegraph/sparql';


// Sloppy.io
//$sparql_url = 'http://kg-blazegraph.sloppy.zone/blazegraph/sparql';

$graph_uri = '';
$graph_key_name = 'context-uri';

foreach ($files as $filename)
{
	if (preg_match('/\.nt$/', $filename))
	{	
		$triples_filename = $basedir . '/' . $filename;
				
		// Load file directly
		$result = upload_from_file(
			$sparql_url,
			$triples_filename, 
			$graph_key_name,
			'http://www.ipni.org'
			);
		
		$rand = rand(1000000, 5000000);
		//$rand = rand(50000000, 100000000);
		echo "\n[sleeping for " . round(($rand / 1000000),2) . ' seconds]';
		usleep($rand);	
		echo "\n";				
	}
}



?>

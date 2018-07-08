<?php

error_reporting(E_ALL);

// Jena-Fuseki API

$config['fuseki-url'] 		= 'https://kg-fuseki.sloppy.zone/';
$config['fuseki-dataset'] 	= 'test';
$config['fuseki-user'] 		= 'admin';
$config['fuseki-password'] 	= 'Tl3O7c5Y6qbFTLk';

/*
$config['fuseki-url'] 		= 'http://localhost:32768/';
$config['fuseki-dataset'] 	= 'test';
$config['fuseki-user'] 		= 'admin';
$config['fuseki-password'] 	= '4R5mAjcSYBV690d';
*/

// If password lost in logs get from comamnd line
// sloppy logs -n 10000 <project> | grep "admin="

//----------------------------------------------------------------------------------------
// $triples_filename is the full path to a file of triples
function upload_from_file($triples_filename)
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];

	$filename = basename($triples_filename);

	$data = array(
		'uploaded_file' => curl_file_create(
			$triples_filename, 
			'application/n-triples', 
			$filename
		)
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	curl_close($ch);

	echo $response;
}

//----------------------------------------------------------------------------------------
// $data is a string of triples
function upload_data($data, $graph = '')
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	if ($graph == '')
	{
	}
	else
	{
		$url .= '?graph=' . $graph;
	}
	
	echo "\n" . __LINE__ . " URL = $url\n";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	// curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/n-triples"));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/n-quads"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code != 200)
	{
		echo $response;	
		die ("Triple store returned $http_code\n");
	}
	
	
	curl_close($ch);

	echo $response;
}

//----------------------------------------------------------------------------------------
// Load a triples file, do it in chunks as it could be large
// $triples_filename is the full path to a file of triples
function upload_from_file_chunks($triples_filename, $chunks = 1000, $graph = '')
{
	global $config;
	
	//print_r($config);
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	if ($graph == '')
	{
	}
	else
	{
		$url .= '?graph=' . $graph;
	}
	
	echo "\n" . __LINE__ . " URL = $url\n";	

	$count = 0;
	$total = 0;
	$triples = '';
	
	$file_handle = fopen($triples_filename, "r");
	while (!feof($file_handle)) 
	{
		$line = fgets($file_handle);
		$triples .= $line;
		
		if (!(++$count < $chunks))
		{
			upload_data($triples);
			
			$total += $count;
			
			echo $total . "\n";
			$count = 0;
			$triples = '';
		}
	}
	
	// left over
	if ($count > 0)
	{
		
		upload_data($triples, $graph);
		
		$total += $count;
			
		echo $total . "\n";		
		$count = 0;
		$triples = '';
	}
		
	
	
			

}

//----------------------------------------------------------------------------------------
// query
function sparql_query($query)
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	// Query is string
	$data = 'query=' . urlencode($query);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/sparql-results+json"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code != 200)
	{
		echo $response;	
		die ("Triple store returned $http_code\n");
	}
	
	
	curl_close($ch);

	echo $response;




}

//----------------------------------------------------------------------------------------
// DESCRIBE
function sparql_describe($uri, $format='application/ld+json')
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	// Query is string
	$data = 'query=' . urlencode('DESCRIBE <' . $uri . '>');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code != 200)
	{
		echo $response;	
		die ("Triple store returned $http_code\n");
	}
	
	curl_close($ch);

	return $response;
}


//----------------------------------------------------------------------------------------
// DELETE
function sparql_delete()
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	// Query is string
	$data = 'update=' . urlencode('DELETE WHERE {?s ?p ?o }');

	$data = 'update=' . urlencode('DELETE WHERE { GRAPH ?g { ?s ?p ?o }}');


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code != 200)
	{
		echo $response;	
		die ("Triple store returned $http_code\n");
	}
	
	curl_close($ch);
	
	echo $response;	



}

//----------------------------------------------------------------------------------------
// DESCRIBE
function sparql_construct($uri, $format='application/ld+json')
{
	global $config;
	
	$url = $config['fuseki-url'] . $config['fuseki-dataset'];
	
	// Query is string
$q = 'CONSTRUCT { 
  ?work <http://schema.org/name> ?name .
  ?work <http://schema.org/datePublished> ?datePublished .
  ?work <http://schema.org/hasPart> ?part .
}
FROM <urn:x-arq:UnionGraph>
WHERE { 
  OPTIONAL { ?work <http://schema.org/name> ?name . }
  OPTIONAL { ?work <http://schema.org/datePublished> ?datePublished . }
  OPTIONAL { ?work <http://schema.org/hasPart> ?part . }
  FILTER (?work = <' . $uri . '> )
}';	
	$q = str_replace("\n", "", $q);
	$url .= '?query=' . rawurlencode($q);
	
	//echo $url . "\n";

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	curl_setopt ($ch, CURLOPT_HEADER,		  0);  

	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	
	
	$http_code = $info['http_code'];
	
	if ($http_code != 200)
	{
		echo $response;	
		die ("Triple store returned $http_code\n");
	}
	
	curl_close($ch);

	return $response;

}


if (0)
{
	$triples_filename = dirname(__FILE__) . '/nanopub.nq';
	upload_from_file_chunks($triples_filename);
}


// test
if (0)
{
	$triples_filename = dirname(__FILE__) . '/data/mendeley_group.nt';
	upload_from_file_chunks($triples_filename);
}

if (0)
{
	$sparql = 'SELECT *
WHERE {
  ?occurrence ?y "BC ZSM Lep 10234" .
   ?occurrence <http://schema.org/name> ?name .
  ?occurrence <http://schema.org/alternateName> ?z .
   
} ';

	sparql_query($sparql);
}

// Test DESCRIBE with JSON-LD
if (0)
{
	$uri = 'https://doi.org/10.11646/zootaxa.4327.1.1';
	
	$uri = 'https://doi.org/10.5281/zenodo.893545';
	
	$uri = 'https://doi.org/10.3897/phytokeys.94.21337';
	
	$uri = 'https://orcid.org/0000-0002-6168-3883';
	
	$uri = 'https://doi.org/10.1016/j.soilbio.2013.10.006';

	$jsonld = sparql_describe($uri);
	
	echo $jsonld;
}

// Delete
if (0)
{
	sparql_delete();
}

if (0)
{
	echo sparql_construct('http://biostor.org/reference/137324', 'application/n-quads');
}


?>
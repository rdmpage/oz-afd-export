<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Get JSON-LD for figures for AFD records with Zenodo parts

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

require_once('php-json-ld/jsonld.php');



//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 

//----------------------------------------------------------------------------------------
function fetch_zenodo_json($id, &$jsonld)
{	
	$url = "https://zenodo.org/api/records/" . $id;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	if ($data != '')
	{
		$obj = json_decode($data);
		
		//print_r($obj);
		
		// image URL
		if (isset($obj->files[0]->links->self))
		{
			$jsonld->contentUrl = $obj->files[0]->links->self;
		}
		
		// image thumbnail
		if (isset($obj->links->thumb250))
		{
			$jsonld->thumbnailUrl = $obj->links->thumb250;
		}
		
	}
}

//----------------------------------------------------------------------------------------
// Call API asking for JSON-LD which we convert to triples 
// Note that we make a second call to get the details of the image itself (sigh)
function fetch_zenodo($id)
{	
	$url = "https://zenodo.org/api/records/" . $id;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_HTTPHEADER => array("Accept: application/ld+json")
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	if ($data != '')
	{
		// triples
		$jsonld = json_decode($data);
		
		// second call 
		fetch_zenodo_json($id, $jsonld);
					
		
		if (0)
		{			
			// JSON-LD for debugging
			echo json_encode($jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			echo "\n";			
		}
		else
		{
			// Triples for export
			$triples = jsonld_to_rdf($jsonld, array('format' => 'application/nquads'));			
			echo $triples;
		}
					 
	}
}




$journal = 'Zootaxa';
//$journal = 'Revue Suisse de Zoologie';

$page = 1000;
$offset = 0;

$done = false;

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	// A specific journal or publication, otherwise we are getting everything
	
	// $sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE = "' . $journal . '"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "45b40323-4325-41c5-acbf-10340e7ba6ca"';
	
	$sql .= ' WHERE zenodo_parts IS NOT NULL';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		$parts = json_decode($result->fields['zenodo_parts']);
		
		foreach ($parts as $id)
		{
			fetch_zenodo($id);	
		}
		
		$result->MoveNext();

	}
	
	
	
	
	if ($result->NumRows() < $page)
	{
		$done = true;
	}
	else
	{
		$offset += $page;
		
		//if ($offset > 3000) { $done = true; }
	}
	

}

?>

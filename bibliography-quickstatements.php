<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Publications to triples, but only for a small set of attributes

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

require_once('php-json-ld/jsonld.php');

require_once (dirname(__FILE__) . '/parse_authors.php');
require_once (dirname(__FILE__) . '/thumbnails.php');


//----------------------------------------------------------------------------------------
$db = NewADOConnection('mysqli');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 


$page = 1000;
$offset = 0;

$done = false;

$statements = array();

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	$sql .= ' WHERE wikidata IS NOT NULL';
		
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF) 
	{
		
		
		$statements[] = array($result->fields['wikidata'], 
			'P6982', 
			'"' . $result->fields['PUBLICATION_GUID'] . '"'
			);
	
		$result->MoveNext();
	}
	
	
	if ($result->NumRows() < $page)
	{
		$done = true;
		
		// dump statements
		print_r($statements);
		
		$quickstatments = '';

		foreach ($statements as $st)
		{		
			$quickstatments .= join("\t", $st) . "\n";
		}
		echo $quickstatments;	
	}
	else
	{
		$offset += $page;
		
		//if ($offset > 3000) { $done = true; }
	}
	

}

?>

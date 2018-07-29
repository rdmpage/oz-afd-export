<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// For each Zenodo-linked record in AFD, get list of parts (i.e., figures) from local
// BLR CouchDB

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

require_once('php-json-ld/jsonld.php');



//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 

//--------------------------------------------------------------------------------------------------
// Get part for a Zenodo record
function get_part($id)
{
	$obj = null;
	
	$url = 'http://127.0.0.1:5984/zenodo/_design/parts/_view/whole-part'
		. '?key=' . urlencode('"' . $id . '"');
		
	echo "-- $url\n";

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
	}
	
	// print_r($obj);
	
	return $obj;
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
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE = "' . $journal . '"';
	
	$sql .= ' WHERE PUBLICATION_GUID = "3e3d5933-9ba7-4828-83ca-019feb9905fe"';
	
	$sql .= ' AND zenodo IS NOT NULL';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		$obj = get_part($result->fields['zenodo']);
		//print_r($obj);
		
		
		if (count($obj->rows) > 0)
		{
			$parts = array();
			foreach ($obj->rows as $row)
			{
				$parts[] = $row->value;
			}
			
			$sql = 'UPDATE bibliography SET zenodo_parts=\'' . json_encode($parts) .  '\' WHERE PUBLICATION_GUID="' . $result->fields['PUBLICATION_GUID'] . '";';
			
			echo $sql . "\n";
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

<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Map AFD to Zenodo 

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 


//--------------------------------------------------------------------------------------------------
function get_match($key)
{
	$obj = null;
	
	$url = 'http://127.0.0.1:5984/zenodo/_design/lookup/_view/triple'
		. '?key=' . urlencode(json_encode($key));
		
	// echo $url . "\n";

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

//--------------------------------------------------------------------------------------------------
function get_from_doi($doi)
{
	$obj = null;
	
	$url = 'http://127.0.0.1:5984/zenodo/_design/identifier/_view/doi'
		. '?key=' . urlencode('"' . $doi . '"');
		
	//echo $url . "\n";

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
$journal = 'Zookeys';
//$journal = 'Revue Suisse de Zoologie';

$page = 1000;
$offset = 0;

$done = false;

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	// A specific journal or publication, otherwise we are getting everything

	//$sql .= ' WHERE PUBLICATION_GUID = "30bc1c51-6b67-40d1-8419-045b3a13fa71"';
	
	$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE = "' . $journal . '"';

	//$sql .= ' WHERE doi="10.1051/parasite/1968432131"';

	//$sql .= ' WHERE updated > "2018-06-16"';
	
	$sql .= ' ORDER BY PUB_YEAR DESC';
		
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		// Bibliographic metadata
		if (0)
		{
		
			$key = array();
		
			if ($result->fields['PUB_PARENT_JOURNAL_TITLE'] != '')
			{
				$key[] = $result->fields['PUB_PARENT_JOURNAL_TITLE'];
			}

			if ($result->fields['volume'] != '')
			{
				$key[] = $result->fields['volume'];
			}

			if ($result->fields['spage'] != '')
			{
				$key[] = $result->fields['spage'];
			}
		
		
			if (count($key) == 3)
			{
				// print_r($key);
		
				$obj = get_match($key);
		
				// print_r($obj);
				
				if (count($obj->rows) == 1)
				{
					$sql = 'UPDATE bibliography SET zenodo=' . str_replace('oai:zenodo.org:', '', $obj->rows[0]->id) . ' WHERE PUBLICATION_GUID="' . $result->fields['PUBLICATION_GUID'] . '";';
				
					echo $sql . "\n";
				}
			
			}
		}
		
		// DOI
		if (1)
		{
			if ($result->fields['doi'] != '')
			{
				$doi = $result->fields['doi'];
				$obj = get_from_doi($doi);
		
				// print_r($obj);
				
				if (count($obj->rows) == 1)
				{
					$sql = 'UPDATE bibliography SET zenodo=' . str_replace('oai:zenodo.org:', '', $obj->rows[0]->id) . ' WHERE doi="' . $doi . '";';
				
					echo $sql . "\n";
				}
			
			}
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

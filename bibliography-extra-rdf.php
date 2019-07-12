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

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	$sql .= ' WHERE zoobank IS NOT NULL';
		
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF) 
	{
		// triples
	
		$triples = array();
	
		$subject_id = 'https://biodiversity.org.au/afd/publication/' . $result->fields['PUBLICATION_GUID'];
	
		$s = '<' . $subject_id . '>';
		
				
					
		// Zoobank
		if ($result->fields['zoobank'] != '')
		{
			$identifier_id = '<' . $subject_id . '#zoobank' . '>';

			$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
			$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
			$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"zoobank"' . '.';
			$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"urn:lsid:zoobank.org:pub:' . strtoupper($result->fields['zoobank']) . '"' . '.';
			
			// uuid ()
			$triples[] = $s . ' <http://schema.org/identifier> "' . strtolower($result->fields['zoobank']) . '" .';	
	
			// sameAs link?
			$triples[] = $s . ' <http://schema.org/sameAs> "http://zoobank.org/References/' . strtoupper($result->fields['zoobank']) . '" .';				
		}	
					
		
	
		//print_r($triples);
	
	
		$t = join("\n", $triples);
	
		// triples or JSON-LD?
		if (1)
		{
			echo $t . "\n";
		}
		else
		{
	
			$doc = jsonld_from_rdf($t, array('format' => 'application/nquads'));
		
			//print_r($doc);
	
			$context = (object)array(
				'@vocab' => 'http://schema.org/',
				'sha1' => 'http://id.loc.gov/vocabulary/preservation/cryptographicHashFunctions/sha1'
			);
	
			$compacted = jsonld_compact($doc, $context);
		
			//print_r($compacted);

			echo json_encode($compacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
			echo "\n";
		}
		
		//exit();
	
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

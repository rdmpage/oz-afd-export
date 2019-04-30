<?php

// add attributes let out before

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');
require_once('php-json-ld/jsonld.php');


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysqli');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 

$enhance_name 		= true;
$enhance_name 		= false; /* if false we don't output TDWG LSID triples tp keep things small */

	
$page = 1000;
$offset = 0;

$done = false;

while (!$done)
{
	$sql = 'SELECT * FROM afd WHERE NAME_GUID IS NOT NULL';
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;
		
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $db->ErrorMsg());

	while (!$result->EOF) 
	{
		$triples = array();
	
		// AFD doesn't have a resolver for names, so use UUID
		$name = '<urn:uuid:' . $result->fields['NAME_GUID'] . '>';

		/*
		if ($result->fields['YEAR'] != '')
		{
			// TDWG
			$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#year> ' . '"' . $result->fields['YEAR'] . '" . ';										
		}
			
		// rank 
		if ($result->fields['RANK'] != '')
		{
			$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#rankString> ' . '"' . addcslashes(strtolower($result->fields['RANK']), '"') . '" . ';
		}
		*/
		
		// Fix bug https://github.com/rdmpage/oz-afd-export/issues/1
		if ($result->fields['taxon_guid_ala'] != '')
		{
			$taxon = '<https://bie.ala.org.au/species/urn:lsid:biodiversity.org.au:afd.taxon:' . $result->fields['taxon_guid_ala'] . '>';
		}
		else
		{
			$taxon = '<https://bie.ala.org.au/species/urn:lsid:biodiversity.org.au:afd.taxon:' . $result->fields['TAXON_GUID'] . '>';
		}

		switch ($result->fields['NAME_TYPE'])
		{
			case 'Valid Name':
				// TDWG LSID links taxon to accepted name 
				$triples[] = $taxon . ' <http://rs.tdwg.org/ontology/voc/TaxonConcept#hasName> ' . $name . ' . ';						
				break;
				
			default:
				break;
		}
		
		if (count($triples) > 0)
		{

							
			$t = join("\n", $triples) . "\n";
		
			//print_r($t);
		
			if (1)
			{
				echo $t . "\n";
			}
			else
			{
	
				$doc = jsonld_from_rdf($t, array('format' => 'application/nquads'));
	
				$context = (object)array(
					'@vocab' => 'http://schema.org/',
					'tcommon' => 'http://rs.tdwg.org/ontology/voc/Common#',
					'tc' => 'http://rs.tdwg.org/ontology/voc/TaxonConcept#',
					'tn' => 'http://rs.tdwg.org/ontology/voc/TaxonName#',	
					'taxrefprop' => 'http://taxref.mnhn.fr/lod/property/',			
					'dwc' => 'http://rs.tdwg.org/dwc/terms/',
	//				'gbif_ns' => 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/',
	//				'gbif_ts' => 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/'
				);
	
				$compacted = jsonld_compact($doc, $context);

				echo json_encode($compacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
				echo "\n";
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

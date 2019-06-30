<?php

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
//	$sql = 'SELECT * FROM afd WHERE NAME_GUID IS NOT NULL AND PUBLICATION_GUID IS NOT NULL';
	$sql = 'SELECT * FROM afd WHERE NAME_GUID IS NOT NULL';
	
	//$sql = 'SELECT * FROM afd WHERE PUBLICATION_GUID="dee77aeb-e878-4827-8be0-707a508eddb4"';
	
	//$sql = 'SELECT * FROM afd WHERE NAME_GUID IS NOT NULL AND PUBLICATION_GUID IS NOT NULL AND taxon_guid_ala IS NOT NULL';

	//$sql = 'SELECT * FROM afd WHERE TAXON_GUID="124ab9d1-5ed7-46c3-aecf-76e70a04e209"';

	// Sundholmia
	//$sql = 'SELECT * FROM afd WHERE TAXON_GUID="069530f5-a83f-4532-b544-0671eea8bbfb"';
	
	// Parabopyrella essingtoni (Bourdon & Bruce, 1983)
	//$sql = 'SELECT * FROM afd WHERE TAXON_GUID="d0204b5f-7093-419e-a6fb-d84b95effbe4"';
	
	// 'Ochlerotatus' daliensis (Taylor, 1916)
	//$sql = 'SELECT * FROM afd WHERE TAXON_GUID="26eb3cc2-adb1-4628-86cd-65cdd3d3e377"';


	//$sql = 'SELECT * FROM afd WHERE GENUS="Manestella"';
	
	//$sql = 'SELECT * FROM afd WHERE NAME_GUID IS NOT NULL AND PUB_PUB_PARENT_JOURNAL_TITLE="Zootaxa"';

	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;
		
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $db->ErrorMsg());

	while (!$result->EOF) 
	{
		$triples = array();
	
		// AFD doesn't have a resolver for names, so use UUID
		$name = '<urn:uuid:' . $result->fields['NAME_GUID'] . '>';
		
		$triples[] = $name . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://rs.tdwg.org/ontology/voc/TaxonName#TaxonName> . ';

		// Store UUID as string
		$triples[] = $name . ' <http://schema.org/identifier> "' . $result->fields['NAME_GUID'] . '" . ';
		
		// Taxon is ALA URL
		
		if ($result->fields['taxon_guid_ala'] != '')
		{
			$taxon = '<https://bie.ala.org.au/species/urn:lsid:biodiversity.org.au:afd.taxon:' . $result->fields['taxon_guid_ala'] . '>';
		}
		else
		{
			$taxon = '<https://bie.ala.org.au/species/urn:lsid:biodiversity.org.au:afd.taxon:' . $result->fields['TAXON_GUID'] . '>';
		}
		
		
		
		// relationship between name and taxon
		switch ($result->fields['NAME_TYPE'])
		{
			case 'Valid Name':
				// TDWG LSID links taxon to accepted name 
				$triples[] = $taxon . ' <http://rs.tdwg.org/ontology/voc/TaxonConcept#hasName> ' . $name . ' . ';		
				
				// TAXREF 
				$triples[] = $taxon . ' <http://taxref.mnhn.fr/lod/property/hasReferenceName> ' . $name . ' . ';
				break;
				
			case 'Synonym':
			case 'Generic Combination':				
				// TAXREF 
				$triples[] = $taxon . ' <http://taxref.mnhn.fr/lod/property/hasSynonym> ' . $name . ' . ';
				break;
				
			case 'Common Name':
				// TAXREF 
				$triples[] = $taxon . ' <http://taxref.mnhn.fr/lod/property/vernacularName> ' . $name . ' . ';
				break;
				
			default:
				break;
		}
		
		// is it the original name
		switch ($result->fields['ORIG_COMBINATION'])
		{
			case 'Y':
				$triples[] = $taxon . ' <http://rs.tdwg.org/dwc/terms/originalNameUsage> ' . '"' . addcslashes($result->fields['SCIENTIFIC_NAME'], '"') . '" . ';
				break;
				
			case 'N':
			default:
				break;
		}
		
			
		// Name itself
		
		// name, may be scientific or common
		// format nicely for display, the tn vocab fields can be used for queries
		// think about how to style, markup, CSS, etc.
		if ($result->fields['NAME_TYPE'] == 'Common Name')
		{
			// common
			$triples[] = $name . ' <http://schema.org/name> ' . '"' . addcslashes($result->fields['NAMES_VARIOUS'], '"') . '" . ';			
		
		}
		else
		{
			// scientific 
			$triples[] = $name . ' <http://schema.org/name> ' . '"' . addcslashes($result->fields['SCIENTIFIC_NAME'], '"') . '" . ';			
		
			if ($enhance_name)
			{
				$name_parts = array();		
				if ($result->fields['FAMILY'] != '')
				{
					if ($result->fields['RANK'] == 'Family')
					{
						$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#uninomial> ' . '"' . addcslashes(strtolower($result->fields['FAMILY']), '"') . '" . ';
						$name_parts[] = $result->fields['FAMILY'];
					}				
				}			
					
				if ($result->fields['GENUS'] != '')
				{
					if ($result->fields['RANK'] == 'Genus')
					{
						$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#uninomial> ' . '"' . addcslashes($result->fields['GENUS'], '"') . '" . ';
					}
					else
					{
						$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#genusPart> ' . '"' . addcslashes($result->fields['GENUS'], '"') . '" . ';
					}
					$name_parts[] = $result->fields['GENUS'];
				}						
		
				if ($result->fields['SUBGENUS'] != '')
				{
					$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#infragenericEpithet> ' . '"' . addcslashes($result->fields['SUBGENUS'], '"') . '" . ';
					$name_parts[] = '(' . $result->fields['SUBGENUS'] . ')';
				}			
		
				if ($result->fields['SPECIES'] != '')
				{
					$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#specificEpithet> ' . '"' . addcslashes($result->fields['SPECIES'], '"') . '" . ';
					$name_parts[] = $result->fields['SPECIES'];
				}

				if ($result->fields['SUBSPECIES'] != '')
				{
					$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#infraspecificEpithet> ' . '"' . addcslashes($result->fields['SUBGENUS'], '"') . '" . ';
					$name_parts[] = $result->fields['SUBSPECIES'];
				}		
					
				$nameComplete = join(' ', $name_parts);
				if ($nameComplete == '')
				{
					$nameComplete = $result->fields['SCIENTIFIC_NAME'];
				
					$authorship = '';
					if ($result->fields['AUTHOR'] != '')
					{
						$authorship = $result->fields['AUTHOR'];
					}
					if ($result->fields['YEAR'] != '')
					{
						$authorship .= ', ' . $result->fields['YEAR'];
					}
				
					$pattern = '\s+\(?' . preg_quote($authorship, '/') . '\)?';
				
					$nameComplete = preg_replace('/' . $pattern . '/u', '', $nameComplete);
								
					$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#nameComplete> ' . '"' . addcslashes($nameComplete, '"') . '" . ';						
				}
				else
				{
					$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#nameComplete> ' . '"' . $nameComplete . '" . ';						
				}
			}
			
						
			// Comments
			if ($result->fields['QUALIFICATION'] != '')
			{
				$triples[] = $name . ' <http://rs.tdwg.org/dwc/terms/taxonRemarks> ' . '"' . addcslashes($result->fields['QUALIFICATION'], '"') . '" . ';						
			}			
			
			// status, nomenclature and taxonomy
			if ($result->fields['NAME_TYPE'] != '')
			{
				$taxonomic_status = '';
				$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/available'; // by default
			
				$name_subtype = $result->fields['NAME_SUBTYPE'];
			
				switch ($result->fields['NAME_TYPE'])
				{
					case 'Synonym':
						$taxonomic_status = 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/synonym';
					
						switch ($name_subtype)
						{
							case 'synonym':
								break;
								
							case 'junior homonym':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/invalidum';									
								// http://purl.obolibrary.org/obo/NOMEN_0000289 -- homonym 
								break;
								
							case 'invalid name':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/invalidum';
								// http://purl.obolibrary.org/obo/NOMEN_0000272 -- invalid
								break;
								
							case 'nomen nudum':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/nudum';
								break;

							case 'replacement name':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/novum';
								break;

							case 'original spelling':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/orthographia';
								break;
								
							case 'subsequent misspelling':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/orthographia';
								break;
								
							case 'emendation':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/correctum';
								break;
								
							case 'nomen dubium':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/dubimum';
								break;
								
							case 'objective synonym':
								$taxonomic_status = 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/homotypicSynonym';
								break;

							case 'subjective synonym':
								$taxonomic_status = 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/heterotypicSynonym';
								break;
								
							case 'nomem oblitum':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/oblitum';
								break;
								
							case 'nomen protectum':
								$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/protectum';
								break;
						
							default:
								break;
						}						
						break;
						
					case 'Valid Name':
						$taxonomic_status = 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/accepted';
						break;
						
					case 'Generic Combination':
						$taxonomic_status = 'http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/synonym';
						$nomenclatural_status = 'http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/combinatio';
						break;
						
					default:
						break;
				}
				
				if ($taxonomic_status != '')
				{
					//$triples[] = $name . ' <http://rs.tdwg.org/dwc/terms/taxonomicStatus> <' .  $taxonomic_status . '> . ';					
					$triples[] = $name . ' <http://rs.tdwg.org/dwc/terms/taxonomicStatus> "' .  str_replace('http://rs.gbif.org/vocabulary/gbif/taxonomicStatus/', '', $taxonomic_status) . '" . ';					
				}
				
				if ($nomenclatural_status != '')
				{
					//$triples[] = $name . ' <http://rs.tdwg.org/dwc/terms/nomenclaturalStatus> <' .  $nomenclatural_status . '> . ';
					$triples[] = $name . ' <http://rs.tdwg.org/dwc/terms/nomenclaturalStatus> "' .  str_replace('http://rs.gbif.org/vocabulary/gbif/nomenclatural_status/', '', $nomenclatural_status) . '" . ';
				}			
			}				
		}
	
		if ($enhance_name)
		{				
			if (($result->fields['AUTHOR'] != '') && ($result->fields['YEAR'] != ''))
			{
				// TDWG
				$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/TaxonName#authorship> ' . '"' . addcslashes($result->fields['AUTHOR'] . ', ' . $result->fields['YEAR'], '"') . '" . ';										

				// TAXREF 
				$triples[] = $name . ' <http://taxref.mnhn.fr/lod/property/hasAuthority> ' . '"' . addcslashes($result->fields['AUTHOR'] . ', ' . $result->fields['YEAR'], '"') . '" . ';										
			}
		
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
		}			
										
		// publication (if we have a publication we always have a GUID)
		if ($result->fields['PUBLICATION_GUID'] != '')
		{
			$triples[] = $name . ' <http://rs.tdwg.org/ontology/voc/Common#publishedInCitation> ' . '<https://biodiversity.org.au/afd/publication/' . $result->fields['PUBLICATION_GUID'] . '> . ';
		}

							
		$t = join("\n", $triples) . "\n\n";
		
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


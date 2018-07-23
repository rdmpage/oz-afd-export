<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Publications to triples

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

require_once('php-json-ld/jsonld.php');

require_once (dirname(__FILE__) . '/parse_authors.php');
require_once (dirname(__FILE__) . '/thumbnails.php');


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 


//--------------------------------------------------------------------------------------------------
function get_pdf_details($pdf)
{
	global $db;
	
	$obj = null;
	
	$sql = "SELECT * FROM sha1 WHERE pdf = " . $db->qstr($pdf) . " LIMIT 1;";
		
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	
	if ($result->NumRows() == 1)
	{
		$obj = new stdclass;
		$obj->sha1 = $result->fields['sha1'];
	}
	else
	{
	
		$url = 'http://bionames.org/bionames-archive/pdfstore?url=' . urlencode($pdf) . '&noredirect&format=json';
		//$url = 'http://direct.bionames.org/bionames-archive/pdfstore?url=' . urlencode($pdf) . '&noredirect&format=json';
		
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
	}
	
	
		
	return $obj;
}

//--------------------------------------------------------------------------------------------------
function get_pdf_images($sha1)
{
	$obj = null;
	
	$url = 'http://bionames.org/bionames-archive/documentcloud/' . $sha1 . '.json';

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
	
	return $obj;
}



$enhance_authors 		= true;
$enhance_metadata 		= true;
$enhance_identifiers	= true;
$enhance_pdf			= true;
$enhance_pdf_as_images	= false;
$enhance_thumbnails		= true;


$use_role				= true;
//$use_role				= false;

$page = 1000;
$offset = 0;

$done = false;

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	// A specific journal or publication, otherwise we are getting everything
	//$sql .= ' WHERE PUBLICATION_GUID = "30bc1c51-6b67-40d1-8419-045b3a13fa71"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "a8447363-5982-472b-b54a-f40476f50f5b"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "48da0d1d-b942-4088-b553-74000d04db19"';

	// chapter
	//$sql .= ' WHERE PUBLICATION_GUID = "504be1f6-4dbb-4012-944c-1f7303cb105f"';
	
	// book
	//$sql .= ' WHERE PUBLICATION_GUID = "9ba81e54-8180-4ec2-9a92-41f783656562"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "d7630315-e7f2-458b-9028-9223a093fef1"'; // PLos with PDF
	
	//$sql .= ' WHERE PUBLICATION_GUID = "8600f99a-f346-4bd1-80b1-f665b505fef4"'; // JSTOR with thumbnail
	
	//$sql .= ' WHERE PUBLICATION_GUID = "6c225120-a4d8-4784-a920-bb9366a4463c"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "6ac1e238-3c72-48f4-821e-52d4a97f4e49"'; // DOI 10.11646/zootaxa.3735.3.1 returns 301, so replace with 10.11646/zootaxa.3745.3.1
	
	//$sql .= ' WHERE PUBLICATION_GUID = "988dbda3-53c5-4018-9faa-723665cea5cf"'; // PDF
	
	//$sql .= ' WHERE PUBLICATION_GUID = "ed3874b2-2148-421c-a7dd-c3ca3847710e"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Journal of Arachnology" AND jstor IS NOT NULL';	
	//$sql .= ' WHERE issn="0013-9440" AND pdf IS NOT NULL';	
	
	//$sql .= ' WHERE jstor is not null AND thumbnailUrl IS NOT NULL';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Koleopterologische Rundschau. Wien" AND thumbnailUrl IS NOT NULL';		
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="The Coleopterists Bulletin" AND jstor IS NOT NULL';		

	//$sql .= ' WHERE PUB_AUTHOR LIKE "%Patoleta%"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Copeia" AND jstor IS NOT NULL';
	
	//$sql .= ' WHERE PUB_TITLE LIKE "%Paraulopus%"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE LIKE "%Auckland%"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Mosquito Systematics"';
	
	//$sql .= ' WHERE doi="10.1051/parasite/1968432131"';

	//$sql .= ' WHERE updated > "2018-06-16"';
	$sql .= ' WHERE updated > "2018-07-23"';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF) 
	{
		// triples
	
		$triples = array();
	
		$subject_id = 'https://biodiversity.org.au/afd/publication/' . $result->fields['PUBLICATION_GUID'];
	
		$s = '<' . $subject_id . '>';
		
		// generic UUID
		//$triples[] = $s . ' <http://schema.org/identifier> <urn:uuid:' . $result->fields['PUBLICATION_GUID'] . '> .';
		$triples[] = $s . ' <http://schema.org/identifier> "' . $result->fields['PUBLICATION_GUID'] . '" .';
		
	
		if ($result->fields['PUB_AUTHOR'] != '')
		{
			if ($enhance_authors)
			{
				$p = parse_authors($result->fields['PUB_AUTHOR']);
						
				$n = count($p);
				for ($i = 0; $i < $n; $i++)
				{
					// Author
					
					$author_id = '<' . $subject_id . '#creator/' . $p[$i]->id . '>';
				
					// assume our faked id is same for all occurences of author 
					$author_id = '<' . 'https://biodiversity.org.au/afd/publication/' . '#creator/' . $p[$i]->id . '>';				
				
					$triples[] = $author_id . ' <http://schema.org/name> ' . '"' . addcslashes($p[$i]->name, '"') . '"' . ' .';
				
					// name parts
					if (isset($p[$i]->givenName))
					{
						$triples[] = $author_id . ' <http://schema.org/givenName> ' . '"' . addcslashes($p[$i]->givenName, '"') . '"' . ' .';				
					}
					if (isset($p[$i]->familyName))
					{
						$triples[] = $author_id . ' <http://schema.org/familyName> ' . '"' . addcslashes($p[$i]->familyName, '"') . '"' . ' .';				
					}
					
					// assume is a person, need to handle cases where this is not true
					$triples[] = $author_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ' . ' <http://schema.org/' . $p[$i]->type . '>' . ' .';			
				
				
					// ordering 
					$index = $i + 1;
										
					if ($use_role)
					{
						// Role to hold author position
						$role_id = '<' . $subject_id . '#role/' . $index . '>';
						
						$triples[] = $role_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ' . ' <http://schema.org/Role>' . ' .';			
						$triples[] = $role_id . ' <http://schema.org/roleName> "' . $index . '" .';			
					
						$triples[] = $s . ' <http://schema.org/creator> ' .  $role_id . ' .';
						$triples[] = $role_id . ' <http://schema.org/creator> ' .  $author_id . ' .';
					}
					else
					{
						// Author is creator
						$triples[] = $s . ' <http://schema.org/creator> ' .  $author_id . ' .';						
					}
					
					
				}
			
				
		
			}
			else
			{
				$triples[] = $s . ' <http://schema.org/creator> ' . '"' . addcslashes($result->fields['PUB_AUTHOR'], '"') . '" .';
			}
		}
	
		if ($result->fields['PUB_YEAR'] != '')
		{
			$triples[] = $s . ' <http://schema.org/datePublished> ' . '"' . addcslashes($result->fields['PUB_YEAR'], '"') . '" .';
		}
	
		if ($result->fields['PUB_TITLE'] .= '')
		{
			$title = $result->fields['PUB_TITLE'];
			
			// clean
			$title = strip_tags($title);
			
			$title = preg_replace('/\.$/', '', $title);
			$title = preg_replace('/\n/', '', $title);
			$title = preg_replace('/\r/', '', $title);
		
		
			$triples[] = $s . ' <http://schema.org/name> ' . '"' . addcslashes($title, '"\\') . '" .';
		}
	
		if ($result->fields['PUB_PAGES'] != '')
		{
			$triples[] = $s . ' <http://schema.org/pagination> ' . '"' . addcslashes($result->fields['PUB_PAGES'], '"') . '" .';
		}
	
		/*
		if ($result->fields['PUB_PARENT_JOURNAL_TITLE'] != '')
		{
			$triples[] = $s . ' <http://prismstandard.org/namespaces/basic/2.1/publicationName> ' . '"' . addcslashes($result->fields['PUB_PARENT_JOURNAL_TITLE'], '"') . '" .';
		}
		*/
	
		if ($result->fields['PARENT_PUBLICATION_GUID'] != '')
		{
			$container_id = '<https://biodiversity.org.au/afd/publication/' . $result->fields['PARENT_PUBLICATION_GUID'] . '>';
			$triples[] = $s . ' <http://schema.org/isPartOf> ' . $container_id . ' .';
			
			// generic UUID
			//$triples[] = $container_id . ' <http://schema.org/identifier> <urn:uuid:' . $result->fields['PARENT_PUBLICATION_GUID'] . '> .';
			$triples[] = $container_id . ' <http://schema.org/identifier> "' . $result->fields['PARENT_PUBLICATION_GUID'] . '" .';
			
			// Journal
			if ($result->fields['PUB_PARENT_JOURNAL_TITLE'] != '')
			{
				$triples[] = $container_id . ' <http://schema.org/name> ' .  '"' . addcslashes($result->fields['PUB_PARENT_JOURNAL_TITLE'], '"') . '" .';
				$triples[] = $container_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/Periodical> .';
			
				if ($enhance_metadata)
				{
					if ($result->fields['issn'] != '')
					{
						$triples[] = $container_id . ' <http://schema.org/issn> ' .  '"' . addcslashes($result->fields['issn'], '"') . '" .';
						//$triples[] = $container_id . ' <http://schema.org/sameAs> <http://worldcat.org/issn/' . $result->fields['issn'] . '> .';
						$triples[] = $container_id . ' <http://schema.org/sameAs> "http://worldcat.org/issn/' . $result->fields['issn'] . '" .';
					}
			
				}
			}
		
			// Book that contains this chapter
			if ($result->fields['PUB_PARENT_BOOK_TITLE'] != '')
			{
				$triples[] = $container_id . ' <http://schema.org/name> ' .  '"' . addcslashes($result->fields['PUB_PARENT_BOOK_TITLE'], '"') . '" .';
				$triples[] = $container_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/Book> .';
										
				if ($enhance_identifiers)
				{
					// ISBN
					if ($result->fields['isbn'] != '')
					{
						$triples[] = $container_id . ' <http://schema.org/isbn> ' . '"' . addcslashes($result->fields['isbn'], '"') . '"' . '.';
						$triples[] = $container_id . ' <http://schema.org/sameAs> "http://worldcat.org/isbn/' . $result->fields['isbn'] . '" .';
					}	
				}
			}
		
		}	
	
		if ($result->fields['PUB_PUBLISHER'] != '')
		{
			$triples[] = $s . ' <http://schema.org/publisher> ' . '"' . addcslashes($result->fields['PUB_PUBLISHER'], '"') . '" .';
		}
	
		if ($enhance_metadata)
		{
			switch ($result->fields['PUB_TYPE'])
			{
				case 'Book':
					break;
				
				case 'Article in Journal':
				default:
					if ($result->fields['volume'] != '')
					{
						$triples[] = $s . ' <http://schema.org/volume> ' . '"' . addcslashes($result->fields['volume'], '"') . '" .';
					}
					if ($result->fields['issue'] != '')
					{
						$triples[] = $s . ' <http://schema.org/issueNumber> ' . '"' . addcslashes($result->fields['issue'], '"') . '" .';
					}
					if ($result->fields['spage'] != '')
					{
						$triples[] = $s . ' <http://schema.org/pageStart> ' . '"' . addcslashes($result->fields['spage'], '"') . '" .';
					}
					if ($result->fields['epage'] != '')
					{
						$triples[] = $s . ' <http://schema.org/pageEnd> ' . '"' . addcslashes($result->fields['epage'], '"') . '" .';
					}
					break;
			}		
		}
	
		if ($enhance_identifiers)
		{
			switch ($result->fields['PUB_TYPE'])
			{
				case 'Book':
					// ISBN
					if ($result->fields['isbn'] != '')
					{
						$triples[] = $s . ' <http://schema.org/isbn> ' . '"' . addcslashes($result->fields['isbn'], '"') . '"' . '.';
						$triples[] = $s . ' <http://schema.org/sameAs> "http://worldcat.org/isbn/' . $result->fields['isbn'] . '" .';
					}					
					break;
				
				case 'Article in Journal':
				default:
					// BioStor
					if ($result->fields['biostor'] != '')
					{
						$identifier_id = '<' . $subject_id . '#biostor' . '>';
		
						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"biostor"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes($result->fields['biostor'], '"') . '"' . '.';
			
						//$triples[] = $s . ' <http://schema.org/sameAs> ' . '<https://hdl.handle.net/' . $result->fields['handle'] . '> ' . '. ';
						$triples[] = $s . ' <http://schema.org/sameAs> ' . '"http://biostor.org/' . $result->fields['biostor'] . '" ' . '. ';
					}	
		
					// DOI
					if ($result->fields['doi'] != '')
					{
						$identifier_id = '<' . $subject_id . '#doi' . '>';
		
						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"doi"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes($result->fields['doi'], '"') . '"' . '.';
			
						//$triples[] = $s . ' <http://schema.org/sameAs> ' . '<https://doi.org/' . $result->fields['doi'] . '> ' . '. ';
						$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://doi.org/' . $result->fields['doi'] . '" ' . '. ';
					}
			
					// Handle
					if ($result->fields['handle'] != '')
					{
						$identifier_id = '<' . $subject_id . '#handle' . '>';
		
						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"handle"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes($result->fields['handle'], '"') . '"' . '.';
			
						//$triples[] = $s . ' <http://schema.org/sameAs> ' . '<https://hdl.handle.net/' . $result->fields['handle'] . '> ' . '. ';
						$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://hdl.handle.net/' . $result->fields['handle'] . '" ' . '. ';			
					}	
			
					// JSTOR
					if ($result->fields['jstor'] != '')
					{
						$identifier_id = '<' . $subject_id . '#jstor' . '>';
		
						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"jstor"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes($result->fields['jstor'], '"') . '"' . '.';
			
						//$triples[] = $s . ' <http://schema.org/sameAs> ' . '<https://www.jstor.org/stable/' . $result->fields['jstor'] . '> ' . '. ';
						$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://www.jstor.org/stable/' . $result->fields['jstor'] . '" ' . '. ';						
					}		
						
					// PMID
					if ($result->fields['pmid'] != '')
					{
						$identifier_id = '<' . $subject_id . '#pmid' . '>';
		
						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"pmid"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes($result->fields['pmid'], '"') . '"' . '.';
			
						$triples[] = $s . ' <http://schema.org/sameAs> "https://www.ncbi.nlm.nih.gov/pubmed/' . $result->fields['pmid'] . '" ' . '. ';			
					}		
		
					// SICI-style identifier to help automate citation linking	
					$sici = array();
				
					if ($result->fields['issn'] != '')
					{
						$sici[] = $result->fields['issn'];
			
						if ($result->fields['PUB_YEAR'] != '')
						{
							$sici[] = '(' . $result->fields['PUB_YEAR'] . ')';
						}										

						if ($result->fields['volume'] != '')
						{
							$sici[] = $result->fields['volume'];
						}

						if ($result->fields['spage'] != '')
						{
							$sici[] = '<' . $result->fields['spage'] . '>';
						}
		
						if (count($sici) == 4)
						{
							$identifier_id = '<' . $subject_id . '#sici' . '>';

							$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
							$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
							$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"sici"' . '.';
							$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . addcslashes(join('', $sici), '"') . '"' . '.';		
						}
					}
		
					// URL
					if ($result->fields['url'] != '')
					{
						$triples[] = $s . ' <http://schema.org/url> ' . '"' . addcslashes($result->fields['url'], '"') . '" .';

						// sameAs link?
						$triples[] = $s . ' <http://schema.org/sameAs> ' . '"' . addcslashes($result->fields['url'], '"') . '" .';				
					}	
		
					// Zenodo
					if ($result->fields['zenodo'] != '')
					{
						$identifier_id = '<' . $subject_id . '#zenodo' . '>';

						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"zenodo"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . $result->fields['zenodo'] . '"' . '.';
				
						// sameAs link?
						$triples[] = $s . ' <http://schema.org/sameAs> "https://zenodo.org/record/' . $result->fields['zenodo'] . '" .';				
					}	
					
					
					// Zoobank
					if ($result->fields['zoobank'] != '')
					{
						$identifier_id = '<' . $subject_id . '#zoobank' . '>';

						$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
						$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
						$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"zoobank"' . '.';
						$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"urn:lsid:zoobank.org:pub:' . $result->fields['zoobank'] . '"' . '.';
						
						// uuid
						$triples[] = $s . ' <http://schema.org/identifier> "' . $result->fields['zoobank'] . '" .';	
				
						// sameAs link?
						$triples[] = $s . ' <http://schema.org/sameAs> "http://zoobank.org/References/' . $result->fields['zoobank'] . '" .';				
					}	
					
					break;
			}
		
		}
	
		if ($enhance_pdf)
		{
			if ($result->fields['pdf'] != '')
			{
				$pdf_id = '<' . $subject_id . '#pdf' . '>';
		
				$triples[] = $s . ' <http://schema.org/encoding> ' . $pdf_id . ' .';

				// PDF
				$triples[] = $pdf_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/MediaObject> .';
				$triples[] = $pdf_id . ' <http://schema.org/contentUrl> ' . '"' . addcslashes($result->fields['pdf'], '"') . '"' . '.';
				$triples[] = $pdf_id . ' <http://schema.org/fileFormat> "application/pdf" .';
			
				$obj = null;
				
				// SHA1 and page images
				$sha1 = '';
				$obj = get_pdf_details($result->fields['pdf']);
			
				if ($obj)
				{
					$sha1 = $obj->sha1;
					$triples[] = $pdf_id . ' <http://id.loc.gov/vocabulary/preservation/cryptographicHashFunctions/sha1> ' . '"' . addcslashes($obj->sha1, '"') . '"' . ' .';			

					if ($enhance_pdf_as_images)
					{
						$images = get_pdf_images($sha1);	
					
						// Include page images in RDF (do we want to do this?)
						for ($i = 1; $i <= $images->pages; $i++)
						{
							// image
							$image_id = '<' . $subject_id . '/page#' . $i . '>';
							$triples[] = $image_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/ImageObject> .';

							// order 
							$triples[] = $image_id . ' <http://schema.org/position> ' . '"' . addcslashes($i, '"') . '"' . ' .';

							// URLs to images
							$triples[] = $image_id . ' <http://schema.org/contentUrl> ' . '"' . addcslashes('http://bionames.org/bionames-archive/documentcloud/pages/' . $sha1 . '/' . $i . '-large', '"') . '"' . ' .';
							$triples[] = $image_id . ' <http://schema.org/thumbnailUrl> ' . '"' . addcslashes('http://bionames.org/bionames-archive/documentcloud/pages/' . $sha1 . '/' . $i . '-small', '"') . '"' . ' .';

							// page image is part of the work
							$triples[] = $s . ' <http://schema.org/hasPart> ' .  $image_id . ' .';						
				
						}
					}
				}
			}	
		}
		
		// Thumbnail as URL to GitHub-hosted thumbnail
		// See https://rawgit.com
		if ($enhance_thumbnails)
		{
			if ($result->fields['thumbnailUrl'] != '')
			{
				$prefix = 'https://cdn.rawgit.com/rdmpage/oz-afd-export/2dcd904e/thumbnails/';
				$prefix = 'https://cdn.rawgit.com/rdmpage/oz-afd-export/master/thumbnails/';

				$thumbnailUrl = $prefix . $result->fields['thumbnailUrl'];
				
				$triples[] = $s . ' <http://schema.org/thumbnailUrl> "' .  $thumbnailUrl . '" .';				
			}
		}		
	
		if ($result->fields['PUB_TYPE'] != '')
		{
			switch ($result->fields['PUB_TYPE'])
			{
				case 'Article in Journal':
					$type = '<http://schema.org/ScholarlyArticle>';
					break;
				
				case 'Book':
					$type = '<http://schema.org/Book>';
					break;
			
				case 'Chapter in Book':
					$type = '<http://schema.org/Chapter>';
					break;

				case 'Miscellaneous':
					$type = '<http://schema.org/CreativeWork>';
					break;
				
				case 'Section in Article':
					$type = '<http://schema.org/CreativeWork>';
					break;
				
				case 'This Work':
					$type = '<http://schema.org/CreativeWork>';
					break;
				
				case 'URL':
					$type = '<http://schema.org/WebSite>';
					break;
				
				default:
					$type = '<http://schema.org/CreativeWork>';
					break;
			}
			$triples[] = $s . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ' . $type . ' .';
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

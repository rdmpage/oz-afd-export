<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Publications to triples

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');
require_once (dirname(__FILE__) . '/parse_authors.php');
require_once (dirname(__FILE__) . '/elastic/elastic.php');


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 


//----------------------------------------------------------------------------------------
function taxa_in_work($guid)
{
	global $db;
	
	$names = array();

	$sql = 'SELECT *	
	FROM afd
	WHERE PUBLICATION_GUID ="' . $guid . '"';

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF) 
	{
		if ($result->fields['NAME_TYPE'] == 'Common Name')
		{
			// common
			$names[] = $result->fields['NAMES_VARIOUS'];		
		}
		else
		{
			$names[] = $result->fields['SCIENTIFIC_NAME'];
		}		
		
		$result->MoveNext();
	}
	
	return $names;
}


//----------------------------------------------------------------------------------------


$page = 1000;
$offset = 0;

$done = false;

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	// A specific journal or publication, otherwise we are getting everything
	//$sql .= ' WHERE PUBLICATION_GUID = "bf327b53-20db-4983-9046-a32234e7009a"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "a8447363-5982-472b-b54a-f40476f50f5b"';
	
	//$sql .= ' WHERE PUB_TITLE LIKE "%Paraulopus%"';
	
	//$sql .= ' WHERE PUB_AUTHOR LIKE "%Ślipiński%"';
	
	//$sql .= ' WHERE updated > "2018-06-01"';
	//$sql .= ' WHERE updated > "2018-06-26"';
	//$sql .= ' WHERE updated > "2018-07-05"';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		// Elastic
		
		$doc = new stdclass;
		$doc->type = "work";

		$doc->search_result_data = new stdclass;
		
		$doc->search_result_data->description = '';
		$description = [];

		$doc->search_data = new stdclass;

		$doc->search_data->fulltext = '';
		$doc->search_data->fulltext_boosted = '';
		
		$fulltext = array();
		$fulltext_boosted = array();
		
		$doc->id = 'https://biodiversity.org.au/afd/publication/' . $result->fields['PUBLICATION_GUID'];
		
		if ($result->fields['PUB_TITLE'] .= '')
		{
			$title = $result->fields['PUB_TITLE'];
			
			// clean
			$title = strip_tags($title);
			
			$title = preg_replace('/\n/', '', $title);
			$title = preg_replace('/\r/', '', $title);
				
			$doc->search_result_data->name = $title;
			
			$fulltext[] = $title;
			$fulltext_boosted[] = $title;
		}
		
	
		if ($result->fields['PUB_AUTHOR'] != '')
		{
			$parsed_authors = parse_authors($result->fields['PUB_AUTHOR']);
			
			$doc->search_result_data->creator = array();
			
			$max_authors = 3;
			$author_count = 0;
			
			foreach ($parsed_authors as $author)
			{
				// For result
				$doc->search_result_data->creator[] =  $author->name;
				
				if ($author_count < $max_authors)
				{
					$description[] = $author->name;
				}
				$author_count++;
				
				// For search
				$doc->search_data->creator[] =  $author->name;					
				$fulltext[] = $author->name;
			}
		}
					
		if ($result->fields['PUB_YEAR'] != '')
		{
			// For result
			$doc->search_result_data->year = $result->fields['PUB_YEAR'];
			$description[] = $result->fields['PUB_YEAR'];
			
			// For search
			$doc->search_data->year = (Integer)$result->fields['PUB_YEAR'];
			$fulltext[] = $result->fields['PUB_YEAR'];
		}
			
		// DOI
		if ($result->fields['doi'] != '')
		{
			// For result
			$doc->search_result_data->doi = $result->fields['doi'];
			
			// For search
			$fulltext[] = $result->fields['doi'];
		}
		
		if ($result->fields['PUB_PARENT_JOURNAL_TITLE'] != '')
		{
			// For result
			$description[] = $result->fields['PUB_PARENT_JOURNAL_TITLE'];
			
			// For search
			$doc->search_data->container = array($result->fields['PUB_PARENT_JOURNAL_TITLE']);
			$fulltext[] = $result->fields['PUB_PARENT_JOURNAL_TITLE'];
		}

		if ($result->fields['PUB_PARENT_BOOK_TITLE'] != '')
		{
			// For result
			$description[] = $result->fields['PUB_PARENT_BOOK_TITLE'];
			
			// For search
			$doc->search_data->container = array($result->fields['PUB_PARENT_BOOK_TITLE']);
			$fulltext[] = $result->fields['PUB_PARENT_BOOK_TITLE'];
		}		
		
		if ($result->fields['volume'] != '')
		{
			// For result
			$description[] = $result->fields['volume'];
			
			// For search
			$fulltext[] = $result->fields['volume'];
		}
		
		if ($result->fields['issue'] != '')
		{
			// For result
			$description[] = $result->fields['issue'];
			
			// For search
			$fulltext[] = $result->fields['issue'];
		}
		
		if ($result->fields['PUB_PAGES'] != '')
		{
			// For result
			$description[] = $result->fields['PUB_PAGES'];
			
			// For search
			$fulltext[] = $result->fields['PUB_PAGES'];
		}
		
	
		if ($result->fields['PUB_TYPE'] != '')
		{
			switch ($result->fields['PUB_TYPE'])
			{
				case 'Article in Journal':
					$type = 'ScholarlyArticle';
					break;
				
				case 'Book':
					$type = 'Book';
					break;
			
				case 'Chapter in Book':
					$type = 'Chapter';
					break;

				case 'Miscellaneous':
					$type = 'CreativeWork';
					break;
				
				case 'Section in Article':
					$type = 'CreativeWork';
					break;
				
				case 'This Work':
					$type = 'CreativeWork';
					break;
				
				case 'URL':
					$type = 'WebSite';
					break;
				
				default:
					$type = 'CreativeWork';
					break;
			}

			// For result
			$doc->search_result_data->type = $type;
			
			// For search
			$doc->search_data->type = array($type);
		}
		
		
		//--------------------------------------------------------------------------------
		$names = taxa_in_work($result->fields['PUBLICATION_GUID']);
		
		foreach ($names as $name)
		{
			$fulltext[] = $name;
		}
		
		// Summarise		
		$doc->search_result_data->description = join(' ', $description);

		$doc->search_data->fulltext = join(' ', $fulltext);
		$doc->search_data->fulltext_boosted = join(' ', $fulltext_boosted);
		
		//--------------------------------------------------------------------------------
		// Store in Elastic search	
		
		// ID is GUID	
		$id = $result->fields['PUBLICATION_GUID'];
		
		$elastic_doc = new stdclass;
		$elastic_doc->doc = $doc;
		$elastic_doc->doc_as_upsert = true;

		print_r($elastic_doc);

		// PUT for first time, POST for update
		$elastic->send('PUT',  $elastic_doc->doc->search_result_data->type . '/' . urlencode($id), json_encode($elastic_doc));				
		//$elastic->send('POST',  $elastic_doc->doc->search_result_data->type . '/' . urlencode($id) . '/_update', json_encode($elastic_doc));	

	
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


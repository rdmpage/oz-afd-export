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


$filename = 'bibliography.jsonl';
$basename = 'bibliography';

$count = 0;
$total = 0;

$chunksize = 10000;

$rows = array();


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
	//$sql .= ' WHERE updated > "2018-08-08"';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		// Elastic
		
		$doc = new stdclass;

		$doc->search_result_data = new stdclass;
		
		$doc->search_result_data->description = '';
		$description = [];

		$doc->search_data = new stdclass;

		$doc->search_data->fulltext = '';
		$doc->search_data->fulltext_boosted = '';
		
		$fulltext = array();
		$fulltext_boosted = array();
		
		$doc->search_result_data->id = 'https://biodiversity.org.au/afd/publication/' . $result->fields['PUBLICATION_GUID'];
		
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
				$fulltext[] 		= $author->name;
				$fulltext_boosted[] = $author->name;
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
		
		// thumbnail
		if ($result->fields['thumbnailUrl'] != '')
		{
			$prefix = 'https://cdn.rawgit.com/rdmpage/oz-afd-export/master/thumbnails/';

			$thumbnailUrl = $prefix . $result->fields['thumbnailUrl'];
			
			$doc->search_result_data->thumbnailUrl = $thumbnailUrl;
		}		
		
		//--------------------------------------------------------------------------------
		// Add names?
		/*
		$names = taxa_in_work($result->fields['PUBLICATION_GUID']);
		
		foreach ($names as $name)
		{
			$fulltext[] = $name;
		}
		*/
		
		// Summarise		
		$doc->search_result_data->description = join(' ', $description);

		$doc->search_data->fulltext = join(' ', $fulltext);
		$doc->search_data->fulltext_boosted = join(' ', $fulltext_boosted);
		
		//--------------------------------------------------------------------------------
		// Store in Elastic search	
		
		// ID is GUID	
		$id = $result->fields['PUBLICATION_GUID'];
		
		// Action
		$meta = new stdclass;
		$meta->index = new stdclass;
		$meta->index->_index = 'ala';	
		$meta->index->_id = $id;
		
		// v. 6		
		$meta->index->_type = '_doc';
		
		// Earlier versions
		//$meta->index->_type = 'thing';
		
		// Request				
		$rows[] = json_encode($meta);
		$rows[] = json_encode($doc);
		
		$count++;
		$total++;
	
		if ($count % $chunksize == 0)
		{
			$output_filename = $basename . '-' . $total . '.json';
		
			$chunk_files[] = $output_filename;
		
			file_put_contents($output_filename, join("\n", $rows) . "\n");
		
			$count = 0;
			$rows = array();
		
			
			if ($total > 5000)
			{
				//$done = true;
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

// Left over?
if (count($rows) > 0)
{
	$output_filename = $basename . '-' . $total . '.json';
	
	$chunk_files[] = $output_filename;
	
	file_put_contents($output_filename, join("\n", $rows) . "\n");
}

echo "--- curl upload.sh ---\n";
$curl = "#!/bin/sh\n\n";
foreach ($chunk_files as $filename)
{
	$curl .= "echo '$filename'\n";
	
	$url = 'http://130.209.46.63/_bulk';	

	$url = 'http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk';
	
	// old
	//$curl .= "curl $url -XPOST --data-binary '@$filename'  --progress-bar | tee /dev/null\n";
	
	// 6
	$curl .= "curl $url -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@$filename'  --progress-bar | tee /dev/null\n";
		

	$curl .= "echo ''\n";
}

file_put_contents(dirname(__FILE__) . '/upload-elastic.sh', $curl);


?>


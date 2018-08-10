<?php

// Generate thumbnails for publications

error_reporting(E_ALL ^ E_DEPRECATED);


require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

$thumbnail_width = 100;

//----------------------------------------------------------------------------------------
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
		if (0)
		{
			// Look up
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
	}
		
	return $obj;
}

//----------------------------------------------------------------------------------------
function get_jstor_thumbnail($jstor, $base_filename)
{	
	global $thumbnail_width;
	
	$thumbnail_filename = '';
	
	$extension = 'gif';
	
	// GIF
	$filename = '/Users/rpage/Development/jstor-thumbnails-o/' . $jstor . '.' . $extension;

	// if no GIF try JPEG
	if (!file_exists($filename))
	{
		$extension = 'jpg';
		$filename = dirname(dirname(__FILE__)) . '/jstor_thumbnails/' . $jstor. '.' . $extension;
	}

	if (!file_exists($filename))
	{
		$extension = 'jpeg';
		$filename = dirname(dirname(__FILE__)) . '/jstor_thumbnails/' . $jstor. '.' . $extension;
	}
	
	if (file_exists($filename))
	{			
		$image = file_get_contents($filename);
		$thumbnail_filename = $base_filename . '.' . $extension;
	
		file_put_contents($thumbnail_filename, $image);
	
		// resize
		$command = 'mogrify -resize ' . $thumbnail_width . 'x ' . $thumbnail_filename;
		//echo $command . "\n";

		system($command);
		
		$ok = true;
	}

	return $thumbnail_filename;
}

//----------------------------------------------------------------------------------------
function get_biostor_thumbnail($biostor, $base_filename)
{	
	global $thumbnail_width;
	
	$thumbnail_filename = '';
	
	$url = 'http://biostor.org/documentcloud/biostor/' . $biostor . '/pages/1-small';

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
		if ($info['http_code'] == 200)
		{	
	
			$thumbnail_filename = $base_filename . '.jpg';
			file_put_contents($thumbnail_filename, $data);
		
			// resize
			$command = 'mogrify -resize ' . $thumbnail_width . 'x ' . $thumbnail_filename;
			//echo $command . "\n";

			system($command);
		}
		else
		{
			echo "-- HTTP " . $info['http_code'] . " $biostor\n";
			
		}
	}

		
	return $thumbnail_filename;
}

//----------------------------------------------------------------------------------------
function get_bionames_thumbnail($sha1, $base_filename, $page = 1)
{	
	global $thumbnail_width;
	
	$thumbnail_filename = '';
	
	$url = 'http://bionames.org/bionames-archive/documentcloud/pages/' . $sha1 . '/' . $page . '-small';
	
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
		if ($info['http_code'] == 200)
		{	
			$thumbnail_filename = $base_filename . '.png';
			file_put_contents($thumbnail_filename, $data);
		
			// resize
			$command = 'mogrify -resize ' . $thumbnail_width . 'x ' . $thumbnail_filename;
			//echo $command . "\n";

			system($command);
		}
		else
		{
			echo "HTTP " . $info['http_code'] . " $sha1\n";
			//exit();
		}
	}

		
	return $thumbnail_filename;
}


//----------------------------------------------------------------------------------------
function get_doi_thumbnail($doi, $base_filename)
{
	global $db;
	
	global $thumbnail_width;
	
	$thumbnail_filename = '';

	$sql = "SELECT * FROM doi_thumbnails WHERE doi = " . $db->qstr($doi) . " LIMIT 1;";
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	
	if ($result->NumRows() == 1)
	{
		$thumbnail = $result->fields['path'];
		
		$filename = '/Users/rpage/Dropbox/BibScrapper/thumbnails/thumbnails/' . $thumbnail;
		
		$image_type = exif_imagetype($filename);
		switch ($image_type)
		{
			case IMAGETYPE_GIF:
				$mime_type = 'image/gif';
				$extension = 'gif';
				break;
			case IMAGETYPE_JPEG:
				$mime_type = 'image/jpg';
				$extension = 'jpg';
				break;
			case IMAGETYPE_PNG:
				$mime_type = 'image/png';
				$extension = 'png';
				break;
			case IMAGETYPE_TIFF_II:
			case IMAGETYPE_TIFF_MM:
				$mime_type = 'image/tif';
				$extension = 'tif';
				break;
			default:
				$mime_type = 'image/gif';
				$extension = 'gif';
				break;
		}

		$image = file_get_contents($filename);
		$thumbnail_filename = $base_filename . '.' . $extension;
	
		file_put_contents($thumbnail_filename, $image);
	
		// resize
		$command = 'mogrify -resize ' . $thumbnail_width . 'x ' . $thumbnail_filename;
		//echo $command . "\n";

		system($command);
	}
		
	return $thumbnail_filename;		
}


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' , 'afd');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->EXECUTE("set names 'utf8'"); 


$base_dir = dirname(__FILE__) . '/thumbnails';

$page = 1000;
$offset = 0;

$done = false;

$force = true;
$force = false;

while (!$done)
{
	$sql = 'SELECT DISTINCT *
	FROM bibliography';
	
	// A specific journal or publication, otherwise we are getting everything
	//$sql .= ' WHERE PUBLICATION_GUID = "30bc1c51-6b67-40d1-8419-045b3a13fa71"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "a8447363-5982-472b-b54a-f40476f50f5b"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "7f6c7ed5-4a35-40d2-aa57-05577e35ec23"';

	// chapter
	//$sql .= ' WHERE PUBLICATION_GUID = "504be1f6-4dbb-4012-944c-1f7303cb105f"';
	
	// book
	//$sql .= ' WHERE PUBLICATION_GUID = "9ba81e54-8180-4ec2-9a92-41f783656562"';
	
	//$sql .= ' WHERE PUBLICATION_GUID = "d7630315-e7f2-458b-9028-9223a093fef1"'; // PLos with PDF
	
	//$sql .= ' WHERE PUBLICATION_GUID = "8600f99a-f346-4bd1-80b1-f665b505fef4"'; // JSTOR with thumbnail
	
	//$sql .= ' WHERE PUBLICATION_GUID = "ff6e5cf7-2ff1-43e7-96ba-63936163890d"'; // Zootaxa PDF thumbnail
	
	//$sql .= ' WHERE PUBLICATION_GUID = "79e2f672-016f-4450-a814-aefaa52ec493"'; // BioStor
	
	//$sql .= ' WHERE PUBLICATION_GUID = "617908ee-5531-42e5-a667-ef6f113a3749"';
			
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE LIKE "%Auckland%"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Papers and Proceedings of the Royal Society of Tasmania" AND pdf IS NOT NULL';	

	// $sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Zoologische Mededelingen (Leiden)" AND pdf IS NOT NULL';	
	//$sql .= ' WHERE issn="0013-9440" AND pdf IS NOT NULL';	
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Annals and Magazine of Natural History"';
	
	//$sql .= ' AND series=13';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Records of the Western Australian Museum"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Journal of Parasitology" AND doi LIKE "10.2307/%"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Proceedings of the Linnean Society of New South Wales"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Revue Suisse de Zoologie"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Bulletin of the British Museum (Natural History) Zoology"';
	$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Stapfia"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE LIKE "%Beagle%"';

	//$sql .= ' AND biostor IS NOT NULL';
	$sql .= ' AND pdf IS NOT NULL';
	//$sql .= ' AND jstor IS NOT NULL';
	
	
	//$sql .= ' WHERE issn="0024-1652"';
	
	//$sql .= ' WHERE doi LIKE "10.2307/%" AND thumbnailUrl IS NULL';
	
	//$sql .= ' WHERE biostor IS NOT NULL';
	//$sql .= ' WHERE jstor IS NOT NULL';	
	
	//$sql .= ' WHERE zootaxa_thumbnail_pdf IS NOT NULL';	
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Proceedings of the Royal Society of Victoria" AND pdf IS NOT NULL';		
//	$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Papers and Proceedings of the Royal Society of Tasmania" AND pdf IS NOT NULL';
//	$sql .= ' WHERE PUBLICATION_GUID = "3845aaab-e4bd-4c07-b398-c6ea4532f3d2"';	
	
	//$sql .= ' AND pdf IS NOT NULL';		
	$sql .= ' AND thumbnailUrl IS NULL';		
	
	//$sql .= ' WHERE updated > "2018-06-16"';
	//$sql .= ' WHERE updated > "2018-07-16"';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;
	
	//echo $sql . "\n";

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);


	while (!$result->EOF) 
	{
		// Can we get a thumbnail?		
		
		// 1. Thumbnail id and folders to hold image
		
		$uuid = $result->fields['PUBLICATION_GUID'];
		
		// Follow ALA and use end characters of UUID (handles case where first characters in UUID are the same )
		$parts = str_split($uuid);
		$n = strlen($uuid);
		
		$dir = $base_dir . '/' . $parts[$n - 1];
		if (!file_exists($dir))
		{
			$oldumask = umask(0); 
			mkdir($dir, 0777);
			umask($oldumask);
		}
		
		$dir = $dir . '/' . $parts[$n - 2];
		if (!file_exists($dir))
		{
			$oldumask = umask(0); 
			mkdir($dir, 0777);
			umask($oldumask);
		}

		$base_filename = $dir . '/' . $uuid;
		$thumbnail_filename = $base_filename;
		
		// 2. do we have this thumbnail already?
		$extension = 'gif';
	
		// do we have it already?
		$thumbnail_filename = $base_filename . '.' . $extension;
	
		// if no GIF try JPEG
		if (!file_exists($thumbnail_filename))
		{
			$extension = 'jpg';
			$thumbnail_filename = $base_filename . '.' . $extension;
		}

		if (!file_exists($thumbnail_filename))
		{
			$extension = 'jpeg';
			$thumbnail_filename = $base_filename . '.' . $extension;
		}

		if (!file_exists($thumbnail_filename))
		{
			$extension = 'png';
			$thumbnail_filename = $base_filename . '.' . $extension;
		}
	
		if (file_exists($thumbnail_filename) && !$force)
		{
			echo "-- Done\n";
			echo 'UPDATE bibliography SET thumbnailUrl="' . str_replace($base_dir . '/', '', $thumbnail_filename) . '" WHERE PUBLICATION_GUID="' . $result->fields['PUBLICATION_GUID'] .'";' . "\n";
		}
		else
		{
			// Go fetch
			$thumbnail_filename = '';
			
			if ($thumbnail_filename == '')
			{
				// JSTOR
				if ($result->fields['jstor'] != '')
				{
					$thumbnail_filename = get_jstor_thumbnail($result->fields['jstor'], $base_filename);			
				}
			}
			
			if ($thumbnail_filename == '')
			{
				// JSTOR DOI
				if ($result->fields['doi'] != '')
				{
					if (preg_match('/10.2307\/(?<id>\d+)/', $result->fields['doi'], $m))
					{
						$thumbnail_filename = get_jstor_thumbnail($m['id'], $base_filename);			
					}
				}
			}
			
			// DOI thumbnails from BioNames
			if ($thumbnail_filename == '')
			{
				if ($result->fields['doi'] != '')
				{
					$thumbnail_filename = get_doi_thumbnail($result->fields['doi'], $base_filename);			
				}
			}
			
			
			// Zootaxa-specific code to handle factvwe may have PDF preview that we can use
			if ($thumbnail_filename == '')
			{
				// PDF
				if ($result->fields['zootaxa_thumbnail_pdf'] != '')
				{
					$sha1 = '';
					$obj = get_pdf_details($result->fields['zootaxa_thumbnail_pdf']);
								
					if ($obj)
					{
					
						if (isset($obj->sha1))
						{
							// By default take thumbnail of first page, but
							// some repositories insert a cover page, can skip those
							// by setting $page to the page number (1-offset) where the work starts.
							$page = 1;
											
							$thumbnail_filename = get_bionames_thumbnail($obj->sha1, $base_filename, $page);			
						}
					}
				}
			}			
			
			if ($thumbnail_filename == '')
			{
				// PDF
				if ($result->fields['pdf'] != '')
				{
					$sha1 = '';
					$obj = get_pdf_details($result->fields['pdf']);
				
					if ($obj)
					{
					
						if (isset($obj->sha1))
						{
							// By default take thumbnail of first page, but
							// some repositories insert a cover page, can skip those
							// by setting $page to the page number (1-offset) where the work starts.
							$page = 1;
						
							switch ($result->fields['PUB_PARENT_JOURNAL_TITLE'])
							{
								case 'Acarologia':
								case 'Publications of the Seto Marine Biological Laboratory':
								//case 'Records of the Australian Museum':
									$page = 2;
									break;
								
								default:
									$page = 1;
									break;						
							}
					
							$thumbnail_filename = get_bionames_thumbnail($obj->sha1, $base_filename, $page);			
						}
					}
				}
			}
			
			// BioStor is last of all
			if ($thumbnail_filename == '')
			{
				// BioStor
				if ($result->fields['biostor'] != '')
				{
					$thumbnail_filename = get_biostor_thumbnail($result->fields['biostor'], $base_filename);			
				}
			}
			
			
			
			if ($thumbnail_filename != '')
			{
				echo "-- Added\n";				
				echo 'UPDATE bibliography SET thumbnailUrl="' . str_replace($base_dir . '/', '', $thumbnail_filename) . '" WHERE PUBLICATION_GUID="' . $result->fields['PUBLICATION_GUID'] .'";' . "\n";
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

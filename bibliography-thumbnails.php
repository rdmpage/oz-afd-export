<?php

// Generate thumbnails for publications

error_reporting(E_ALL ^ E_DEPRECATED);


require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

$thumbnail_width = 100;

//--------------------------------------------------------------------------------------------------
function get_pdf_details($pdf)
{
	$obj = null;
	
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
			echo "HTTP " . $info['http_code'] . "\n";
			exit();
		}
	}

		
	return $thumbnail_filename;
}

//----------------------------------------------------------------------------------------
function get_bionames_thumbnail($sha1, $base_filename)
{	
	global $thumbnail_width;
	
	$thumbnail_filename = '';
	
	$url = 'http://bionames.org/bionames-archive/documentcloud/pages/' . $sha1 . '/1-small';
	
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
			exit();
		}
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
			
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE LIKE "%Auckland%"';
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Copeia" AND jstor IS NOT NULL';	

	//$sql .= ' WHERE biostor IS NOT NULL';	
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Peckhamia" AND pdf IS NOT NULL';		
	$sql .= ' WHERE pdf IS NOT NULL';		
	
	//$sql .= ' WHERE updated > "2018-06-16"';
	//$sql .= ' WHERE updated > "2018-07-16"';
	
	$sql .= ' LIMIT ' . $page . ' OFFSET ' . $offset;

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
	
		if (file_exists($thumbnail_filename))
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
				// BioStor
				if ($result->fields['biostor'] != '')
				{
					$thumbnail_filename = get_biostor_thumbnail($result->fields['biostor'], $base_filename);			
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
						$thumbnail_filename = get_bionames_thumbnail($obj->sha1, $base_filename);			
					}
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

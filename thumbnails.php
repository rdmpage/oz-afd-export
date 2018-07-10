<?php

// Get thumbnails from various sources

//----------------------------------------------------------------------------------------
function get_jstor_thumbnail($jstor)
{	
	$thumbnail = '';
	
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
		$image_type = exif_imagetype($filename);
		switch ($image_type)
		{
			case IMAGETYPE_GIF:
				$mime_type = 'image/gif';
				break;
			case IMAGETYPE_JPEG:
				$mime_type = 'image/jpg';
				break;
			case IMAGETYPE_PNG:
				$mime_type = 'image/png';
				break;
			case IMAGETYPE_TIFF_II:
			case IMAGETYPE_TIFF_MM:
				$mime_type = 'image/tif';
				break;
			default:
				$mime_type = 'image/gif';
				break;
		}
		
		$image = file_get_contents($filename);
		
		$tmp_file = dirname(__FILE__) . '/tmp/jstor-' . $jstor . '.' . $extension;
		file_put_contents($tmp_file, $image);
		
		// resize
		$command = 'mogrify -resize 100x ' . $tmp_file;
		//echo $command . "\n";
	
		system($command);
		
		// load resized image
		$image = file_get_contents($tmp_file);
		
		//$base64 = chunk_split(base64_encode($image));
		$base64 = base64_encode($image);
		$thumbnail = 'data:' . $mime_type . ';base64,' . $base64;				
	}
	
	return $thumbnail;
}

//----------------------------------------------------------------------------------------
function get_doi_thumbnail($doi)
{
	global $db;
	
	$thumbnail = '';

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
				
		$tmp_file = dirname(__FILE__) . '/tmp/' . preg_replace('/(\/|\(|\)|\<|\>|:|;)/', '-', $doi) . '.' . $extension;
		file_put_contents($tmp_file, $image);
		
		// resize
		$command = 'mogrify -resize 100x ' . $tmp_file;
		//echo $command . "\n";
	
		system($command);
		
		// load resized image
		$image = file_get_contents($tmp_file);
		
		//$base64 = chunk_split(base64_encode($image));
		$base64 = base64_encode($image);
		$thumbnail = 'data:' . $mime_type . ';base64,' . $base64;				
	}
	
	return $thumbnail;
}

?>


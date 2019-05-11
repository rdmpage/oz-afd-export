<?php

// Generate thumbnails for publications

error_reporting(E_ALL ^ E_DEPRECATED);


require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');

$thumbnail_width = 100;

//--------------------------------------------------------------------------
/**
 * @brief GET a resource
 *
 * Make the HTTP GET call to retrieve the record pointed to by the URL. 
 *
 * @param url URL of resource
 *
 * @result Contents of resource
 */
function get_redirect($url, $userAgent = '', $content_type = '', $cookie = null)
{
	global $config;
	
	$redirect = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,  0); 
	curl_setopt ($ch, CURLOPT_HEADER,		  1);  
	
	// timeout (seconds)
	curl_setopt ($ch, CURLOPT_TIMEOUT, 240);

	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	if ($cookie)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $cookie);
	}
	
	if ($userAgent != '')
	{
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}	
	
	if ($config['proxy_name'] != '')
	{
		curl_setopt ($ch, CURLOPT_PROXY, $config['proxy_name'] . ':' . $config['proxy_port']);
	}
	
	if ($content_type != '')
	{
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array ("Accept: " . $content_type));
    }
	
			
	$curl_result = curl_exec ($ch); 
	
	//echo $curl_result;
	//exit();
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		//print_r($info);
		 
		$header = substr($curl_result, 0, $info['header_size']);
		//echo $header;
		
		
		$http_code = $info['http_code'];
		
		if ($http_code == 303)
		{
			$redirect = $info['redirect_url'];
		}
		
		if ($http_code == 302)
		{
			$redirect = $info['redirect_url'];
		}
		
	}
	return $redirect;
}

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
		if (1)
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
	
	echo "-- $url\n";
	
	$url = get_redirect($url);
	
	echo "-- $url\n";
	
	

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
function get_gallica_thumbnail($url, $base_filename)
{	
	global $thumbnail_width;
	
	$thumbnail_filename = '';
	
	$url = $url . '.thumbnail';
	$url = 'https://ozymandias-demo.herokuapp.com/image_proxy.php?url=' . urlencode($url);
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_USERAGENT => 'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405'	  
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
$db = NewADOConnection('mysqli');
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
//$force = false;

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

	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Turkish Journal of Zoology"';
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Proceedings of the Royal Society of Queensland"';
	
	//$sql .= ' WHERE PUB_AUTHOR LIKE "%salle%"';

	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE LIKE "%Beagle%"';

	//$sql .= ' AND biostor IS NOT NULL';
	//$sql .= ' AND pdf IS NOT NULL';
	//$sql .= ' AND jstor IS NOT NULL';
	
	
	//$sql .= ' WHERE issn="1021-3589"';
	//$sql .= ' AND pdf IS NOT NULL';
	
	//$sql .= ' WHERE doi LIKE "10.2307/%" AND thumbnailUrl IS NULL';
	
	//$sql .= ' WHERE biostor IS NOT NULL';
	//$sql .= ' WHERE jstor IS NOT NULL';	
	
	//$sql .= ' WHERE zootaxa_thumbnail_pdf IS NOT NULL';	
	
	//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Proceedings of the Royal Society of Victoria" AND pdf IS NOT NULL';		
//$sql .= ' WHERE PUB_PARENT_JOURNAL_TITLE="Bulletin of the Raffles Museum" AND pdf IS NOT NULL';
	//$sql .= ' WHERE PUBLICATION_GUID = "9f1b85dc-5f7e-4703-bb1a-3df91f3f493f"';	

	// Gallica
	//$sql .= ' WHERE PUBLICATION_GUID IN ("2d0fa005-9431-4e1f-bdb6-b7e697b3e847")';
	//$sql .= ' AND url IS NOT NULL';	
	
	//$sql .= ' WHERE issn="0166-6584"';
	//$sql .= ' AND pdf IS NOT NULL';		
		
	//$sql .= ' AND thumbnailUrl IS NULL';		
	
	//$sql .= ' WHERE updated > "2018-06-16"';
	//$sql .= ' WHERE updated > "2018-07-16"';
	
	$sql = "SELECT * FROM bibliography WHERE PUBLICATION_GUID IN ('92bd6c02-8690-4f2d-a7b5-5fb535489466','4c240592-8743-4b01-85d0-18a10a4cfd65','403ba41d-b59c-4881-b0bb-924082fad7e0','2dd6ca3a-d658-47a8-b0db-affa5a3b704f','203bb776-1e0e-458c-b52d-5f335160a93b','3ecfa3d2-6e15-482c-a4b7-64764c46605c','ab0a1c96-0db3-4f7d-a6b5-f6b775954726','7481788d-0992-44ae-8007-bee7fc3aa0d6','2dafbe59-b96d-4944-bccc-0b12530960cc','4848905f-1589-4d9e-8afb-f52d45920e7d','bb559344-16ae-4380-92c6-69629cd61b65','ad92c2f1-a06a-4a4d-92e4-80102f9d89e8','b09931ca-9c23-460c-b97d-d3f90e315303','bcd79824-4d09-43ea-b28c-f81c8007c087','8b2386a3-925b-4cd1-8fe1-c57394f5f572','a891714d-8966-40cc-877a-ef37f1465697','a53c4e29-b52f-4043-87ab-cbe6f9df3123','a990d7d9-87a2-449f-9e67-ccefc7dbc9b3','989372c0-a9e1-41eb-8c14-1e0f71376ab0','92613e27-6f54-46c2-bdeb-22e048b066d1','94f1d5a8-ae4c-4db0-a8d8-05b0acf4ee59','98b2785b-365e-43f9-a97d-0fdb5fc30c22','a75c8737-8bea-43c1-b603-6c9e3a424219','982d3e9e-a491-4fb2-bd36-c604f7e5a046','a8ccc74c-8fc1-4303-a701-2a6e6b7f3373','30c113a4-0f51-4f2d-979c-d51a10f251eb','94e5648a-5e6a-4ec8-b436-bbbe1e8c7676','75521787-afd2-434c-b689-eaa7cccf5d7d','cfbe1973-829b-4671-b247-bf8109a7eab7','90763082-60a4-4da5-b2c7-c9c68ad3cfcb','f01817b0-573a-46d1-98b9-e502ad45645d','96f98059-0945-4a18-ac04-dbd9c00e70cd','4837397a-d354-4284-8bad-1a51b31dbec5','d80b964a-1add-408a-9d6a-ecfa0cac54f6','fb892d57-490a-4d25-ad2c-52e640eb85e9','94ca953a-497f-4227-a468-5404ec3f35bc','93834843-79e6-48a9-9931-ed516aba6c8f','609392e9-cdad-431a-9370-ab9a02356175','76f5a585-2558-4753-b8f8-5f6ae4d76be6','3be7c256-3d06-43bd-a734-8affa8061679','6353fdce-0971-4cf6-8c79-ab8d8a2b1ed0','aa6cea3d-7864-48ca-8470-caadefe551ec','23167252-4b5a-4d08-84df-82c0441c7bc5','6f1a9c1d-bafe-4f68-853d-4b3504af8840','904db4bd-9593-4dfe-8deb-6a5fc7a3c642','26ad1d4c-f979-47c0-8411-19ed3b457019','98af5a92-38ce-416d-a3a5-dab0201eb2a1','a6da4ee8-fc1e-4e04-adfe-5a35aef22fa1','c98aaa99-b8fc-4a13-9b5c-686c25e547ac','7dc7b63e-ae3c-426e-851a-2303fb5ad51e','5db05890-20ec-4348-bc7f-d071779b4304','96641ad3-9346-43ed-869c-1dac158eee67','a2e9f4c6-e615-43ba-9bad-e13fc7931e3e','8e2ec49b-6ef5-4d03-ac3b-adaefa46543f','8e4a8c9d-5058-4ed6-bdbb-731bfa162759','be6b848f-29e1-4366-977b-40dd37755c1a','102e91a6-8aab-474e-bb08-6e6d81a31379','8dd191fe-06d6-448c-b3a2-1f440f2978d6','b2143853-0a4a-455b-a0ff-a62c7466e353','40c7137b-d725-4a0d-8f06-5fa16778dcd5','9921da00-93ea-4a3b-b784-fec44aa5cd4a','b402bd05-2a07-4565-b7d5-8ea32c0bce3f','37ce66eb-10c8-4573-bb14-1d8a2771c61c','f7885842-25b8-48eb-8279-27243e6f248a','983c8cf1-f58d-40c8-8525-34f75875f0a2','42b64780-60a4-4226-8892-cffc3dd651e9','9be34222-cf5f-4550-b401-60f53e028ae7','95afe1ee-d614-45bb-90f3-7d029bf96ca9','96d219dd-b614-4417-8594-e630d034818c','ba2eb113-4279-4f72-ae68-b68a38567146','920089b5-cdd6-4221-904d-344a9024cea7','bc25370c-84e5-4602-b8fc-edaa5496a705','5a599590-8f61-421e-8730-5451fda5d0a3','95a787a8-d85c-454a-9fe7-98e88b1cfc8d','e2ff2018-bf23-4cb1-b049-39bbffb72a28','b137bfe9-987b-4c3d-9583-aaccb27d01e3','993a5cb3-3299-402e-9103-63cc5746ba01','9410936a-2496-4324-9670-58adbeaf04ce','96cbfaa1-284f-4426-b1da-228de3215e92','c8d541ae-2e7b-4b7c-8c44-e8fb0a1e95d2','97331536-3d5f-4f4b-8eb9-90a1c9b09f78','3b2a3226-10a6-48fa-a1d0-207e2711ce90','18cc6963-bcf7-4ff5-b953-9eec4077e7ee','b1f5380a-eeda-48d5-9116-920ee155580b','ce65ce6d-2034-41fd-a17b-e336f53e6809','7a57bbce-8508-4682-95dd-4de66ce6e92e','beef8711-98fa-40a1-8748-c1998925c1be','2d72ee23-dae1-4e5b-a428-0ca2841edb3b','afac5a41-bbf4-4a82-a0d7-3a1bddf52460','ab45be5b-6911-4d82-9e7b-7cfede001988','9240b40c-0aba-468b-b339-91d245a0a869','95b41054-f979-4baf-91c8-21b7eae93ec8','90af0c9e-0854-41e1-be4e-a00d4c19f7b1','9d508378-6af0-425d-81ef-64276398f645','eabf6620-d806-4237-9aa6-b1562e9cdd1f','94747ae4-a9c7-4e4f-9493-5ceb208aaed0','9418fe1b-ca2e-4f6d-8d98-d36418f937c0','22d2ea39-2326-444c-94ab-4cd90a2df3f0','0dd489d8-4a68-4944-b177-a22ea4841c01','0db4dadf-9922-438a-95ff-82506faa7e51','4c87bd2c-172d-4529-99cb-c51c140ce96a','7826ed29-ebdc-4ebb-9fda-71a806159a28','5aa38811-0c8e-4805-a51c-d0524a8b31fc','906655fb-5515-4634-8ecd-63a3867f7c81','958311fa-ad5a-4d6a-976b-75416645cfb6','5f19b7c6-c9ef-4789-af3d-371225129484','922171c4-2729-4944-a5c5-7ccb625e8765','9626d4c1-ff5f-4051-ac65-cce9fa68d320','98a84ab0-7f10-4a95-87c5-ac9497aa26e8','75a421c6-c3ce-4767-bcdc-f116729da96b','4f6a7eb3-8219-443a-bc77-b28b1faa4e86','3d551cf7-ee3d-4f03-94d2-ecb0aae51390','2d88ac95-9fb4-459b-b50d-14843cf42953','65dbe4eb-3698-4914-aa99-ebbad106718a','5ae2b5ea-bd2c-454d-b505-205e48f1af97','bcbc181f-5ed0-4666-998b-985e0b9d381c','ba836766-afd8-4a54-a2f0-0f700a0f602f','7485246d-b4f2-4cd5-aa9a-a770c9d89647','4afba3d5-8f50-472c-bef6-c5585853b9a9','9090ba79-1eb9-45d1-85e4-cd4b4a2b7f9e','428026af-a7cc-406a-b4f5-b29eb059acf2','ace80b0e-c0e4-494c-959e-b167c36000aa','a70fcd9b-7719-4814-99c2-e5f499abebde','9633c4a4-6c65-4ecf-b05f-75a27a85e835','4c9373b4-f823-4f5f-a86b-0846284ecfdf','c3ba90d7-7a92-4318-a2ce-5cdd1f07aeb8','152e1b03-12a2-4364-bd80-1057f1376279','ad88f4dd-24ed-4c26-8033-aebbcc30adec','19ecae84-41e0-40d6-bb1d-be6d91d15efb','df178afa-09a3-4643-aa80-975e59252d79','74830ae5-e270-42ff-b3f8-66be381e51e8','ff246cbe-11f3-46d8-8345-6390b4e6885a','dc562984-2c22-4e32-b05d-25a35925fa6f','64e54bb4-d535-49c3-ba45-e56d64e1b71b','1806701a-a7a0-4795-b20a-6f9e2b8943b5','6ff3a0b2-3a97-41ff-9b11-ed69cd5619fe','c2609402-7cca-4c06-9b45-57de06f9d8da','33d429cb-2ef7-427b-8252-9321ddcfca35','0c005730-571d-40cb-a48f-85cb0b2e2461','4e373dd1-73bb-4f61-b8a6-c1d7c363107f','e552828b-900e-4daf-b3ba-01f8b52e9ff3','549b195f-88d4-4e57-b476-8f19ff72b511','5f525035-0f55-4f7f-b782-29ebc30b7a0a','eab73ae1-6563-4de8-a5a4-9d8082f8c2fd','a930f9e5-a0a2-4929-9d6c-019a29773087','37bbef6d-c891-470a-b0ea-d352ff738c87','d9c53076-9fde-4a8f-a979-f55edcc3454b','bc6c33b2-2d5b-454e-a3f9-06eca455755d','716b2fb4-39a0-4831-8c8c-bc53cadedf6b','f424778b-3106-4275-a4b0-d05e9ab01960','d2312647-153a-4dfd-bf8a-2ce1030786c2','7530ad8b-36c0-4951-965a-1bf1d0c675a4','7ead76d1-1fcf-4f1d-9dc7-2b52e67d1f4f','4b1a2241-f8f6-4b9f-82a2-6e7918ed2780','b0a03b3a-d91d-428a-99d0-173c6a1622ec','fa25db82-cb6d-4db0-82fa-3ddef67d2268','98d87681-94e6-454c-8cdf-222bd8ff15eb','6033db86-7b0c-460a-83cf-2a640a148793','62f557cb-399a-4d02-993d-656285e2203e','95db59af-590e-434e-8f59-583595e688f8','959dd41d-75d0-49e1-9bba-4bdcc44ea24e','9080bedb-20df-47dc-8d72-8ac19559408f','b9bf8f45-ec5f-47db-a666-10a3fe816488','9359a2eb-5a16-46ba-99b7-5362d21a53f0','92e45529-8442-4aac-a0f9-de0d205d6dcb','057cf00a-083b-4a64-a4da-6317e78f2cbf','974ccac0-ff1e-4f18-a67a-1f3606476e76','a746e16c-38c7-4f07-838e-0b8d652116d7','29b8d2e1-ac20-4b75-856c-1a23ba401bb9','5f21d73a-8d0a-4b34-8e7b-440d74262c4e','e6ac5198-15e4-4848-971b-22672282a9f6','ee51487a-1e7a-4922-a68e-05286528a9a5','adf19abb-6768-4e84-b16a-3a40b106ca19','842f4dce-c824-4662-b82f-71c0d92f9ec6','b0954ad5-ba07-4c29-8f8d-30e0f05bc3e5','bb09ce27-ac78-49c4-8407-5e83b39af27a','5fee1a09-6529-485e-b2d0-1e50c3e3704f','5e7c8a04-7aa5-4699-916a-4e3a1c4acc31','aad2e11a-1b28-48c4-8167-b26c787b8ec7','91dcb160-4ad9-4423-860b-28748d684762','bceb9103-2fee-442b-acf6-1dbc87498f5f','97af2387-639d-4366-967f-756895dd26eb','049492d6-f281-458f-9cdf-4d6d996e1986','288ffefd-2f8a-45e1-b132-39b0c70ab356','69800bcc-657c-43af-ac6a-5ef73c5f8ac5','928c5c44-ede4-4e27-a13b-b53fba73ba50','f65244b0-605d-49da-8b39-06bc23f43168','95c590cf-1290-4b0b-8a48-f9faa8798589','bbef8db7-5532-4f8e-8591-577b5d4617ff','bbcf933b-0309-4059-9441-f999ec7b10dc','d736c9b2-4446-48ef-9ede-c2eb2cb35f13','d52ad23d-d058-43ea-b65a-24c6859da23f','2a3e9d46-20f2-427e-b713-56e9d88c33cf','5e38c616-d42b-4311-8b75-82136a21c640','ea9dbec4-3b96-417e-b032-32814e255f5e','2f880e00-5505-4c05-af4a-05e15e2f3384','d260b39f-e39b-4d93-83fe-c7ab3dc2fd4d','a69bce8b-c1bc-457b-a0e2-474c9a9dde36','d3357cc2-49ce-4c59-811e-a21c864bb83c','2a4fb7c7-1c50-449d-9d4c-f60632c68c11','771572ca-443c-479a-93e4-d25c99f34a36','c3875acd-fbb0-4a88-a1ac-2857c59f4470','5c1085df-fac4-4a93-8de6-67b86d47ac07','77098843-bad3-454e-a504-46a93f525031','970d3c51-8316-4e3b-988c-77fc25b59567','861da6b4-d67e-45d5-a084-425144c9a0d3','57d258b5-2054-4f20-8a76-8ef339334004','7d61cc5b-be5c-4d8f-95b9-815b4d24d133','1c46e986-2abe-439e-941e-bc9261218cd3','135d8922-e987-4062-83d1-d889da36d57a','0258ccbb-fbdd-4c24-801b-6cd8787b6fe2','0832c7b2-2159-4179-800c-9753df103817')";
	
	
	//$sql = 'SELECT * FROM bibliography WHERE PUBLICATION_GUID IN ("22d2ea39-2326-444c-94ab-4cd90a2df3f0")';
	
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
		
		//echo $thumbnail_filename . "\n";
		
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
			
			// Gallica
			if ($thumbnail_filename == '')
			{
				if ($result->fields['url'] != '')
				{
					if (preg_match('/gallica.bnf.fr/', $result->fields['url']))
					{
						$thumbnail_filename = get_gallica_thumbnail($result->fields['url'], $base_filename);
					}
				}
			}

			
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
								case 'Insecta Matsumurana':
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

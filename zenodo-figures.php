<?php

// Fetch Zenodo record

// Note that not all work records "know" that they have figures, whereas figures
// always seem to be linked to works. This makes discover of figures a bit tricky...

require_once(dirname(__FILE__) . '/php-json-ld/jsonld.php');


$stack = array();

//----------------------------------------------------------------------------------------
function fetch_zenodo_json($id, &$jsonld)
{	
	global $stack;

	$url = "https://zenodo.org/api/records/" . $id;

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
		
		//print_r($obj);
		
		// image URL
		if (isset($obj->files[0]->links->self))
		{
			$jsonld->contentUrl = $obj->files[0]->links->self;
		}
		
		// image thumbnail
		if (isset($obj->links->thumb250))
		{
			$jsonld->thumbnailUrl = $obj->links->thumb250;
		}
		
		// parts
		if (isset($obj->metadata->related_identifiers))
		{
			foreach ($obj->metadata->related_identifiers as $related)
			{
				switch ($related->relation)
				{
					// [identifier] => http://zenodo.org/record/252172
					case 'hasPart':
						if (preg_match('/http:\/\/zenodo.org\/record\/(?<id>\d+)/', $related->identifier, $m))
						{
							$stack[] = $m['id'];
						}
						break;
						
					// already done in JSON-LD
					/*
					case 'cites':
						if (!isset($jsonld->cites))
						{
							$jsonld->cites = array();
						}
						
						if ($related->scheme == 'doi')
						{						
							$cited = new stdclass;
							$cited->{'@id'} = 'https://doi.org/' . $related->identifier;
						
							$jsonld->cites[] = $cited;
						}
						break;*/
				
					default:
						break;
				}
			}
			
		
		}
		
	}
}

//----------------------------------------------------------------------------------------
// Call API asking for JSON-LD which we convert to triples 
// Note that we make a second call to get the details of the image itself (sigh)
function fetch_zenodo($id)
{
	global $stack;
	
	$url = "https://zenodo.org/api/records/" . $id;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_HTTPHEADER => array("Accept: application/ld+json")
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	if ($data != '')
	{
		// triples
		$jsonld = json_decode($data);
		
		// second call 
		fetch_zenodo_json($id, $jsonld);
					
		
		if (0)
		{			
			// JSON-LD for debugging
			echo json_encode($jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			echo "\n";			
		}
		else
		{
			// Triples for export
			$triples = jsonld_to_rdf($jsonld, array('format' => 'application/nquads'));			
			echo $triples;
		}
					 
	}
}

// I added this DOI:10.24199/j.mmv.2014.72.07
// https://zenodo.org/record/1297035
$stack = array(
1297027, 
1297029, 
1297031, 
1297033
);

// https://zenodo.org/record/221278
// Zootaxa 
$stack = array(
221279,
221280,
221281,
221281
);

// https://zenodo.org/record/1308571
// DOI: 10.1651/11-3476.1
// Bogidiella Veneris, a New Species of Subterranean Amphipoda (Bogidiellidae) from Australia, with Remarks on the Systematics and Biogeography
$stack = array(
1308571,
1308559,
1308561,
1308563,
1308565,
1308567,
1308569
);

/*
$stack = array(
223235,

223236,
223237,
223238,
223239,
223240,
223241,
223242,
223243
);

$stack = array(
577518,
937726,
937728,
937730,
937732,
937734,
937736,
937738
);
*/

$stack=array(
577774,
942629,
942631,
942633,
942637,
942639,
942641,
942643,
942645,
942647,
942649,
942651,
942653,
942655,
942657,
942659,
942661,
942663,
942665,
942667,
942669,
942671,
942673,
942675,
942677,
942679,
942681,
942683,
942685,
942687,
942689,
942691,
942693,
942695,
942697,
942699,
942701,
942703,
942705,
942707,
942709,
942711,
942713,
942715,
942717,
942719,
942721,
942723,
942725,
942727,
942729,
942731,
942733,
942735,
942737,
942739,
942741,
942743,
942745,
942747,
942749,
942751,
942753,
942755,
942757,
942763,
942785,
942807
);


while (count($stack) > 0)
{
	$id = array_pop($stack);
	
	//echo "-- Fetching node $id...\n";
	
	fetch_zenodo($id);	
}

?>
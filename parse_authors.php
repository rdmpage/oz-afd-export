<?php

// Parse author name string

// latinse

//----------------------------------------------------------------------------------------
// https://stackoverflow.com/a/1454409/9684
function strtr_utf8($str, $from, $to) {
    $keys = array();
    $values = array();
    preg_match_all('/./u', $from, $keys);
    preg_match_all('/./u', $to, $values);
    $mapping = array_combine($keys[0], $values[0]);
    return strtr($str, $mapping);
}

//----------------------------------------------------------------------------------------
function name_to_id ($name)
{
	//echo $name . "\n";
	
	$name = str_replace('.', '', $name);
	$name = str_replace('´', '', $name);	
	$name = str_replace('­', '', $name);
	
	$name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);	
	
	$name = strip_tags($name);
	
	
	//echo $name . "\n";
	
	$name = mb_convert_case($name, MB_CASE_LOWER);
	
	//echo $name . "\n";
	
	/*
	$name = strtr(utf8_decode($name), 
		utf8_decode("ÀÁÂÃÄÅàáâãäåæĀāĂăĄąÇçĆćĈĉĊċČčÐðĎďĐđÈÉÊËèéêëĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħÌÍÎÏìíîïĨĩĪīĬĭĮįİıĴĵĶķĸĹĺĻļĽľĿŀŁłÑñŃńŅņŇňŉŊŋÒÓÔÕÖØòóôõöøŌōŎŏŐőŔŕŖŗŘřŚśŜŝŞşŠšſŢţŤťŦŧÙÚÛÜùúûüŨũŪūŬŭŮůŰűŲųŴŵÝýÿŶŷŸŹźŻżŽž"),
		"aaaaaaaaaaaaaaaaaaaccccccccccddddddeeeeeeeeeeeeeeeeeegggggggghhhhiiiiiiiiiiiiiiiiiijjkkkllllllllllnnnnnnnnnnnoooooooooooooooooorrrrrrsssssssssttttttuuuuuuuuuuuuuuuuuuuuwwyyyyyyzzzzzz");
	*/
	
	$name = strtr_utf8($name, 
		"ÀÁÂÃÄÅàáâãäåæĀāĂăĄąÇçĆćĈĉĊċČčÐðĎďĐđÈÉÊËèéêëĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħÌÍÎÏìíîïĨĩĪīĬĭĮįİıĴĵĶķĸĹĺĻļĽľĿŀŁłÑñŃńŅņŇňŉŊŋÒÓÔÕÖØòóôõöøŌōŎŏŐőŔŕŖŗŘřŚśŜŝŞşŠšſŢţŤťŦŧÙÚÛÜùúûüŨũŪūŬŭŮůŰűŲųŴŵÝýÿŶŷŸŹźŻżŽž",
		"aaaaaaaaaaaaaaaaaaaccccccccccddddddeeeeeeeeeeeeeeeeeegggggggghhhhiiiiiiiiiiiiiiiiiijjkkkllllllllllnnnnnnnnnnnoooooooooooooooooorrrrrrsssssssssttttttuuuuuuuuuuuuuuuuuuuuwwyyyyyyzzzzzz");

	//echo $name . "\n";

	$parts = explode(' ', $name);

	$id = join('-', $parts);
	
	//echo $name . $id . "\n";
	//exit();

	return $id;
}


//----------------------------------------------------------------------------------------
function parse_authors($authorstring)
{
	$parsed_authors = array();
	
	//echo $authorstring . "\n";
	
	// ignore Jr (it's a hassle)
	$authorstring = preg_replace('/\.\s+Jr\.?/u', '.', $authorstring);
	
	// remove et al.
	$authorstring = preg_replace('/,?\s+et al./', '', $authorstring);
	
	// trim authors before ' in ' because we want authors of work, not name
	$authorstring = preg_replace('/^(.*)\s+in\s+/u', '', $authorstring);		

	$authorstring = preg_replace('/\.\s*,\s+([^&])/u', '.|$1', $authorstring);
	
	$authorstring = preg_replace('/,? & /', '|', $authorstring);
	$authorstring = preg_replace('/([A-Z])\.([A-Z])/u', '$1. $2', $authorstring);
	$authorstring = preg_replace('/([A-Z])\.([A-Z])/u', '$1. $2', $authorstring);
	
	//echo $authorstring . "\n";

	$authors = explode("|", $authorstring);	
	
	foreach ($authors as $author)
	{	
		$parts = preg_split('/,\s*/u', $author);
		if (count($parts) == 2)
		{
			$a = new stdclass;
			
			$a->type = 'Person';
			
			$a->name 		= $parts[1] . ' ' . $parts[0];
			$a->givenName 	= $parts[1];
			$a->familyName 	= $parts[0];
			
			//print_r($a);
			
			$a->id = name_to_id($a->name);
			
			$parsed_authors[] = $a;
		}
		else
		{
		
			$a = new stdclass;
			
			$a->type = 'Person';
			
			$a->name 		= $author;
			
			$matched = false;
			
			if (!$matched)
			{
				if (preg_match('/^(?<familyName>[A-Z]\w+)\s+(?<givenName>[A-Z]\.(\s+[A-Z]\.)*)$/u', $author, $m))
				{
					$a->givenName 	= $m['givenName'];
					$a->familyName 	= $m['familyName'];
					
					$a->id = name_to_id($a->name);
					
					$matched = true;

				}
			}
						
		}	
	}
	
	
	return $parsed_authors;
}

if (0)
{
	// tests 

	
	$tests = array(
	'Lasley, R.M. Jr, Lai, J.C.Y. & Thoma, B.P.',
	'Neil, H., P. McMillan, D. Tracey, R. Sparks, P. Marriott, C. Francis & L. Paul.',
	'Champion, G.C.',
	'Evans, H.E. & Matthews, R.W.',
	'Polhemus, J.T. & Polhemus, D.A.',
	'Lambkin, C.L. in Lambkin, C.L. & Bartlett, J.S.'
	);
	
	foreach ($tests as $authorstring)
	{
		$parsed_authors = parse_authors($authorstring);
		print_r($parsed_authors);
	}
}



?>
<?PHP

/****************************************
	
	parsing.php
	
	Used to convert the rdf/xml version
	of the CIA factbook into a MySQl-DB
	
	Parsing functions used by create_db.php
	and extract_data.php
	
*******************************************/


/*	Creates a simple_xml_object and manipulates some elementnames in order to
 *	eleminate some inconsistencies
 *	Takes as argument the filename as string
 */

function xml_create( $filename ) {
	
	// Datei in einen String laden, um einzelne Elementnamen zu konvertieren
	
	if (file_exists($filename)) {
	    $file_string = file_get_contents($filename);
		echo "<br>XML-file $filename successfully loaded<br>";
	} else {
		exit ("XML-file $filename could not be loaded");
	}
	

	// Element-names-manipulations
		
	$file_string = str_replace("Population-data","Population-total-data",$file_string);
	$file_string = str_replace("Irrigatedland-data","Irrigatedland-total-data",$file_string);
	$file_string = str_replace("Coastline-data","Coastline-total-data",$file_string);
	$file_string = str_replace("Airports-data","Airports-total-data",$file_string);
	$file_string = str_replace("malesage17-49","malesage18-49",$file_string);
	$file_string = str_replace("metropolitanFrance","",$file_string);	
	
	$file_string = str_ireplace("Coalition[RomanoPRODI]","Coalition",$file_string);	
	$file_string = str_ireplace("Coalition[SilvioBERLUSCONI]","Coalition",$file_string);	
	
	
	// Simple_XML_Objekt erzeugen

	$xml = simplexml_load_string($file_string);

return $xml;
	
}



/* Extracts the data from the a simple_xml_object,
 * takes as arguments the simple_xml_object ($xml)
 * and an array containing the element names to extract.
 * 
 * Returns an associative Array (Element names are the keys)
 *
 */
	

function xml_get_element_data ( $xml, $element_names ) {
	
	// Array $element_data contains the extracted data
	$element_data = array();

	/*
		Fill $element_data with empty arrays. Needs to be a 2-dimensional array, 
		as it has to be able to contain more than one item from each element.

	*/

	foreach ($element_names as $element) {
		
		$element_data[$element] = array();
		
	} // END foreach
	
	/*
		Filling the 2-dimensional array $element_data with data:
		First foreach loop: 1. dimension: Element-name (Key)
		Second foreach loop: 2. dimension, array containig each occurrance of the element.
		Usually length 1.
	*/

	foreach ($element_names as $element) {

		$i = 0;

		foreach ($xml->xpath("//factbook:$element") as $item) {
			
			// Leeren Elementen den Wert 0 zuweisen
			if ( $item ) {
				$element_data[$element][$i] = $item;
			} else {
				$element_data[$element] = NULL;
			}
			$i++;
			

		} // END foreach

		// Overwrite empty elements
		
		if ($i == 0) $element_data[$element] = NULL;

	} // END foreach
	
	
	return $element_data;

} // END function parse_element_array


	
	
	
	
/*	Reads the datatype from the data (INT, DOUBLE, TEXT).
 *	Needs the array conatining the elementnames and the array
 *	cintaining the data.
 *	Returns an associative array (elementname = key)
 *	containing the datatyoe for each element
 */
	
function xml_get_element_type ( $element_names, $elementdata ) {
	
	// Array $element_type contains the datatypes
	$element_type = array();

	 $item = 0;
	
	foreach ($element_names as $element)	{
		
		// Get datatype (only digits and "-" -> INT, INT + "." = DOUBLE, else TEXT)
		

		if ( strspn($elementdata[$element][$item],"-0123456789") == strlen($elementdata[$element][$item]) ) {

			$element_type[$element] = "BIGINT";		

		} else if ( strspn($elementdata[$element][$item],"-.0123456789") == strlen($elementdata[$element][$item]) ) {

			$element_type[$element] = "DOUBLE";		

		} else {
			
			$element_type[$element] = "TEXT";
			
		} // END if

	} // END foreach
	
	
	return $element_type;

} // END function xml_get_element_type


/*	Converts elementnames, for the use as coloumnnames in the database */
	
	
function xml_convert_element_names ( $element_names, $elementdata ) {
	
	$element_names_converted = array();
	
	foreach ( $element_names as $element ) {
		

		// Delete "-data" at the end of elementnames 
		$element_name_new = str_replace("-data","",$element);
		
		// Replace "-" by "_" 
		$element_name_new = str_replace("-","_",$element_name_new);
		
		/*	Copies only elementnames to the array, which have at least one occurrence
		 *	in the source file. If the source rdf-file does not contain the element, 
		 *	the specific elementname is assigned NULL.
		 */
		
		if ( $elementdata[$element] ) {
			$element_names_converted[$element] = $element_name_new;
		} else {
			$element_names_converted[$element] = NULL;
		}

	} // END foreach
	
	return $element_names_converted;
	
} // END xml_convert_element_names



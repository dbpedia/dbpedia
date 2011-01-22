<?PHP

/***********************************************
 *
 *  Converts the Project-Gutenberg RDF-Dump 
 *  into a MySQL database
 *
 **********************************************/   

/****************************
 *
 *  Database conenction
 *
 ****************************/
 
$db_user = "root"; // Database user
$db_pwd = "root"; // Database password
$db_host = "127.0.0.1:8889"; // Database host

$db_connection = mysql_connect($db_host, $db_user, $db_pwd, true)
	or die('Verbindung nicht möglich: ' . mysql_error());


// Database name

$db_name = "gutenberg";


$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());

$sql = "SET NAMES utf8";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );



/*************************************
 *
 *  Database tables
 *
 ************************************/
 
$tablename1 = "texts";
$tablename2 = "creators";
$tablename3 = "contributors";
$tablename4 = "files";
$tablename5 = "subject";


// RDF-Dumps filename

$file = "catalog.rdf";


// Create Simple_XML_Objekt

if (file_exists($file)) {


$xmlstr = file_get_contents($file); 

} else {
	
	echo "XML-Date $file konnte nicht geladen werden";
    exit('XML-Date $file konnte nicht geladen werden');
}


// Delete namespaces

function killItemBefore($source, $item, $target) {

	$tmp = array();
	$splitter = $item.":" . $target;
//	echo "removing ".$splitter."<br />";
	$tmp = explode($splitter, $source);
	return implode($target, $tmp);
}


$xmlstr = killItemBefore($xmlstr, "rdf","parseType");
$xmlstr = killItemBefore($xmlstr, "rdf","ID");
$xmlstr = killItemBefore($xmlstr, "pgterms", "etext");
$xmlstr = killItemBefore($xmlstr, "dc","title");
$xmlstr = killItemBefore($xmlstr, "dc","publisher");
$xmlstr = killItemBefore($xmlstr, "dc","creator");
$xmlstr = killItemBefore($xmlstr, "dc","description");
$xmlstr = killItemBefore($xmlstr, "rdf","Bag");
$xmlstr = killItemBefore($xmlstr, "rdf","li");
$xmlstr = killItemBefore($xmlstr, "dc","contributor");
$xmlstr = killItemBefore($xmlstr, "pgterms","friendlytitle");
$xmlstr = killItemBefore($xmlstr, "dc","language");
$xmlstr = killItemBefore($xmlstr, "dc","created");
$xmlstr = killItemBefore($xmlstr, "dcterms","ISO639-2");
$xmlstr = killItemBefore($xmlstr, "rdf","value");
$xmlstr = killItemBefore($xmlstr, "dcterms","W3CDTF");
$xmlstr = killItemBefore($xmlstr, "dc","rights");
$xmlstr = killItemBefore($xmlstr, "rdf","resource");
$xmlstr = killItemBefore($xmlstr, "rdf","RDF");
$xmlstr = killItemBefore($xmlstr, "dc","subject");
$xmlstr = killItemBefore($xmlstr, "dcterms","LCC");
$xmlstr = killItemBefore($xmlstr, "dcterms","LCSH");
$xmlstr = killItemBefore($xmlstr, "pgterms","file");
$xmlstr = killItemBefore($xmlstr, "dc","format");
$xmlstr = killItemBefore($xmlstr, "dcterms","extent");
$xmlstr = killItemBefore($xmlstr, "dcterms","modified");
$xmlstr = killItemBefore($xmlstr, "dcterms","isFormatOf");
$xmlstr = killItemBefore($xmlstr, "dcterms","IMT");
$xmlstr = killItemBefore($xmlstr, "rdf","about");


$xmlstr = str_replace("ISO639-2", "ISO6392", $xmlstr);

// Dateinamen mit URL-Verknuepfen

$filepath = "http://www.gutenberg.org/dirs/";
$xmlstr = str_replace("&f;", $filepath, $xmlstr);


//echo($xmlstr);

/*
$tmp = array();
$tmp = explode("ISO639-2", $xmlstr );
$xmlstr = implode("MY_LANG_REPLACE", $tmp );
*/

$xml = new SimpleXMLElement($xmlstr);

echo "XML-Objekt erzeugt";

/****************************************************

	1. foreach() - loops thru each pgterms:etext
	elements and inserts them in the database
	
****************************************************/



foreach ($xml->etext as $textitem) {
	
	/*
	echo "id: " . $textitem['ID'] . "<br />";
	echo "publisher: " . $textitem->publisher . "<br />";
	echo "title: " . $textitem->title . "<br />";
	echo "creator:<br />";
	echo "friendlytitle: " . $textitem->friendlytitle . "<br />";
	echo "language: " . $textitem->language->MY_LANG_REPLACE->value . "<br />";
	echo "created: " . $textitem->created->W3CDTF->value . "<br />";
	echo "rights:" . $textitem->rights['resource'] . "<br /><br /><br /><br /><br />";
	*/
	
	$textId = $textitem['ID'];
	$creator = $textitem->creator;
	$contributor = $textitem->contributor;
	$publisher = $textitem->publisher;
	$title = $textitem->title;
	$friendlytitle = $textitem->friendlytitle;
	$language = $textitem->language->ISO6392->value;
	$created = $textitem->created->W3CDTF->value;
  	$rights = $textitem->rights['resource'];
  	$description = $textitem->description;
	$subject = $textitem->subject->LCSH->value;
	$subjectb = $textitem->subject->LCC->value;

	// PRINTOUT ALL ITEMS
	/*
	echo "<br><h3>New Item</h3>";
	echo "TextId: $textId; Creator: $creator; Publisher: $publisher; Title: $title;
	 	 Friendlytitle: $friendlytitle; Language: $language; Created: $created; Rights: $rights;
	 	 Contributor: $contributor <br>";
	*/
	
	
	//////////////////////////////////////////
	//										                 
	//		SQL - STRING			
	//									
	//////////////////////////////////////////	
	
	
	///////////////////////////////////
	//
	// Table "texts"
	//
	///////////////////////////////////
  
  // Delete " ' " and replace Null-Strings with "NULL"
  
  $textId = str_replace("'", "", $textId);
  $textId = str_replace("etext", " ", $textId);
  
  if ( $publisher) {
  	$publisher = str_replace("'", "", $publisher);
  	$publisher = "'$publisher'";
  	if ( strlen($publisher) == 0) $publisher = "NULL";
  } else {
  	$publisher = "NULL";
  }
  
	if ( $title) {
		$title = str_replace("'", "", $title);
		$title = "'$title'";
		if ( strlen($title) == 0) $title = "NULL";
	} else {
  	$title = "NULL";
  }
		
	if ( $friendlytitle) {
		$friendlytitle = str_replace("'", "", $friendlytitle);
		$friendlytitle = "'$friendlytitle'";
		if ( strlen($friendlytitle) == 0) $friendlytitle = "NULL";
	} else {
  	$friendlytitle = "NULL";
  }
	
	if ( $language) {
		$language = str_replace("'", "", $language);
		$language = "'$language'";
		if ( strlen($language) == 0) $language = "NULL";
	} else {
  	$language = "NULL";
  }
	
	if ( $created) {
		$created = str_replace("'", "", $created);
		$created = "'$created'";
		if ( strlen($created) == 0) $created = "NULL";
	} else {
  	$created = "NULL";
  }
	
  if ( $rights) {
  	$rights = str_replace("'", "", $rights);
  	$rights = "'$rights'";
  	if ( strlen($rights) == 0) $rights = "NULL";
  } else {
  	$rights = "NULL";
  }
  
  if ( $description) {
  	$description = str_replace("'", "", $description);
  	$description = "'$description'";
  	if ( strlen($description) == 0) $description = "NULL";
  } else {
  	$description = "NULL";
  }
  	
  
  
	$sql = "INSERT INTO $tablename1 VALUES ($textId, $publisher, $title, $friendlytitle, $language, $created, $rights, $description); ";

  $sql_exec = mysql_query($sql, $db_connection)
  or die ($sql . mysql_error());
 
  $sql = "";

    
	///////////////////////////////////
	//
	// Tabelle "creator"
	//
	/////////////////////////////////// 
    
    // Creators
	// If more than one creator, insert each entry (creator->bag->li) into database
  
		if  ( $creator->Bag ) {
		
			//echo "<b>Creators</b> <br />";
	
			foreach ( $textitem->creator->Bag->li as $creator ) {

				//echo $creator . "<br />";
        $creator = str_replace("'", " ", $creator);
        if ( strlen($creator) == 0) $creator = "NULL";
        $sql .= "INSERT INTO $tablename2 VALUES ($textId, '$creator'); ";
        
        $sql_exec = mysql_query($sql, $db_connection)
          or die ($sql . mysql_error());
        $sql = "";
        
			} // END foreach ( creators )			
		
    // If only one creatotr, insert into database	
		} else if ( $creator ) {
    
      		$creator = str_replace("'", " ", $creator);   
      		if ( strlen($creator) == 0) $creator = "NULL"; 
      			$sql .= "INSERT INTO $tablename2 VALUES ($textId, '$creator'); ";	
			
			$sql_exec = mysql_query($sql, $db_connection)
        		or die ($sql . mysql_error());
      		$sql = "";
			
		} // END if ( creators )
	
/// echo $sql;	

	
	
  ///////////////////////////////////
	//
	// Table "contributor"
	//
	///////////////////////////////////
	
		
		// Contributors 
		// If more than one contributor, go level down (contributor->Bag->li) and insert each contributor into table contributors
				
		if ( $contributor->Bag ) {
			
			//echo "<b>Contributors:</b> <br />";
			
			foreach ( $textitem->contributor->Bag->li as $contributor ) {
				
				//echo $textId . $contributor . "<br />";
				$contributor = str_replace("'", " ", $contributor);
				
				// Parse the contributor type from RDF-File:
				// "Name, lastname [Illustrator]" ...
				
				$contributor = explode('[',$contributor);
				$contributor_name = $contributor[0];
				if ( strlen($contributor_name) == 0) $contributor_name = "NULL";
				$contributor_type = str_replace( "]","",$contributor[1] );
				if ( strlen($contributor_type) == 0) $contributor_type = "NULL";
				
				$sql .= "INSERT INTO $tablename3 VALUES ($textId, '$contributor_name', '$contributor_type');";
				
				$sql_exec = mysql_query($sql, $db_connection)
          			or die ($sql . mysql_error());
        		$sql = "";
			
			} // END foreach ( contributors )	
		
    // If only one contributor, inser into table contributors
		} else if ( $contributor ) {
		
			$contributor = str_replace("'", " ", $contributor);
			
			$contributor = explode('[',$contributor);
			$contributor_name = $contributor[0];
			if ( strlen($contributor_name) == 0) $contributor_name = "NULL";
			$contributor_type = str_replace( "]","",$contributor[1] );
			if ( strlen($contributor_type) == 0) $contributor_type = "NULL";
			
			$sql .= "INSERT INTO $tablename3 VALUES ($textId, '$contributor_name', '$contributor_type');";
			
			$sql_exec = mysql_query($sql, $db_connection)
        		or die ($sql . mysql_error());
      	$sql = "";
			
			//echo "Contributor: YES";
			
		} // END if ( contributors )	
		
		
	  ///////////////////////////////////
		//
		// Table "subject"
		//
		///////////////////////////////////


			// Subject 
			// If more than one subject, go level down (subject->Bag->li) and insert each subject into table subjects

			if ( $textitem->subject->Bag ) {
				
				foreach ( $textitem->subject->Bag->li as $subject ) {
					$subject = $subject->LCSH->value;	
					//echo $textId . $subject . "<br />";
					$subject = str_replace("'", " ", $subject);

					$sql .= "INSERT INTO $tablename5 VALUES ($textId, '$subject');";
					$sql_exec = mysql_query($sql, $db_connection)
	          			or die ($sql . mysql_error());
	        		$sql = "";

				} // END foreach ( subject )	

	    // If only one subject, insert into table subjects	
			} else if ( $subject ) {
				//echo "Subject1: $subject<br>";
				$subject = str_replace("'", " ", $subject);
				
				if ( strlen($subject) == 0) $subject = "NULL";

				$sql .= "INSERT INTO $tablename5 VALUES ($textId, '$subject');";
				$sql_exec = mysql_query($sql, $db_connection)
	        		or die ($sql . mysql_error());
	      		$sql = "";

				//echo "Contributor: YES";

			} // END if ( subject )
			
			// Subject Typ b (dcterms:LCC)
			
			if ($subjectb) {
				//echo "Subjectb: $subjectb<br>";
				$subjectb = str_replace("'", " ", $subjectb);
				
				if ( strlen($subjectb) == 0) $subjectb = "NULL";

				$sql .= "INSERT INTO $tablename5 VALUES ($textId, '$subjectb');";

				$sql_exec = mysql_query($sql, $db_connection)
	        		or die ($sql . mysql_error());
	      		$sql = "";
			}
		



} // END foreach ( etext )


////////////////////////////////////////
//
//  Extract Links to the original Text-files (on the PG site)
//
///////////////////////////////////////


foreach ($xml->file as $fileitem) {

  $filename = $fileitem['about'];
  $format = $fileitem->format[0]->IMT->value;
  $format2 = $fileitem->format[1]->IMT->value;
  $extent = $fileitem->extent;
  $modified = $fileitem->modified->W3CDTF->value;
  $filetextId = $fileitem->isFormatOf['resource'];
  
  // " ' " entfernen
  
  if ( $filename) {
  	$filename = str_replace("'", " ", $filename);
  	if ( strlen($filename) == 0) $filename = "NULL";
  }
  
  if ( $format) { 
  	$format = str_replace("'", " ", $format);
  	if ( strlen($format) == 0) $format = "NULL";
  }
  
  if ( $format2) {
  	$format2 = str_replace("'", " ", $format2);
  	if ( strlen($format2) == 0) $format2 = "NULL";
  }
  
  if ( $extent) {
  	$extent = str_replace("'", " ", $extent);
  	if ( strlen($extent) == 0) $extent = "NULL";
  }
  
  if ( $modified) {
  	$modified = str_replace("'", " ", $modified);
  	if ( strlen($modified) == 0) $modified = "NULL";
  }
  
  if ( $filetextId) {
  	$filetextId = str_replace("'", " ", $filetextId);  
  	if ( strlen($filetextId) == 0) $filetextId = "NULL";
  }
  
  
  
  
  // delete "#" in textId 
  
  $filetextId = str_replace("#etext","", $filetextId);

/*
  echo $filename . "<br>" . $format . "<br>" . $format2 . "<br>" . $extent . "<br>"
  . $modified . "<br>" . $filetextId . "<br><br>"; 
*/

  $sql = "INSERT INTO $tablename4 VALUES (
        $filetextId, '$filename', '$format', '$format2' , '$modified', '$extent'); ";

  $sql_exec = mysql_query($sql, $db_connection)
    or die ($sql . mysql_error() );


} //END foreach ( file )


// Change author-names for URIs


$sql = "ALTER TABLE  `contributors` ADD  `name_encoded` VARCHAR( 255 ) NOT NULL";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );

$sql = "ALTER TABLE  `creators` ADD  `name_encoded` VARCHAR( 255 ) NOT NULL";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
	
$sql = "UPDATE contributors SET name_encoded = name;";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE creators SET name_encoded = name;";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE contributors SET name_encoded = REPLACE(name_encoded,' ','_');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE contributors SET name_encoded = REPLACE(name_encoded,',','');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE contributors SET name_encoded = REPLACE(name_encoded,'.','');";
$sql_exec = mysql_query($sql)
		or die( $sql . mysql_error() );
$sql = "UPDATE creators SET name_encoded = REPLACE(name_encoded,' ','_');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE creators SET name_encoded = REPLACE(name_encoded,',','');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE creators SET name_encoded = REPLACE(name_encoded,'.','');";
$sql_exec = mysql_query($sql)
		or die( $sql . mysql_error() );
$sql = "UPDATE contributors SET name_encoded = TRIM(TRAILING '_' FROM name_encoded); ";
$sql_exec = mysql_query($sql);
$sql = "UPDATE creators SET name_encoded = TRIM(TRAILING '_' FROM name_encoded); ";
$sql_exec = mysql_query($sql);

$sql_close = mysql_close($db_connection);


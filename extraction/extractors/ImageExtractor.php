<?php

/**
 * Extracts the first image of a Wikipediapage. Constrcuts a thumbnail from it, and
 * the fullsize image.
 */

class ImageExtractor extends Extractor 
{
    
    private $dbConnection;
    private $dbCommonsConnection;
    // maximum acceptable offset in Wikipedia text for an image
    // (http://sourceforge.net/tracker/index.php?func=detail&aid=1792407&group_id=190976&atid=935520)
    private $maximumImageOffset = 2000;
    
    public function start($language) {
        include ('databaseconfig.php');
	    $this->language = $language;
        $this->dbConnection = mysql_connect($host,$user,$password, true /* open a new connection! */);
        if ( !mysql_select_db($dbprefix.$this->language , $this->dbConnection) );
        	echo mysql_error();
        $this->dbCommonsConnection = mysql_connect($host,$user,$password, true /* open a new connection! */);
        if ( !mysql_select_db($dbprefix.'commons' , $this->dbCommonsConnection) );
        	echo mysql_error();        	
    }
	
	public function extractPage($pageID, $pageTitle, $pageSource) {
		$result = new ExtractionResult(
			$pageID, $this->language, $this->getExtractorID());

		$image_ar = $this->extract_image_url($pageSource);

		$image = ucfirst($image_ar[0]);
		$width = $image_ar[1];

		if ($image == null) {
			return $result;
		}

		$ImageURL = $this->make_image_url($image, false, true);
		$ImageURLSmall = $this->make_image_url($image, $width);

		$image = str_replace(" ", "_", trim($image));

		if (!URI::validate($ImageURL) || !URI::validate($ImageURLSmall)
			// !URI::validate(URI::wikipediaEncode($ImageURL)) ||
			// !URI::validate(URI::wikipediaEncode($ImageURLSmall))
		) {
			return $result;
		}

		// Add fullsize image   
		$result->addTriple(
				RDFtriple::page($pageID), 
				RDFtriple::URI(FOAF_DEPICTION),
				RDFtriple::URI($ImageURL));

		// Add depiction has thumbnail  
		$result->addTriple(
				RDFtriple::URI($ImageURL), 
				RDFtriple::URI(FOAF_THUMBNAIL),
				RDFtriple::URI($ImageURLSmall));

		// Add object has thumbnail  
		$result->addTriple(
				RDFtriple::page($pageID), 
				RDFtriple::URI(DBO_THUMBNAIL),
				RDFtriple::URI($ImageURLSmall));

        // add triples linking back to the Wikipedia image description
        $image = urlencode($image);
        $wikipediaImageDescription = 'http://'.$this->language.'.wikipedia.org/wiki/Image:'.$image; 
        $result->addTriple(
                RDFtriple::URI($ImageURLSmall), 
                RDFtriple::URI(DC_RIGHTS),
                RDFtriple::URI($wikipediaImageDescription));                          

        $result->addTriple(
                RDFtriple::URI($ImageURL), 
                RDFtriple::URI(DC_RIGHTS),
                RDFtriple::URI($wikipediaImageDescription));                     
     
        return $result;
    }
    
    public function finish() { 
        mysql_close($this->dbConnection);
        mysql_close($this->dbCommonsConnection);
        return null;
    }
    
    
    function extract_image_url($text) {
	    $names = array();
	    $offsets = array();
	    $widths = array();
	    
	    // Remove HTML-Tags from text
	    $text = $tpl = trim(preg_replace("/<[^>]+>/"," ",$text));
	    
	    // find images in infoboxes
	    // $text_infoboxes = preg_replace('~{\|.*\|}~s', '', $text); // remove Prettytables
		preg_match_all('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x', $text, $templates); // search {{....}}

		foreach($templates[0] as $tpl) {
			if($tpl[0]!='{') {
				continue;
			}
			$tpl=substr($tpl,2,-2);

			$tpl=preg_replace('/<\!--[^>]*->/mU','',$tpl);

			//Replace "|" inside labeled links with *** to avoid splitting them like triples
			$tpl=preg_replace('/\[\[([^\]]+)\|([^\]]*)\]\]/','[[\1***\2]]',$tpl,-1,$count);
			// Replace "|" inside subtemplates with "\\" to avoid splitting them like triples
			$tpl = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/",array($this,'replaceBarInSubTemplate'),$tpl);

			// find template keyvalue pairs
			preg_match_all("/\|\s*([^=|]+)\s*=?([^|]*)/", $tpl, $props, PREG_SET_ORDER);

			foreach ($props as $keyvalue) {
				$keyvalue=str_replace('***','|',$keyvalue);
				$keyvalue=str_replace('####','|',$keyvalue);
				$propvalue = trim($keyvalue[2]);
				if(trim($propvalue) == "") {
					continue;
				}
			    if (preg_match_all("~[^\"/\*?<>|:]+\.(?:jpe?g|png|gif|svg)~i", $propvalue, $match, PREG_OFFSET_CAPTURE)) {
			        $trimmedImageName = trim($match[0][0][0]);
			    	$names[] = $trimmedImageName;
			    	$offsets[] = strpos($text, $trimmedImageName);
			        //$offsets[] = $match[0][0][1];
			        $widths[] = 200;
			        break 2;
			    }
			}
		}				
				
	    if (preg_match_all("~\[\[[a-zA-Z0-9-]+:([^]|]*\.(?:jpe?g|png|gif|svg))(\|[^]]*)?\]\]~i", $text, $match, PREG_OFFSET_CAPTURE)) {
	        $names[] = $match[1][0][0];
	        $offsets[] = $match[0][0][1];
	        $width = 200;
	        // Todo: where is $attributes defined/set?
	        if (isset($attributes[2])) {
	            $attributes = $match[2][0][0];
	            if (preg_match("/(\d+)px/", $attributes, $m)) {
	                $width = (int) $m[1];
	            }
	        }
	        $widths[] = $width;
	    }
	    
	    if (!$offsets) {
	        return null;
	    }
	
	    $smallest_offset = min($offsets);
	    // make sure the first image is not too far down in the text
	    // Todo: only needed if we use several regexes to find images (names array not in correct order)
	    if($smallest_offset > $this->maximumImageOffset) {
	    	return null;    
	    }
	    
	    for ($i = 0; $i < count($names); $i++) {
	        if ($offsets[$i] != $smallest_offset) continue;
	        // we detected the image with minimum offset
	        $name = $names[$i];
	        $width = $widths[$i];
	
	        // reject non-free images
	        if($this->is_image_nonfree($name))
	        	return null;
	    }

	    return array($name, $width);
	}

	// checks whether the image is non-free
	function is_image_nonfree($image_name) {
	    $clean_name = str_replace(" ", "_", trim($image_name));	
	
	    // first check current language Wikipedia
		$sql = "SELECT old_text FROM text,page WHERE text.old_id=page.page_latest AND page.page_namespace=6 AND page.page_title = '".mysql_escape_string($clean_name)."';";
		$res = mysql_query($sql, $this->dbConnection);
		if(!$res) 
			echo mysql_error();
		if(mysql_num_rows($res)>0) {
			$var = mysql_fetch_array($res);
			$img_desc = $var['old_text'];
			//if(preg_match('/{{non-free/i', $img_desc) != 0)
			if(stripos($img_desc,'{{non-free')) {
				return true;
			}
		}
		
	    // check Wikipedia Commons
		$sql = "SELECT old_text FROM text,page WHERE text.old_id=page.page_latest AND page.page_namespace=6 AND page.page_title = '".mysql_escape_string($clean_name)."';";
		$res = mysql_query($sql, $this->dbCommonsConnection);
		if(!$res) 
			echo mysql_error();
		else if(mysql_num_rows($res)>0) {
			$var = mysql_fetch_array($res);
			$img_desc = $var['old_text'];
			if(stripos($img_desc,'{{non-free'))
				return true;
		}
		
		return false;
	}

	function make_image_url($image_name, $width = false, $fullsize = false) {
		$clean_name = str_replace(" ", "_", trim($image_name));
	    $hash = md5($clean_name);
	    $hash1 = substr($hash, 0, 1);
	    $hash2 = substr($hash, 0, 2);
	    if (!$width) {
	        $width = 200;
	    }
	    $ext = preg_match("/.svg$/i", $image_name) ? ".png" : "";
		// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
	    $sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($clean_name)."';";
	    $sqlQuery = mysql_query($sql, $this->dbConnection);
	    if ( !$sqlQuery) echo mysql_error();
	    if ( mysql_num_rows($sqlQuery) ) $prefix = "http://upload.wikimedia.org/wikipedia/en/";
	    else $prefix = "http://upload.wikimedia.org/wikipedia/commons/";
		
		// urlencode the image name, otherwise it won't valdiate
	    // example: http://en.wikipedia.org/wiki/Diego_Vel%C3%A1zquez
	    $clean_name = urlencode($clean_name);
	    
	    if ( !$fullsize )
			return $prefix . "thumb/{$hash1}/{$hash2}/{$clean_name}/{$width}px-{$clean_name}{$ext}";
		else 
			return $prefix . "{$hash1}/{$hash2}/{$clean_name}";
	}

	// Helpfunction for preg_replace_callback, to replace "|" with #### inside subtemplates
	public static function replaceBarInSubTemplate($stringArray) {
		return str_replace("|","####",$stringArray[0]);
	}
}


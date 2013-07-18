<?php

/**
 * The PropertyExtractor finds all properties that are used in all templates
 * and writes them into the dbpedia_properties_[$language] database.
 *
 * @author: Paul Kreis <mail@paulkreis.de>
 */
class PropertyExtractor extends  Extractor {
	private $DumpFile = "";
	private $FileName = "";
	
    public function start($language) {
        $this->language = $language;
        $this->counter = 0;
		$this->FileName = "TEIL 2 properties_$language.sql";
		$this->DumpFile = fOpen($this->FileName,"w");
	}
	
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        include ("databaseconfig.php");

        $this->counter++;
		echo $this->counter . "\n";

        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
        if($this->decode_title($pageTitle)==NULL) return $result;

        // Remove comments
        $text = Util::removeComments($pageSource);

        // Search {{....}}
        preg_match_all('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x', $text, $rawTemplates);

        foreach($rawTemplates[0] as $rawTemplate) {
            if($rawTemplate[0]!='{') {
                return $result;
            }

            // Delete {{ and }}
            $rawTemplate = substr($rawTemplate,2,-2);

            // get template name
            preg_match_all("/([^|]*)/", $rawTemplate, $templateNames, PREG_SET_ORDER);
            $templateName = strtolower(trim($templateNames[0][0]));

            // Remove comments
            $rawTemplate = Util::removeComments($rawTemplate);
            // Replace "|" inside subtemplates to avoid splitting them like triples
            $rawTemplate = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/",array($this,'replaceBarInSubTemplate'),$rawTemplate);
            // Replace "|" inside labeled links to avoid splitting them like triples
            $check = false;
            while ($check === false) {
                $rawTemplate1 = preg_replace('/\[\[([^\]]+)\|([^\]]*)\]\]/','[[\1***@@@***@@@***@@@***@@@\2]]',$rawTemplate,-1,$count);
                if ($rawTemplate == $rawTemplate1) {
                    $check = true;
                    $rawTemplate = $rawTemplate1;
                } else {
                    $rawTemplate = $rawTemplate1;
                }
            }

            // Find template keyvalue pairs
            preg_match_all("/\|\s*\|?\s*([^=|<>]+)\s*=([^|]*)/", $rawTemplate, $keyvalues, PREG_SET_ORDER); // my original
            //preg_match_all("/\|\s*([^=]+)\s*=?([^|]*)/", $rawTemplate, $keyvalues, PREG_SET_ORDER); // new MBE
            //preg_match_all("/\|\s*([^=]+)\s*=([^|]*)/", $rawTemplate, $keyvalues, PREG_SET_ORDER); // orginal

            // Next template if there are no keyvalue pairs
            if (count($keyvalues) == 0) {
                return $result;
            }

            foreach ($keyvalues as $keyvalue) {
                $keyvalue = str_replace('***@@@***@@@***@@@***@@@','|',$keyvalue);
                $keyvalue = str_replace('***---***---***---***---','|',$keyvalue);
                $propkey = trim($keyvalue[1]);
                $propvalue = trim($keyvalue[2]);

                if ($propvalue == '') continue;

                $s = "http://dbpedia.org/resource/" . URI::wikipediaEncode($pageID);
                $p = "http://dbpedia.org/property/" . $this->propertyToCamelCase($propkey);
                $o = $propvalue;
                $line = "INSERT INTO propertietriples (resourceURI, propertiyURI, propertyValue) VALUES ('$s','".mysql_escape_string($p)."','".mysql_escape_string($o)."')";
				fWrite($this->DumpFile, $line."\n");
            }
            // add wikiPageUsesTemplate
            $p = "http://dbpedia.org/property/wikiPageUsesTemplate";
            $o = "http://dbpedia.org/resource/Template:".$this->encodeLocalName($templateName);
            $line = "INSERT INTO propertietriples (resourceURI, propertiyURI, propertyValue) VALUES ('$s','".mysql_escape_string($p)."','".mysql_escape_string($o)."')";
			fWrite($this->DumpFile, $line."\n");
        }
        return $result;
    }

    /**
    * Replace forbidden Ascii symbols by "-"
    *
    * Forbidden Ascii symbols in a String are replaced by -
    *
    * @param	string	$string	any text
    * @return	string	$string	Text mit valid Ascii symbols
    */
    function encodeLocalName($string) {
        //  return urlencode(str_replace(" ","_",trim($string)));
        $string = urlencode(str_replace(" ","_",trim($string)));
        // Decode slash "/", colon ":", as wikimedia does not encode these
        $string = str_replace("%2F","/",$string);
        $string = str_replace("%3A",":",$string);

        return $string;
    }

    private function decode_title($s) {
        if (is_null($s)) return null;
        $label = preg_replace("/^(Category|Template):/", "", str_replace('_', ' ', $s));
        // Take care of "(" ")" "&"
        $label = str_replace('%28','(',$label);
        $label = str_replace('%29',')',$label);
        $label = str_replace('%26','&',$label);
        return $label;
    }

    /**
     * Helpfunction for preg_replace_callback, to replace "|" with ***---***---***---***--- inside subtemplates
     *
     * @param unknown_type $stringArray
     * @return string
     */
    private static function replaceBarInSubTemplate($stringArray) {
        return str_replace('|','***---***---***---***---',$stringArray[0]);
    }

    /**
     * Converts Multi-word properties to CamelCase (e.g. "place_of_birth" => "placeOfBirth")
     *
     * @param $predicate
     * @return $predicate
     */
    private function propertyToCamelCase($predicate) {
        //	Start Consistent Property Names (CamelCase)
        $predicate = strtolower($predicate);
        $pSingleWords = preg_split("/_+|\s+|\-|:+/",$predicate);
        $predicate = $pSingleWords[0];
        for($i=1; $i < count($pSingleWords); $i++) {
            $predicate .= ucwords($pSingleWords[$i]);
        }
        // Replace digits at the beginning of a property with _. E.g. 01propertyName => _01propertyName (edited by Piet)
        if ( preg_match("/^([0-9]).*$/",$predicate) ) $predicate = "_" . $predicate;
        $predicate = str_replace('/','%2F',$predicate);
        return $predicate;
    }

    public function finish() {
        //return $this->getPredicates();
    }
}
?>

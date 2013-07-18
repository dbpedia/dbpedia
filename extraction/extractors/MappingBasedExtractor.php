<?php

/**
 * The MappingBasedExtractor extracts infoboxes/templates from Wikipedia articles.
 * It matches the template properties to the DBpedia ontology ones.
 *
 * @author: Georgi Kobilarov (FU Berlin)
 */

class MappingBasedExtractor extends Extractor {
    public $flagNewSchema = false;
    public $flagStrictExport = false;

    private $mysql;

    public function start($language) {
        $this->language = $language;
        include ("databaseconfig.php");
        $catalog = $dbprefix."extraction_".$language;
        $this->mysql = new MySQL($host, $user, $password, $catalog);
    }

    public function extractPage($pageID, $pageTitle,  $pageSource) {
        
        $mysql = $this->mysql;

        $result = new ExtractionResult(
        $pageID, $this->language, $this->getExtractorID());
        if($this->decode_title($pageTitle)==NULL) return $result;

        // don't remove tables
        // 1. some templates are used within templates, e.g. http://en.wikipedia.org/wiki/Plato
        // 2. the regex sometimes reaches PREG_BACKTRACK_LIMIT_ERROR
        // $text=preg_replace('~{\|.*\|}~s','',$pageSource); // remove Prettytables
        $text = $pageSource;

		$templates = Util::getTemplates($text);

		foreach ($templates as $template) {
			$tpl = $template["content"];
			
            //TODO: HIER NICHT "TEMPLATE" HARDCODE, SONDERN SPRACHABHAENGIG
            $dbpedia_uri = "http://dbpedia.org/resource/Template:" . Util::encodeLocalName($template["name"]);
            //var_dump($dbpedia_uri);

            // get template ID from DB
            $templatequery = "select template_id from template_uri where uri = '$dbpedia_uri'";

            $templatequeryresult = $mysql->query($templatequery);
            $tqrow = mysql_fetch_array($templatequeryresult, MYSQL_ASSOC);
            $template_id = $tqrow['template_id'];
            if(!isset($template_id)) {
                continue;
            }
			
            $props = Util::getTemplateProperties($tpl);
            
            //TODO: INNER JOIN VERWENDEN STATT NORMALEM JOIN
            // find ontology class with template id
            $classquery = "select name, class_id from class, template_class where template_class.template_id = '$template_id' and template_class.class_id = class.id";

            $classqueryresult = $mysql->query($classquery);
            $cqrow = mysql_fetch_array($classqueryresult, MYSQL_ASSOC);
            $class_id = $cqrow['class_id'];
            $class_name = $cqrow['name'];

            // get template properties
            $template_properties = array();
            $template_properties_to_merge = array();

            // get merging rules for template ID
            $mergequery = "select ordered_template_property_ids from template_property_merge_rule where template_id = '$template_id'";
            $mergequeryresult = $mysql->query($mergequery);

            $i = 0;
            while ($mergerow = mysql_fetch_array($mergequeryresult, MYSQL_ASSOC)) {
                $temp = explode(",", $mergerow['ordered_template_property_ids']);
                $merging_group_count[$i] = 0;
                foreach ($temp as $tempp) {
                    $template_properties_to_merge[] = $tempp;
                    $merging_group[$tempp] = $i;
                    $merging_group_count[$i]++;
                }
                $i++;
            }

            $merge_template_sets_done = array();
            $main_propid_from_merging_group[] = array();
            $main_propvalue_from_merging_group[] = array();

            
            foreach ($props as $keyvalue) {
                $propkey = mysql_escape_string($keyvalue[1]);
				$propvalue = $keyvalue[2];
				
                if ((trim($propvalue) == "") || ($propvalue == null)) {
                    continue;
                }
                $propquery = "select id from template_property where name = '$propkey' and template_id = '$template_id'";
                $propqueryresult = $mysql->query($propquery);
                $pqrow = mysql_fetch_array($propqueryresult, MYSQL_ASSOC);
                $template_property_id = $pqrow['id'];
                if(!is_null($template_property_id)) {
                    if (!in_array($template_property_id, $template_properties_to_merge)) {
                        $template_properties[$template_property_id] = trim($propvalue);
                    } else {
                        $query = "select class_property_id from template_property_class_property where template_property_id = $template_property_id";
                        $dbresult = $mysql->query($query);
                        $target_unit = null;
                        while ($row = mysql_fetch_array($dbresult, MYSQL_ASSOC)) {
                            $class_property_ids = $row['class_property_id'];
                            $ptrquery = "select * from parser_type_rule where class_property_id = '$class_property_ids'";
                            $ptrresult = $mysql->query($ptrquery);
                            $ptrrow = mysql_fetch_array($ptrresult, MYSQL_ASSOC);
                            $parser_rule = $ptrrow['parser_type'];
                            $unit_type = $ptrrow['unit_type'];
                            $target_unit = $ptrrow['target_unit'];
                        }
                        $unit_exact_type = null;
                        $query1 = "select unit_exact_type from template_parser_type_rule where template_property_id = $template_property_id";
                        $dbresult1 = $mysql->query($query1);
                        while ($row1 = mysql_fetch_array($dbresult1, MYSQL_ASSOC)) {
                            if (strlen($row1['unit_exact_type']) > 0) {
                                $unit_exact_type = $row1['unit_exact_type'];
                            }
                        }

                        if (!in_array($merging_group[$template_property_id], $merge_template_sets_done)) {
                            $merge_template_sets_done[] = $merging_group[$template_property_id];
                            $propvalue = trim($propvalue);
                            if ($parser_rule == "unit") {
                                if ($unit_type == "Length") {
                                    $parseResultArray = UnitValueParser::parseValue($propvalue, $this->language, array(PAGEID=>$pageID, PROPERTYNAME=>$propkey, UNITTYPE=>$unit_type, UNITEXACTTYPE=>$unit_exact_type, TARGETUNIT=>$target_unit, IGNOREUNIT=>true));
                                    if(!is_null($parseResultArray)) {
                                        foreach ($parseResultArray as $parseResults) {
                                            $propvalue = (string)$parseResults[0] . " $unit_exact_type";
                                        }
                                    }
                                }
                            } else if ($parser_rule == "geocoordinates") {
                                if (($merging_group_count[$merging_group[$template_property_id]] == 6) || ($merging_group_count[$merging_group[$template_property_id]] == 8)) {
                                    //{{coord|51|30|29|N|00|07|29|W}}
                                    $propvalue = "{{coord|".$propvalue;
                                    $geocoordinatescount = $merging_group_count[$merging_group[$template_property_id]]-1;
                                }
                            } else {
                                // TODO: new Unit type!
                            }
                            $template_properties[$template_property_id] = $propvalue;
                            $main_propid_from_merging_group[$merging_group[$template_property_id]] = $template_property_id;
                            $main_propvalue_from_merging_group[$merging_group[$template_property_id]] = $propvalue;
                        } else {
                            $main_propvalue = $main_propvalue_from_merging_group[$merging_group[$template_property_id]];
                            $main_template_property_id = $main_propid_from_merging_group[$merging_group[$template_property_id]];
                            if ($parser_rule == "unit") {
                                if ($unit_type == "Length") {
                                    $parseResultArray = UnitValueParser::parseValue($propvalue, $this->language, array(PAGEID=>$pageID, PROPERTYNAME=>$propkey, UNITTYPE=>$unit_type, UNITEXACTTYPE=>$unit_exact_type, TARGETUNIT=>$target_unit));
                                    if(!is_null($parseResultArray)) {
                                        foreach ($parseResultArray as $parseResults) {
                                            $propvalue = (string)$parseResults[0] . " $unit_exact_type";
                                        }
                                    }
                                    $template_properties[$main_template_property_id] = $main_propvalue . " " . $propvalue;
                                }
                            } else if ($parser_rule == "geocoordinates") {
                                $geocoordinatescount--;
                                if (($merging_group_count[$merging_group[$template_property_id]] == 6) || ($merging_group_count[$merging_group[$template_property_id]] == 8)) {
                                    //{{coord|51|30|29|N|00|07|29|W}}
                                    if ($geocoordinatescount == 0) {
                                        $propvalue = $propvalue."}}";
                                    }
                                    $main_propvalue_from_merging_group[$merging_group[$main_template_property_id]] = $main_propvalue . "|". $propvalue;
                                    $template_properties[$main_template_property_id] = $main_propvalue . "|" . $propvalue;
                                } else {
                                    $main_propvalue_from_merging_group[$merging_group[$main_template_property_id]] = $main_propvalue . " " . $propvalue;
                                    $template_properties[$main_template_property_id] = $main_propvalue . " " . $propvalue;
                                }
                            } else {
                                // TODO: new Unit type!
                            }
                            unset($template_properties[$template_property_id]);
                        }
                    }
                }
            }

            foreach ($template_properties as $template_property_id => $propvalue) {
                $query = "select class_property_id from template_property_class_property where template_property_id = $template_property_id";
                $dbresult = $mysql->query($query);
                while ($row = mysql_fetch_array($dbresult, MYSQL_ASSOC)) {
                    $class_property_ids = $row['class_property_id'];

                    // foreach template_property_class_property.class_property_id
                    // get parser_type_rule.parser_type
                    $target_unit = null;
                    $ptrquery = "select * from parser_type_rule where class_property_id = '$class_property_ids'";
                    $ptrresult = $mysql->query($ptrquery);
                    $ptrrow = mysql_fetch_array($ptrresult, MYSQL_ASSOC);
                    $parser_rule = $ptrrow['parser_type'];
                    $unit_type = $ptrrow['unit_type'];
                    $target_unit = $ptrrow['target_unit'];

                    $cpquery = "select cp.type, cp.datatype_range, cp.name, c.name as superclass from class_property cp inner join class c on(cp.class_id = c.id) where cp.id = $class_property_ids";
                    $cpresult = $mysql->query($cpquery);
                    $cprow = mysql_fetch_array($cpresult, MYSQL_ASSOC);
                    $property_type = $cprow['type'];
                    $datatype_range = $cprow['datatype_range'];
                    $property_name = $cprow['name'];

                    $ontclass = $cprow['superclass'];


                    //IF PROPERTY IS NOT FROM ONTOLOGY, BUT EXTERNAL, SUCH AS FOAF
                    if(!$cprow) {
                        $cpquery = "select name, uri, class_id from class_property where id = $class_property_ids";
                        $cpresult = $mysql->query($cpquery);
                        $cprow = mysql_fetch_array($cpresult, MYSQL_ASSOC);

                        //TODO: IST DIE CLASS_ID NICHT IMMER NULL IN DIESEM FALL???
                        $domain_class_id = $cprow['class_id'];

                        if (($domain_class_id == null) && ($cprow['uri'] == "http://xmlns.com/foaf/0.1/") && ($cprow['name'] == "homepage")) {
                            try {
                                $result->addTriple(
                                RDFtriple::page($pageID),
                                RDFtriple::URI("http://xmlns.com/foaf/0.1/homepage"),
                                RDFtriple::URI($propvalue));
                            } catch(Exception $e) {
                                //TODO uncorrect URI
                            }
                        } else if (($domain_class_id == null) && ($cprow['uri'] == "http://xmlns.com/foaf/0.1/") && ($cprow['name'] == "name")) {
                            if (strpos($propvalue, "{{PAGENAME}}") === false) {

                                if (strpos($propvalue, "{{") === false) {
                                    $parseResults = StringParser::parseValue($propvalue, $this->language, null);

                                    foreach($parseResults as $mystring) {
                                        if($mystring != "") {
                                            $result->addTriple(
                                            RDFtriple::page($pageID),
                                            RDFtriple::URI("http://xmlns.com/foaf/0.1/name"),
                                            RDFtriple::Literal($mystring));
                                        }
                                    }
                                }

                            }
                        }
                    } else {

                        /*
                        if (strpos($propvalue, "[[") !== false) {
                        $propvalue = Util::replaceWikiLinks($propvalue);
                        }
                        */

                        switch($property_type) {
                            case 'object':
                                $rangequery = "SELECT c.name as rangeclass FROM class_property_range cpr inner join class c on (cpr.range_class_id = c.id) where property_id = $class_property_ids";
                                $rangeresult = $mysql->query($rangequery);
                                $rowrange = mysql_fetch_array($rangeresult, MYSQL_ASSOC);
                                $rangeclass = $rowrange['rangeclass'];
                                $propvalue = Util::removeWikiEmphasis($propvalue);

                                //TODO:ADD LANGUAGE AS PARAM
                                $parseResults = ObjectTypeParser::parseValue($propvalue,$this->language,$rangeclass);

                                foreach($parseResults as $r) {
                                    $result->addTriple(
                                    RDFtriple::page($pageID),
                                    RDFtriple::property($ontclass, $property_name, $this->flagNewSchema),
                                    RDFtriple::page($r));
                                }
                                break;

                            case 'datatype':
                                switch ($datatype_range) {
                                    case 'string':
                                       switch ($parser_rule) {
                                            case 'geocoordinates':
                                                //TODO: Predicate URIs entweder nur in DB oder nur hardcoden?

                                                $propvalue = Util::removeHtmlTags($propvalue);
                                                $propvalue = Util::removeHtmlComments($propvalue);
                                                $parseResultArray = GeoParser::parseValue($propvalue, $this->language, null);
                                                if(!is_null($parseResultArray)) {

                                                    // http://www.georss.org/georss/point:(NULL) 52.5166666667 13.4166666667
                                                    // geo:lat 52.516666 (xsd:float)
                                                    // geo:long 13.416667 (xsd:float)

                                                    // $output = array('georss'=>$georss,'lat'=>$lat,'long'=>$long);

                                                    $georss = $parseResultArray["georss"];
                                                    $lat = $parseResultArray["lat"];
                                                    $long = $parseResultArray["long"];

                                                    if($georss != null){
                                                        $result->addTriple(
                                                        RDFtriple::page($pageID),
                                                        RDFtriple::URI("http://www.georss.org/georss/point"),
                                                        RDFtriple::Literal($georss));
                                                    }
                                                    if($lat != null){
                                                        $result->addTriple(
                                                        RDFtriple::page($pageID),
                                                        RDFtriple::URI("http://www.w3.org/2003/01/geo/wgs84_pos#lat"),
                                                        RDFtriple::Literal($lat, "http://www.w3.org/2001/XMLSchema#float",NULL));
                                                    }
                                                    if($long != null){
                                                        $result->addTriple(
                                                        RDFtriple::page($pageID),
                                                        RDFtriple::URI("http://www.w3.org/2003/01/geo/wgs84_pos#long"),
                                                        RDFtriple::Literal($long, "http://www.w3.org/2001/XMLSchema#float",NULL));
                                                    }
                                                } else {
                                                    //TODO: DEBUG LOGFILE FOR UN-PARSED VALUES
                                                    $this->addLiteral($result, $pageID, $ontclass, $property_name, $propvalue);
                                                }
                                                break;
                                            default:
                                                $parseResults = StringParser::parseValue($propvalue, $this->language, null);
                                                foreach($parseResults as $mystring) {
                                                    $this->addLiteral($result, $pageID, $ontclass, $property_name, $mystring);
                                                }
                                                break;
                                        }
                                        break;
                                    case 'integer':
                                        if (strpos($propvalue, "{{") !== false) {
                                            $propvalue = Util::replaceTemplates($propvalue);
                                        }
                                        $propvalue = Util::removeHtmlTags($propvalue);
                                        $propvalue = Util::removeHtmlComments($propvalue);
                                        $propvalue = Util::removeWikiEmphasis($propvalue);

                                        /*
                                        preg_match_all("/([^0-9]+)[0-9]/", $propvalue, $other_characters, PREG_SET_ORDER);
                                        $only_commata_whitespaces_dots = true;
                                        foreach ($other_characters as $other_character) {
                                        //echo $other_character[1];
                                        if (($other_character[1] != " ") && ($other_character[1] != ",") && ($other_character[1] != ".")) {
                                        $only_commata_whitespaces_dots = false;
                                        break;
                                        }
                                        }
                                        if ($only_commata_whitespaces_dots) {
                                        $propvalue = preg_replace("/[^0-9]*([0-9])/", "$1", $propvalue);
                                        }
                                        */

                                        $parseResults = NumberParser::parseValue($propvalue, $this->language, array("integer"));

                                        if(!is_null($parseResults)) {
                                            $this->addLiteral($result, $pageID, $ontclass, $property_name, $parseResults, "http://www.w3.org/2001/XMLSchema#integer");
                                        } else {
                                            //TODO: ADD DEGUB LOGFILE FOR UN-PARSED TRIPLES
                                            if (!$this->flagStrictExport) {
                                                $this->addLiteral($result,$pageID,$ontclass,$property_name,$propvalue);
                                            }
                                        }

                                        break;

                                    case 'float':
                                        if (strpos($propvalue, "{{") !== false) {
                                            $propvalue = Util::replaceTemplates($propvalue);
                                        }
                                        $propvalue = Util::removeHtmlTags($propvalue);
                                        $propvalue = Util::removeHtmlComments($propvalue);
                                        $propvalue = Util::removeWikiEmphasis($propvalue);

                                        $parseResults = NumberParser::parseValue($propvalue, $this->language, array("float"));

                                        if(!is_null($parseResults)) {
                                            $this->addLiteral($result,$pageID,$ontclass, $property_name,$parseResults, "http://www.w3.org/2001/XMLSchema#float");
                                        } else {
                                            //TODO: ADD DEGUB LOGFILE FOR UN-PARSED TRIPLES
                                            if (!$this->flagStrictExport) {
                                                $this->addLiteral($result,$pageID,$ontclass,$property_name,$propvalue);
                                            }
                                        }
                                        break;

                                    case 'double':
                                       switch ($parser_rule) {
                                            case 'currency':

                                                $propvalue = Util::removeHtmlTags($propvalue);
                                                $propvalue = Util::removeHtmlComments($propvalue);
                                                $parseResultArray = UnitValueParser::parseValue($propvalue, $this->language, array(PAGEID=>$pageID, PROPERTYNAME=>$property_name, UNITTYPE=>'Currency', UNITEXACTTYPE=>null, TARGETUNIT=>null));
                                                if(!is_null($parseResultArray)) {
                                                    foreach ($parseResultArray as $parseResults) {
                                                        $parsedDataType = $parseResults[1];
                                                        if($parsedDataType == "") {
                                                            $parsedDataType = null;
                                                        }

                                                        if($parseResults[0] != ""){
                                                            $this->addLiteral($result,$pageID,$ontclass, $property_name,(string)$parseResults[0],$parsedDataType);
                                                        }
                                                    }
                                                } else {
                                                    if (!$this->flagStrictExport) {
														$propvalue = Util::removeTemplates($propvalue);
                                                        $this->addLiteral($result,$pageID,$ontclass, $property_name,$propvalue);
                                                    }
                                                }
                                                break;

                                            case 'unit':
                                                $propvalue = Util::replaceWikiLinks($propvalue);

                                                //TODO: WARUM NUR IN DIESEM FALL CITE RAUSNEHMEN?
                                                preg_match_all("/{{2}cite.*?\}{2}/i", $propvalue, $matches);
                                                foreach ($matches as $match) {
                                                    $propvalue = str_replace($match[0], Util::replaceTemplates($match[0]) ,$propvalue);
                                                }
                                                $propvalue = Util::removeHtmlTags($propvalue);
                                                $propvalue = Util::removeHtmlComments($propvalue);

                                                // get unit exact type

                                                $unit_exact_type = null;
                                                // if property is part of merged properties then unit is (probably) already appended, otherwise append unit (here: "exact unit type")
                                                if (!in_array($template_property_id, $template_properties_to_merge)) {
                                                    $query1 = "select unit_exact_type from template_parser_type_rule where template_property_id = $template_property_id";
                                                    $dbresult1 = $mysql->query($query1);
                                                    while ($row1 = mysql_fetch_array($dbresult1, MYSQL_ASSOC)) {
                                                        if (strlen($row1['unit_exact_type']) > 0) {
                                                            $unit_exact_type = $row1['unit_exact_type'];
                                                        }
                                                    }
                                                }

                                                $parseResultArray = UnitValueParser::parseValue($propvalue, $this->language, array(PAGEID=>$pageID, PROPERTYNAME=>$property_name, UNITTYPE=>$unit_type, UNITEXACTTYPE=>$unit_exact_type, TARGETUNIT=>$target_unit));

                                                if(!is_null($parseResultArray)) {
                                                    foreach ($parseResultArray as $parseResults) {
                                                        $parsedDataType = $parseResults[1];
                                                        if($parsedDataType == "") {
                                                            $parsedDataType = null;
                                                        }
                                                        if($parseResults[0] != "") {
                                                            $this->addLiteral($result,$pageID,$ontclass,$property_name,(string)$parseResults[0],$parsedDataType);
                                                        }
                                                    }
                                                } else {
                                                    //TODO: GENERATE LOGFILE WITH UNPARSED VALUES
                                                    if (!$this->flagStrictExport) {
                                                        $this->addLiteral($result,$pageID,$ontclass,$property_name,$propvalue);
                                                    }
                                                }

                                                break;
                                            default:
                                                if (strpos($propvalue, "{{") !== false) {
                                                    $propvalue = Util::replaceTemplates($propvalue);
                                                }
                                                $propvalue = Util::removeHtmlTags($propvalue);
                                                $propvalue = Util::removeHtmlComments($propvalue);
                                                $propvalue = Util::removeWikiEmphasis($propvalue);

                                                $parseResults = NumberParser::parseValue($propvalue, $this->language, array("float"));

                                                if(!is_null($parseResults)) {
                                                    $this->addLiteral($result,$pageID,$ontclass,$property_name,$parseResults,"http://www.w3.org/2001/XMLSchema#double");
                                                } else {
                                                    //TODO: ADD DEGUB LOGFILE FOR UN-PARSED TRIPLES
                                                    if (!$this->flagStrictExport) {
                                                        $this->addLiteral($result,$pageID,$ontclass, $property_name,$propvalue);
                                                    }
                                                }
                                                break;
                                       }
                                       break;

                                    case 'date':
                                        // TODO: when DateTimeParser uses restrictions (start date / end date), pass them as parameter
                                        // $parseResults = DateTimeParser::parseValue($propvalue, $this->language, array($unit_type));
                                        $parseResultArray = DateTimeParser::parseValue($propvalue, $this->language, array(PAGEID=>$pageID, PROPERTYNAME=>$property_name, UNITTYPE=>$unit_type, UNITEXACTTYPE=>$unit_exact_type, TARGETUNIT=>$target_unit));

                                        if(!is_null($parseResultArray)) {
                                            $this->addLiteral($result,$pageID,$ontclass,$property_name,$parseResultArray[0],$parseResultArray[1]);
                                        } else {
                                            if(!$this->flagStrictExport) {
                                                $parseResults = StringParser::parseValue($propvalue, $this->language, null);

                                                foreach($parseResults as $mystring) {
                                                    $this->addLiteral($result,$pageID,$ontclass,$property_name,$mystring);
                                                }
                                            }
                                        }

                                        break;

                                    default:
                                        break;

                                }

                                break;

                            default:

                                break;

                        }
                    }
                }
            }

        }

        return $result;
    }

    function encode_title($s, $namespace = null) {
        $result = urlencode(str_replace(' ', '_', $s));
        if ($namespace) {
            $result = $namespace . ":" . $result;
        }
        return $result;
    }

    function decode_title($s) {
        if (is_null($s)) return null;
        $label = preg_replace("/^(Category|Template):/", "", str_replace('_', ' ', $s));
        // take care of "(" ")" "&"
        $label = str_replace('%28','(',$label);
        $label = str_replace('%29',')',$label);
        $label = str_replace('%26','&',$label);
        return $label;
    }

    public function getLinkForLabeledLink($text2) {
        return preg_replace("/\|.*/", "", $text2) ;
    }

    public function setFlagForNewSchemaExport($flag) {
           $this->flagNewSchema = $flag;
    }

    public function setFlagForStrictExport($flag) {
           $this->flagStrictExport = $flag;
    }
    
    private function addLiteral( $result, $pageID, $class, $property, $value, $datatype = null, $lang = null ) {
        $result->addTriple(
        RDFtriple::page($pageID),
        RDFtriple::property($class, $property, $this->flagNewSchema),
        RDFtriple::Literal($value, $datatype, $lang));
    }
}

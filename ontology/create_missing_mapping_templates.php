<?php

require_once("../extraction/databaseconfig.php");
require_once("mapping_db_util.php");
require_once("../extraction/parsers/config.inc.php");

$conditions = array("value" => "contains", "equals" => "equals", "set" => "isSet", "exists" => "equals");

function getExactUnit($template_property_id) {
    $query1 = "select unit_exact_type, standard_unit from template_parser_type_rule where template_property_id = $template_property_id";
    $dbresult1 = mysql_query($query1);
    while ($row1 = mysql_fetch_array($dbresult1, MYSQL_ASSOC)) {
        if (strlen($row1['unit_exact_type']) > 0) {
            return $row1['unit_exact_type'];
        }
    }
}

function getStandardUnit($template_property_id) {
    $query1 = "select unit_exact_type, standard_unit from template_parser_type_rule where template_property_id = $template_property_id";
    $dbresult1 = mysql_query($query1);
    while ($row1 = mysql_fetch_array($dbresult1, MYSQL_ASSOC)) {
        if (strlen($row1['standard_unit']) > 0) {
            return $row1['standard_unit'];
        }
    }
}

if(false === function_exists('lcfirst')) {
    /**
    * Make a string's first character lowercase
    *
    * @param string $str
    * @return string the resulting string.
    */
    function lcfirst( $str ) {
        $str[0] = strtolower($str[0]);
        return (string)$str;
    }
}

$mappings_dir = "mappings";

$dimensions = array("Area" => $GLOBALS['Area'], "Volume" => $GLOBALS['Volume'], "Length" => $GLOBALS['Length'], "Speed" => $GLOBALS['Speed'], "Force" => $GLOBALS['Force'], "Energy" => $GLOBALS['Energy'], "Temperature" => $GLOBALS['Temperature'], "Mass" => $GLOBALS['Mass'], "Pressure" => $GLOBALS['Pressure'], "Torque" => $GLOBALS['Torque'], "FuelEfficiency" => $GLOBALS['FuelEfficiency'], "Power" => $GLOBALS['Power'], "PopulationDensity" => $GLOBALS['PopulationDensity'], "InformationUnit" => $GLOBALS['InformationUnit'], "Frequency" => $GLOBALS['Frequency'], "FlowRate" => $GLOBALS['FlowRate'], "Density" => $GLOBALS['Density']);

$db = "dbpedia_extraction_en";
$db_en = "dbpedia_en";
$link = mysql_connect("160.45.137.74", "root", "timbuktu.war.einmal1") or die ("No connection to database possible.");
mysql_select_db($db, $link);

$dbpedia_datatypes = array();

// directory

if (!file_exists($mappings_dir)) {
    mkdir($mappings_dir);
}
if (!is_dir($mappings_dir)) {
    echo "$mappings_dir should be a directory, not a file";
    die();
}

$query_template_uri = "select * from template_uri";
$dbresult = mysql_query($query_template_uri) or die("Query failed: " . mysql_error() . ' - ' . $query_template_uri);
while ($row = mysql_fetch_array($dbresult, MYSQL_ASSOC)) {
    $template_id = $row["template_id"];
    $template = strtolower(str_replace("http://dbpedia.org/resource/Template:", "", $row["uri"]));
    $template = str_replace("/help", "", $template);

    $pageTitles = array();
    $classIdForTemplate = array();
    $classNames = array();

    mysql_select_db($db_en, $link);
    // 
    $query = "select * from page where page_namespace = 10 and page_is_redirect = 1 and LOWER(CONVERT(page_title USING latin1)) = '$template'";
    $dbresult_template = mysql_query($query) or die("Query failed: " . mysql_error() . ' - ' . $query);
    while ($row_template = mysql_fetch_array($dbresult_template, MYSQL_ASSOC)) {
        //echo $row_template["page_title"], PHP_EOL;
        $pageTitle = $row_template["page_title"];
        if (mysql_num_rows($dbresult_template) > 1) {
            echo "For template $template there is more than one entry which is a redirect: ".$row_template["page_title"], PHP_EOL;
        }
        $query_lang = "select old_text from text t inner join page p on (p.page_latest = t.old_id) where p.page_title = '" . mysql_escape_string($pageTitle) . "' and page_namespace = 10";
        $result_lang = mysql_query($query_lang);
        while ($row_text = mysql_fetch_array($result_lang, MYSQL_ASSOC)) {
            $returnString = $row_text["old_text"];
        }
        if(isset($returnString)) {
            preg_match("~\[\[Template:(.*)\]\]~", $returnString, $matches);
            if (isset($matches[1])) {
                $mappingfile = str_replace(" ", "_", $matches[1]);
                if (!file_exists("../trunk/extraction/src/main/resources/mappings-3.5/".$mappingfile.".txt")) {
                    $pageTitles[] = $mappingfile;
                    echo $row_template["page_title"].PHP_EOL;
                    mysql_select_db($db, $link);
                    $classquery = "select name, class_id from class, template_class where template_class.template_id = '$template_id' and template_class.class_id = class.id";
                    $classqueryresult = mysql_query($classquery);
                    $cqrow = mysql_fetch_array($classqueryresult, MYSQL_ASSOC);
                    $class_id = $cqrow['class_id'];
                    $class_name = $cqrow['name'];
                    $classIdForTemplate[$mappingfile] = $class_id;
                    $classNames[$class_id] = $class_name;
                    mysql_select_db($db_en, $link);
                }
            }
        }
    }

    // add rules (uri)
    /*
    mysql_select_db($db, $link);
    $result = mysql_query("SELECT new_class_id FROM rule_uri WHERE template_uri = '".$row["uri"]."'");
    while ($row_rule = mysql_fetch_row($result)) {
        //$this->rules_uri[$row_rule[0]] = $row_rule[1];
        mysql_select_db($db_en);
        $template_rule = strtolower(str_replace("http://dbpedia.org/resource/Template:", "", $row["uri"]));
        $template_rule = str_replace("/help", "", $template_rule);
        $query = "select * from page where page_namespace = 10 and lower(page_title) LIKE '$template_rule'";
        $dbresult_template = mysql_query($query) or die("Query failed: " . mysql_error() . ' - ' . $query);
        while ($row_template = mysql_fetch_array($dbresult_template, MYSQL_ASSOC)) {
            $pageTitles[] = $row_template["page_title"];
            if (mysql_num_rows($dbresult_template) > 1) {
                echo "For template $template_rule there is more than one entry: ".$row_template["page_title"], PHP_EOL;
            }
            mysql_select_db($db);
            $classquery = "select id, name from class where id = ".$row_rule[0];
            $classqueryresult = mysql_query($classquery);
            $cqrow = mysql_fetch_array($classqueryresult, MYSQL_ASSOC);
            $class_id = $cqrow['id'];
            $class_name = $cqrow['name'];
            $classIdForTemplate[$row_template["page_title"]] = $class_id;
            $classNames[$class_id] = $class_name;
            mysql_select_db($db_en);
        }
    }
    */

    mysql_select_db($db);
    foreach ($pageTitles as $id => $pageTitle) {
        $result = mysql_query("SELECT template_property, type, value, new_class_id FROM rule_property WHERE class_id = ".$classIdForTemplate[$pageTitle]);
        $row_count = mysql_num_rows($result);
        if ($row_count > 0) {
            $output = "{{ConditionalMapping\n| cases =\n";
            while ($row_rules = mysql_fetch_row($result)) {
                $output .= " {{Condition";
                if ($row_rules[1] == "exists") {
                    $output .= "\n    | templateProperty = 1\n";
                    $output .= "    | mode = ".$conditions[$row_rules[1]]."\n";
                    $output .= "    | value = ".$row_rules[0]."\n }}\n";
                } else {
                    $output .= "\n    | templateProperty = ".$row_rules[0]."\n";
                    $output .= "    | mode = ".$conditions[$row_rules[1]]."\n";
                    if ($row_rules[1] == "set") {
                        $output .= " }}\n";
                    } else {
                        $output .= "    | value = ".$row_rules[2]."\n }}\n";
                    }
                }
                $output .= " {{TemplateMapping\n";
                $classquery = "select name from class where id = ".$row_rules[3];
                $classqueryresult = mysql_query($classquery);
                $cqrow = mysql_fetch_array($classqueryresult, MYSQL_ASSOC);
                $output .= "    | mapToClass = ".$cqrow['name']."\n }}\n\n";
            }
            $output .= " {{Condition | otherwise }}\n";
            $output .= " {{TemplateMapping\n";
            $output .= "    | mapToClass = ".$classNames[$classIdForTemplate[$pageTitle]]."\n }}\n\n";
        } else {
            $output = "{{TemplateMapping\n";
            $output .= "| mapToClass = ".$classNames[$classIdForTemplate[$pageTitle]]."\n";
        }

            // get merging rules for template ID
            $mergequery = "select ordered_template_property_ids from template_property_merge_rule where template_id = '$template_id'";
            $mergequeryresult = mysql_query($mergequery);
            $i = 0;
            $template_properties_to_merge = array();
            $merging_group = array();
            while ($mergerow = mysql_fetch_array($mergequeryresult, MYSQL_ASSOC)) {
                $temp = explode(",", $mergerow['ordered_template_property_ids']);
                foreach ($temp as $tempp) {
                    $template_properties_to_merge[] = $tempp;
                    $merging_group[$i][] = $tempp;
                }
                $i++;
            }


            $query = "select * from template_property where template_id = '$template_id'";
            $query_template_properties = mysql_query($query);
            $template_properties = array();
            while ($row_template_properties = mysql_fetch_array($query_template_properties, MYSQL_ASSOC)) {
                $template_properties[$row_template_properties["id"]] = $row_template_properties["name"];
            }
            if (sizeof($template_properties) > 0) {
                if ($row_count > 0) {
                    $output .= "| defaultMappings = \n";
                } else {
                    $output .= "| mappings = \n";
                }
                foreach ($template_properties as $template_property_id => $template_property) {
                    $unit_exact_type = getExactUnit($template_property_id);
                    $standard_unit = getStandardUnit($template_property_id);

                    $query = "select class_property_id from template_property_class_property where template_property_id = $template_property_id";
                    $dbresult2 = mysql_query($query);
                    $target_unit = null;
                    while ($row_class_property = mysql_fetch_array($dbresult2, MYSQL_ASSOC)) {
                        $class_property_id = $row_class_property['class_property_id'];
                        $ptrquery = "select * from parser_type_rule where class_property_id = $class_property_id";
                        $ptrresult = mysql_query($ptrquery);
                        $ptrrow = mysql_fetch_array($ptrresult, MYSQL_ASSOC);
                        $parser_rule = $ptrrow['parser_type'];
                        $unit_type = $ptrrow['unit_type'];
                        $target_unit = $ptrrow['target_unit'];

                        $cpquery = "select cp.type, cp.datatype_range, cp.name, c.name as superclass from class_property cp inner join class c on(cp.class_id = c.id) where cp.id = $class_property_id";
                        $cpresult = mysql_query($cpquery);
                        $cprow = mysql_fetch_array($cpresult, MYSQL_ASSOC);
                        $property_type = $cprow['type'];
                        $datatype_range = $cprow['datatype_range'];
                        $property_name = $cprow['name'];

                        $class = $cprow['superclass'];

                        if (!in_array($template_property_id, $template_properties_to_merge)) {
                            $output .= "\t{{PropertyMapping | templateProperty = $template_property ";
                            if ($class && $property_name) {
                                $output .= "| ontologyProperty = $property_name ";
                            } else {
                                $cpquery = "select name from class_property where id = $class_property_id";
                                $cpresult = mysql_query($cpquery);
                                $cprow = mysql_fetch_array($cpresult, MYSQL_ASSOC);
                                $property_name = $cprow['name'];
                                if (!$class && $property_name == "name") {
                                    $output .= "| ontologyProperty = foaf:name ";
                                } else if (!$class && $property_name == "homepage") {
                                    $output .= "| ontologyProperty = foaf:homepage ";
                                } else {
                                    echo "No ontology class or property ($property_name) defined for: $template_property", PHP_EOL;
                                }
                            }

                            if($unit_exact_type) {
                                $unit = $unit_exact_type;
                                foreach ($dimensions as $dimension => $units) {
                                    if (isset($units[$unit])) {
                                        $unit = $units[$unit];
                                        break;
                                    }
                                }
                                $unit = str_replace(" ", "", lcfirst(ucwords(strtolower(trim($unit)))));
                                $output .= "| unit = $unit ";
                            } else if ($standard_unit) {
                                foreach ($dimensions as $dimension => $units) {
                                    if (in_array($standard_unit, $units)) {
                                        $output .= "| unit = $dimension ";
                                    }
                                }
                            }
                            $output .= "}}\n";
                        }
                    }
                }
            }

            // merged properties
            foreach ($merging_group as $template_properties) {
                $brackets_open = false;
                foreach ($template_properties as $id => $template_property_id) {
                    $query = "select name from template_property where id = $template_property_id";
                    $query_template_property = mysql_query($query);
                    while ($row_template_properties = mysql_fetch_array($query_template_property, MYSQL_ASSOC)) {
                        $template_property = $row_template_properties["name"];
                    }

                    $unit_exact_type = getExactUnit($template_property_id);

                    $query = "select class_property_id from template_property_class_property where template_property_id = $template_property_id";
                    $dbresult2 = mysql_query($query);
                    $target_unit = null;
                    if (mysql_num_rows($dbresult2) > 1) {
                        die("[DIE] merged property should be mapped to more than 1 class property!");
                    }
                    while ($row_class_property = mysql_fetch_array($dbresult2, MYSQL_ASSOC)) {
                        $class_property_id = $row_class_property['class_property_id'];
                        $ptrquery = "select * from parser_type_rule where class_property_id = $class_property_id";
                        $ptrresult = mysql_query($ptrquery);
                        $ptrrow = mysql_fetch_array($ptrresult, MYSQL_ASSOC);
                        $parser_rule = $ptrrow['parser_type'];
                        $unit_type = $ptrrow['unit_type'];
                        $target_unit = $ptrrow['target_unit'];
                        $cpquery = "select cp.type, cp.datatype_range, cp.name, c.name as superclass from class_property cp inner join class c on(cp.class_id = c.id) where cp.id = $class_property_id";
                        $cpresult = mysql_query($cpquery);
                        $cprow = mysql_fetch_array($cpresult, MYSQL_ASSOC);
                        $property_type = $cprow['type'];
                        $datatype_range = $cprow['datatype_range'];
                        $property_name = $cprow['name'];
                        $class = $cprow['superclass'];
                    }

                    if ($parser_rule == "unit") {
                        if ($unit_type == "Length") {
                            if ($id == 0) {
                                $output .= "\t{{CalculateMapping | operation = add";
                                $brackets_open = true;
                            }
                            $output .= " | templateProperty".($id+1)." = $template_property ";
                            if($unit_exact_type) {
                                $unit = $unit_exact_type;
                                foreach ($dimensions as $dimension => $units) {
                                    if (isset($units[$unit])) {
                                        $unit = $units[$unit];
                                        break;
                                    }
                                }
                                $unit = str_replace(" ", "", lcfirst(ucwords(strtolower(trim($unit)))));
                                $output .= " | unit".($id+1)." = $unit ";
                            } else {
                                die("[DIE] no unit defined for $class.$property_name");
                            }
                            if ($id == (sizeof($template_properties)-1)) {
                             $output .= " | ontologyProperty = $property_name ";
                            }
                        } else {
                            echo "[DEFINE] $parser_rule $unit_type", PHP_EOL;
                        }
                    } else if ($parser_rule == "geocoordinates") {
                        if ($id == 0) {
                            $output .= "\t{{GeocoordinatesMapping";
                            $brackets_open = true;
                            //echo "geo: $template", PHP_EOL;
                        }
                        $geo = array(
                            "latd" => "latitudeDegrees",
                            "lat_d" => "latitudeDegrees",
                            "lat_sec" => "latitudeSeconds",
                            "latm" => "latitudeMinutes",
                            "longd" => "longitudeDegrees",
                            "long_d" => "longitudeDegrees",
                            "long_sec" => "longitudeSeconds",
                            "longm" => "longitudeMinutes",
                            "latNS" => "latitudeDirection",
                            "lat_EW" => "latitudeDirection",
                            "E_or_W" => "latitudeDirection",
                            "N_or_S" => "longitudeDirection",
                            "lat_degrees" => "latitudeDegrees",
                            "lat_direction" => "latitudeDirection",
                            "lat_m" => "latitudeMinutes",
                            "lat_minutes" => "latitudeMinutes",
                            "lat_seconds" => "latitudeSeconds",
                            "latitude" => "latitude",
                            "longitude" => "longitude",
                            "lats" => "latitudeSeconds",
                            "lon_deg" => "longitudeDegrees",
                            "lon_min" => "longitudeMinutes",
                            "lon_sec" => "longitudeSeconds",
                            "longEW" => "longitudeDirection",
                            "long_NS" => "longitudeDirection",
                            "long_degrees" => "longitudeDegrees",
                            "long_direction" => "longitudeDirection",
                            "long_m" => "longitudeMinutes",
                            "long_minutes" => "longitudeMinutes",
                            "long_seconds" => "longitudeSeconds",
                            "longs" => "longitudeSeconds",
                            "source_lat_NS" => "latitudeDirection",
                            "source_lat_d" => "latitudeDegrees",
                            "source_lat_m" => "latitudeMinutes",
                            "source_lat_s" => "latitudeSeconds",
                            "source_long_EW" => "longitudeDirection",
                            "source_long_d" => "longitudeDegrees",
                            "source_long_m" => "longitudeMinutes",
                            "source_long_s" => "longitudeSeconds",
                        );
                        if (isset($geo[$template_property])) {
                            $output .= " | ".$geo[$template_property]." = $template_property ";
                        } else {
                            $missing[$template_property] = "1";
                            //echo"[GEO] ".$template_property. " $template", PHP_EOL;
                        }
                    } else if ($parser_rule == "date") {
                        if ($id == 0) {
                            $output .= "\t{{CombineDateMapping";
                            $brackets_open = true;
                            echo "[MISSING DATE UNIT] $template", PHP_EOL;
                        }
                        $output .= " | templateProperty".($id+1)." = $template_property ";
                        $output .= " | unit".($id+1)." = ";
                        if ($id == (sizeof($template_properties)-1)) {
                         $output .= " | ontologyProperty = $property_name ";
                        }
                    } else {
                        echo "New parser rule for merging: $parser_rule", PHP_EOL;
                    }
                }
                if ($brackets_open) {
                    $output .= "}}\n";
                }
            }

        $output .= "}}";
        $fileName = $mappings_dir."/".$pageTitle.'.txt';
        $file = $fileName;
        $handle = fopen($file, 'w+');
        fwrite($handle, $output);
        fclose($handle);
    }
}

if (isset($missing)) {
    echo "Missing Geo-TemplateProperty-Mappings:\n";
    ksort($missing);
    foreach ($missing as $name => $bla) {
        echo " \"$name\" => \"\",\n";
    }
}
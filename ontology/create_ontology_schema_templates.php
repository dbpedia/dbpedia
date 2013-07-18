<?php

require_once("../extraction/databaseconfig.php");
require_once("mapping_db_util.php");
require_once("../extraction/parsers/config.inc.php");

$ontologyschema_dir = "ontologyschema/dbpedia";

$dimensions = array("Area" => $GLOBALS['Area'], "Volume" => $GLOBALS['Volume'], "Length" => $GLOBALS['Length'], "Speed" => $GLOBALS['Speed'], "Force" => $GLOBALS['Force'], "Energy" => $GLOBALS['Energy'], "Temperature" => $GLOBALS['Temperature'], "Mass" => $GLOBALS['Mass'], "Pressure" => $GLOBALS['Pressure'], "Torque" => $GLOBALS['Torque'], "FuelEfficiency" => $GLOBALS['FuelEfficiency'], "Power" => $GLOBALS['Power'], "PopulationDensity" => $GLOBALS['PopulationDensity'], "InformationUnit" => $GLOBALS['InformationUnit'], "Frequency" => $GLOBALS['Frequency'], "FlowRate" => $GLOBALS['FlowRate'], "Density" => $GLOBALS['Density']);

$db = "dbpedia_extraction_en";
$link = mysql_connect($host, $user, $password) or die ("No connection to database possible.");
mysql_select_db($db, $link);

$dbpedia_datatypes = array();

// directory

if (!file_exists($ontologyschema_dir)) {
    mkdir($ontologyschema_dir);
}
if (!is_dir($ontologyschema_dir)) {
    echo "$ontologyschema_dir should be a directory, not a file";
    die();
}

// Get classes

$query1 = "SELECT name, parent_id, label FROM class ";
$result1 = mysql_query($query1) or die("Query error: " . mysql_error());

// Add classes to OWL

while ($line=mysql_fetch_array($result1, MYSQL_ASSOC) ) {
	$class=$line["name"];
	$label=$line["label"];
	$subclassnr=$line["parent_id"];

	if ($class != "Thing") {
        if (!is_dir($ontologyschema_dir."/".$class)) {
            mkdir($ontologyschema_dir."/".$class);
        }
        $output = "{{ DBpediaClass\n|rdfs:label = $label\n";
		if ($subclassnr!="") {
			$ssubname="SELECT name from class WHERE id=$subclassnr";
			$subresult=mysql_query($ssubname);
			$sub= mysql_fetch_array($subresult, MYSQL_ASSOC);
			$subclass=$sub["name"];
			if ($subclass == "Thing") {
				$output .= "|rdfs:subClassOf = owl:Thing\n";
			} else {
				$output .= "|rdfs:subClassOf = $subclass\n";
			}
		}
        $output .= "}}";
        $fileName = $ontologyschema_dir."/".$class.'.txt';
        $file = $fileName;
        $handle = fopen($file, 'w+');
        fwrite($handle, $output);
        fclose($handle);
	}
}

// Get properties

$property_domains;
$property_labels;
$property_range_types;

$property_range_from_domain = array();

$query2 = "SELECT name, class_id, type, datatype_range, id, label FROM class_property WHERE class_id IS NOT NULL";
$result2 = mysql_query($query2) or die("Query error: " . mysql_error());
while ($prop=mysql_fetch_array($result2, MYSQL_ASSOC) ) {
	$classid=$prop["class_id"];
	$propertytype=$prop["type"];
	$property=$prop["name"];
	$propertyformat=$prop["datatype_range"];
	$propertyid=$prop["id"];
	$propertylabel=$prop["label"];
	if (! isset($property_ranges[$property])) {
		$property_ranges[$property] = array();
	}
	
	$selclass="SELECT name from class WHERE id=$classid";
	if ($classresult = mysql_query($selclass)) {
		if(mysql_num_rows($classresult)>0) {
			while($cl= mysql_fetch_array($classresult, MYSQL_ASSOC)) {
				$classname = $cl["name"];
				if (($property == "name") || ($property == "homepage")) {
					echo "$classname.$property should use foaf: properties";
				}
			}
		} else {
			if (($property != "name") && ($property != "homepage")) {
				echo "Error, class.id: $class_id, Property: $property\n";
			}
		}
	} else {
		if (($property != "name") && ($property != "homepage")) {
			echo "Error, class.id: $class_id, Property: $property\n";
		}
	}
	$property_domains[$property][] = $classname;
	$property_labels[$property][] = $propertylabel;

	if ($propertytype=='object') {
		// TODO check
		if (isset($property_range_types[$property]) && $property_range_types[$property] == "datatype") {
			echo (implode("; ", $property_domains[$property]) . " - $property: object and datatype ranges\n");
		}
		$property_range_types[$property] = "object";
		$queryrange = "SELECT name FROM class_property_range, class where property_id=$propertyid and id=range_class_id ";
		$rg=mysql_query($queryrange);
		while( $ran= mysql_fetch_array( $rg, MYSQL_ASSOC)){
			if (!in_array($ran["name"], $property_ranges[$property])) {
				$property_ranges[$property][] = $ran["name"];
				$property_range_from_domain[$property][$classname][] = $ran["name"];
			} else {
				$property_range_from_domain[$property][$classname][] = $ran["name"];
			}
		}
	} elseif ($propertytype=='datatype') {
		// TODO check
		if (isset($property_range_types[$property]) && $property_range_types[$property] == "object") {
			echo (implode("; ", $property_domains[$property]) . " - $property: object and datatype ranges\n");
		}
		$property_range_types[$property] = "datatype";
		if ($propertyformat!="") {
			if ($propertyformat=="String") {
				if (!in_array("string", $property_ranges[$property])) {
					$property_ranges[$property][] = "string";
					$property_range_from_domain[$property][$classname][] = "string";
				}
			} else if ($propertyformat=="double") {
                $query_dbpedia_datatype = "SELECT target_unit, unit_type, parser_type FROM parser_type_rule WHERE class_property_id = ".$propertyid;
                $result_dbpedia_datatype = mysql_query($query_dbpedia_datatype) or die("Query error: " . mysql_error());
                $row_dbpedia_datatype = mysql_fetch_row($result_dbpedia_datatype);
                $unit_dimension = $row_dbpedia_datatype[0];
                if ($row_dbpedia_datatype[1]) {
                    $dimension = $row_dbpedia_datatype[1];
                } else if ($row_dbpedia_datatype[2]) {
                    $dimension = ucfirst($row_dbpedia_datatype[2]);
                } else {
                    die ("No dimension: $class.$property");
                }
				if (!in_array($dimension, $property_ranges[$property])) {
                    $property_ranges[$property][] = $dimension;
                    $dbpedia_datatypes[] = $dimension;
                }
                if ($unit_dimension) {
                    $unit_dimension = str_replace(" ", "", lcfirst(ucwords(strtolower(trim($unit_dimension)))));
					$property_range_from_domain[$property][$classname][] = $unit_dimension;
                    $dbpedia_datatypes[] = $unit_dimension;
                }
                else if ($dimension == "Currency") {
					$property_range_from_domain[$property][$classname][] = $dimension;
                } else {
                    die ("Unknown dimension or unit for $classname.$property");
                }
            } else {
				if (!in_array($propertyformat, $property_ranges[$property])) {
					$property_ranges[$property][] = $propertyformat;
					$property_range_from_domain[$property][$classname][] = $propertyformat;
				} else {
					$property_range_from_domain[$property][$classname][] = $propertyformat;
				}
			}
		}
	}

}

// Add properties to OWL

$addedSubProperties = array();
foreach ($property_domains as $property => $domains) {
    if (($property != "name") && ($property != "homepage")) {
        foreach ($domains as $domain) {
            if ($domain != "Thing") {
                if($property_range_types[$property] == "object") {
                    $output = "{{ DBpediaObjectProperty\n";
                } else {
                    $output = "{{ DBpediaDatatypeProperty\n";
                }
                $propertylabel = "";
                foreach ($property_labels[$property] as $property_label) {
                    if (strlen(trim($property_label)) > strlen($propertylabel)) {
                        $propertylabel = trim($property_label);
                    }
                }
                if ($propertylabel == "") {
                    $propertylabel = $property;
                }
                $output .= "|rdfs:label = $propertylabel\n";

                if ($domain == "Thing") {
                } else {
                    $output .= "|rdfs:domain = $domain\n";
                }
                if($property_range_types[$property] == "object") {
                    if (sizeof($property_range_from_domain[$property][$domain]) == 1) {
                        if ($property_range_from_domain[$property][$domain][0] == "Thing") {
                            $output .= "|rdfs:range = owl:Thing\n";
                            echo "$domain.$property has owl:Thing as range, please find appropriate class", PHP_EOL;
                        } else {
                            $output .= "|rdfs:range = ".$property_range_from_domain[$property][$domain][0]."\n";
                        }
                    } else if (sizeof($property_range_from_domain[$property][$domain]) == 0) { // object, but no Class as range -> owl:Thing
                        //$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
                    } else {
                        echo "Property $domain.$property has more than 1 range", PHP_EOL;
                        echo " Ranges: [X] ", $property_range_from_domain[$property][$domain][0], ", [ ] ", $property_range_from_domain[$property][$domain][1], PHP_EOL;
                        $output .= "|rdfs:range = ".$property_range_from_domain[$property][$domain][0]."\n";
                    }
                }
                if($property_range_types[$property] == "datatype") {
                    if (sizeof($property_range_from_domain[$property][$domain]) > 1) {
                        echo "More than one range on a DatatypeProperty: $domain.$property", PHP_EOL;
                    } else {
                        if (isset($property_range_from_domain[$property][$domain][0])) {
                            if (in_array($property_range_from_domain[$property][$domain][0], $dbpedia_datatypes)) {
                                $output .= "|rdfs:range = ".$property_range_from_domain[$property][$domain][0]."\n";
                            } else {
                                $output .= "|rdfs:range = xsd:".$property_range_from_domain[$property][$domain][0]."\n";
                            }
                        } else {
                            echo "No range on DatatypeProperty: $domain.$property", PHP_EOL;
                        }
                    }
                }
                $output .= "}}";
                $fileName = "$ontologyschema_dir/$domain/$property.txt";
                $file = $fileName;
                $handle = fopen($file, 'w+');
                fwrite($handle, $output);
                fclose($handle);
            } else {
                //echo "Property $property has owl:Thing as domain", PHP_EOL;
            }
        }
    }
}
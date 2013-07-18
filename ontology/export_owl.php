<?php

/**
* Export Wikipedia template mappings from database to DBpedia ontology
* as OWL.
*
* @author	Anja Jentzsch <mail@anjajentzsch.de>
*/

$version = "3.4";
$date = "2009-10-05";

require_once("../extraction/databaseconfig.php");
require_once("mapping_db_util.php");
require_once("../extraction/parsers/config.inc.php");

$dimensions = array("Area" => $GLOBALS['Area'],"Volume" => $GLOBALS['Volume'], "Length" => $GLOBALS['Length'], "Speed" => $GLOBALS['Speed'], "Force" => $GLOBALS['Force'], "Energy" => $GLOBALS['Energy'], "Temperature" => $GLOBALS['Temperature'], "Mass" => $GLOBALS['Mass'], "Pressure" => $GLOBALS['Pressure'], "Torque" => $GLOBALS['Torque'], "FuelEfficiency" => $GLOBALS['FuelEfficiency'], "Power" => $GLOBALS['Power'], "PopulationDensity" => $GLOBALS['PopulationDensity'], "InformationUnit" => $GLOBALS['InformationUnit'], "Frequency" => $GLOBALS['Frequency'], "FlowRate" => $GLOBALS['FlowRate'], "Density" => $GLOBALS['Density']);

$db = "dbpedia_extraction_en";
$link = mysql_connect($host, $user, $password) or die ("No connection to database possible.");
mysql_select_db($db, $link);

$dbpedia_datatypes = array();

// Get classes

$query1 = "SELECT name, parent_id, label FROM class ";
$result1 = mysql_query($query1) or die("Query error: " . mysql_error());
$output='<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
	xmlns = "http://dbpedia.org/ontology/"
	xml:base="http://dbpedia.org/ontology/"
	xmlns:owl="http://www.w3.org/2002/07/owl#"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">

	<owl:Ontology rdf:about="">
		<owl:versionInfo xml:lang="de">Version '.$version.' '.$date.'</owl:versionInfo>
	</owl:Ontology>
';

$output .= "\n";
foreach ($dimensions as $dimension => $unit)
{
    $output .= "\t<rdfs:Datatype rdf:about=\"http://dbpedia.org/ontology/dbpedia_datatypes_$version.xsd#$dimension\"/>\n";
    $units_dimension = array_values(array_unique($unit));
    foreach ($units_dimension as $unit_dimension)
    {
        $unit_dimension = str_replace(" ", "", lcfirst(ucwords(strtolower(trim($unit_dimension)))));;
        $output .= "\t\t<rdfs:Datatype rdf:about=\"http://dbpedia.org/ontology/dbpedia_datatypes_$version.xsd#$unit_dimension\"/>\n";
    }
}
$output .= "\n";

// Add classes to OWL

while ($line=mysql_fetch_array($result1, MYSQL_ASSOC) ) {
	$class=$line["name"];
	$label=$line["label"];
	$subclassnr=$line["parent_id"];

	if ($class != "Thing") {
		$output=$output."<owl:Class rdf:about=\"http://dbpedia.org/ontology/$class\">\n";
		$output.="\t<rdfs:label xml:lang=\"en\">$label</rdfs:label>\n";
		if ($subclassnr!="") {
			$ssubname="SELECT name from class WHERE id=$subclassnr";
			$subresult=mysql_query($ssubname);
			$sub= mysql_fetch_array($subresult, MYSQL_ASSOC);
			$subclass=$sub["name"];
			if ($subclass == "Thing") {
				$output=$output."\t<rdfs:subClassOf rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n" ;
			} else {
				$output=$output."\t<rdfs:subClassOf rdf:resource=\"http://dbpedia.org/ontology/$subclass\"/>\n" ;
			}
			$output=$output."</owl:Class>\n\n";
		}
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
		if($property_range_types[$property] == "object") {
			$output .= "<owl:ObjectProperty rdf:about=\"http://dbpedia.org/ontology/$property\">\n";
		} else {
			$output .= "<owl:DatatypeProperty rdf:about=\"http://dbpedia.org/ontology/$property\">\n";
		}
			$propertylabel = "";
			foreach ($property_labels[$property] as $property_label) {
				if (strlen(trim($property_label)) > strlen($propertylabel)) {
					$propertylabel = trim($property_label);
				}
			}
			if ($propertylabel == "") {
				// TODO: split at UpperCaseLetter or underscore (_)
				$propertylabel = $property;
			}
			$output.="\t<rdfs:label xml:lang=\"en\">$propertylabel</rdfs:label>\n";

			if (sizeof($property_domains[$property]) == 1) {
				if ($property_domains[$property][0] == "Thing") {
					$output .= "\t<rdfs:domain rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
				} else {
					$output .= "\t<rdfs:domain rdf:resource=\"http://dbpedia.org/ontology/".$property_domains[$property][0]."\"/>\n";
				}
			} else {
				$output .= "\t<rdfs:domain>\n";
				$output .= "\t\t<owl:Class>\n";
				$output .= "\t\t\t<owl:unionOf rdf:parseType=\"Collection\">\n";
				foreach ($property_domains[$property] as $propertydomain) {
					$output .= "\t\t\t\t<owl:Class rdf:about=\"$propertydomain\"/>\n";
				}
				$output .= "\t\t\t</owl:unionOf>\n";
				$output .= "\t\t</owl:Class>\n";
				$output .= "\t</rdfs:domain>\n";
			}

			if($property_range_types[$property] == "object") {
				if (sizeof($property_ranges[$property]) == 1) {
					if ($property_ranges[$property][0] == "Thing") {
						$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
					} else {
						$output .= "\t<rdfs:range rdf:resource=\"http://dbpedia.org/ontology/".$property_ranges[$property][0]."\"/>\n";
					}
				} else if (sizeof($property_ranges[$property]) == 0) { // object, but no Class as range -> owl:Thing
					$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
				} else {
					foreach ($property_ranges[$property] as $propertyrange) {
						if ($propertyrange == "Thing") {
							$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
						} else {
							$output .= "\t<rdfs:range rdf:resource=\"http://dbpedia.org/ontology/".$propertyrange."\"/>\n";
						}
					}
				}
			}
			if($property_range_types[$property] == "datatype") {
				foreach ($property_ranges[$property] as $propertyrange) {
                    if (in_array($propertyrange, $dbpedia_datatypes)) {
    					$output .= "\t<rdfs:range rdf:resource=\"http://dbpedia.org/ontology/".$propertyrange."\"/>\n";
                    } else {
    					$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2001/XMLSchema#".$propertyrange."\"/>\n";
                    }
				}
                if (sizeof($property_ranges[$property]) > 1)
                {
                    echo "More than one range on a DatatypeProperty: $domain.$property", PHP_EOL;
                }
			}

		if($property_range_types[$property] == "object") {
			$output .= "</owl:ObjectProperty>\n\n";
			foreach ($property_domains[$property] as $domain) {
				if ($domain != "Thing") {
					$output .= "<owl:ObjectProperty rdf:about=\"http://dbpedia.org/ontology/".$domain."/".$property."\">\n";
					$output .= "\t<rdfs:label xml:lang=\"en\">$propertylabel</rdfs:label>\n";
					$output .= "\t<rdfs:subPropertyOf rdf:resource=\"http://dbpedia.org/ontology/$property\"/>\n";
					$output .= "\t<rdfs:domain rdf:resource=\"http://dbpedia.org/ontology/".$domain."\"/>\n";
					if ($property_range_from_domain[$property][$domain][0] == "Thing") {
						$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2002/07/owl#Thing\"/>\n";
					} else {
						$output .= "\t<rdfs:range rdf:resource=\"http://dbpedia.org/ontology/".$property_range_from_domain[$property][$domain][0]."\"/>\n";
					}
					$output .= "</owl:ObjectProperty>\n\n";
				}
			}
		} else {
			$output .= "</owl:DatatypeProperty>\n\n";
			foreach ($property_domains[$property] as $domain) {
				if ($domain != "Thing") {
					$output .= "<owl:DatatypeProperty rdf:about=\"http://dbpedia.org/ontology/".$domain."/".$property."\">\n";
					$output .= "\t<rdfs:label xml:lang=\"en\">$propertylabel</rdfs:label>\n";
					$output .= "\t<rdfs:subPropertyOf rdf:resource=\"http://dbpedia.org/ontology/$property\"/>\n";
					$output .= "\t<rdfs:domain rdf:resource=\"http://dbpedia.org/ontology/".$domain."\"/>\n";
                    if (in_array($property_range_from_domain[$property][$domain][0], $dbpedia_datatypes)) {
    					$output .= "\t<rdfs:range rdf:resource=\"http://dbpedia.org/ontology/".$property_range_from_domain[$property][$domain][0]."\"/>\n";
                    } else {
    					$output .= "\t<rdfs:range rdf:resource=\"http://www.w3.org/2001/XMLSchema#".$property_range_from_domain[$property][$domain][0]."\"/>\n";
                    }
					$output .= "</owl:DatatypeProperty>\n\n";
				}
			}
		}
	}
}

$output.="</rdf:RDF>\n";
$fileName = 'dbpedia_'.$version.'.owl';
$uploadfile = $fileName;
$handle = fopen($uploadfile, 'w+');
fwrite($handle, $output);
fclose($handle);

$output = "<schema xmlns=\"http://www.w3.org/2001/XMLSchema\" targetNamespace=\"http://dbpedia.org/ontology/\" elementFormDefault=\"qualified\">\n";
foreach ($dimensions as $dimension => $unit)
{
    $output .= "<simpleType name=\"$dimension\" base=\"double\" />\n";
    $units_dimension = array_values(array_unique($unit));
    foreach ($units_dimension as $unit_dimension)
    {
        $unit_dimension = str_replace(" ", "", lcfirst(ucwords(strtolower(trim($unit_dimension)))));;
        $output .= "\t<simpleType name=\"$unit_dimension\" base=\"$dimension\" />\n";
    }
}

$output.="</schema>\n";
$fileName = 'dbpedia_datatypes_'.$version.'.xsd';
$uploadfile = $fileName;
$handle = fopen($uploadfile, 'w+');
fwrite($handle, $output);
fclose($handle);
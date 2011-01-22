<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

/**
* Insert Wikipedia template mapping rules into database.
*
* @author	Anja Jentzsch <mail@anjajentzsch.de>
*/

$csv_rules_class = "rules_class.csv";
$csv_rules_properties = "rules_properties.csv";

require_once("../extraction/databaseconfig.php");
require_once("mapping_db_util.php");

$language = "en";

mysql_connect ($host, $user, $password) or die ("Database connection could not be established.");
mysql_select_db ("dbpedia_extraction_".$language) or die ("Database 'dbpedia_extraction_$language' not found.");

dropAndCreateRulesTables();

$classes = array();

$result = mysql_query('SELECT id, name FROM class');
if (mysql_num_rows($result) > 0) {
	while ($row = mysql_fetch_row($result)) {
		$classes[$row[1]] = $row[0];
	}
}

$csv_rules_class_content[] = read_csv($csv_rules_class);

$csv_rules_properties_content[] = read_csv($csv_rules_properties);

// uri -> class
foreach ($csv_rules_class_content[0] as $csvRow) {
	$class = null;
	$template_uri = null;
	$csvRow[0] = trim($csvRow[0]);
	$csvRow[1] = trim($csvRow[1]);
	if ($csvRow[1] != "Set Class" && $csvRow[0] != "") {
		if (strpos($csvRow[0], "http://dbpedia.org/resource/Template:") === false) {
			echo "[DIE] Template URI of incorrect format: $csvRow[0]";
			die();
		} else {
			$template_uri = $csvRow[0];
		}
		if (!in_array(str_replace(" ", "", $csvRow[1]), array_keys($classes))) {
			echo "[DIE] Class does not exist: $csvRow[1]";
			die();
		} else {
			$class = str_replace(" ", "", $csvRow[1]);
			$class_id = $classes[$class];
		}
		$strSQL = "INSERT INTO rule_uri (template_uri, new_class_id) VALUES ('".$template_uri."', ".$class_id.")";
		if (!mysql_query ($strSQL)) {
			die("[DIE] " . mysql_error() . " - query: ".$strSQL);
		}
	}
}

// property -> class
foreach ($csv_rules_properties_content[0] as $csvRow) {
	$csvRow[0] = trim($csvRow[0]);
	$csvRow[1] = trim($csvRow[1]);
	$csvRow[2] = trim($csvRow[2]);
	$csvRow[3] = trim($csvRow[3]);
	$csvRow[4] = str_replace(" ", "", trim($csvRow[4]));
	if (($csvRow[0] != "class") && ($csvRow[1] != "property")) {
		$type = $csvRow[2];
	    if ($type === "set" || $type === "exists") {
	        $value = null;
	    } else if ($type === "value") {
			if ($csvRow[3] === "") {
				echo "[DIE] When using 'value' you have to define a value.";
				die();
			}
			$value = $csvRow[3];
	    } else {
			echo "[DIE] Use only 'set', 'exists' or 'value'.";
			die();
		}
		
		if (!in_array($csvRow[0], array_keys($classes))) {
			echo "[DIE] (Old) Class does not exist: $csvRow[0]";
			die();
		} else {
			$class = $csvRow[0];
			$class_id = $classes[$class];
		}
		
		if (!in_array($csvRow[4], array_keys($classes))) {
			echo "[DIE] (New) Class does not exist: $csvRow[4]";
			die();
		} else {
			$new_class = $csvRow[4];
			$new_class_id = $classes[$new_class];
		}
		$result = mysql_query('SELECT template_id FROM template_class where class_id='.$class_id);
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_row($result)) {
				$template_id = $row[0];
			}
		} else {
			echo "[DIE] No template URI for class $class found.";
			die();
		}
                /*
		$result = mysql_query('SELECT id FROM template_property where name="'.$csvRow[1].'" and template_id='.$template_id);
		$template_property_id = null;
                while ($row = mysql_fetch_row($result)) {
                    $template_property_id = $row[0];
		}
		if (!isset($template_property_id)) {
			echo "Template property $csvRow[1] does not exist for class $class and template_id $template_id.\n";
		} else {
                 */
                    $strSQL = "INSERT INTO rule_property (class_id, template_property, type, value, new_class_id) VALUES ('".$class_id."', '".$csvRow[1]."', '".$type."', '".$value."', '".$new_class_id."')";
                    if (!mysql_query ($strSQL)) {
                            die(mysql_error() . " - query: ".$strSQL);
                    }
                 // }
	}
}


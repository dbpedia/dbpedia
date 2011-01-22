<?php
/**
 * Helper functions for mapping_db.php
 */

function getSubClasses($class_id, $level = null) {
	$result = mysqlQuery("SELECT id FROM class where parent_id = '$class_id'");
	$classes = array();
	$classes[] = $class_id;
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			$sub_class_id = $row[0];
			$classes = array_merge($classes, getSubClasses($sub_class_id));
		}
	}
	return $classes;
}

/**
 * Get template property id from template id and template property name
 *
 * @param int $template_id
 * @param string $template_property
 * @return unknown
 */
function getTemplatePropertyId($template_id, $template_property) {
	$result = mysql_query('SELECT id FROM template_property where name="'.$template_property.'" and template_id='.$template_id);
	if (mysql_num_rows($result) == 1) {
		while ($row = mysql_fetch_row($result)) {
			return $row[0];
		}
	} elseif(mysql_num_rows($result) > 1) {
		echo "found duplicate definition of '$template_property' on template $template_id\n";
	}
	return null;
}

function insertTemplatePropertyAndGetId($template_id, $template_property) {
	if ($template_property_id = getTemplatePropertyId($template_id, $template_property)) {
		return $template_property_id;
	} else {
		$strSQL = "INSERT INTO template_property (template_id, name) VALUES ('".$template_id."', '".$template_property."')";
		mysql_query ($strSQL);
		if ($template_property_id = getTemplatePropertyId($template_id, $template_property)) {
			return $template_property_id;
		}
	}
	die ("couldn't insert or find template property '$template_property' on template '$template_id' - ".mysql_error());
	return null;
}

function getClassIdFromClassName($class_name) {
	global $classes_ids;

	$class_name = str_replace(" ", "", $class_name);
	if (sizeof($classes_ids) > 0) {
		$class_id = $classes_ids[$class_name];
		if (!is_numeric($class_id)) {
			echo __LINE__." class id for class $class_name not found\n";
		}
	} else {
		$result = mysqlQuery("SELECT id FROM class where name = '$class_name'");
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_row($result)) {
				return $row[0];
			}
		}
	}
	return $class_id;
}

function getClassNameFromClassId($class_id) {
	global $classes_ids;
	
	if (!$classes_ids) {
		$result = mysqlQuery("SELECT name FROM class where id = '$class_id'");
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_row($result)) {
				return $row[0];
			}
		}
	}
	return array_search($class_id, $classes_ids);
}

function getClassNameFromTemplateId($template_id) {
	global $classes_template_ids;

	return $classes_template_ids[$template_id];
}

function getOntologyPropertyId($class_id, $ontology_property_name) {
	$ontology_property_id = null;
	$result = mysql_query('SELECT id FROM class_property where name="'.$ontology_property_name.'" and class_id='.$class_id);
	if ($result) {
		if (mysql_num_rows($result) == 1) {
			while ($row = mysql_fetch_row($result)) {
				$ontology_property_id = $row[0];
			}
		} elseif(mysql_num_rows($result) > 1) {
			echo "found duplicate definition of '$ontology_property_name' on class $class_id\n";
		}
	}
	return $ontology_property_id;
}

function createOntologyDatatypePropertyAndGetId($class_id, $ontology_property_name, $type, $datatype_range, $ontology_property_label) {
	$strSQL = "INSERT INTO class_property (class_id, name, type, datatype_range, label) VALUES ('".$class_id."', '".$ontology_property_name."', '".$type."', '".strtolower($datatype_range)."', '".$ontology_property_label."')";
	if (!mysql_query ($strSQL)) {
		if (strpos(mysql_error(), "Duplicate entry") === false) {
			die(__LINE__." [DIE] class id: $class_id, $ontology_property_name - ".mysql_error());
		}
	}
	return getOntologyPropertyId($class_id, $ontology_property_name);
}

function createOntologyObjectPropertyAndGetId($class_id, $ontology_property_name, $type, $range, $ontology_property_label) {
	$result = mysql_query('SELECT id FROM class_property where name="'.$ontology_property_name.'" and class_id='.$class_id);
	if (mysql_num_rows($result) == 0) {
		$strSQL = "INSERT INTO class_property (class_id, name, type, label) VALUES ('".$class_id."', '".$ontology_property_name."', '".$type."', '".$ontology_property_label."')";
		if (!mysql_query ($strSQL)) {
			if (strpos(mysql_error(), "Duplicate entry") === false) {
				die(__LINE__." [DIE] class id: $class_id, $ontology_property_name - ".mysql_error());
			}
		}
		$ontology_property_id = getOntologyPropertyId($class_id, $ontology_property_name);
		if ($range_class_id = getClassIdFromClassName($range)) {
			$strSQL = "INSERT INTO class_property_range (property_id, range_class_id) VALUES ('".$ontology_property_id."', '".$range_class_id."')";
			if (!mysql_query ($strSQL)) {
				if (strpos(mysql_error(), "Duplicate entry") === false) {
					die(__LINE__." [DIE] class id: $class_id, $ontology_property_name - ".mysql_error());
				}
			}
		} else {
			die(__LINE__." [DIE] class $range not found in database!");
		}
	} else {
		while ($row = mysql_fetch_row ($result) ) {
			return $row[0];
		}
	}
	return getOntologyPropertyId($class_id, $ontology_property_name);
}

function create_property_mapping($ontology_property_id, $template_property_id) {
	$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$template_property_id."', '".$ontology_property_id."')";
	if (!mysql_query ($strSQL)) {
		if (strpos(mysql_error(), "Duplicate entry") === false) {
			die (__LINE__ ."[DIE] Template Mapping error - ".mysql_error());
		}
	}
}

/**
 * 
 *
 * @param string $class_id class id
 * @param string $ontology_property_name ontology property name
 * @param string $ontology_property_label ontology property label
 * @param string $range ontology property range
 * @return ontology property id
 */
function createOntologyProperty($class_id, $ontology_property_name, $ontology_property_label, $range) {
	global $datatype_ranges;
	global $parser_types;
	global $overall_classes;

	if ($ontology_property_name  == "") {
		if ($ontology_property_label != "") {
			$ontology_property_name = getCamelCasePropertyNameFromLabel($ontology_property_label);
		} else {
			echo __LINE__." This should never happen!";
		}
	}
	if (strpos($range, ",") !== false) {
		die (__LINE__ . ", in range");
	}
	// Object or Datatype Property?
	if (in_array(strtolower($range), $datatype_ranges)) {
		$range_type = "datatype";
		$range = strtolower($range);
	} elseif (in_array(strtolower($range), $parser_types)) {
		$range_type = "datatype";
		// TODO: New Parser Type Rule
		
		// FIXME FIXME FIXME: $property_name is not defined
		$template_property_parser[$property_name] = strtolower($range);
		if (strtolower($range) == "unit") {
			echo "$class_id.$ontology_property_name - We can't handle split properties with Units at the moment.";
		}
	} else {
		if ($range == "object") {
			$range = "Thing";
		}
		$range = str_replace(" ", "", $range);
		if (in_array($range, $overall_classes)) {
			$range_type = "object";
		} else {
			echo "[WARNING] Unknown range for $class_id.$ontology_property_name: $range\n";
		}
	}

	// Insert new class property and range into DB
	if (!$ontology_property_id = getOntologyPropertyId($class_id, $ontology_property_name)) {
		if ($range_type == "datatype") {
			$ontology_property_id = createOntologyDatatypePropertyAndGetId($class_id, $ontology_property_name, $range_type, $range, $ontology_property_label);
		} else {
			$ontology_property_id = createOntologyObjectPropertyAndGetId($class_id, $ontology_property_name, $range_type, $range, $ontology_property_label);
		}
	}
	if ($ontology_property_id) {
		if ($range_type == "datatype") {	// datatype property
			
			// FIXME FIXME FIXME: $one_property is not defined.
			
			$query = "INSERT INTO parser_type_rule (class_property_id, parser_type, unit_type) VALUES ('".$ontology_property_id."', '".$template_property_parser[$one_property]."', NULL)";
			mysqlQuery($query, __LINE__);
		}
	} else {
		die (__LINE__." [DIE] error");
	}
	return $ontology_property_id;
}

function createRelationFromTemplatePropertyToClassProperty($template_id, $template_property_id, $ontology_property_id) {
	$query = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$template_property_id."', '".$ontology_property_id."')";
	mysqlQuery($query, __LINE__);
	/* TODO If called from other than split property: Units are allowed...
	if ($range == "unit") {
		if ($unit_exact_type) {
			$query = "INSERT INTO template_parser_type_rule (template_property_id, unit_exact_type) VALUES ('".$template_property_id."', '".$unit_exact_type."')";
			if (!mysql_query ($strSQL)) {
				//die(mysql_error() . " - query: ".$strSQL);
			}
		}
	}
	*/
}

function mysqlQuery($query, $line = null) {
	global $csvRow;
	
	if (!$result = mysql_query ($query)) {
		$mysql_error = mysql_error();
		if (strpos($mysql_error, "Duplicate entry") === false) {
			if (strpos($mysql_error, "Data truncated for column 'unit_type'") !== false) {
				var_dump($csvRow);
				die("[LINE $line] MySQL Error: ".mysql_error()."\n  MySQL Query:".$query."\n  Missing parser rule type!");
			} else {
				var_dump($csvRow);
				die("[LINE $line] MySQL Error: ".mysql_error()."\n  MySQL Query:".$query);
			}
		}
	}
	return $result;
}

function getCamelCasePropertyNameFromLabel($label) {
	return str_replace(" ", "", lcfirst(ucwords(strtolower(trim($label)))));
}

/**
 * create 
 * @return void
 */
function createAndSelectDatabase( $name ) {
	mysqlQuery("CREATE DATABASE ".$name." CHARSET=binary;");
	mysql_select_db($name);
}

/**
 * drop and create rules tables
 */
function dropAndCreateRulesTables() {
	mysqlQuery("DROP TABLE IF EXISTS rule_property");
	mysqlQuery("CREATE TABLE IF NOT EXISTS rule_property (
		  class_id int(11) NOT NULL,
		  template_property varchar(255) NOT NULL,
		  type enum('value','set','exists') NOT NULL default 'value',
		  value varchar(255) NULL,
		  new_class_id int(11) NOT NULL
		  ) ENGINE=MyISAM CHARSET=binary;");

	mysqlQuery("DROP TABLE IF EXISTS rule_uri");
	mysqlQuery("CREATE TABLE IF NOT EXISTS rule_uri (
		  template_uri varchar(255) NOT NULL,
		  new_class_id int(11) NOT NULL
		  ) ENGINE=MyISAM  CHARSET=binary;");
}

/**
 * drop and create mapping tables
 */
function dropAndCreateMappingTables() {
	mysqlQuery("DROP TABLE IF EXISTS class");
	mysqlQuery("CREATE TABLE IF NOT EXISTS class (
		  id int(11) NOT NULL AUTO_INCREMENT,
		  name varchar(50) NOT NULL DEFAULT '',
		  parent_id int(11) DEFAULT NULL,
		  description varchar(255) DEFAULT NULL,
		  label varchar(255) DEFAULT NULL,
		  PRIMARY KEY (id),
		  UNIQUE KEY name (name)
		) ENGINE=MyISAM  CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS class_property");
	mysqlQuery("CREATE TABLE IF NOT EXISTS class_property (
		  id int(11) NOT NULL AUTO_INCREMENT,
		  name varchar(255) NOT NULL DEFAULT '',
		  class_id int(11) DEFAULT NULL,
		  type enum('object','datatype') NOT NULL DEFAULT 'object',
		  description varchar(255) DEFAULT NULL,
		  datatype_range varchar(100) DEFAULT NULL,
		  uri varchar(100) DEFAULT NULL,
		  label varchar(255) DEFAULT NULL,
		  PRIMARY KEY (id),
		  UNIQUE KEY name (name,class_id)
		) ENGINE=MyISAM  CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS class_property_range");
	mysqlQuery("CREATE TABLE IF NOT EXISTS class_property_range (
		  property_id int(11) NOT NULL DEFAULT '0',
		  range_class_id int(11) NOT NULL DEFAULT '0',
		  PRIMARY KEY (property_id,range_class_id)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS parser_type_rule");
	mysqlQuery("CREATE TABLE IF NOT EXISTS parser_type_rule (
		  class_property_id int(11) NOT NULL,
		  parser_type enum('date','geocoordinates','unit','currency','url','merge') NOT NULL,
		  unit_type enum('Length','Area','Volume','Speed','Force','Energy','Temperature','Mass','Pressure','Torque','FuelEfficiency','Power','PopulationDensity','Currency','Time','InformationUnit','Frequency','FlowRate','Density','Angle') DEFAULT NULL,
		  target_unit varchar(255) DEFAULT NULL,
		  PRIMARY KEY (class_property_id,parser_type)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_class");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_class (
		  template_id int(11) NOT NULL AUTO_INCREMENT,
		  class_id int(11) NOT NULL DEFAULT '0',
		  PRIMARY KEY (template_id,class_id),
		  UNIQUE KEY class_id (class_id)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_parser_type_rule");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_parser_type_rule (
		  template_property_id int(11) NOT NULL,
          unit_exact_type varchar(255) DEFAULT NULL,
		  standard_unit varchar(50) DEFAULT NULL,
  		  PRIMARY KEY template_property_id (template_property_id, unit_exact_type, standard_unit)
        ) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_property");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_property (
		  name varchar(255) NOT NULL,
		  id int(11) NOT NULL AUTO_INCREMENT,
		  template_id int(11) NOT NULL,
		  PRIMARY KEY (id),
		  UNIQUE KEY name (name,template_id)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_property_class_property");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_property_class_property (
		  template_property_id int(11) NOT NULL,
		  class_property_id int(11) NOT NULL,
		  PRIMARY KEY (template_property_id,class_property_id)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_property_merge_rule");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_property_merge_rule (
		  ordered_template_property_ids varchar(255) NOT NULL,
		  class_property_id int(11) NOT NULL,
		  template_id int(11) NOT NULL,
		  UNIQUE KEY ordered_template_property_ids (ordered_template_property_ids,class_property_id)
		) ENGINE=MyISAM CHARSET=binary;");
	mysqlQuery("DROP TABLE IF EXISTS template_uri");
	mysqlQuery("CREATE TABLE IF NOT EXISTS template_uri (
		  template_id int(11) NOT NULL,
		  uri varchar(255) NOT NULL
		) ENGINE=MyISAM CHARSET=binary;");
}

function read_csv($f="") {
    if ($f AND is_file($f)) {
        // set excel type delimiter, etc
        $delimiter = ';';
        $enclosure = '"';

        // read file & parse
        $input = file($f);
        $csv = array();
        foreach ( $input as $key => $value ) {
            // rtrim crap at the end of the string
            $tmp = explode($delimiter,rtrim($value));

            // parse
            $in_quote = false;
            $arr = array();
            foreach ( $tmp as $key => $value ) {
                if ( $in_quote ) {
                    if (has_quote($value,$enclosure) ) {
                        $in_quote = false;
                        $value = substr_replace($value,'',-1,1);
                    }
                    $key = (count($arr)-1);
                    $arr[$key] .= trim($delimiter.$value); // continue last array element
                } else {
                    if (has_quote($value,$enclosure) ) {
                        $in_quote = true;
                        $value = substr_replace($value,'',0,1);
                    } else if ( substr($value,0,1) == $enclosure AND substr($value,-1,1) == $enclosure ) {
                        // string is quoted, remove quotes
                        $value = substr_replace($value,'',0,1); // start
                        $value = substr_replace($value,'',-1,1); // end
                    }
                    $arr[] = trim($value); // append to array
                }
            }

            foreach ( $arr as $key => $value ) {
                $arr[$key] = trim(str_replace($enclosure.$enclosure,$enclosure,$value));
            }

            // append to array
            $csv[] = $arr;
        } // end foreach
        return $csv;
    } // end if
} // end func

function has_quote ($str="",$enc="") {
    $c = substr_count($str,$enc);
    if ( stristr(($c/2),".") ) {
        return true;
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

?>
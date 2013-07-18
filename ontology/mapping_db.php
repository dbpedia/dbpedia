<?php

/**
* Insert Wikipedia template mappings into database.
*
* @author	Anja Jentzsch <mail@anjajentzsch.de>
*/
ini_set('memory_limit', '1512M');
$show_missing_labels = false;

error_reporting(E_ALL & ~E_NOTICE);

require_once("../extraction/databaseconfig.php");
require_once("mapping_db_util.php");
require_once("../extraction/parsers/config.inc.php");

$language = "en";

$mapping_csv = "mapping.csv";
$hierarchy_csv = "hierarchy.csv";

mysql_connect ($host, $user, $password) or die ("Database connection could not be established.");
mysql_select_db ("dbpedia_extraction_".$language) or createAndSelectDatabase("dbpedia_extraction_".$language);

dropAndCreateMappingTables();

$mapping_csv_content = read_csv($mapping_csv);

$datatype_ranges = array("string", "integer", "float");
$parser_types = array("unit", "currency", "date", "geocoordinates", "url");
$unit_types = array('Length','Area','Volume','Speed','Force','Energy','Temperature','Mass','Pressure','Torque','FuelEfficiency','Power','PopulationDensity','Currency','Time','InformationUnit','Frequency','FlowRate','Density','Angle');
//$unit_types = array("Length", "Area", "Volume", "Speed", "Force", "Energy", "Temperature", "Mass", "Pressure", "Torque", "Fuel efficiency", "Power", "Currency", "Population density", "Flow rate", "Density", "Time", "Weight");

$overall_classes = array();
$classes_ids = array();
$splitted = array();
$wrong_mappings = array();

$class_id = 0;
$template_id = -1;
$class = "";

// FOAF
$strSQL = "INSERT INTO class_property (name, type, uri) VALUES ('name', 'object', 'http://xmlns.com/foaf/0.1/')";
if (!mysql_query ($strSQL)) {
	die("[FOAF] ". mysql_error() . " - query: ".$strSQL);
}
$property_id_foaf_name = mysql_insert_id();

$strSQL = "INSERT INTO class_property (name, type, uri) VALUES ('homepage', 'object', 'http://xmlns.com/foaf/0.1/')";
if (!mysql_query ($strSQL)) {
	die("[FOAF] ". mysql_error() . " - query: ".$strSQL);
}
$property_id_foaf_homepage = mysql_insert_id();

foreach ($mapping_csv_content as $csvRow) {
	if (($csvRow[0] != "Class") && ($csvRow[0] != "")) {
		$class = str_replace(" ", "", $csvRow[0]);
		$classLabel = $csvRow[0];
		$result_cid = mysql_query('SELECT id FROM class where name="'.$class.'"');
		if (mysql_num_rows($result_cid) > 0) {
			while ( $row_c = mysql_fetch_row ( $result_cid ) ) {
				$class_id = $row_c[0];
				// echo "Class already found: $class, ID: $class_id\n";
			}
		} else {
			// Found a new class
			$strSQL = "INSERT INTO class (name, label) VALUES ('".$class."', '".$classLabel."')";
			if (!mysql_query ($strSQL)) {
				// echo "[INFO] " . mysql_error() . "\n";
			} else {
				$result_cid = mysql_query('SELECT id FROM class where name="'.$class.'"');
				if (mysql_num_rows($result_cid) > 0) {
					while ( $row_c = mysql_fetch_row ( $result_cid ) ) {
						$class_id = $row_c[0];
						// echo "Class added: $class, ID: $class_id\n";
						$overall_classes[] = $class;
						$classes_ids[$class] = $class_id;
					}
				}
			}
		}

		//Find URI(s) -> Template classes
		$spalte = 1;
		$template_uri_found = false;
		while (($csvRow[$spalte] != "") || ($csvRow[$spalte+1] != "") || ($csvRow[$spalte+2] != "")) {
			if (($csvRow[$spalte] != "") && (strpos($csvRow[$spalte], "http") !== false)) {
				if (!$template_uri_found) {
					$result = mysql_query('SELECT template_id FROM template_class where class_id='.$class_id);
					if (mysql_num_rows($result) == 0) {
						$strSQL = "INSERT INTO template_class (class_id) VALUES (".$class_id.")";
						if (!mysql_query ($strSQL)) {
							die("template_class: " . mysql_error());
						}
					}
					$result = mysql_query('SELECT template_id FROM template_class where class_id='.$class_id);
					if (mysql_num_rows($result) > 0) {
						while ($row_t = mysql_fetch_row($result)) {
							$template_id = $row_t[0];
							$classes_template_ids[$template_id] = $class;
							$template_uri_found = true;
						}
						$template_ids_class_ids[$class_id] = $template_id;
					} else {
						die("No Template Class found after insertion for $class");
					}
				}
				
				$template_class_uri = $csvRow[$spalte];
				$template_class_uri = str_replace("/page/", "/resource/", $template_class_uri);
				$query = 'SELECT uri FROM template_uri where uri="'.$template_class_uri.'" and template_id='.$template_id;
				$result = mysqlQuery($query);
				if (mysql_num_rows($result) == 0) {
					$query = "INSERT INTO template_uri (template_id, uri) VALUES ('".$template_id."', '".$template_class_uri."')";
					mysqlQuery($query);
				}
			}
			$spalte++;
		}
	}
}

$csv_c = read_csv($hierarchy_csv);

foreach ($csv_c as $row) {
	$i = 0;
	while (($row[$i] != "") || ($row[$i+1] != "") || ($row[$i+2] != "")) {
		if ($row[$i] != "") {
			$class = str_replace(" ", "", $row[$i]);
			$classLabel = $row[$i];
			if (!in_array($class, $overall_classes)) {
				$strSQL = "INSERT INTO class (name, label) VALUES ('".$class."', '".$classLabel."')";
				if (!mysql_query ($strSQL)) {
					// echo "[INFO] " . mysql_error() . "\n";
					$result_cid = mysql_query('SELECT id FROM class where name="'.$class.'"');
					if (mysql_num_rows($result_cid) > 0) {
						while ( $row_c = mysql_fetch_row ( $result_cid ) ) {
							$class_id = $row_c[0];
							$overall_classes[] = $class;
							$classes_ids[$class] = $class_id;
						}
					}
				} else {
					$result_cid = mysql_query('SELECT id FROM class where name="'.$class.'"');
					if (mysql_num_rows($result_cid) > 0) {
						while ( $row_c = mysql_fetch_row ( $result_cid ) ) {
							$class_id = $row_c[0];
							// echo "[Hierarchy] Class added: $class, ID: $class_id\n";
							$overall_classes[] = $class;
							$classes_ids[$class] = $class_id;
						}
					}
				}
			}
		}
		$i++;
	}
	$i = 1;
	$parentclass = str_replace(" ", "", $row[0]);
	while (($row[$i] != "") || ($row[$i+1] != "") || ($row[$i+2] != "")) {
		if ($row[$i] != "") {
			$class = str_replace(" ", "", $row[$i]);
			$strSQL = "UPDATE class SET parent_id='".$classes_ids[$parentclass]."' WHERE id=".$classes_ids[$class];
			if (!mysql_query ($strSQL)) {
				echo "[WARNING] " . mysql_error() . "\n";
			}
		}
		$i++;
	}
}

// Find parentless classes
$result_cid = mysql_query('SELECT name FROM class WHERE parent_id IS NULL or parent_id = "0"');
if ($result_cid) {
	while ($row_c = mysql_fetch_row( $result_cid )) {
		if ($row_c[0] != "Thing") {
			die("[DIE] class w/o parent: $row_c[0] \n");
		}
	}
}

$run = 1;

$foaf_property_at_class_wo_template = array();

while ($run < 4) {
	$new_class = false;
	echo "$run. RUN \n";
	for ($csv_row = 0; $csv_row < sizeof($mapping_csv_content); $csv_row++) {
		$csvRow = $mapping_csv_content[$csv_row];
		if (($csvRow[0] != "Class") && ($csvRow[0] != "")) {
			$template_id = -1;
			$class_id = -1;
			$new_class = true;
			$class = str_replace(" ", "", $csvRow[0]);
			$class_id = getClassIdFromClassName($class);
			if (is_numeric($class_id)) {
				$result = mysql_query('SELECT template_id FROM template_class where class_id='.$class_id);
				if (mysql_num_rows($result) > 0) {
					while ( $row = mysql_fetch_row ( $result ) ) {
						$template_id = $row[0];
					}
				}
				continue;
			} else {
				die (__LINE__." Didn't find class $class");
			}
		}

		if ($new_class) {
			if (($csvRow[1] != "")) {
				$template_property = $csvRow[1];
				$template_properties = array();
				$template_property_parser = array();
				$template_properties_beautified_names = array();
				$ranges = array();

				if ($csvRow[7] != "") {	// Property merging
					preg_match("/([a-z]*)([0-9]*)/", $csvRow[7], $match);
					if ((strlen($match[1]) > 0) && (strlen($match[2]) > 0)) {
						if ($run == 1) {
							if ($template_id == -1) {
								echo "[DIE - MERGE] Don't define merging on classes without template URIs: $class.$template_property";
								die();
							}
							// echo "[merge] $class $csvRow[1] $csvRow[7] $csvRow[5] $csvRow[6]\n";
							$merge_template_property[$class_id][$match[1]]["name"][$match[2]] = $template_property;
							$merge_template_property[$class_id][$match[1]]["label"][$match[2]] = $csvRow[4];
							$merge_template_property[$class_id][$match[1]]["type"][$match[2]] = $csvRow[5];
							$merge_template_property[$class_id][$match[1]]["exact_type"][$match[2]] = $csvRow[6];
							if (trim($csvRow[3]) == "") {
								if ($merge_template_property[$class_id][$match[1]]["superclass"] != null) {
									die("[DIE - MERGE] Define super class for each properties which should be merged: $class_name.$template_property");
								}
							} else {
								$merge_template_property[$class_id][$match[1]]["superclass"] = trim($csvRow[3]);
							}
						}
					}
				} else {
					if ($csvRow[2] != "") {		// range, UNIT or =
//Equivalent Template Properties
						if (trim($csvRow[2]) == "=") {	// =
							if ($csvRow[3] != "") {
								$sameAsLink = $csvRow[3];
								if ($equivalent_template_property_id = getTemplatePropertyId($template_id, $sameAsLink)) {
									$template_property_id = insertTemplatePropertyAndGetId($template_id, $template_property);
									$result = mysqlQuery("SELECT class_property_id FROM template_property_class_property where template_property_id='".$equivalent_template_property_id."'");
									if (mysql_num_rows($result) > 0) {
										while ($row = mysql_fetch_row($result)) {
											$class_pid = $row[0];
											mysqlQuery("INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$template_property_id."', '".$class_pid."')", __LINE__);
										}
									/*
									} elseif ($template_id >= 0) {
										// TODO: what happens here?
										$result = mysql_query('SELECT id FROM template_property where name="'.$sameAsLink.'" and template_id='.$template_id);
										while ($row = mysql_fetch_row($result)) {
											$temp_link_pid = $row[0];
											$result = mysql_query('SELECT class_property.id AS id FROM class_property, template_property_class_property where template_property_class_property.template_property_id ="'.$temp_link_pid.'" and template_property_class_property.class_property_id = class_property.id');
											while ($row = mysql_fetch_row($result)) {
												$class_link_pid = $row[0];
												$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$class_link_pid."')";
												if (!mysql_query ($strSQL)) {
													// die(mysql_error() . " - query: ".$strSQL);
												}
											}
										}
										*/
									}
								}
							}
// Range and Split Properties
						} else { // Range
							// TODO: Splitting shouldn't be necessary anymore
							// SplitRanges shouldn't be in any direct subClass-relation (same parent is ok though)
							if ($csvRow[2] == "object")  {
								$range_types = "object";
								$property_name = $template_property;
								$ranges[$property_name][0] = "Thing";
								$template_properties[] = $property_name;
								// TODO: beautified name, further lines
								if ($csvRow[4]  != "") {
									$template_properties_beautified_names[$property_name][] = $csvRow[4];
								}
							} else {
								$ranges = explode (",", $csvRow[2]);
								if (sizeof($ranges) > 1) {
									// echo "[INFO] Splitting needed: $class.$csvRow[1] -> $csvRow[2]\n";
								}
								$range_types = "";
								foreach ($ranges as $range) {
									// Object or Datatype Property?
									if (in_array(strtolower($range), $datatype_ranges)) {
										if ($range_types == "datatype") {
											echo "[WARNING] Two dataset ranges found at $class.$template_property\n";
										} else {
											$range_types_new = "datatype";
											$property_name = $template_property;
											$template_properties[] = $property_name;
											// TODO: beautified name, further lines
											if ($csvRow[4]  != "") {
												$template_properties_beautified_names[$property_name][] = $csvRow[4];
											}
											$ranges[$property_name][] = strtolower($range);
										}
									} else {
										if (in_array(strtolower($range), $parser_types)) {
											switch (strtolower($range)) {
												case "date":
													$property_name = $template_property;
													$template_properties[] = $property_name;
													$range_types_new = "datatype";
													$template_property_parser[$property_name] = strtolower($range);
													$ranges[$property_name][] = "date";
													break;
												case "unit":
													if (in_array($csvRow[5], $unit_types)) {
														$property_name = $template_property;
														$template_properties[] = $property_name;
														$range_types_new = "datatype";
														$template_property_parser[$property_name] = strtolower($range);
														$ranges[$property_name][] = "double";
														$unit_type[$property_name] = $csvRow[5];
														if (trim($csvRow[6]) != "") {
															$unit_exact_type[$property_name] = $csvRow[6];
														}
														if (trim($csvRow[8]) != "") {
															if (in_array($csvRow[8], array_keys($GLOBALS[$csvRow[5]]))) {
																$template_property_target_unit[$class][$property_name] = $GLOBALS[$csvRow[5]][$csvRow[8]];
															} else {
																echo "$csvRow[8] not defined for $csvRow[5]\n";
															}
														}
													} else {
														if ($run == 1) {
															echo "[Unit Type] Please define unit type for $class.$csvRow[1]\n";
														}
													}
													break;
												case "currency":
													$property_name = $template_property;
													$template_properties[] = $property_name;
													$range_types_new = "datatype";
													$template_property_parser[$property_name] = strtolower($range);
													$ranges[$property_name][] = "double";
													break;
												default:
													$property_name = $template_property;
													$template_properties[] = $property_name;
													$range_types_new = "datatype";
													$template_property_parser[$property_name] = strtolower($range);
													$ranges[$property_name][] = "string";
											}
											// TODO: beautified name, further lines
											if ($csvRow[4]  != "") {
												$template_properties_beautified_names[$property_name][] = $csvRow[4];
											}
										} else {
											$range_types_new = "object";
											$range = str_replace(" ", "", $range);
											if (in_array($range, $overall_classes)) {
												$property_name = $template_property;
												$template_properties[] = $property_name;
												// TODO: beautified name, further lines
												if ($csvRow[4]  != "") {
													$template_properties_beautified_names[$property_name][] = $csvRow[4];
												}
												$ranges[$property_name][] = $range;
											} else {
												echo "[WARNING] Unknown object range for $class.$template_property: $range\n";
											}
										}
									}

									if (($range_types != $range_types_new) && ($range_types != "")) {
										// TODO: Error (don't mix datatype & object properties)
										die ("[ERROR] mixed datatype & object properties");
									} else {
										$range_types = $range_types_new;
									}
								}
							}

							// Insert new property into DB
							// TODO: add description
							foreach ($template_properties as $one_property) {
								$class_properties = array();
								if (isset($template_properties_beautified_names[$one_property])) {
									foreach ($template_properties_beautified_names[$one_property] as $beautified_name) {
										$propertyName = getCamelCasePropertyNameFromLabel($beautified_name);
										$propertyLabel = $beautified_name;
										$class_properties[] = $propertyName;
										$label[$propertyName] = $propertyLabel;
									}
								} else {
									if ($show_missing_labels) {
										echo "[LABEL] Define label for $class.$one_property\n";
									}
									$propertyName = getCamelCasePropertyNameFromLabel($one_property);
									$propertyLabel = $one_property;
									$class_properties[] = $propertyName;
									$label[$propertyName] = $propertyLabel;
								}
								foreach ($class_properties as $class_property) {
									$result = mysql_query('SELECT id FROM class_property where name="'.$class_property.'" and class_id='.$class_id);
									if (mysql_num_rows($result) == 0) {
										if ($range_types == "datatype") {	// range = "datatype"
											$strSQL = "INSERT INTO class_property (class_id, name, type, datatype_range, label) VALUES ('".$class_id."', '".$class_property."', '".$range_types."', '".$ranges[$one_property][0]."', '".$label[$class_property]."')";
											if (!mysql_query ($strSQL)) {
												die(mysql_error() . " - query: ".$strSQL);
											}
											$result = mysql_query('SELECT id FROM class_property where name="'.$class_property.'" and class_id='.$class_id);
											if (mysql_num_rows($result) == 0) {
												//die(mysql_error() . " - query: ".$strSQL);
											} else {
												$pid = -1;
												while ( $row = mysql_fetch_row ($result) ) {
													$pid = $row[0];
												}
												// add parser type rule
												if ($pid > -1) {
													if ($template_property_parser[$one_property] != "") {
														if (($template_property_parser[$one_property] == "unit") && (!$template_property_target_unit[$class][$one_property])) {
															if ($run == 1) {
																echo "[Target Unit] Please define target unit for $class.$class_property\n";
															}
														}
														if ((strlen($unit_type[$one_property]) > 0) && ($unit_type[$one_property] != null)) {
															$strSQL = "INSERT INTO parser_type_rule (class_property_id, parser_type, unit_type, target_unit) VALUES ('".$pid."', '".$template_property_parser[$one_property]."', '".$unit_type[$one_property]."', '".$template_property_target_unit[$class][$one_property]."')";
														} else {
															$strSQL = "INSERT INTO parser_type_rule (class_property_id, parser_type, unit_type, target_unit) VALUES ('".$pid."', '".$template_property_parser[$one_property]."', NULL, '".$template_property_target_unit[$class][$one_property]."')";
														}
														mysqlQuery($strSQL, __LINE__);
													}

												}
											}
										} else {	// range = "object"
											$strSQL = "INSERT INTO class_property (class_id, name, type, label) VALUES ('".$class_id."', '".$class_property."', '".$range_types."', '".$label[$class_property]."')";
											//echo "$class $class_property $label[$class_property]\n";
											// TODO: don't add duplicate property names for same namespace
											if (!mysql_query ($strSQL)) {
												die(mysql_error() . " - query: ".$strSQL);
											}
											$result = mysql_query('SELECT id FROM class_property where name="'.$class_property.'" and class_id='.$class_id);
											if (mysql_num_rows($result) == 0) {
												//die(mysql_error() . " - query: ".$strSQL);
											} else {
												$pid = -1;
												while ( $row = mysql_fetch_row ($result) ) {
													$pid = $row[0];
												}
												if (sizeof($ranges) > 0) {
													foreach ($ranges[$one_property] as $this_property_range) {
														if (strtolower($this_property_range) == "object") {
															$this_property_range = "Thing";
														}
														$strSQL = "INSERT INTO class_property_range (property_id, range_class_id) VALUES ('".$pid."', '".$classes_ids[$this_property_range]."')";
														if (!mysql_query ($strSQL)) {
															die(mysql_error() . " - query: ".$strSQL);
														}
													}
												}
											}
										}
									}
									$result = mysql_query('SELECT id FROM class_property where name="'.$class_property.'" and class_id='.$class_id);

									if (($template_id >= 0) && (mysql_num_rows($result) > 0)) {
										while ($row = mysql_fetch_row ($result)) {
											$class_property_id = $row[0];
											// Add template property and relation to class properties
											$strSQL = "INSERT INTO template_property (template_id, name) VALUES ('".$template_id."', '".$one_property."')";
											if (!mysql_query ($strSQL)) {
												// die(mysql_error() . " - query: ".$strSQL);
											}
											$tid = -1;
											//echo 'SELECT id FROM template_property where template_id="'.$template_id.'" and name="'.$one_property.'"';
											$result_tid = mysql_query('SELECT id FROM template_property where template_id="'.$template_id.'" and name="'.$one_property.'"');
											if (mysql_num_rows($result_tid) > 0) {
												while ($row = mysql_fetch_row( $result_tid)) {
													$tid = $row[0];
												}
											}
											mysqlQuery("INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$tid."', '".$class_property_id."')");
											if (isset($template_property_parser[$one_property]) && $template_property_parser[$one_property] == "unit") {
												// TODO: use [$class][$property] instead of [$property]
												if (isset($unit_exact_type[$one_property]) || isset($template_property_target_unit[$class][$one_property])) {
													$query = "INSERT INTO template_parser_type_rule (template_property_id, unit_exact_type, standard_unit) VALUES ('".$tid."', ";
													if (isset($unit_exact_type[$one_property])) {
														$query .= "'".$unit_exact_type[$one_property]."', ";
													} else {
														$query .= "'', ";
													}
													if (isset($template_property_target_unit[$class][$one_property])) {
														$query .= "'".$template_property_target_unit[$class][$one_property]."');";
													} else {
														$query .= "'');";
													}
													mysqlQuery($query);
												}
											}
										}
									}
								}
							}
						}
// subPropertyOf or split
					} elseif (($csvRow[2]  == "") && ($csvRow[4]  != "")) {
						//if ($csvRow[3]  != "") {
						if ($run > 1) {
							$firstRow = true;
							$current_csv_row = $csv_row;
							while ($firstRow || (($csvRow[1] == "") && ($csvRow[3] != "") && ($csvRow[4] != "")) || (($csvRow[1] == "") && ($csvRow[4] != "") && ($csvRow[5] != ""))) {
								$firstRow = false;
								$superProperty = $csvRow[4];
								$expectedUnit = $csvRow[6];
								if ($csvRow[3]  != "") {	// found superClass
									$superClass = str_replace(" ", "", $csvRow[3]);
									if (in_array($superClass, $overall_classes)) {
										$superClassId = getClassIdFromClassName($superClass);
										if ($superClassId) {
											$result = mysql_query('SELECT id FROM class_property where name="'.$superProperty.'" and class_id='.$superClassId);
											if (mysql_num_rows($result) > 0) {
												$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
												while ($row = mysql_fetch_row($result)) {
													$class_pid = $row[0];
													$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$class_pid."')";
													if (!mysql_query ($strSQL)) {
														// die(mysql_error() . " - query: ".$strSQL);
													}

													if ($expectedUnit != "") {
														$result_unit = mysql_query('SELECT parser_type FROM parser_type_rule where class_property_id="'.$class_pid.'"');
														if (mysql_num_rows($result_unit) > 0) {
															while ($row = mysql_fetch_row($result_unit)) {
																if ($row[0] == "unit") {
																	$strSQL = "INSERT INTO template_parser_type_rule (template_property_id, unit_exact_type) VALUES ('".$temp_pid."', '".$expectedUnit."')";
																	if (!mysql_query ($strSQL)) {
																	}
																}
															}
														}
													}
												}
											} else {
												// find superClass the superProperty is actually defined on (where superClass.superProperty linked to)
												$result = mysql_query('SELECT template_id FROM template_class where class_id='.$superClassId);
												if (mysql_num_rows($result) > 0) {
													while ($row = mysql_fetch_row($result)) {
														$superClassTemplateId = $row[0];
														$result = mysql_query('SELECT id FROM template_property where name="'.$superProperty.'" and template_id='.$superClassTemplateId);
														while ($row = mysql_fetch_row($result)) {
															$temp_link_pid = $row[0];
															$result_class_property = mysql_query('SELECT class_property.id AS id FROM class_property, template_property_class_property where template_property_class_property.template_property_id ="'.$temp_link_pid.'" and template_property_class_property.class_property_id = class_property.id');
															if (mysql_num_rows($result_class_property) > 0) {
																while ($row = mysql_fetch_row($result_class_property)) {
																	$class_link_pid = $row[0];
																	$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
																	$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$class_link_pid."')";
																	if (!mysql_query ($strSQL)) {
																		if (strpos(mysql_error(), "Duplicate entry") === false) {
																			echo "$class.$template_property - does not exist: $superClass.$superProperty (".mysql_error().")\n";
																		}
																	}
																}
															} else {
																echo "$class.$template_property -> $superClass.$superProperty not found \n";
															}
														}
													}
												} else {
													$result = mysql_query('SELECT id FROM class_property WHERE name ="'.$superProperty.'" AND class_id = "'.$superClassId.'"');
													if (mysql_num_rows($result) > 0) {
														while ($row = mysql_fetch_row($result)) {
															$class_link_pid = $row[0];
															$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
															mysqlQuery("INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$class_link_pid."')");
														}
													} else {
														echo "$class.$template_property -> $superClass.$superProperty not found \n";
													}
													/*
													if ($run >2) {
														if (in_array("", $foaf_property_at_class_wo_template)) {
															$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$property_id_foaf_name."')";
															if (!mysql_query($strSQL)) {
																if (strpos(mysql_error(), "Duplicate entry") === false) {
																	echo "$csvRow[1] (".mysql_error().")\n";
																}
															}
														} else {
															echo "[$class] $superClass.$superProperty not found\n";
														}
													}
													*/
												}
											}
										}
									} elseif (($superClass == "foaf") && ($superProperty == "name")) {
										$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
										$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$property_id_foaf_name."')";
										if (!mysql_query($strSQL)) {
											if (strpos(mysql_error(), "Duplicate entry") === false) {
												echo "$csvRow[1] foaf:name property not properly added (".mysql_error().")\n";
												$foaf_property_at_class_wo_template[] = "$superClass.$superProperty";
											}
										}
									} elseif (($superClass == "foaf") && ($superProperty == "homepage")) {
										$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
										$strSQL = "INSERT INTO template_property_class_property (template_property_id, class_property_id) VALUES ('".$temp_pid."', '".$property_id_foaf_homepage."')";
										if (!mysql_query($strSQL)) {
											if (strpos(mysql_error(), "Duplicate entry") === false) {
												echo "$csvRow[1] foaf:homepage property not properly added (".mysql_error().")\n";
												$foaf_property_at_class_wo_template[] = "$superClass.$superProperty";
											}
										}
									} else {
										echo "$superClass(.$superProperty) ($class.$template_property) not found \n";
									}
								} elseif (($csvRow[4] != "") && ($csvRow[5] != "")) {
									if ((!getOntologyPropertyId($class_id, $superProperty))  && ($splitted[$template_id][$template_property][getOntologyPropertyId($class_id, getCamelCasePropertyNameFromLabel($csvRow[4]))] == null)) {
										echo "";
										$temp_pid = insertTemplatePropertyAndGetId($template_id, $template_property);
										$ontology_property_id = createOntologyProperty($class_id, null, $csvRow[4], $csvRow[5]);
										createRelationFromTemplatePropertyToClassProperty($template_id, $temp_pid, $ontology_property_id);
										if (getTemplatePropertyId($template_id, $template_property)) {
											$splitted[$template_id][$template_property][$ontology_property_id] = true;
											//echo ("[SPLIT] on same class: [$class] $csvRow[1] -> [E] $csvRow[4] - [F] $csvRow[5] \n");
										}
									} elseif ((getOntologyPropertyId($class_id, $superProperty))  && ($splitted[$template_id][$template_property][getOntologyPropertyId($class_id, getCamelCasePropertyNameFromLabel($csvRow[4]))] == null)) {
										echo "Can't split: $class.$template_property - $superProperty already defined on $class, check ranges first\n";
									}
								} else {
									$wrong_mappings[] = "$class.$csvRow[1]	|	$csvRow[4]	|	$csvRow[5]";
								}
								$current_csv_row = $current_csv_row + 1;
								$csvRow = $mapping_csv_content[$current_csv_row];
							}
						}
					}
				}
			}
		}
	}
// Merge properties
	if ($run == 1) {
		if ($merge_template_property != null) {
			/*
				$merge_template_property[$class_id][$match[1]]["name"][$match[2]] = $template_property;
				$merge_template_property[$class_id][$match[1]]["label"][$match[2]] = $csvRow[4];
				$merge_template_property[$class_id][$match[1]]["type"][$match[2]] = $csvRow[8];
				$merge_template_property[$class_id][$match[1]]["exact_type"][$match[2]] = $csvRow[9];
			*/
			foreach ($merge_template_property as $this_classid => $class_ids) {
				foreach ($class_ids as $merge_parts) {
					$class_name = getClassNameFromClassId($this_classid);
					$template_id = $template_ids_class_ids[$this_classid];
					$label = "";
					$type = "";
					$exact_type = array();
					$superclass = null;
					$unit_range = null;
					$ontology_property_id = null;
					if ($merge_parts["superclass"] == null) {
						foreach ($merge_parts["label"] as $labels) {
							if (($label != "") && ($label != $labels)) {
								echo "[DIE - MERGE] 2 labels [$label, $labels] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$label = $labels;
							}
						}
						if ($label == "") {
							echo "[DIE - MERGE] no new label defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
							die();
						}
						foreach ($merge_parts["type"] as $types) {
							if (($types != "") && ($type != "") && ($type != $types)) {
								echo "[DIE - MERGE] 2 types [$type, $types] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$type = $types;
							}
						}
						foreach ($merge_parts["exact_type"] as $exact_types) {
							/*
							if (($exact_type != "") && ($exact_type != $exact_types)) {
								echo "[DIE - MERGE] 2 exact_types [$exact_type, $exact_types] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$exact_type = $exact_types;
							}
							*/
							$exact_type[] = $exact_types;
						}
						// 1. Ontologie Property anlegen
						// 2. Ranges / Units

						if (in_array(strtolower($type), $datatype_ranges)) {
							$datatype_range = strtolower($type);
							$type = "datatype";
						} else if (in_array(strtolower($type), $parser_types)) {
							// TODO: New Parser Type Rule
							switch (strtolower($type)) {
								case "date":
									$datatype_range = "date";
									break;
								case "unit":
									echo "[DIE - MERGE] use exact unit type instead of 'UNIT' at $class_name.".implode(",", $merge_parts["name"])." \n";
									die();
								default:
									$datatype_range = "string";
							}
							$type = "datatype";
						} else if (in_array($type, $unit_types)) {
							$unit_range = $type;
							$type = "string";
						} else if (in_array(str_replace(" ", "", $type), $overall_classes)) {
							$type = "object";
						} else {
							echo "[DIE - MERGE] Unknown range for $class_name.$label: $type\n";
							die();
						}

						$ontology_property_id = createOntologyDatatypePropertyAndGetId($class_id, getCamelCasePropertyNameFromLabel($label), $type, $datatype_range, $label);
						if (in_array($unit_range, $unit_types)) {
							$strSQL = "INSERT INTO parser_type_rule (class_property_id, parser_type, unit_type) VALUES ('".$ontology_property_id."', '".$type."', '".$unit_range."')";
							if (!mysql_query ($strSQL)) {
								die(mysql_error() . " - query: ".$strSQL);
							}
						}

						$template_property_ids = array();
						$i = 0;
						foreach ($merge_parts["name"] as $names) {
							// 3. create template property
							$template_property_id  = insertTemplatePropertyAndGetId($template_id, $name);
							if ($exact_type[$i] != "") {
								$strSQL = "INSERT INTO template_parser_type_rule (template_property_id, unit_exact_type) VALUES ('".$template_property_id."', '".$exact_type[$i]."')";
								if (!mysql_query ($strSQL)) {
									//die(mysql_error() . " - query: ".$strSQL);
								}
							}
							$template_property_ids[]  = $template_property_id;
							// 4. Mapping
							create_property_mapping($ontology_property_id, $template_property_id);
							$i++;
						}
						// 5. Merge Rule
						$strSQL = "INSERT INTO template_property_merge_rule (ordered_template_property_ids, class_property_id, template_id) VALUES ('".implode(",", $template_property_ids)."', '".$ontology_property_id."', '".$template_id."')";
						if (!mysql_query ($strSQL)) {
							//die(mysql_error() . " - query: ".$strSQL);
						}
					} else {
						$superclass = $merge_parts["superclass"];
						foreach ($merge_parts["label"] as $labels) {
							if (($label != "") && ($label != $labels)) {
								echo "[DIE - MERGE] 2 properties $superclass.[$label, $labels] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$label = $labels;
							}
						}
						if ($label == "") {
							echo "[DIE - MERGE] no property on $superclass defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
							die();
						}
						foreach ($merge_parts["type"] as $types) {
							if (($types != "") && ($type != "") && ($type != $types)) {
								echo "[DIE - MERGE] 2 types [$type, $types] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$type = $types;
							}
						}
						foreach ($merge_parts["exact_type"] as $exact_types) {
							/*
							if (($exact_type != "") && ($exact_type != $exact_types)) {
								echo "[DIE - MERGE] 2 exact_types [$exact_type, $exact_types] defined for: $class_name.".implode(",", $merge_parts["name"])." \n";
								die();
							} else {
								$exact_type = $exact_types;
							}
							*/
							$exact_type[] = $exact_types;
						}

						// 1. Ontologie Property anlegen
						// 2. Ranges / Units

						if ($type != "") {
							if (in_array(strtolower($type), $datatype_ranges)) {
								$datatype_range = strtolower($type);
								$type = "datatype";
							} else if (in_array(strtolower($type), $parser_types)) {
								// TODO: New Parser Type Rule
								switch (strtolower($type)) {
									case "date":
										$datatype_range = "date";
										break;
									case "unit":
										echo "[DIE - MERGE] use exact unit type instead of 'UNIT' at $class_name.".implode(",", $merge_parts["name"])." \n";
										die();
									default:
										$datatype_range = "string";
								}
								$type = "datatype";
							} else if (in_array($type, $unit_types)) {
								$unit_range = $type;
								$type = "string";
							} else if (in_array(str_replace(" ", "", $type), $overall_classes)) {
								$type = "object";
							} else {
								echo "[DIE - MERGE] Unknown range for $class_name.$label: $type\n";
								die();
							}
						}

						if (in_array(str_replace(" ", "", $superclass), $overall_classes)) {
							$result = mysql_query('SELECT id FROM class where name="'.str_replace(" ", "", $superclass).'"');
							if (mysql_num_rows($result) == 0) {
								echo "[DIE - MERGE] $superclass not found at $class_name\n";
								die();
							}
							while ($row = mysql_fetch_row($result)) {
								$superClassId = $row[0];
								$result = mysql_query('SELECT id FROM class_property where name="'.$label.'" and class_id='.$superClassId);
								if (mysql_num_rows($result) > 0) {
									while ($row = mysql_fetch_row($result)) {
										$ontology_property_id = $row[0];
									}
								} else {
									echo "[DIE - MERGE] $superclass.$label not found at $class_name\n";
									die();
								}
							}
						}
/*
						if (in_array($unit_range, $unit_types)) {
							$strSQL = "INSERT INTO parser_type_rule (class_property_id, parser_type, unit_type) VALUES ('".$ontology_property_id."', '".$type."', '".$unit_range."')";
							if (!mysql_query ($strSQL)) {
								die(mysql_error() . " - query: ".$strSQL);
							}
						}
*/
						$i = 0;
						$template_property_ids = array();
						foreach ($merge_parts["name"] as $names) {
							// 3. create template property
							$template_property_id  = insertTemplatePropertyAndGetId($template_id, $names);
							$template_property_ids[]  = $template_property_id;
							// 4. Mapping
							create_property_mapping($ontology_property_id, $template_property_id);
							if ($exact_type[$i] != "") {
								$strSQL = "INSERT INTO template_parser_type_rule (template_property_id, unit_exact_type) VALUES ('".$template_property_id."', '".$exact_type[$i]."')";
								if (!mysql_query ($strSQL)) {
									//die(mysql_error() . " - query: ".$strSQL);
								}
							}
							$i++;

						}
						// 5. Merge Rule
						$strSQL = "INSERT INTO template_property_merge_rule (ordered_template_property_ids, class_property_id, template_id) VALUES ('".implode(",", $template_property_ids)."', '".$ontology_property_id."', '".$template_id."')";
						if (!mysql_query ($strSQL)) {
							//die(mysql_error() . " - query: ".$strSQL);
						}
					}
				}
			}
		}
	}
	$run = $run+1;
}

// LOG

echo "\n\n=== LOG ===\n\n";

// get template properties w/o mapping to class property
echo "1. Template Properties w/o mapping to Class Property/ies:\n";
$result = mysqlQuery("SELECT template_id, name FROM template_property  WHERE id NOT IN (SELECT template_property_id as id FROM template_property_class_property)", __LINE__);
while ($row = mysql_fetch_row($result)) {
	$template_id = $row[0];
	$template_property_name = $row[1];
	$uris = "";
	$result1 = mysqlQuery("SELECT uri FROM template_uri WHERE template_id = $template_id", __LINE__);
	while ($row1 = mysql_fetch_row($result1)) {
		$uris .= $row1[0] . " ";
	}
	echo "   $template_property_name ON $uris\n";
}


// get template properties w/o mapping to class property
echo "\n\n2. Object Class Properties w/o Range:\n";
$result = mysqlQuery("SELECT name, class_id FROM class_property WHERE id NOT IN (SELECT property_id AS id FROM class_property_range) AND TYPE = \"object\" AND class_id >=0", __LINE__);
while ($row = mysql_fetch_row($result)) {
	$property_name = $row[0];
	$class_id = $row[1];
	echo "   ".getClassNameFromClassId($class_id).".".$property_name."\n";
}

// get template properties w/o mapping to class property
echo "\n\n3. Properties with > 1 Range:\n";
$result = mysqlQuery("SELECT name, class_id, type, datatype_range FROM class_property WHERE class_id >= 1 GROUP BY name HAVING COUNT(name) > 1", __LINE__);
$property_names = array();
while ($row = mysql_fetch_row($result)) {
	$properties[]["name"] = $row[0];
}
if (sizeof($properties) > 0) {
	foreach ($properties as $id => $property) {
		$result = mysqlQuery("SELECT name, class_id, type, datatype_range FROM class_property WHERE name = '".$property["name"]."'", __LINE__);
		while ($row = mysql_fetch_row($result)) {
			$properties[$id]["datatype_range"][] = $row[3];
			$properties[$id]["type"][] = $row[2];
			$properties[$id]["class_id"][] = $row[1];
		}
		foreach ($properties[$id]["type"] as $tid => $type) {
			if ($tid == 0) {
				$this_old_type = $type;
	 		}
			if ($this_old_type != $type) {
				$properties_ranges[$id] = $properties[$id];
			}
			$this_old_type = $type;
		}
	}
}
if (sizeof($properties_ranges) > 0) {
	foreach ($properties_ranges as $id => $property) {
		echo "   ".$property["name"].":\n";
		foreach ($property["type"] as $tid => $type) {
			echo "     (class: ".getClassNameFromClassId($property["class_id"][$tid]).") ".$property["type"][$tid]." ".$property["datatype_range"][$tid]."\n";
		}
	}
}

// wrong mappings
echo "\n\n4. Wrong Mappings:\n";
echo "     Template.Property	|	Column E	|	Column F\n";
echo "     =================================================\n";
foreach ($wrong_mappings as $wrong_mapping) {
	echo "     $wrong_mapping\n";
}

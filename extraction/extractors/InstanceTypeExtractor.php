<?php

/**
 *
 *
 */

class InstanceTypeExtractor extends Extractor {
	const replacePatternLinks = "***@@@***@@@***@@@***@@@";
	const replacePatternSubTemplates = "***---***---***---***---";

	private $rules_uri = array();
	private $rules_property = array();
	
	private $mysql;
					
	public function start($language) {
		
		$this->language = $language;

		include ("databaseconfig.php");
		$catalog = $dbprefix."extraction_".$language;
		$this->mysql = new MySQL($host, $user, $password, $catalog);
		
		$mysql = $this->mysql;
		
		$result = $mysql->query('SELECT template_uri, new_class_id FROM rule_uri');
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$this->rules_uri[$row[0]] = $row[1];
			}
		}

		$result = $mysql->query('SELECT class_id AS class_id, template_property AS template_property, type AS type, value AS value, new_class_id AS new_class_id FROM rule_property');
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$this->rules_property[] = $row;
			}
		}
	}

	public function extractPage($pageID, $pageTitle, $pageSource) {

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

                        $dbpedia_uri = "http://dbpedia.org/resource/Template:" . Util::encodeLocalName($template["name"]);

			// Template URI Rule
			$class_id = $this->rules_uri[$dbpedia_uri];

                        $props = Util::getTemplateProperties($tpl);
			
			if ($class_id == null) {
				//Template ID in DB finden
				$templatequery = "select template_id from template_uri where uri = '$dbpedia_uri'";
				$templatequeryresult = $mysql->query($templatequery);
				$tqrow = mysql_fetch_array($templatequeryresult, MYSQL_ASSOC);
				$template_id = $tqrow['template_id'];
				if(!isset($template_id)) {
                                    continue;
                                }
				//NO TEMPLATE-MAPPING FOUND, PROCEED WITH NEXT TEMPLATE IN PAGE
                                /*
				if(!isset($template_id)) {
					$first_template_parameter = trim($props[0][1]);
					if (strpos($first_template_parameter, "=") === false) {
						$templatequery = "select template_id from template_uri where uri = '$dbpedia_uri' and template_type ='" . $mysql->escape($first_template_parameter) . "'";
						$templatequeryresult = $mysql->query($templatequery);
						$tqrow = mysql_fetch_array($templatequeryresult, MYSQL_ASSOC);
						$template_id = $tqrow['template_id'];
						if(!isset($template_id)) {
							continue;
						}
					} else {
						continue;
					}
				}
                                */

				//Klasse zu Template finden
				$classquery = "select name, class_id from class, template_class where template_class.template_id = '$template_id' and template_class.class_id = class.id";
				$classqueryresult = $mysql->query($classquery);
				$cqrow = mysql_fetch_array($classqueryresult, MYSQL_ASSOC);
				$class_id = $cqrow['class_id'];
				$class_name = $cqrow['name'];
			}

			// TODO: $this->rules_property

			$rule_property_classes = array();
			foreach ($this->rules_property as $key => $rule) {
				if ($class_id == $rule[0]) {
					$rule_property_classes[$key] = $rule;
				}
			}

			if ($rule_property_classes != null) {
				foreach ($props as $keyvalue) {
					$propkey = $mysql->escape(trim($keyvalue[1]));
					$propvalue = trim($keyvalue[2]);
/*
					$propquery = "select name from template_property where name = '$propkey' and template_id = '$template_id'";
					$propqueryresult = $mysql->query($propquery);
					$pqrow = mysql_fetch_array($propqueryresult, MYSQL_ASSOC);
					$found_template_property_name = $pqrow['id'];
                                        if ($found_template_property_name != null) {
*/
                                         foreach ($rule_property_classes as $rule) {
                                                if ($rule[1] == $propkey) {
                                                        if (($rule[2] == "set") && (trim($propvalue) != "")) {
                                                                $class_id = $rule[4];
                                                        } else if ($rule[2] == "exists") {
                                                                $class_id = $rule[4];
                                                        } else if (($rule[2] == "value") && (trim($propvalue) == $rule[3])) {
                                                                $class_id = $rule[4];
                                                        }
                                                        continue;
                                                }
                                        }
				}
			}

			if ($class_id != null) {
                            $classes = $this->get_class_path($class_id);
							
							$subject = RDFtriple::page($pageID);
							$predicate = RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");

                            foreach ($classes as $c) {
								$type = $c === 'Thing' ? 'http://www.w3.org/2002/07/owl#'.$c : 'http://dbpedia.org/ontology/'.$c;
								$object = RDFtriple::URI($type);
                                $result->addTriple($subject, $predicate, $object);
                            }
                        }
		}

		return $result;
	}
	public function finish() {
		return null;
	}

	function get_class_path($node) {

		// look up the parent of this node
		$query = "SELECT parent_id, name FROM class WHERE id = $node";
		//var_dump($query);
		$result = $this->mysql->query($query);
		$row = mysql_fetch_array($result);

		// save the path in this array
		$path = array();
		$path[] = $row['name'];

		// only continue if this $node isn't the root node
		// (that's the node with no parent)
		if ($row['parent_id'] != null) {
			// the last part of the path to $node, is the name
			// of the parent of $node

			// we should add the path to the parent of this node
			// to the path
			$path = array_merge($this->get_class_path($row['parent_id']), $path);
		}

		// return the path
		return $path;
	}

	// Helpfunction for preg_replace_callback, to replace "|" with #### inside subtemplates
	public static function replaceBarInSubTemplate($stringArray) {
		return str_replace("|",InstanceTypeExtractor::replacePatternSubTemplates,$stringArray[0]);
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

	function encodeLocalName($string) {
		$string = strtolower(trim($string));
		//  return urlencode(str_replace(" ","_",trim($string)));
		$string = urlencode(str_replace(" ","_",trim($string)));
		// Decode slash "/", colon ":", as wikimedia does not encode these
		$string = str_replace("%2F","/",$string);
		$string = str_replace("%3A",":",$string);

		return $string;
	}

}


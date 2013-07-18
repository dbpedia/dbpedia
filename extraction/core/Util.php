<?php

/**
 * The Util class offers functions that are needed globally and often.
 *
 * @author Anja Jentzsch <mail@anjajentzsch.de>
 **/


class Util {
    const replacePatternLinks = "***@@@***@@@***@@@***@@@";
    const replacePatternSubTemplates = "***---***---***---***---";


	public static function getOaiIDfromIdentifier($language, $oaiidentifier){
			//oai:en.wikipedia.org:enwiki:1717878
			$prefix = "oai:".$language.".wikipedia.org:".$language."wiki:";
			return str_replace($prefix,"", $oaiidentifier);
	 }

	 //md5 until somebody finds a better one
	 //maybe md5 is good, I tested it on the whole dbpedia ntriple set
	 // and it produces 0 collisions
	 public static function defaultHashFunction($str){
		 	return md5(trim($str));
		 }

	/**
	 * Get templates from given text
	 *
	 * @param string $text
	 * @return array templates
	 */
	public static function getTemplates($text) {
        $result_templates = array();
		preg_match_all('/\{{2}(((?>[^\{\}]+)|(?R))*)\}{2}/x', $text, $templates); // search {{....}}
        foreach($templates[1] as $template) {
            $template = Util::removeComments($template);

            // get template name
            preg_match_all("/([^|]*)/", $template, $template_name, PREG_SET_ORDER);
            $template_name = trim($template_name[0][0]);

            $result_templates[]["content"] = $template;
            if (strlen($template_name) > 0) {
	            $result_templates[sizeof($result_templates)-1]["name"] = $template_name;
            }
        }
        return $result_templates;
	}

        public function encodeLocalName($string) {
            $string = strtolower(trim($string));
            //  return urlencode(str_replace(" ","_",trim($string)));
            $string = urlencode(str_replace(" ","_",trim($string)));
            // Decode slash "/", colon ":", as wikimedia does not encode these
            $string = str_replace("%2F","/",$string);
            $string = str_replace("%3A",":",$string);

            return $string;
        }


	/**
	 * Get properties from template
	 *
	 * @param string $template
	 * @return array properties
	 */
	public static function getTemplateProperties($template) {
        //Replace "|" inside labeled links with  to avoid splitting them like triples
        $template = preg_replace('/\[\[([^\]]+)\|([^\]]*)\]\]/','[[\1'.Util::replacePatternLinks.'\2]]', $template);
        // Replace "|" inside subtemplates with "\\" to avoid splitting them like triples
        $template = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/", 'Util::replaceBarInSubTemplate', $template);

        // find template keyvalue pairs
        preg_match_all("/\|\s*([^=|]+)\s*=?([^|]*)/", $template, $properties, PREG_SET_ORDER);

        foreach ($properties as $id => $keyvalue) {
            $keyvalue = str_replace(Util::replacePatternLinks, '|', $keyvalue);
            $keyvalue = str_replace(Util::replacePatternSubTemplates, '|', $keyvalue);
            $properties[$id][1] = trim($keyvalue[1]);
            $properties[$id][2] = trim($keyvalue[2]);
        }
        return $properties;
	}

    public static function replaceBarInSubTemplate($stringArray) {
        return str_replace("|", Util::replacePatternSubTemplates, $stringArray[0]);
    }

    /**
	 * Replace Template Code with compiled Template
	 *
	 * @param string $text
	 * @return text
	 */
    public static function replaceTemplates($text) {
        //$url = "http://160.45.137.78:88/wikipedia_tpl/index.php/Albert_Einstein?tpl=".urlencode($text);
        //$text = file_get_contents($url);
        return $text;
    }

    /**
	 * Replace Wiki Links with their labels
	 *
	 * @param string $text
	 * @return text
	 */
    public static function replaceWikiLinks($text) {
        $text = preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/",'Util::getLabelForLink', $text);
        return $text;
    }

    /**
	 * Remove comment sections
	 *
	 * @param string $text
	 * @return text
	 */
    public static function removeComments($text) {
        $text = preg_replace("/\s*<!--.*?-->\s*/s", "", $text);
        return $text;
    }

    /**
	 * Remove templates
	 *
	 * @param string $text
	 * @return text
	 */
    public static function removeTemplates($text) {
        $text = preg_replace('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x', "", $text);
        return $text;
    }

    /**
	 * Remove HTML tags
	 *
	 * @param string $text
	 * @param array $tags_to_remove
	 * @param array $tags_to_keep
	 * @return text
	 */
    public static function removeHtmlTags($text, $tags_to_remove = null, $tags_to_keep = null) {
        if ($tags_to_remove) {
            if ($tags_to_keep) {
                if (!in_array($tags_to_remove, $tags_to_keep)) {
                    if ($tags_to_remove == "a") {
                        $text = preg_replace("/<".$tags_to_remove."[^>]*>(.*?)<\/".$tags_to_remove.">/s", "$1", $text);
                    }  else if ($tags_to_remove == "br") {
                        $text = str_replace("<br>", "", $text);
                        $text = str_replace("<br/>", "", $text);
                        $text = str_replace("<br />", "", $text);
                    } else {
                        $text = preg_replace("/<".$tags_to_remove."[^>]*>.*?<\/".$tags_to_remove.">/s", "", $text);
                    }
                }
            } else {
                if ($tags_to_remove == "a") {
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>(.*?)<\/".$tags_to_remove.">/s", "$1", $text);
                } else if ($tags_to_remove == "br") {
                    $text = str_replace("<br>", "", $text);
                    $text = str_replace("<br/>", "", $text);
                    $text = str_replace("<br />", "", $text);
                } else if ($tags_to_remove == "ref")  {
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>.*?<\/".$tags_to_remove.">/s", "", $text);
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>/s", "", $text);
                } else if ($tags_to_remove == "nowiki")  {
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>(.*?)<\/".$tags_to_remove.">/s", "$1", $text);
                } else if ($tags_to_remove == "small")  {
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>(.*?)<\/".$tags_to_remove.">/s", "$1", $text);
                } else {
                    $text = preg_replace("/<".$tags_to_remove."[^>]*>.*?<\/".$tags_to_remove.">/s", "", $text);
                }
            }

        } else {
            $text = preg_replace("/<a[^>]*>(.*?)<\/a>/s", "$1", $text);
            $text = preg_replace("/<nowiki>(.*?)<\/nowiki>/s", "$1", $text);
            $text = preg_replace("/<ref[^>]*>(.*?)<\/ref>/s", "", $text);
            $text = preg_replace("/<sup[^>]*>(.*?)<\/sup>/s", "", $text);
            $text = strip_tags($text);
            $text = trim($text);
        }
        return $text;
    }

    /**
	 * Remove HTML comments
	 *
	 * @param string $text
	 * @return text
	 */
    public static function removeHtmlComments($text) {
        $text = preg_replace("/<!--(.*?)-->/s", "$1", $text);
        return $text;
    }

    /**
	 * Remove Wiki emphasis
	 *
	 * @param string $text
	 * @return text
	 */
    public static function removeWikiEmphasis($text) {
        $text = preg_replace("/'''''(.*?)'''''/s", "$1", $text);
        $text = preg_replace("/'''(.*?)'''/s", "$1", $text);
        $text = preg_replace("/''(.*?)''/s", "$1", $text);
        return $text;
    }


	private static function _getMediaWikiNamespace($language, $what){
			global $MEDIAWIKI_NAMESPACES;
			//echo $language.$what;die;
			if(!in_array($what, $MEDIAWIKI_NAMESPACES['legal'])){
				Logger::error('no namespace for '.$what.' illegal use, does not exist');
				};
			if(!isset($MEDIAWIKI_NAMESPACES[$language])){
				Logger::warn('namespaces not set in core/language_namespaces for: '.$language);
				$MEDIAWIKI_NAMESPACES[$language] = array();
			 }
			if(!isset($MEDIAWIKI_NAMESPACES[$language][$what])){
				Logger::warn('no namespace for '.$what.' in language: '.$language.' in core/language_namespaces using english instead of: '.$language);
				$MEDIAWIKI_NAMESPACES[$language][$what] = $MEDIAWIKI_NAMESPACES['en'][$what];
			}
			return $MEDIAWIKI_NAMESPACES[$language][$what];
		}

	public static function getDBpediaCategoryPrefix($language){
			return DB_RESOURCE_NS . urlencode(self::_getMediaWikiNamespace($language, MW_CATEGORY_NAMESPACE)).':';
		}

	public static function getMediaWikiCategoryNamespace($language){
			return self::_getMediaWikiNamespace($language, MW_CATEGORY_NAMESPACE);
		}

	public static function getMediaWikiNamespace($language, $what){
			//global $MEDIAWIKI_NAMESPACES;
			return self::_getMediaWikiNamespace($language, $what);
		}

    /**
	 * Determines whether an article is a redirect.
	 *
	 * @param string $pageSource
	 * @param string $language
	 * @return boolean
	 */
    public static function isRedirect($pageSource, $language) {

        // dbpedia.php requires the redirects.php with creates the MEDIAWIKI_REDIRECTS array
        global $MEDIAWIKI_REDIRECTS;
        if (! isset($MEDIAWIKI_REDIRECTS[$language]) || sizeof($MEDIAWIKI_REDIRECTS[$language]) == 0) {
            return preg_match('/^\s*#redirect\s*:?\s*\[\[/i', $pageSource) === 1;
        } else {
            // Note: at the moment, no redirect tag contains special regex characters.
            // If that changes, we have to preg_escape() the redirect tags.
            if (preg_match('/^\s*('.implode('|', $MEDIAWIKI_REDIRECTS[$language]).')\s*:?\s*\[\[/i', $pageSource) === 1) {
                return true;
            };
        }
        return false;
    }

    /**
	 * Determines whether an article is a disambiguation.
	 *
	 * @param string $pageSource
	 * @param string $language
	 * @return boolean
	 */
    public static function isDisambiguation($pageSource, $language) {

        // dbpedia.php requires the disambig.php with creates the MEDIAWIKI_DISAMBIGUATIONS array
        global $MEDIAWIKI_DISAMBIGUATIONS;
		if (isset($MEDIAWIKI_DISAMBIGUATIONS[$language])) {
			foreach ($MEDIAWIKI_DISAMBIGUATIONS[$language] as $disambig) {
				if (strpos($pageSource, '{{'.$disambig.'}}') !== false) {
					return true;
				}
			}
		}

        switch ($language) {
            case 'en' :
                if (strpos($pageSource, "{{disambig}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Disambig}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Disambig-Chinese-char-title?}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Disambig-cleanup}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Fish-dab}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Geodis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Hndis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Hndis-cleanup}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Hospitaldis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Letter disambig}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Mathdab}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{NA Broadcast List}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Numberdis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{POWdis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Roaddis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Schooldis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{SIA}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Shipindex}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Schooldis}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Mountainindex}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Given name}}") !== false) {
                    return true;
                }
                if (strpos($pageSource, "{{Surname}}") !== false) {
                    return true;
                }
                break;
            case 'af' :
                if (strpos($pageSource, "{{Dubbelsinnig}}") !== false) {
                    return true;
                }
                break;
            case 'ar' :
                if (strpos($pageSource, "{{?????}}") !== false) {
                    return true;
                }
                break;
            case 'bg' :
                if (strpos($pageSource, "{{?????????}}") !== false) {
                    return true;
                }
                break;
            case 'da' :
                if (strpos($pageSource, "{{Flertydig}}") !== false) {
                    return true;
                }
                break;
            case 'de' :
                if (strpos($pageSource, "{{Begriffskl�rung}}") !== false) {
                    return true;
                }
                break;
            case 'eo' :
                if (strpos($pageSource, "{{Apartigilo}}") !== false) {
                    return true;
                }
                break;
            case 'es' :
                if (strpos($pageSource, "{{Desambiguaci�n}}") !== false) {
                    return true;
                }
                break;
            case 'eu' :
                if (strpos($pageSource, "{{argipen}}") !== false) {
                    return true;
                }
                break;
            case 'fr' :
                if (strpos($pageSource, "{{Homonymie}}") !== false) {
                    return true;
                }
                break;
            case 'fy' :
                if (strpos($pageSource, "{{Homonymie}}") !== false) {
                    return true;
                }
                break;
            case 'fr' :
                if (strpos($pageSource, "{{Neibetsjuttings}}") !== false) {
                    return true;
                }
                break;
            case 'it' :
                if (strpos($pageSource, "{{Disambigua}}") !== false) {
                    return true;
                }
                break;
            case 'ko' :
                if (strpos($pageSource, "{{disambig}}") !== false) {
                    return true;
                }
                break;
            case 'la' :
                if (strpos($pageSource, "{{Discretiva}}") !== false) {
                    return true;
                }
                break;
            case 'li' :
                if (strpos($pageSource, "{{Verdudeliking}}") !== false) {
                    return true;
                }
                break;
            case 'nl' :
                if (strpos($pageSource, "{{Doorverwijspagina}}") !== false) {
                    return true;
                }
                break;
            case 'no' :
                if (strpos($pageSource, "{{Peker}}") !== false) {
                    return true;
                }
                break;
            case 'nl' :
                if (strpos($pageSource, "{{Doorverwijspagina}}") !== false) {
                    return true;
                }
                break;
            case 'pl' :
                if (strpos($pageSource, "{{disambig}}") !== false) {
                    return true;
                }
                break;
            case 'pt' :
                if (strpos($pageSource, "{{disambig}}") !== false) {
                    return true;
                }
                break;
            case 'ru' :
                if (strpos($pageSource, "{{???????????????}}") !== false) {
                    return true;
                }
                break;
            case 'sv' :
                if (strpos($pageSource, "{{F�rgrening}}") !== false) {
                    return true;
                }
                break;
        }
        return false;
    }

    public static function replaceExternalLinks($text) {
        $text = preg_replace("~\[([a-zA-Z]+://[^\s\]]*)\s+([^\]]+)\s*\]~", "$2", $text);
        $text = preg_replace("~\[([a-zA-Z]+://[^\s\]]*)\s*\]~", "$1", $text);
        return $text;
    }

    public static function getLabelForLink($text) {
        if (is_array($text)) {
            return str_replace("]]","",str_replace("[[","",preg_replace("/.*\|/", "[[", $text[0]))) ;
        }
        else {
            return str_replace("]]","",str_replace("[[","",preg_replace("/.*\|/", "[[", $text))) ;
        }
    }

    /**
	 * Writes a log message
	 *
	 * @param string $pageID
	 * @param string $extractorID
	 * @param string $language
	 * @param string $propValue
	 * @param string $propName
	 * @param string $msg
	 */
    public static function writeLogMsg($pageID, $extractorID, $language , $propName, $propValue, $msg = "(none)") {
        $message = $extractorID." has caused a error:
            pageID: 		".$pageID."
            language: 		".$language."
            proberty name: 	".$propName."
            property value: ".$propValue."
            message: 		".$msg;
        Logger::error($message);
    }


    public static function startsWith($haystack, $needle){
        // Recommended version, using strpos
        return strpos($haystack, $needle) === 0;
    }

    public static function endsWith($haystack, $needle){
        $start = strlen($haystack) - strlen($needle);
        return strpos($haystack, $needle, $start) === $start;
    }
    public static function deck($in, $space = 0){
        $w = str_repeat('&nbsp;',$space);
        return '<td>'.$w .$in.$w .'</td>';
    }
    public static function row($in, $space = 0){
        $w = str_repeat('&nbsp;',$space);
        return '<tr>'.$w.$in.$w.'</tr>'."\n";
    }



    /**
     *
     * Converts string to canonical wiki representation
     * Namespace is only recognized if there is an entry in namespaces
     * Namespace part and name part will be trimmed
     * Remaining whitespaces will be replaced by underscores
     * TODO Multiple consequtive underscores will be replaced by a single underscore
     * The whole namespace name will be turned lowercase except for the first letter
     * The first letter of the name will be made uppercase
     *
     * Example
     *    mYnameSPACE  :     wHat     EVER
     * will currently become:
     * MYnameSPACE:WHat_____EVER
     * should become
     * MYnameSPACE:WHat_EVER
     *
     *
     * @param <type> $str The source string
     * @param <type> $namespaces An array containing the names of namespaces
     * @return <type> A canonical representation of the wiki name
     *
     */
    public static function toCanonicalWikiCase($str, $namespaces = array())
    {
        $parts = explode(":", $str, 2);
        $namespaceName = "";
        if(sizeof($parts) == 2) {
            $tmp = self::canonicalWikiTrim($parts[0]);
            $tmp = ucfirst(strtolower($tmp));

            if(in_array($tmp, $namespaces)) {
                $namespaceName = "$tmp:";
                $articleName = ucfirst(self::canonicalWikiTrim($parts[1]));
            }
        }

        if(!isset($articleName))
        $articleName = ucfirst(self::canonicalWikiTrim($str));

        //echo $namespaceName . "\n";
        //echo $articleName . "\n";
        return "$namespaceName$articleName";
    }

    /**
     * Removes heading and trailing whitespaces
     * Replaces remaining white spaces with underscore
     * Replaces consecutive underscores with a single underscore
     *
     * @param <type> $name
     * @return <type>
     */
    public static function canonicalWikiTrim($name)
    {
        $result = trim($name);
        $result = str_replace(' ', '_', $result);
        $result = preg_replace("/_+/", "_", $result);

        return $result;
    }


    /**
     * Retrieves data from a wiki export page and returns the namespace
     * mappings.
     *
     * @param <type> $mediawikiExportUri
     * @return <type>
     *
     */
    public static function retrieveNamespaceMappings($mediawikiExportUri)
    {
        ini_set('user_agent', 'DBpedia');
        $xml = simplexml_load_file($mediawikiExportUri."Php", 'SimpleXMLElement', LIBXML_NOCDATA);

        // for some reason xpath didn't work
        // wanted to do $xml->xpath("//namespace")... oh well
        $result = array();
        foreach($xml->siteinfo->namespaces->children() as $namespace) {
            $key = (integer)$namespace["key"];
            $namespace = self::canonicalWikiTrim((string)$namespace);

            // Skip article namespace
            if(strlen($namespace) == 0)
            continue;

            $result[$key] = $namespace;
        }

        return $result;
    }
}


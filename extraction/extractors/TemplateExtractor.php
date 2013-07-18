<?php

// =============== WARNING ===============
// FIXME: This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============


/**
 * The TemplateExtractor finds all templates, properties and there values
 * and writes them into the $dbprefix.'templates_'.$anguage database
 * for statistics
 *
 * @author: Paul Kreis <mail@paulkreis.de>
 */

class TemplateExtractor extends  Extractor
{
    public function start($language) {
        $this->language = $language;
    }

    public function extractPage($pageID, $pageTitle,  $pageSource) {
        include ("databaseconfig.php");

        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
        if($this->decode_title($pageTitle)==NULL) return $result;

        $this->DBlink = mysql_connect($host, $user, $password)
        or die("Database connection failed: " . mysql_error());

        // Create database if not exists
        if (!mysql_select_db($dbprefix.'templates_'.$this->language)) {
            mysql_query("CREATE DATABASE `".$dbprefix."templates_".$this->language."` ;");
            mysql_select_db($dbprefix.'templates_'.$this->language) or die("Database selection failed: " . $dbprefix."templates_".$this->language);
            mysql_query("CREATE TABLE IF NOT EXISTS `properties` (
                                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                                    `template_id` int(11) NOT NULL,
                                                    `property_name` blob NOT NULL,
                                                    `count` int(11) NOT NULL DEFAULT '0',
                                                    `countFilled` int(11) NOT NULL DEFAULT '0',
                                                    PRIMARY KEY (`id`),
                                                    KEY `propertyName` (`property_name`(255)),
                                                    KEY `templatenID` (`template_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");
            mysql_query("CREATE TABLE IF NOT EXISTS `property_values` (
                                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                                    `property_id` int(11) NOT NULL,
                                                    `template_id` int(11) NOT NULL,
                                                    `value` blob NOT NULL,
                                                    `page_id` blob NOT NULL,
                                                    PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");
            mysql_query("CREATE TABLE IF NOT EXISTS `templates` (
                                                   `id` int(11) NOT NULL AUTO_INCREMENT,
                                                   `template_name` blob NOT NULL,
                                                   `count` int(11) NOT NULL DEFAULT '0',
                                                    PRIMARY KEY (`id`),
                                                    KEY `templatenName` (`template_name`(255))) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");
        }

        mysql_query("SET NAMES utf8", $this->DBlink);

        echo $pageID . "\n";

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
            // Remove comments
            $rawTemplate = Util::removeComments($rawTemplate);

            // Get template label
            preg_match_all("/([^|]*)|/", $rawTemplate, $rawTemplateResult, PREG_SET_ORDER);
            $templateLabel = preg_replace('/<\!--.*-->/m','',$rawTemplateResult[0][0]);
            $templateLabel = trim(strtolower($templateLabel));

            // Replace "|" inside subtemplates to avoid splitting them like triples
            $rawTemplate = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/",array($this,'replaceBarInSubTemplate'),$rawTemplate);

            //Replace "|" inside labeled links to avoid splitting them like triples
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

            // Write templates into database
            $sql = "SELECT * FROM templates WHERE template_name = '$templateLabel' ;";
            $sqlResult = mysql_query($sql);

            // If the table_name ($templateLabel) not exists
            if (mysql_num_rows($sqlResult) == 0) {
                $sql_query = "INSERT INTO templates (template_name, count) VALUES ('$templateLabel', 1)";
                if (!mysql_query ($sql_query)) {
                    if (strpos(mysql_error(), "Duplicate") === false) {
                        die("".mysql_error() . " - query: $sql_query - template name: $templateLabel");
                    }
                }
            }

            // If the table_name ($templateLabel) already exists
            else {
                $templateCount = mysql_result($sqlResult,0,'count');
                $templateCount = $templateCount + 1;
                $sql_query = "UPDATE templates SET count = '$templateCount' WHERE template_name = '$templateLabel';";
                if (!mysql_query ($sql_query)) {
                    die("ERROR: " . mysql_error() . " --- Query: $sql_query ");
                }
            }

            // Get template ID
            $sql = "SELECT id FROM templates WHERE template_name = '$templateLabel' ;";
            $sqlResult = mysql_query($sql);
            $templateID = mysql_result($sqlResult,0);

            // Write properties and values into database
            foreach ($keyvalues as $keyvalue) {
                $keyvalue = str_replace('***@@@***@@@***@@@***@@@','|',$keyvalue);
                $keyvalue = str_replace('***---***---***---***---','|',$keyvalue);
                $propkey = strtolower(str_replace("'","\'",str_replace("\\","\\\\",trim($keyvalue[1]))));
                $propvalue = str_replace("'","\'",str_replace("\\","\\\\",trim($keyvalue[2])));

                // Write properties into database
                $sql = "SELECT * FROM properties WHERE property_name = '$propkey' AND template_id = '$templateID' ;";
                $sqlResult = mysql_query($sql);

                // If the property is not in the database
                if (mysql_num_rows($sqlResult) == 0) {
                    if ($propvalue !='') $propertyCountFilled = 1; else $propertyCountFilled = 0;
                    $sql_query = "INSERT INTO properties (template_id, property_name, count,countFilled) VALUES ('$templateID', '$propkey', 1,'$propertyCountFilled')";
                    if (!mysql_query ($sql_query)) {
                        if (strpos(mysql_error(), "Duplicate") === false) {
                            die("".mysql_error() . " - query: $sql_query - property name: $propkey");
                        }
                    }
                }

                // If the proprty is already in the database
                else {
                    $propertyID = mysql_result($sqlResult,0,'id');
                    $propertyCount = mysql_result($sqlResult,0,'count');
                    $propertyCount = $propertyCount + 1;
                    $propertyCountFilled = mysql_result($sqlResult,0,'countFilled');
                    if ($propvalue !='') {
                        $propertyCountFilled = $propertyCountFilled + 1;
                    }
                    $sql_query = "UPDATE properties SET count = '$propertyCount', countFilled = '$propertyCountFilled' WHERE id = '$propertyID';";
                    if (!mysql_query ($sql_query)) {
                        die("ERROR: " . mysql_error() . " --- Query: $sql_query ");
                    }
                }

                // Write values into database
                $propertyID = mysql_result(mysql_query("SELECT id FROM properties WHERE property_name = '$propkey' AND template_id = '$templateID' "),0);
                $fixedPageID = mysql_escape_string($pageID);
                $sql_query = "INSERT INTO property_values (property_id, template_id, value, page_id) VALUES ('$propertyID','$templateID','$propvalue','$fixedPageID')";
                if (!mysql_query ($sql_query)) {
                    die("ERROR: " . mysql_error() . " --- Query: $sql_query ");
                }
            }

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

    /**
     * Helpfunction for preg_replace_callback, to replace "|" with ***---***---***---***--- inside subtemplates
     *
     * @param unknown_type $stringArray
     * @return string
     */
    public static function replaceBarInSubTemplate($stringArray) {
        return str_replace('|','***---***---***---***---',$stringArray[0]);
    }
    public function finish() {
        //return $this->getPredicates();
    }
}
?>

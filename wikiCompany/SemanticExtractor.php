<?php

/**
 * Extracts Semantic Links and literals from wikicompany
 */

class SemanticExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/wikicompany/extractors/SemanticExtractor";
    private $language;
    private $dbConnection;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        include ('extraction/config.inc.php');
	    $this->language = $language;
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
        
        $pageID = encodeLocalName($pageID);
                
        // Remove Template as this is already extracted by the Infobox Extractor
        // Find subtemplates and remove Subtemplates, which are listed as ignored!
		preg_match_all('~\{((?>[^{}]+)|(?R))*\}~x',$pageSource,$subTemplates);
			foreach($subTemplates[0] as $key=>$subTemplate) {
				$subTemplate=preg_replace("/(^\{\{)|(\}\}$)/","",$subTemplate); // Cut Brackets / {}
				$pageSource=str_replace('{{'.$subTemplate.'}}','',$pageSource);	
			}
       
		// Extract internal Semantic Links
        $findSemanticLinks = preg_match_all('/(\[\[)([a-zA-z0-9\- _]+)(::)([^\]]+)\]\]/',$pageSource,$matches,PREG_SET_ORDER);
        foreach ($matches as $match) {
        	$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate(encodeLocalName($match[2])),
                RDFtriple::page($match[4]));  
        }     
        
             
        // Extract Literals
        $findSemanticLinks = preg_match_all('/(\[\[)([a-zA-Z\-_ ]+)(:=)([^\]]+)\]\]/',$pageSource,$matches,PREG_SET_ORDER);
        foreach ($matches as $match) {
        	$triple = array();
        	$triple = parseAttributeValue($match[4],$pageID,$match[2]); // object, object_is, datatype(, language)
        	$lexicalForm = $triple[0];
        	$datatype = $triple[2];
        	$predicate = propertyToCamelCase(encodeLocalName($match[2]));
        	
        	// Continue if empty String
        	if ($lexicalForm == null)
        		continue;
        	
        	$result->addTriple(
                RDFtriple::page($pageID), 
                RDFTriple::predicate($predicate),
                RDFtriple::literal($lexicalForm,$datatype,'en'));  
           
        }     
        
           
              
                
        return $result;
    }
    
    public function finish() { 
        return null;
    }
    
    

}


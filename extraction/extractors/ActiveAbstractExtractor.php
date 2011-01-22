<?php

/**
	 * Gets the Abstract
	 * Attention the following code block is an adaption 
	 * of the Active Abstract MediaWiki Plugin
	 * created by Brion Vibber et. al.
	 * It is not Public Domain, but has the same license as the MediaWiki
**/

class ActiveAbstractExtractor extends Extractor 
{
	//overrides default
	protected $generateOWLAxiomAnnotations = true;
   
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
		
		//TODO image namespace
		
		$text = $pageSource;	
		
		//TODO not sure what to take as magic number here:
		// 4096 was to short, e.g. inappropriate for london
		$text = substr( $text, 0, 8192 ); 
		$text = self::stripMarkup($text);
		
		//TODO REMOVE THIS LINE FOR DEBUGGING:
		$text = $this->_exceptions($text);
	
		//2 is probalby perfect, since it guarantuees a certain lentgh
		$firstTwoSentences = $this->_extractStart($text, 2);
		
		//better than nothing
		$fullabstract=$firstTwoSentences;
		
		//this is crazy code as it could also be 0
		//it is a heuristical approach to nicen the abstracts.
		// take anything until you find  '=='
		if(($pos = strpos($text, '=='))!==false){
				$fullabstract = trim(substr($text,0,$pos));
			}else {
				$fullabstract = trim($text);
			}
			
		if(!empty($firstTwoSentences)){
			$s =  $this->getPageURI();
			$p =  RDFtriple::URI(DBCOMM_COMMENT, false);
			$o =  RDFtriple::Literal($firstTwoSentences, NULL, $this->language);
			$this->log('debug','Found: '.$s->toString()." ".$p->toString()." ".$o->toString());
			
			$result->addTriple($s,$p,$o);
		}
		if(!empty($fullabstract)){
			$s =  $this->getPageURI();
			$p =  RDFtriple::URI(DBCOMM_ABSTRACT, false);
			$o =  RDFtriple::Literal($fullabstract, NULL, $this->language);
			$this->log('debug','Found: '.$s->toString()." ".$p->toString()." ".$o->toString());
			
			$result->addTriple($s,$p,$o);
		}
		//TODO $clipped = substr( $extract, 0, 1024 ); 
		//TODO UtfNormal::cleanUp( $clipped ); in include/normal/UtfNormal
		
        return $result;
    }
	
	
	private function _exceptions($text){
			$arr = array(
					"()",
					"__NOTOC__"
				);
			
			foreach($arr as $one){
				$text = str_replace($one,'',$text);				
				}
			$text = str_replace('(,','(',$text);
			
			return trim($text);
		
		}
	
	
	/**
	 * Strip markup to show plaintext
	 * Attention the following code block is an adaption 
	 * of the Active Abstract MediaWiki Plugin
	 * created by Brion Vibber et. al.
	 * It is not Public Domain, but has the same license as the MediaWiki
	 * @param string $text
	 * @return string
	 * @access private
	 */
	public static function stripMarkup($text, $image = 'image', $category = 'Category', $language = 'en'){
		
		$category = Util::getMediaWikiCategoryNamespace($language);
		
		$image = preg_quote( $image, '#' );
		// $image = preg_quote( $wgContLang->getNsText( NS_IMAGE ), '#' );
		
		$text = preg_replace( '/(<ref>.+?<\/ref>)/s', "", $text ); // remove ref
		$text = str_replace( "'''", "", $text );
		$text = str_replace( "''", "", $text );
		$text = preg_replace( '#<!--.*?-->#s', '', $text ); // HTML-style comments
		$text = preg_replace( '#</?[a-z0-9]+.*?>#s', '', $text ); // HTML-style tags
		$text = preg_replace( '#\\[[a-z]+:.*? (.*?)\\]#s', '$1', $text ); // URL links
		$text = preg_replace( '#\\{\\{\\{.*?\\}\\}\\}#s', '', $text ); // template parameters
		//$text = preg_replace( '#\\{\\{.*?\\}\\}#s', '', $text ); // template calls
		$text = preg_replace('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x','', $text); // search {{....}}
		$text = preg_replace( '#\\{\\|.*?\\|\\}#s', '', $text ); // tables

		$text = preg_replace( "#
			\\[\\[
				:?$image\\s*:
					(
						[^][]*
						\[\[
						[^][]*
						\]\]
					)*
				[^][]*
			\\]\\]#six", '', $text ); // images

		$text = preg_replace( '#\\[\\[('.$category.':.*)\\]\\]#s', '', $text ); // Category Links
		$text = preg_replace( '#\\[\\[([^|\\]]*\\|)?(.*?)\\]\\]#s', '$2', $text ); // links
		$text = preg_replace( '#^:.*$#m', '', $text ); // indented lines near start are usually disambigs or notices
		//TODO $text = Sanitizer::decodeCharReferences( $text );
		return trim( $text );
		}
		
		
	/**
	 * Extract the first two sentences, if detectable, from the text.
	 * Attention the following code block is an adaption 
	 * of the Active Abstract MediaWiki Plugin
	 * created by Brion Vibber et. al.
	 * It is not Public Domain, but has the same license as the MediaWiki
	 * 
	 * @param string $text
	 * @return string
	 * @access private
	 */
	function _extractStart( $text , $numSentences) {
		$endchars = array(
			'.', '!', '?', // regular ASCII
			'。', // full-width ideographic full-stop
			'．', '！', '？', // double-width roman forms
			'｡', // half-width ideographic full stop
			);
		
		$endgroup = implode( '', array_map( 'preg_quote', $endchars ) );
		$end = "[$endgroup]";
		$sentence = ".*?$end+";
		
		$howmany = str_repeat  ( $sentence  , $numSentences  );
		
		//$firsttwo = "/^($sentence$sentence)/u";
		$firsttwo = "/^($howmany)/u";
		
		if( preg_match( $firsttwo, $text, $matches ) ) {
			return $matches[1];
		} else {
			// Just return the first line
			$lines = explode( "\n", $text );
			return trim( $lines[0] );
		}
	}
	
	
	
	
    public function finish() { 
        return null;
    }
    
	
    
}



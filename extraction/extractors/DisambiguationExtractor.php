<?php

/**
 * Extracts the Disambiguation-Links from a Wikipedia page, looking for {{disambig}} template
 * It extracts only disambiguationlinks to other articles that contain the pageID!
 * e.g. Madonna disambiguates Madonna_(entertainer), but not Mary_(mother_of_Jesus)
 *
 */

class DisambiguationExtractor extends Extractor
{

	public function extractPage($pageID, $pageTitle,  $pageSource) {

		global $MEDIAWIKI_DISAMBIGUATIONS_EXTENSION;
		$result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());

		if (Util::isDisambiguation($pageSource, $this->language)) {

			// use only links that include the name of the current page and don't include a namespace.
			// Example: http://en.wikipedia.org/wiki/User
			// - we omit [[Wikipedia:Username policy]]
			// - we include [[User (computing)]] and many others
			// - TODO: we should include [[Consumer]], but don't - it doesn't include "user"
			if (isset($MEDIAWIKI_DISAMBIGUATIONS_EXTENSION[$this->language])) {
				foreach ($MEDIAWIKI_DISAMBIGUATIONS_EXTENSION[$this->language] as $disambig) {
					if (strpos($pageID, $disambig)) {
						$pageIDClean = str_replace('_('.$disambig.')', '', $pageID);
					}
				}
			} else {
				$pageIDClean = str_replace('_(disambiguation)', '', $pageID);
			}

            if(!isset($pageIDClean)){
                $pageIDClean = "";
                $warn = "pageidclean not set";
                }

			$regex = '/\[\[([^:\[\]]*?'.preg_quote($pageIDClean).'[^\[\]]*?)\]\]/i';

			if (preg_match_all($regex,$pageSource,$matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$object=DB_RESOURCE_NS. URI::wikipediaEncode($this->getLinkForLabeledLink($match[1]));
					try {
						$object = RDFtriple::URI($object);
					} catch (Exception $e) {
						$this->log('warn', 'Caught exception: '.  $e->getMessage(). "\n");
						continue;
					}
					$result->addTriple(
					RDFtriple::page($pageID),
					RDFtriple::URI(DB_DISAMBIGUATES,false),
					$object);
				}
			}
		}
        
        if(isset($warn)){
            $this->log('warn', $warn." $pageID \n");
            }


		return $result;
	}
	public function finish() {
		return null;
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
		return preg_replace("/^(Category|Template):/", "", str_replace('_', ' ', $s));
	}

	function getLinkForLabeledLink($text2) {
		return preg_replace("/\|.*/", "", $text2) ;
	}

}



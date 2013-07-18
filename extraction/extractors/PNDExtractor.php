<?php

/**
 * The PNDExtractor extracts PND data from a Wikipedia article.
 * Information on PND data in articles can be found here:
 * http://de.wikipedia.org/wiki/Hilfe:PND
 *
 * @author Anja Jentzsch <mail@anjajentzsch.de>
 */

class PNDExtractor extends Extractor {

	private $pageTitle;
	private $pageID;
	
	public function extractPage($pageID, $pageTitle,  $pageSource) {
		$this->pageTitle = $pageTitle;
		$this->pageID = $pageID;
		$result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
		if((!Util::isRedirect($pageSource, $this->language)) && (!Util::isDisambiguation($pageSource, $this->language))) {
			$this->findPND($pageSource,$pageTitle, $result);
		}
		return $result;
	}


	public function findPND($text, $pageTitle, &$result) {
		$templates = Util::getTemplates($text);
		foreach ($templates as $template) {
            if ($template["name"] == "Normdaten") {
                preg_match('/\|\s*PND\s*=\s*([0-9X]*)/i', $template["content"], $match);
                if (isset($match[1])) {
                    // add individualised PND
                    $result->addTriple(
                    RDFtriple::page($this->pageID),
                    RDFtriple::URI(DBO_INDIVIDUALISED_PND,false),
                    RDFtriple::Literal($match[1], NULL, NULL)
                    );
                }
            } else if ($template["name"] == "PND") {
				preg_match('/\s*PND\s*\|\s*([0-9X]*)(.*)/i', $template["content"], $match);
				if (isset($match)) {
					if (isset($match[1]) && (strlen($match[1]) >=9)) {
						// add individualised PND
						$result->addTriple(
						RDFtriple::page($this->pageID),
						RDFtriple::URI(DBO_INDIVIDUALISED_PND,false),
						RDFtriple::Literal($match[1], NULL, NULL)
						);
					}
					if (isset($match[2])) {
						preg_match('/\|\s*([0-9X]*)/i', $match[2], $match1);
						if (isset($match1[1]) && (strlen($match1[1]) >=9)) {
							// add non-individualised PND
							$result->addTriple(
							RDFtriple::page($this->pageID),
							RDFtriple::URI(DBO_NON_INDIVIDUALISED_PND,false),
							RDFtriple::Literal($match1[1], NULL, NULL)
							);
						}
					}
				}
            } else if ($template["name"] == "PNDfehlt") {
                preg_match('/\s*PNDfehlt\s*(\|\s*.*)/i', $template["content"], $match);
                if (isset($match[1])) {
                    preg_match('/\|\s*([0-9X]*)/i', $match[1], $match1);
                    if (isset($match1[1]) && (strlen($match1[1]) >=9)) {
                        // add non-individualised PND
                        $result->addTriple(
                        RDFtriple::page($this->pageID),
                        RDFtriple::URI(DBO_NON_INDIVIDUALISED_PND,false),
                        RDFtriple::Literal($match1[1], NULL, NULL)
                        );
                    }
                }
            }
        }
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
		return preg_replace("/^.*:/", "", str_replace('_', ' ', $s));
	}
}


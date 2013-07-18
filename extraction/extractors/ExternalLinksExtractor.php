<?php

/**
 * This Extractors reads out all Links from the "External Links" section of
 * a Wikipedia article.
 *
 *
 */

class ExternalLinksExtractor extends Extractor
{
	 private $allPredicates;


	/*
	 * Overrides superclass method
	 * */
    public function start($language) {
        $this->language = $language;
		$this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());


				$extlinks = $this->extract_external_links($pageSource, $this->language);
				   while(list($ExtURL,$ExtName) = each($extlinks))
					{

                        // Replace single quotes with %27
                        $ExtURL = str_replace("'", "%27", $ExtURL);
                        $ExtURL = str_replace("\\", "\\\\", $ExtURL);

						if (!URI::validate($ExtURL)) continue;
						$result->addTriple(
							$this->getPageURI(),
		                	RDFtriple::URI(DB_REFERENCE,false),
		                	RDFtriple::URI($ExtURL)
						);

					}

        return $result;
    }

	private function getPredicates() {
	   $this->allPredicates->addPredicate(DB_REFERENCE);
    	return $this->allPredicates->getPredicateTriples();
    }
    public function finish() {
       return $this->getPredicates();
    }

	 function extract_external_links($text, $Language) {

		if ($Language == "en"){
			$i = preg_match("/(==+ *External links *==+.*)?$/s", $text, $match);
			//preg_match("/^(.*?)([\n\r] *==+ *External links *==+.*)?$/s", $text, $match);
			// note that we get warnings here when not explicitly checking whether $match has at least two entries
			if (($i!=0) && (count($match) >= 2)) {
				// echo "$text\n";
				$text = $match[1];
			}
		}


        $result = array();
        $set = array();

        // Remove internal links, this makes matching easier
        $text = preg_replace("/\[\[.*?\]\]/", " ", $text);
        preg_match_all("/\[(https?:[^ \r\n\]]+?)( .*?)\]/", $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (strlen($match[1]) > 255) {
                continue;
            }

            // MySQL does some weird case folding for duplicate detection
            // on primary key columns. The URLs end up in a
            // primary key column, and thus we have to make
            // sure there are no different case versions of the
            // same URL for any given article.
            $lower = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $match[1]));
            if (isset($set[$lower])) {
                continue;
            }
            $set[$lower] = true;

            if (isset($match[2])) {
                $result[$match[1]] = trim($match[2]);
            } else {
                $result[$match[1]] = "";
            }

            if (count($result) >= 20) {
                break;
            }
        }
        return $result;
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



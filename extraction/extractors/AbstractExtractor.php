<?php

/**
 * This extractor extracts the long and short abstract for a page and
 * writes them to two destinations. It gets the rendered page from a 
 * modified Wikipedia instance whose location is defined in databaseconfig.php. 
 * Its extract() method returns an empty result. 
 */
class AbstractExtractor extends Extractor {
	
	/** the cURL handle */
	private $curl;
	
	/** the URL of the index.php page of the modified Wikipedia instance */
	private /* final */ $page_url_format;
	
	/** becomes true if setDestinations is called **/
	private $extraDestinationsGiven = false;
		
	/** destination for short abstract, may be null */
	private /* final */ $shortDestination;
	
	/** predicate URI for short abstract, never null */
	private /* final */ $shortPredicate;
	
	/** destination for long abstract, may be null */
	private /* final */ $longDestination;
	
	/** predicate URI for long abstract, never null */
	private /* final */ $longPredicate;
	
	/**
	 * @param $shortDestination destination for short abstract. 
	 * If null, no short abstracts will be extracted.
	 * @param $longDestination destination for long abstract.
	 * If null, no long abstracts will be extracted.
	 */
	public function __construct() {
		parent::__construct();
		//no validation required
		$this->shortPredicate = RDFtriple::URI(RDFS_COMMENT,false);
		$this->longPredicate = RDFtriple::predicate("abstract");
	}
	
	public function setDestinations($shortDestination, $longDestination){
			if(!($shortDestination instanceOf Destination &&  $longDestination instanceOf Destination)) {
				die($this->getExtractorID()."::setDestinations, must be type of Destination");
			}
			$this->extraDestinationsGiven = true;
			$this->shortDestination = $shortDestination;
			$this->longDestination = $longDestination;
		}
	
	/**
	 * Remember language, set URL of modified Wikipedia instance, init cURL, 
	 * call start() on destinations.
	 * @param $language
	 * @return void
	 */
	public function start($language) {
		
		$this->language = $language;
		
		$this->page_url_format = Options::getOption('AbstractExtractor.page_url_format');
		if ($this->page_url_format == null || strlen($this->page_url_format) == 0) {
			die('Please define AbstractExtractor.page_url_format in your option file, e.g. dbpedia.ini.');
		}
		
		$this->curl = curl_init();
		
		if($this->extraDestinationsGiven){
			$this->longDestination->start();
			$this->shortDestination->start();
		}
	}
	
	/**
	 * Close cURL, call finish() on destinations.
	 * @return void
	 */
	public function finish() {
		
		curl_close($this->curl);
		
		if($this->extraDestinationsGiven){
			$this->longDestination->finish();
			$this->shortDestination->finish();
		}
	} 
	
	/**
	 * Binding resources to the lifetime of object
	 * */
	public function __destruct(){
			@curl_close($this->curl);
		}

	/**
	 * Extracts the long and short abstract for the given page and writes them to 
	 * the destinations given to the constructor.
	 * @param $pageID
	 * @param $pageTitle
	 * @param $pageSource
	 * @return empty extraction result
	 */
	public function extractPage($pageID, $pageTitle, $pageSource) {
		$extractionResult = null;
		if(Util::isRedirect($pageSource, $this->language)) {
			$this->log('info',"found redirect: ".$pageTitle);
		} else if(Util::isDisambiguation($pageSource, $this->language)) {
			$this->log('info',"found disambiguation: ".$pageTitle);
		} else {
		 	$extractionResult = $this->extractAbstracts($pageID, $pageTitle) ;
		}
		
		return (null===$extractionResult)?new ExtractionResult($pageID, $this->language, $this->getExtractorID()):$extractionResult;
	}

	/**
	 * Extract the abstracts for the given page and write a triple to each destination
	 * given to the constructor.
	 * @param $pageID
	 * @param $pageTitle
	 * @return void
	 */
	private function extractAbstracts($pageID, $pageTitle) {
		$extractionResult = null;
		$url = sprintf($this->page_url_format, $this->language, str_replace("%3A", ":", str_replace("%2F", "/", urlencode($pageTitle))));
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FAILONERROR, true);
		$text =  curl_exec($this->curl);

		if(false === $text) {
			$this->log('error', $this->getExtractorID().' call to '.$url. ' failed');
			$this->log('error', 'Please define AbstractExtractor.page_url_format correctly in your option file, e.g. dbpedia.ini.');
			$this->log('error', 'nr '.curl_errno($this->curl). "\n".curl_error($this->curl));
			// die($this->getExtractorID().' call to '.$url. ' failed');
			echo($this->getExtractorID().' call to '.$url. ' failed');
			return new ExtractionResult($pageID, $this->language, $this->getExtractorID());
		}

		if (preg_match("/alt=\"#redirect/i", $text, $match)) { 
			$this->log('warn',"found redirect: ".$pageTitle);
		} else if ((strlen($text) == 0) || (strpos($text, "class=\"noarticletext\""))) {
			$this->log('warn',"found no abstract: ".$pageTitle);
		} else if (strpos($text, "<html") !== false) {
			$this->log('warn',"server response contains <html: ".$pageTitle);
		} else if (strpos($text, "If you think this is a server error, please contact")) {
			$this->log('warn',"server response says: 'If you think this is a server error, please contact': ".$pageTitle);
		} else {
			$shorttext =  $this->short($text);
			
			
			if($this->extraDestinationsGiven){
				$this->writeTriple($this->longDestination, $pageID, $this->longPredicate, $text);
				$this->writeTriple($this->shortDestination, $pageID, $this->shortPredicate, $shorttext);
			} else{
				$result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
				$subject =  RDFtriple::page($pageID);
				$objectlong =  RDFtriple::Literal($text, NULL, $this->language);
				$objectshort =  RDFtriple::Literal($shorttext, NULL, $this->language);
				$this->log('debug','Found: '.$subject->toString()." ".$predicate->toString()." ".$objectlong->toString());
				$this->log('debug','Found: '.$subject->toString()." ".$predicate->toString()." ".$objectshort->toString());
				$result->addTriple($subject,$this->longPredicate,$objectlong);
				$result->addTriple($subject,$this->shortPredicate,$objectshort);
				$extractionResult =  $result;
				return $result;
			}
		}
	}
	
	/**
	 * Returns the first sentences of the given text that have less than 500 characters.
	 * A sentence ends with a dot followed by whitespace.
	 * TODO: probably doesn't work for most non-European languages.
	 * TODO: analyse ActiveAbstractExtractor, I think this works  quite well there,
	 * because it takes the first two or three sentences
	 * @param $text
	 * @param $max max length
	 * @return result string
	 */
	public function short($text, $max = 500) {
		
		if (strlen($text) < $max) return $text;
		
		$sentences = preg_split("/(?<=\.\s)/", $text, -1);
		
		$text = "";
		$sum = 0;
		
		foreach ($sentences as $sentence) {
			$cur = strlen($sentence);
			if ($sum + $cur > $max) break;
			$sum += $cur;
			$text .= $sentence;
		}
		
		// Remove leading and trailing spaces
		return trim($text);
	}

	/**
	 * Write given triple to given destination.
	 * @param $destination must not be null
	 * @param $pageID will be turned into a RDF URI using RDFtriple::page()
	 * @param $predicate must be a RDF URI
	 * @param $text will be turned into a RDF literal using RDFtriple::Literal()
	 * @return void
	 */
	private function writeTriple($destination, $pageID, $predicate, $text) {
		$result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
		$subject =  RDFtriple::page($pageID);
		$object =  RDFtriple::Literal($text, NULL, $this->language);
		// $this->log('warn','Found: '.$subject->toString()." ".$predicate->toString()." ".$object->toString());
		$result->addTriple($subject,$predicate,$object);
		Timer::start('destination:accept');
		$destination->accept($result);
		Timer::stop('destination:accept');
	}
	
}

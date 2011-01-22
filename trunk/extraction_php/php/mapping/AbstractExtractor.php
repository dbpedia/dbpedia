<?php
namespace dbpedia\mapping
{
    
use dbpedia\LocalConfiguration;
use dbpedia\core\RdfQuad;
use dbpedia\ontology\Ontology;
use dbpedia\ontology\OntologyDataTypeProperty;
use dbpedia\util\StringUtil;

class AbstractExtractor implements Mapping
{
    private $name = "AbstractExtractor";
    
    const DESTINATION_ID = "AbstractExtractor.destination";

    const SHORT_PROPERTY = "rdfs:comment";
    
    // needs to start with upper case for SMW 
    const LONG_PROPERTY = "Abstract";

    private $logger;
    
    public static function addProperties( Ontology $ontology )
    {
        $ontology->addProperty(new OntologyDataTypeProperty(self::SHORT_PROPERTY, $ontology->getClass("owl:Thing"), $ontology->getDataType("xsd:string")));
        $ontology->addProperty(new OntologyDataTypeProperty(self::LONG_PROPERTY, $ontology->getClass("owl:Thing"), $ontology->getDataType("xsd:string")));
    }
    
    protected $ontology;
    private $destination;
    private $curl;

    private $language = "en";

    private function __construct( Ontology $ontology, ExtractionContext $context )
    {
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
	$this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public static function load($ontology, $context)
    {
        $extractor = new AbstractExtractor($ontology, $context);
        return $extractor;
    }

    public function extract($node, $subjectUri, $pageContext)
    {
		$this->curl = curl_init();
		$url = sprintf(LocalConfiguration::abstractPageUrlFormat, $this->language, $node->getRoot()->getTitle()->encoded());
        curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FAILONERROR, true);
		$text = curl_exec($this->curl);

		if(false === $text) {
    		//echo curl_error($this->curl);
            // TODO
/*
        ('error', 'nr '.curl_errno($this->curl). "\n".curl_error($this->curl));
            $this->log('error', $this->getExtractorID().' call to '.$url. ' failed');
			$this->log('error', 'Please define AbstractExtractor.page_url_format correctly in your option file, e.g. dbpedia.ini.');
			$this->log('error', 'nr '.curl_errno($this->curl). "\n".curl_error($this->curl));
			// die($this->getExtractorID().' call to '.$url. ' failed');
			echo($this->getExtractorID().' call to '.$url. ' failed');
			return new ExtractionResult($pageID, $this->language, $this->getExtractorID());
*/
		}
        if (!empty($text))
        {
            $text = StringUtil::htmlDecode($text);

            $shorttext =  $this->short($text);
            if(!empty($shorttext))
            {
                $quad = new RdfQuad($subjectUri, $this->ontology->getProperty(self::SHORT_PROPERTY), $shorttext, $node->getSourceUri());
                $this->destination->addQuad($quad);
            }
            
            $quad = new RdfQuad($subjectUri, $this->ontology->getProperty(self::LONG_PROPERTY), $text, $node->getSourceUri());
            $this->destination->addQuad($quad);
        }

            return true;
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
}
}

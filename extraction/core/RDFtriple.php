<?php


/**
 * This class constructs RDFtriples.
 *
 * author: Georgi Kobilarov (FU-Berlin)
 */

class RDFtriple {
	private static $pageCacheKey ;
	private static $pageCacheValue ;
    private $subject;
    private $predicate;
    private $object;
	private $annotations = array();
	//lazy initialization
	private $ntriple = null;
	private $SPARULpattern = null;
	private $hashcodeWithOaiId = null;
	private $oaiId = null;


	function __construct($subject, $predicate, $object) {
       	Timer::start('RDFtriple::construct');
	    $this->subject = $subject;
        $this->predicate = $predicate;
		$this->object = $object;
		Timer::stop('RDFtriple::construct');
/*
		if($object->isURI())
		{
				// Note: checkVocabScript.sh looks for 'dbpedia.org', so we hide that string here...
				if(preg_match("/http\:\/\/db                 pedia.org\/resource\//",$object->getURI()) == 1)
				{
				// Note: checkVocabScript.sh looks for 'dbpedia.org', so we hide that string here...
				$this->object = self::URI("http://db            pedia.org/resource/" . self::resolveRedirect(str_replace("http://db          pedia.org/resource/","",$object->getURI())));
				}
				else
				{
				$this->object = $object;
				}
		}
		else
		{
		 $this->object = $object;
		}
*/

    }

	public function validate(){

			try{
				$this->subject->myValidate();
				$this->predicate->myValidate();
				$this->object->myValidate();
			}catch (Exception $e){
					Logger::warn($e->getMessage());
					return false;
				}
			return true;
		}

	//this is a critical function, please think before you change it
	//a lot of dependancies
	public function hashcode(){
			return md5(trim($this->toNTriples()));
		}
	//this is a critical function, please think before you change it
	//a lot of dependancies
	public function	getOWLAxiomURI(){
		return AXIOM_PREFIX.$this->hashcode();
	}
	//this is a critical function, please think before you change it
	//a lot of dependancies
	public static function	recoverOWLAxiomId($s, $p, $o){
		$o = str_replace('"""','"',$o);
		return AXIOM_PREFIX.md5(trim(self::_toNTriplesHelper($s, $p, $o)));
	}


	//this is a critical function, please think before you change it
	//a lot of dependancies
/*
	public function hashcodeWithOaiId($oaiId){
			Timer::start('RDFtriple::hashcodeWithOaiId');
			if(!is_null($this->oaiId) and $this->oaiId !=$oaiId){
					Logger::warn('replacing oaid for '.$this->toNTriples());
					$this->oaiId = null;
					$this->hashcodeWithOaiId = null;
				}

			if(is_null($this->hashcodeWithOaiId)){
				$nt = $this->toNTriples();
				$this->hashcodeWithOaiId = $oaiId.'_'.$this->hashcode();
				$this->oaiId = $oaiId;
			}
			Timer::stop('RDFtriple::hashcodeWithOaiId');
			return $this->hashcodeWithOaiId;
		}

	public static function	recoverOWLAxiomId($oaiId, $s, $p, $o){
		$o = str_replace('"""','"',$o);
		return AXIOM_PREFIX.$oaiId.'_'.md5(trim(self::_toNTriplesHelper($s, $p, $o)));
	}
*/

	public function equals ($triple){
			if(!$triple instanceOf RDFtriple){
				return false;
				}
			return ($this->toNTriples() == $triple->toNTriples());

			$s1=$this->getSubject();
			$s2=$this->getSubject();

			$p1=$this->getPredicate();
			$p2=$triple->getPredicate();

			if(!($s1->equals($s2) && $p1->equals($p2))){
				return false;
				}

			$o1=$this->getObject();
			$o2=$triple->getObject();

			if(!($o1 instanceOf URI && $o2 instanceOf URI && $o1->equals($o2))){
					return false;
				}

			return ($o1->equals($o2));

		}




	public function addOWLAxiomAnnotation($p, $o){
			$arr =  array();
			$arr['p'] = $p;
			$arr['o'] = $o;
			$this->annotations[] = $arr;

		}

	/*
	 * if the original uri was db:London
	 * and generates db:London/rating
	 * the add this to the triples with subject db:London/rating
	 * with $subjecturi = db:London
	 * */
	public function addOnDeleteCascadeAnnotation(URI $subjecturi){
			if($this->subject->equals($subjecturi)){
                return;
			}
            $p = RDFtriple::URI(DBM_ONDELETECASCADE, false);
			$this->addOWLAxiomAnnotation($p, $subjecturi);
		}

	public function addExtractedFromTemplateAnnotation(URI $uri){
			$p = RDFtriple::URI(DBM_EXTRACTEDFROMTEMPLATE, false);
			$this->addOWLAxiomAnnotation($p, $uri);
		}

	public function addDCModifiedAnnotation(){
			$p = RDFtriple::URI(DC_MODIFIED, false);
			$o = RDFtriple::Literal(date('c'), XS_DATETIME, "");
			$this->addOWLAxiomAnnotation($p, $o);
		}
	public function addExtractedByAnnotation($extractorID){
			$p = new URI(DBM_ORIGIN, false);
			$o = RDFtriple::URI($extractorID);
			$this->addOWLAxiomAnnotation($p, $o);
		}



	public function getOWLAxiomAnnotations($oaiId){
		  if(count($this->annotations)==0){
				return $this->annotations;
			}
			Timer::start('RDFtriple::getOWLAxiomAnnotations::total');
			$axiomUniqueId = $this->getOWLAxiomId($oaiId);

			$axiomAnnotations = array();
			$axiom = new URI($axiomUniqueId,false);
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(RDF_TYPE, false), RDFtriple::URI(OWL_AXIOM, false));
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_SUBJECT, false), $this->getSubject());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_PREDICATE, false),  $this->getPredicate());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_OBJECT, false),  $this->getObject());
			foreach ($this->annotations as $one){
				$axiomAnnotations[] = new RDFtriple($axiom, $one['p'], $one['o']);
			}
			//Timer::stop('RDFtriple::getOWLAxiomAnnotations::tripleCreation');
			Timer::stop('RDFtriple::getOWLAxiomAnnotations::total');
			return $axiomAnnotations;

		}

	public function getOWLAxiomAnnotationsAsNTriple($oaiId){
		  if(count($this->annotations)==0){
				return $this->annotations;
			}
			Timer::start('RDFtriple::getOWLAxiomAnnotationsAsNTriple::total');
			$axiomUniqueId = $this->getOWLAxiomURI();
			$axiomNTtriple = '<'.$axiomUniqueId.'>';
			$axiomAnnotations = array();

			//$axiomAnnotations[] = $axiomNTtriple.' <'.RDF_TYPE.'>  <'.OWL_AXIOM.'>'." .\n";
			$axiomAnnotations[] = $axiomNTtriple.' <'.OWL_SUBJECT.'> '.$this->getSubject()->toNTriples()." .\n";
			$axiomAnnotations[] = $axiomNTtriple.' <'.OWL_PREDICATE.'> '.$this->getPredicate()->toNTriples()." .\n";
			$axiomAnnotations[] = $axiomNTtriple.' <'.OWL_OBJECT.'> '.$this->getObject()->toNTriples()." .\n";

			$axiom = new URI($axiomUniqueId,false);
			foreach ($this->annotations as $one){
				 $tmptriple = new RDFtriple($axiom, $one['p'], $one['o']);
				 $axiomAnnotations[] =  $tmptriple->toNTriples();
			}
			//print_r($axiomAnnotations);die;
			//Timer::stop('RDFtriple::getOWLAxiomAnnotations::tripleCreation');
			Timer::stop('RDFtriple::getOWLAxiomAnnotationsAsNTriple::total');
			return $axiomAnnotations;

		}


	function getSubject() {
		return $this->subject;
	}

	function getPredicate() {
		return $this->predicate;
	}


	function getObject(){
		return $this->object;
	}

/*
 * lazy initialisation and cache
 * name: toSPARULPattern
 * @param
 * @return
 */
	function toSPARULPattern() {
		//init
		if(is_null($this->SPARULpattern)){
			$this->SPARULpattern = $this->subject->toSPARULPattern() . " " .
               							$this->predicate->toSPARULPattern() . " " .
                						$this->object->toSPARULPattern() . " . ";
		}

		return $this->SPARULpattern;

    }

	/*
 * lazy initialisation and cache
 * name: toNTriples
 * @param
 * @return
 */
	function toNTriples() {
		//init
		if(is_null($this->ntriple)){
			$this->ntriple = self::_toNTriplesHelper(
				$this->subject->toNTriples() ,
                $this->predicate->toNTriples() ,
                $this->object->toNTriples()
			);
		}
		return $this->ntriple;

    }

	private static function  _toNTriplesHelper($s, $p, $o){
			return "$s $p $o .\n";
		}

    function toString() {
        return $this->toNTriples();
    }

    function __toString() {
        $result = $this->toString();
        foreach($this->annotations as $item)
            $result += "\n\t->({$item['p']}, {$item['o']})";

        return $this->toString();
    }


	function toStringNoEscape() {
		return $this->subject->toNTriples() .
                $this->predicate->toNTriples() .
                $this->object->toCSV() . " \n";
	}

	public static function page($pageID) {
		if(self::$pageCacheKey!=$pageID){

			$encPageID = URI::wikipediaEncode($pageID);
			$returnPageID = strtoupper(substr($encPageID,0,1)) . substr($encPageID,1);
			$resourceURI = DB_RESOURCE_NS.$returnPageID;
			$uri = new URI($resourceURI);
			self::$pageCacheKey = $pageID;
			self::$pageCacheValue = $uri;
		}
		return self::$pageCacheValue;
    }

	/**
	* PageID Parameter must be ENCODED!
	**/
	public static function resolveRedirect($pageID)
	{

		return $pageID;

		/*
		include ("databaseconfig.php");

		$DBlink = mysql_connect($host, $user, $password, true)
		or die("Keine Verbindung moeglich: " . mysql_error());

		mysql_select_db('dbpedia_extraction', $DBlink) or die("Auswahl der Datenbank fehlgeschlagen");

		mysql_query("SET NAMES utf8", $DBlink);

		$decPageID = str_replace("/","%2F",$pageID);
		$decPageID = str_replace(":","%3A",$decPageID);
		$decPageID = mysql_escape_string(urldecode(str_replace("_"," ",trim($decPageID))));

		$redirectquery = "select page_to from redirects where page_from = '$decPageID'";

		$redirectqueryresult = mysql_query($redirectquery, $DBlink) or die("Anfrage redirectqueryresult fehlgeschlagen: " . mysql_error());
		$row = mysql_fetch_array($redirectqueryresult, MYSQL_ASSOC);
		$pageto = $row['page_to'];

		if(isset($pageto))
		{
			$returnPageID = URI::wikipediaEncode($pageto);
		}
		else
		{
			$returnPageID = $pageID;
		}

		return $returnPageID;
		*/
	}

    static function URI($uri, $doValidation = true) {
        return new URI($uri, $doValidation );
    }

    /**
     * @param $class ontology class name
     * @param $property ontology property name
     * @param $newSchema should the old schema be used (use only property name)
     * or the new schema (concatenate class name and ontology name, separated by a slash)
     * @return ontology property URI
     */
    static function property( $class, $property, $newSchema = true ) {
        if ($newSchema) {
            return new URI(DB_ONTOLOGY_NS.$class."/".$property, false);
        } else {
            return new URI(DB_ONTOLOGY_NS.$property, false);
        }
    }

    /**
     * @param $predicate
     * @param $doValidation
     * @return generic predicate URI
     */
    static function predicate($predicate, $doValidation = true) {
		$predicate = DB_PROPERTY_NS.$predicate;
        return new URI($predicate, $doValidation);
    }
	static function meta($localname) {
		$localname = DB_META_NS.$localname;
        return new URI($localname);
    }
    static function blank($label) {
    	return new RDFblankNode($label);
    }
    static function literal($value, $datatype = null, $lang = null) {
       return new RDFliteral($value, $datatype, $lang);


    }

}


/*
 *
 *
 * public  getOWLAxiomAnnotationssecontry(){
		  if(count($this->annotations)==0){
				return $this->annotations;
			}
			Timer::start('RDFtriple::getOWLAxiomAnnotations::total');
			$name = AXIOM_PREFIX.StringIDGenerator::nextID();
			$axiomAnnotations = array();
			Timer::start('RDFtriple::getOWLAxiomAnnotations::name');

            //$id = self::$idGenerator->generate();
            //$name = self::$idPrefix.$id;

			Timer::stop('RDFtriple::getOWLAxiomAnnotations::name');
			Timer::start('RDFtriple::getOWLAxiomAnnotations::tripleCreation');

			$axiom = new URI($name,false);
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(RDF_TYPE, false), RDFtriple::URI(OWL_AXIOM, false));
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_SUBJECT, false), $this->getSubject());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_PREDICATE, false),  $this->getPredicate());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_OBJECT, false),  $this->getObject());
			foreach ($this->annotations as $one){
				$axiomAnnotations[] = new RDFtriple($axiom, $one['p'], $one['o']);
			}
			Timer::stop('RDFtriple::getOWLAxiomAnnotations::tripleCreation');
			Timer::stop('RDFtriple::getOWLAxiomAnnotations::total');
			return $axiomAnnotations;

		}
 *
	public  getOWLAxiomAnnotationsOld(){
			if(count($this->annotations)==0){
					return $this->annotations;
			}
			Timer::start('RDFtriple::getOWLAxiomAnnotations::total');
			$axiomAnnotations = array();
			Timer::start('RDFtriple::getOWLAxiomAnnotations::name');
			$name = str_replace(DB_RESOURCE_NS,"",$this->getSubject()->getURI());
			$name = 'a'.$name;
			$name = str_replace('%','_',
					str_replace('/','_',
					str_replace(':','_',
								$name)));
			if(strlen($name)>200){
				$name = substr($name,0,200);
				}
			Timer::stop('RDFtriple::getOWLAxiomAnnotations::name');
			$axiom = new RDFblankNode($name."_".$this->myID);
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(RDF_TYPE, false), RDFtriple::URI(OWL_AXIOM, false));
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_SUBJECT, false), $this->getSubject());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_PREDICATE, false),  $this->getPredicate());
			$axiomAnnotations[] = new RDFtriple($axiom, RDFtriple::URI(OWL_OBJECT, false),  $this->getObject());
			foreach ($this->annotations as $one){
				$axiomAnnotations[] = new RDFtriple($axiom, $one['p'], $one['o']);
			}
			Timer::stop('RDFtriple::getOWLAxiomAnnotations::total');
			return $axiomAnnotations;

		}
*/
    // id generator used for blank node id generation
    // will be initialized when using annotations
    // NOTE: Requires a database
    //private static $idPrefix;
    //private static $idGenerator;

/*
	public  static function getTripleID(){
			return self::$counter++;
		}

	public static function resetCounter(){
			self::$counter=0;
		}
*/


<?php

require_once("extractors/infobox/extractTemplates.php");

/**
 * Note this Triple Generator generates triples, but the signature differes:
 * This class' generate method takes a breadcrumb object
 *
 */
class RootTripleGenerator
{
    private $breadCrumbTransformer;
    private $templateDb;
    private $language;
    private $templateNameFilter;
    private $parseHintToTripleGenerator;
    //private $defaultTripleGenerator;

    private $mediaWikiUtil;
    
    
    private $allowUnmappedProperties;
    
    private static $wikiPageUsesTemplateUri;

    private function log($lvl, $message)
    {
        Logger::logComponent('extractor', get_class($this), $lvl, $message);
    }

    public function __construct(
        $language,
        $templateNameFilter,
        $templateDb,
        $parseHintToTripleGenerator,
        $mediaWikiUtil)
    {
        self::$wikiPageUsesTemplateUri = new URI(DB_WIKIPAGEUSESTEMPLATE, false);

        $this->breadCrumbTransformer = new DefaultBreadCrumbTransformer();
        $this->templateDb = $templateDb;
        $this->templateNameFilter = $templateNameFilter;
        $this->parseHintToTripleGenerator = $parseHintToTripleGenerator;
        $this->mediaWikiUtil = $mediaWikiUtil;
        //$this->defaultTripleGenerator = new DefaultTripleGenerator($language);
        
        $this->allowUnmappedProperties = Options::getOption('allowUnmappedProperties');
    }


    /**
     *  is called if no parse hint was given.
     *  It must return an array with these elements:
     * 0 (mandatory): The derived property name (e.g. area_km2 -> area)
     * 1 (optional) : The parse hint - if any
     */
    private function deriveParseHintFromName($name)
    {
        // TODO implement :)
        // e.g. mass_lbs -> pounds
        // or area_km2 -> km2
        return;
    }

    private function getTripleGenerator($parseHint)
    {
        if(!isset($parseHint))
            return null;

        if(!array_key_exists($parseHint, $this->parseHintToTripleGenerator))
            return null; //$this->defaultTripleGenerator;

        return $this->parseHintToTripleGenerator[$parseHint];
    }

    /**
     * subject: the subject sub-templates should be attached to.
     * text: the text to parse
     *
     * Recursively processes templates.
     *
     * templateBaseName: e.g. Neutron/mass
     * baseName e.g. Neturon
     *
     * parentPropertyName: under which property the parent called this function
     *
     * the breadcrumb passed to the function denotes the current location
     * in the template path.
     *
     *
     *
     * e.g. London/Infobox_City/2/review/Infobox_SomeInfobox/0/myproperty
     *
     */
    public function generate($breadCrumb, $value)
    {
        $result = $this->myGenerate($breadCrumb, $value);
        
        $allTriples = array_merge($result[0], $result[1]);

        $n = count($allTriples);
        $this->log(DEBUG, "Generated a total of $n triples at $breadCrumb");
        return $allTriples;
    }


    /**
     * This function returns two results:
     * [triples: the generated triples, metaTriples: meta triples 2: the used templates]
     *
     * Unfortunately I haven't renamed the variables yet - result
     * is the array containing the result TRIPLES!! - its not this
     * 2 element.
     *
     */
    private function myGenerate(BreadCrumb $breadCrumb, $value) //, $depth = 0, $parentPropertyName = null)
    {
        // result is the array containing: triples, meta triples, used templates
        $result = array(array(), array(), array());

        $rootSubjectUri = RDFTriple::page($breadCrumb->getRoot());

        // this array is only relevant on depth 0
        $relatedClasses = array();

        //$metaTriples = array();
        //$usedTemplateNames = array();

        // 'parent' means the parent of the value - thus subject and predicate
        $parentName = $this->breadCrumbTransformer->transform($breadCrumb);
        $parentResource = RDFTriple::page($parentName);
        $parentPropertyName = null;
        $tmp = $breadCrumb->peekTop(0);
        if(isset($tmp))
            $parentPropertyName = $tmp->getPropertyName();

        // Get all templates on this site, indexed by name
        // (there may be multiple templates with the same name)
        $nameToTemplates = SimpleWikiTemplateMatcher::match($value);

        //print_r($value);
        //print_r($nameToTemplates);
        //echo "NOW COMES THE STORM\n";
        foreach($nameToTemplates as $templateName => $templates) {
            if(strlen($templateName) < 1)
                continue;

//echo "GOT TEMPLATE NAME $templateName\n";
            $templateName =
                $this->mediaWikiUtil->toCanonicalWikiCase($templateName);

            if(!$this->templateNameFilter->doesAccept($templateName))
                continue;

            $templateUri = RDFTriple::URI(DB_TEMPLATE_NS.$templateName, false);

            $result[2][$templateName] = 1;

            // Get annotations for the template - if there are any
            $lookupName = "Template:$templateName/doc";
             if($breadCrumb->getDepth() == 0) {
				$ta = $this->templateDb->getTemplateAnnotation($lookupName);

            // Create the triples for "relatesToClass"
            // But only for the page itself (not for sub templates)
            // if no related class exists, default to rdf:type owl:Thing
           
                if(isset($ta)) {
                    foreach($ta->getRelatedClasses() as $item){
                        $relatedClasses[$item] = 1;
					}
				}
            }

            foreach($templates as $templateIndex => $template) {
//echo "GOT TEMPLATE INDEX $templateIndex\n";

                // Iterate over all arguments
                $arguments = $template->getArguments();

                foreach($arguments as $argumentName => $values) {
//echo "GOT ARGUMENT NAME $argumentName\n";

                    // propertyNs defaults to DB_PROPERTY_NS unless there
                    // exists a mapping in the templatedb. In that case it will
                    // be set to DB_ONTOLOGY_NS
                    $propertyNs = DB_PROPERTY_NS;

                    $pa = null;
                    if(isset($ta)) {
                        $pas = $ta->getPropertyAnnotations();

                        if(array_key_exists($argumentName, $pas)) {
                            $pa = $pas[$argumentName];
                            $propertyNs = DB_ONTOLOGY_NS;
                        }
                    }
                    //print_r($ta);
//echo "PROPERTY NS : $lookupName - $argumentName = $propertyNs\n";
                    
                    // Fake a property mapping if there was none in the db
                    // This maps argumentName back to iteself
                    if(!isset($pa)) {
                    	// If there was no mapping we might ignore it
                    	// depending on an option (We can prevent this extractor
                    	// to generate triples with properties in the
                    	// dbp:property namespace
                    	// We allow such triples on subResources though.
                    	if($this->allowUnmappedProperties != true &&
                    		$breadCrumb->getDepth() == 0) {
	                    		continue;
	                    }

                    	// If there was no mapping, also rename numeric
                        // argument names (e.g. 1 becomes property1)
                        // this is just cosmetic for the result
                        if(is_numeric($argumentName))
                            $argumentName = "property$argumentName";

                        $pa = new PropertyAnnotation($argumentName);
                        $pa->addMapping(new PropertyMapping($argumentName));
                    }

                    foreach($pa->getMappings() as $pm) {

                        $parseHint = $pm->getParseHint();

	//echo "Mapping $argumentName : {$pm->getRenamedValue()}\n\n";
                        // if the renamed value is not set, use the original
                        // name
                        // otherwise use the mapped value
                        if(!isEmptyString($pm->getRenamedValue()))
                        	$argumentName = $pm->getRenamedValue();
                        
                        $argumentName = trim($argumentName);
	//echo "Mapping $argumentName : {$pm->getRenamedValue()}\n\n";
                        
                        // Skip empty properties
                        // FIXME does that even happen?
                        if(strlen($argumentName) < 1)
                            continue;
//echo "TN = $templateName, AN = $argumentName\n";
                        $childBreadcrumb = $breadCrumb->createClone();
                        $childBreadcrumb->push(
                            new BreadcrumbNode(
                                $templateName, $templateIndex, $argumentName));

                        //$templateChildName = $this->breadcrumbToSubject($childBreadcrumb);
                        $templateChildName =
                            $this->breadCrumbTransformer->transform($childBreadcrumb);

                        // If there is no parse hint we might be able to derive it
                        if(!isset($parseHint))
                            $parseHint =
                                $this->deriveParseHintFromName($argumentName);

                        // Attempt to obtain a triple generator
                        $tripleGenerator =
                            $this->getTripleGenerator($parseHint);

                        // If we DONT have a triple generator
                        // we fall through to default handling
                        $localResult = array(array(), array(), array());
                        if(isset($tripleGenerator)) {

                            foreach($values as $valueIndex => $value) {
                                //echo "GOT VALUE $value\n";
                                $value = trim($value);
                                // Skip empty values
                                if($value == "") {
                                    continue;
                                }

                                //echo "PROCESSING $templateChildName - $argumentName $value\n";
                                    
                                $tmp = $tripleGenerator->generate(
                                    $templateChildName,
                                    $argumentName,
                                    $value);

                                $localResult[0] = array_merge($localResult[0], $tmp);  
                                      
                                //echo "LOCALRESULT\n";
                                //print_r($localResult[0]);
//print_r($triples);
//echo "\nSigh\n";
                                //if(isset($triples))
                                //    $result = array_merge($result, $triples);
                            }
                                    // append the generated triples
                            //continue;
                        }
                        else {
                            // No parse hint - default handling
                            // if property date and object an timespan
                            // we extract it with following special case
                            $argumentName = propertyToCamelCase($argumentName);
                            $argumentName = encodeLocalName($argumentName);

                            if(in_array($argumentName, $GLOBALS['W2RCFG']['ignoreProperties']))
                                continue;

                            // turn the argument name into a property name
                            $propertyName = $propertyNs . $argumentName;

                            foreach($values as $valueIndex => $value) {
                                $value = trim($value);

                                // Skip empty values
                                if($value == "")
                                    continue;

                                if ($argumentName == "date") {
                                    $value = str_replace("[", "", $value);
                                    $value = str_replace("]", "", $value);
                                    $value = str_replace("&ndash;","-", $value);
                                }

                                // Parse out sub templates
                                // if something was extracted:
                                // .) connect subject with subsubject
                                // .) indicate usage at wikipage
                                $subResources = $this->myGenerate(
                                    $childBreadcrumb,
                                    $value);

                                for($i = 0; $i < 3; ++$i) {
                                	$localResult[$i] = array_merge($localResult[$i], $subResources[$i]);
                                }
 
                                //$result = array_merge($result, $triples);
        //echo "GOT OBJECT $value\n";

                                $localResult[0] = array_merge(
                                    $localResult[0],
                                    parseAttributeValueWrapper(
                                        $value,
                                        $templateChildName,
                                        $propertyName,
                                        $this->language));

                                //$result = array_merge($result, $triples);
                            }
                        }

                            // For each triple add the ExtractedFromTemplate-Annotation
                            // Exclude triples with wikiPageUsesTemplate as predicate though
                            foreach($localResult[0] as $triple)
                                $triple->addExtractedFromTemplateAnnotation($templateUri);

                            // Add on delete cascade annotation
                            if($breadCrumb->getDepth() > 1) {
                                foreach($localResult[0] as $triple)
                                    $triple->addOnDeleteCascadeAnnotation($rootSubjectUri);
                            }

                            // merge the results
                            //for($i = 0; $i < 3; ++$i)
                            //    $result[$i] = array_merge($result[$i], $localResult[$i]);
                        //}
                    	
                        for($i = 0; $i < 3; ++$i)
	                    	$result[$i] = array_merge($result[$i], $localResult[$i]);
                    }
                }


                /*
                 How to connect a sub-subject to the root subject?
                if($breadCrumb->getDepth() == 0)
                    continue;

                // Create the parent-child connection
                $parentChildTriple = new RDFtriple(
                    $parentResource,
                    RDFtriple::URI(DB_PROPERTY_NS . encodeLocalName($parentPropertyName), false),
                    RDFtriple::page($templateChildName));

                //$result[1][] = $parentChildTriple;
                 */
            }
        }


        if(count($relatedClasses) > 0) {
            foreach($relatedClasses as $relatedClass => $dummy) {
                $result[1][] = new RDFtriple(
                    $parentResource,
                    RDFtriple::URI(RDF_TYPE, false),
                    RDFtriple::URI(DB_ONTOLOGY_NS . $relatedClass, false));
            }
        }
        else if($breadCrumb->getDepth() == 0) {
            $result[1][] = new RDFtriple(
                $parentResource,
                RDFtriple::URI(RDF_TYPE, false),
                RDFtriple::URI(OWL_THING, false));
        }


        // Add the wiki page uses template triples - but only on depth 0
        if($breadCrumb->getDepth() == 0) {
            foreach($result[2] as $name => $dummy)
                $result[1][] = new RDFTriple(
                    $parentResource,
                    self::$wikiPageUsesTemplateUri,
                    RDFTriple::URI(DB_TEMPLATE_NS . $name, false));
        }

        $n = count($result[0]) + count($result[1]);
        $this->log(TRACE, "Generated a total of $n triples at $breadCrumb");
        foreach($result[0] as $item)
            $this->log(TRACE, $item);

        foreach($result[1] as $item)
            $this->log(TRACE, $item);

        return $result;
    }

}


function isEmptyString($str)
{
	if(isset($str))
		return false;
		
	return strlen(trim($str)) == 0;
}

/**
 * A wrapper for parseAttributeValue
 *
 *
 * @global <type> $parseResult
 * @param <type> $value
 * @param <type> $templateChildName
 * @param <type> $propertyName
 * @param <type> $language
 * @return <type>
 */
function parseAttributeValueWrapper($value, $templateChildName, $propertyName, $language)
{
    $result = array();

    global $parseResult;

    $parseResult = null;
    
    $localResult = parseAttributeValue(
        $value,
        $templateChildName,
        $propertyName,
        $language);

    $items = array();

    // remap local and global results into a uniform schema
    if(isset($parseResult)) {
        foreach($parseResult as $item) {
            list(, , $o, $ot, $dt, $ol) = $item;
            $items[] = array($o, $ot, $dt, $ol);
        }
    }

    $parseResult = null;
    
    if(isset($localResult)) {
        list($o, $ot, $dt, $ol) = $localResult;
        $items[] = array($o, $ot, $dt, $ol);
    }

    foreach($items as $item) {
        $object         = $item[0];
        $objectType     = $item[1];
        $dataType       = $item[2];
        $objectLanguage = $item[3];

        // And another hack... we pass the language to the
        // parse function, and we don't get it back...
        if(!isset($objectLanguage))
            $objectLanguage = $language;

        // special newline handling
        $br = array('<br>','<br/>','<br />');
        if($objectType == 'l')
            $object = str_replace($br, "\n", $object);
        else if($objectType == 'r')
            $object = str_replace($br, '', $object);

        //echo "Got object type '$objectType'\n";
        if($objectType == "r")
            $object = RDFtriple::URI($object);
        else if($objectType == "l")
            $object = RDFtriple::Literal($object, $dataType, $objectLanguage);
        else {
            Logger::warn("Shouldn't happen - found a blank node where none expected - objectType = $objectType");
            continue;
        }

        $result[] = new RDFtriple(
            RDFtriple::page($templateChildName),
            RDFtriple::URI($propertyName),
            $object);
    }


    return $result;
}

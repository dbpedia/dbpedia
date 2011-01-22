<?php

define ("TBLTEMPLATE",  'TemplateAnnotation');
define ("TBLTEMPLATECLASSES",  'TemplateAnnotation_relatedClasses');
define ("TBLPROPERTYANNOTATION",  'PropertyAnnotation');
define ("TBLPROPERTYMAPPING",  'PropertyMapping');

class PropertyMapping
{
    private $renamedValue;
    private $parseHint;

    public function __construct($renamedValue, $parseHint = null)
    {
        $this->renamedValue = $renamedValue;
        $this->parseHint = $parseHint;
    }

    public function getRenamedValue()
    {
        return $this->renamedValue;
    }

    public function setRenamedValue($renamedValue)
    {
       $this->renamedValue = $renameValue;
    }

    public function getParseHint()
    {
        return $this->parseHint;
    }

    public function setParseHint($parseHint)
    {
        $this->parseHint = $parseHint;
    }
}


class PropertyAnnotation
{
    private $name;
    private $isIgnored;

    private $mappings;

    public function __construct()
    {
        $this->mappings = array();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function isIgnored()
    {
        return $this->isIgnored;
    }

    public function getMappings()
    {
        return $this->mappings;
    }

    public function addMapping($mapping)
    {
        $this->mappings[] = $mapping;
    }
}


class TemplateAnnotation
{
    private $name;
    private $relatedClasses;
    private $propertyAnnotations;
    private $isIgnored;

    public function __construct($name, $relatedClasses, $propertyAnnotations, $isIgnored)
    {
        $this->name = $name;
        $this->relatedClasses = isset($relatedClasses) ? $relatedClasses : array();
        $this->propertyAnnotations = $propertyAnnotations;
        $this->isIgnored = $isIgnored;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPropertyAnnotations()
    {
        return $this->propertyAnnotations;
    }

    public function getRelatedClasses()
    {
        return $this->relatedClasses;
    }

    public function isIgnored()
    {
        return $this->isIgnored;
    }
}


class DummyTemplateDb
{
    public function getTemplateAnnotation($templateName)
    {
    	return;
    }
}

/**
 * This class is a repository (isn't it?) for the template db.
 *
 */
class TemplateDb
{
    /**
     * Tablename mappings
     *
     */
    
    //private static $tblProperty             = "TemplateAnnotation";

    private $odbc;

    public function __construct($odbc)
    {
        $this->odbc = $odbc;
    }

    public function getConnection()
    {
        return $this->odbc;
    }


    /**
     * Returns a TemplateAnnotation if there is a matching template
     * otherwise the result is not set.
     *
     */
    public function getTemplateAnnotation($templateName)
    {
        $row = $this->getTemplateId($templateName);
        $templateId = $row[0];
        $isIgnored = $row[1];
        if(!isset($templateId))
            return;

        return
            new TemplateAnnotation(
                $templateName,
                $this->getRelatedClasses($templateId),
                $this->getPropertyAnnotations($templateId),
                $isIgnored
            );
    }


    private function getTemplateId($templateName)
    {
		Timer::start('LiveMappingBasedExtractor::getTemplateId');
        $query = 'Select id, isIgnored 
From '.TBLTEMPLATE.' 
WHERE name = ?';
		
		$this->log(DEBUG, str_replace('?',"'".$templateName."';",$query));
        $stmt = $this->odbc->prepare($query, get_class($this));
	    odbc_execute($stmt, array($templateName));

        $id = null;
        $isIgnored = null;
        if(odbc_fetch_row($stmt)) {
           $id = odbc_result($stmt, "id");
           $isIgnored = odbc_result($stmt, "isIgnored");
        }

		Timer::stop('LiveMappingBasedExtractor::getTemplateId');
        return array($id, $isIgnored);
    }

    private function getRelatedClasses($templateId)
    {
		Timer::start('LiveMappingBasedExtractor::getRelatedClasses');
$query = 'Select name 
From '.TBLTEMPLATECLASSES.' 
WHERE parent_id = ?';
	
		$this->log(DEBUG, str_replace('?',$templateId.";",$query));
        $stmt = $this->odbc->prepare($query, get_class($this));
        odbc_execute($stmt, array($templateId));

        $result = array();

        while(odbc_fetch_row($stmt)) {
           $name         = odbc_result($stmt, "name");

           $result[] = $name;
        }
		Timer::stop('LiveMappingBasedExtractor::getRelatedClasses');
        return $result;
    }

    /**
     * Returns the mappings for the given template
     * The result has the form Map<String, List<PropertyAnnotation>>
     *
     */
    private function getPropertyAnnotations($templateId)
    {
		Timer::start('LiveMappingBasedExtractor::getPropertyAnnotations');
		
        $query =
            "SELECT ".
                "name, renamedValue, parseHint, isIgnored ".
            "FROM ".
                TBLPROPERTYANNOTATION." a Join " .
                TBLPROPERTYMAPPING." b On (a.id = b.parent_id) ".
            "WHERE ".
                "a.parent_id = ?";
		
		$this->log(DEBUG, str_replace('?',$templateId.";",$query));
        $stmt = $this->odbc->prepare($query, get_class($this));
        odbc_execute($stmt, array($templateId));

        $result = array();

        while(odbc_fetch_row($stmt)) {
           $name         = odbc_result($stmt, "name");
           $parseHint    = odbc_result($stmt, "parseHint");
           $renamedValue = odbc_result($stmt, "renamedValue");
           $isIgnored    = odbc_result($stmt, "isIgnored");

           $pa = null;
           if(!array_key_exists($name, $result)) {
               $pa = new PropertyAnnotation($name, $isIgnored);
               $result[$name] = $pa;
           }
           else
                $pa = $result[$name];

           $pm = new PropertyMapping($renamedValue, $parseHint);
           $pa->addMapping($pm);
        }
		Timer::stop('LiveMappingBasedExtractor::getPropertyAnnotations');
		
        return $result;
    }
	
	private function log ($lvl, $message){
			Logger::logComponent("template",get_class($this)."", $lvl ,$message);
		}

}


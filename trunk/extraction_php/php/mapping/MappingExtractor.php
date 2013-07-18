<?php
namespace dbpedia\mapping
{
class MappingExtractor implements Mapping
{
    private $name = "MappingExtractor";

    private $logger;

    protected $templateMappings = array();
    
    protected $tableMappings = array();
    
    private $context;

    private function __construct( $pages, $ontology, $context )
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->context = $context;
        $this->buildMappings($pages, $ontology);
    }

    public static function load( $pages, $ontology, $context )
    {
        $extractor = new MappingExtractor($pages, $ontology, $context);
        return $extractor;
    }

    private function buildMappings($pages, $ontology)
    {
        foreach ($pages as $page)
        {
            $children = $page->getChildren('TemplateNode');
            foreach ($children as $child)
            {
                if ($child->getTitle()->decoded() == TemplateMapping::TEMPLATE_NAME)
                {
                    try
                    {
                        $name = $page->getTitle()->decoded();
                        
                        // TODO: throw exception if there are duplicate mappings for a template
                        if (! isset($this->templateMappings[$name]))
                        {
                            $templateMapping = TemplateMapping::load($child, $ontology, $this->context);
                            $this->templateMappings[$name] = $templateMapping;
                        }
                    }
                    catch(\Exception $e)
                    {
                        $this->logger->warn("Couldn't load template mapping: ".$e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    }
                }
                else if ($child->getTitle()->decoded() == TableMapping::TEMPLATE_NAME)
                {
                    try
                    {
                        $tableMapping = TableMapping::load($child, $ontology, $this->context);
                        $this->tableMappings[] = $tableMapping;
                    }
                    catch(\Exception $e)
                    {
                        $this->logger->warn("Couldn't load table mapping: ".$e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    }
                }
                else if ($child->getTitle()->decoded() == ConditionalMapping::TEMPLATE_NAME)
                {
                    try
                    {
                        $name = $page->getTitle()->decoded();

                        // TODO: throw exception if there are duplicate mappings for a template
                        if (! isset($this->templateMappings[$name]))
                        {
                            $conditionalMapping = ConditionalMapping::load($child, $ontology, $this->context);
                            $this->templateMappings[$name] = $conditionalMapping;
                        }
                    }
                    catch(\Exception $e)
                    {
                        $this->logger->warn("Couldn't load conditional mapping: ".$e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    }
                }
                else
                {
                    $this->logger->warn("Unknown template: ".$child->getTitle());
                }
            }
        }

        $redirects = $this->context->getRedirects()->getRedirects();
        foreach($redirects as $template => $redirects_to)
        {
            $templateLocalName = $template;
            $alreadyVisitedTemplates = array();
            while ((!isset($this->templateMappings[$templateLocalName])) && (isset($redirects_to)))
            {
                if (in_array($templateLocalName, $alreadyVisitedTemplates))
                {
                    break;
                }
                $alreadyVisitedTemplates[] = $templateLocalName;
                $templateLocalName = $redirects_to;
                if (isset($redirects[$templateLocalName]))
                {
                    $redirects_to = $redirects[$templateLocalName];
                }
                else
                {
                    $redirects_to = null;
                }
            }
            if (isset($this->templateMappings[$templateLocalName]))
            {
                $this->templateMappings[$template] = $this->templateMappings[$templateLocalName];
            }
        }
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $children = $node->getChildren();
        foreach ($children as $child)
        {
            if ($child instanceof \dbpedia\wikiparser\TemplateNode)
            {
                $name = $child->getTitle()->decoded();
                
                if(isset($this->templateMappings[$name]))
                {
                    $this->templateMappings[$name]->extract($child, $subjectUri, $pageContext);
                }
            }
            else if($child instanceof \dbpedia\wikiparser\TableNode)
            {
                foreach($this->tableMappings as $tableMapping)
                {
                    $tableMapping->extract($child, $subjectUri, $pageContext);
                }
            }
        }

        return true;
    }

    public function __toString()
    {
        $str = '';
        $str .= "Extractor".PHP_EOL;
        $str .= "---------".PHP_EOL;
        $str .= "Class: '".$this->name."'".PHP_EOL;
        $str .= "Template mappings:".PHP_EOL;
        foreach($this->templateMappings as $mapping_title => $mapping)
        {
            $str .= $mapping_title." =>".PHP_EOL;
            $str .= $mapping.PHP_EOL;
        }
        $str .= "Table mappings:".PHP_EOL;
        foreach($this->tableMappings as $mapping_title => $mapping)
        {
            $str .= $mapping_title." =>".PHP_EOL;
            $str .= $mapping.PHP_EOL;
        }
        return $str;
    }
    
    public function getContext()
    {
    	return $this->context;
    }    
}
}

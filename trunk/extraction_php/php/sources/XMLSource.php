<?php
namespace dbpedia\sources
{

use dbpedia\wikiparser\WikiTitle;

/**
 * Reads Wikipedia pages from the XML dump
 */
class XMLSource
extends Source
{
    private $logger;

    private $processor;
    private $file;

    private $namespaces;

    private $currentTag;
    private $isInsideData;

    private $currentTitle;
    private $currentSource;

    public function __construct($file, $namespaces = array(WikiTitle::NS_MAIN))
    {
        parent::__construct();
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->file = $file;
        $this->namespaces = $namespaces;
    }

    public function processSources($processor)
    {
        $this->processor = $processor;

        $xml_parser = xml_parser_create();
        xml_set_object($xml_parser, $this);
        xml_set_element_handler($xml_parser, 'processStartTag', 'processEndTag');
        xml_set_character_data_handler($xml_parser, 'processContents');

        if (!($fp = fopen($this->file, "r")))
        {
            //TODO exception
            die("could not open XML input");
        }

        while ($data = fread($fp, 65536))
        {
            if (!xml_parse($xml_parser, $data, feof($fp)))
            {
                //TODO throw exception
                die(sprintf("XML error: %s at line %d",
                            xml_error_string(xml_get_error_code($xml_parser)),
                            xml_get_current_line_number($xml_parser)));
            }
        }

        //TODO call fclose?

        xml_parser_free($xml_parser);
    }

    protected function processStartTag($parser, $name)
    {
        $this->currentTag = $name;
        $this->isInsideData = false;
    }

    protected function processEndTag()
    {
        $this->currentTag = '';
        $this->isInsideData = false;

        if($this->currentTitle && $this->currentSource)
        {
            try
            {
                $title = WikiTitle::parse($this->currentTitle);
            }
            catch (\Exception $e)
            {
                $this->logger->warn('Exception parsing title' . $this->currentTitle . PHP_EOL . $e->getTraceAsString());

                $this->currentTitle = null;
                $this->currentSource = null;
                
                return;
            }

            if(in_array($title->nsCode(), $this->namespaces))
            {
                call_user_func($this->processor, $title, $this->currentSource);
            }

            $this->currentTitle = null;
            $this->currentSource = null;
        }
    }

    protected function processContents($parser, $data)
    {
        switch ($this->currentTag)
        {
            case "TITLE":
                if ($this->isInsideData)
                    $this->currentTitle .= $data;
                else
                    $this->currentTitle = $data;
                break;
            case "TEXT":
                if ($this->isInsideData)
                    $this->currentSource .= $data;
                else
                    $this->currentSource = $data;
                break;
        }

        $this->isInsideData = true;
    }
}
}

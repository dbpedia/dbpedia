<?php

// TODO: move this to dbpedia\util or so
namespace dbpedia
{

use dbpedia\wikiparser\WikiTitle;
use dbpedia\wikiparser\LinkNode;
use dbpedia\wikiparser\TextNode;

// TODO: rename to Redirects or so...
class Redirect
{
    private $logger;

    private $redirects = array();

    public function __construct()
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public function addRedirect($title, $source)
    {
        if ($title->nsCode() == WikiTitle::NS_TEMPLATE)
        {
            if (preg_match('/^\s*#redirect\s*:?\s*\[\[/i', $source) === 1)
            {
                //HACK should use regular expression instead
                try
                {
                    $parser = new \dbpedia\wikiparser\WikiParser();
                    $this->addRedirect_old($parser->parse($title, $source));
                }
                catch (\Exception $e)
                {
                    $this->logger->warn('Exception parsing ' . $title . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    return;
                }
            }
        }
    }
    
    private function addRedirect_old($pageNode)
    {
        if ($pageNode->getTitle()->nsCode() == WikiTitle::NS_TEMPLATE)
        {
            $children = $pageNode->getChildren();
            if ((sizeof($children) >= 2) && ($children[0] instanceof TextNode) && ($children[1] instanceof LinkNode) && (!$children[1]->isExternalLink()))
            {
                $text = strtolower(trim($children[0]->getText()));
                if (($text == "#redirect") || ($text == "#redirect:"))
                {
                    $destination = $children[1]->getDestination()->decoded();
                    if ($children[1]->getDestination()->nsCode() == WikiTitle::NS_TEMPLATE)
                    {
                        $this->redirects[$pageNode->getTitle()->decoded()] = $destination;
                    }
                }
            }
        }
    }

    public function getRedirects()
    {
        return $this->redirects;
    }
}

}
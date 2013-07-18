<?php
namespace dbpedia\mapping
{
use dbpedia\util\WikiUtil;
use dbpedia\util\StringUtil;
use dbpedia\wikiparser\TextNode;
use dbpedia\wikiparser\LinkNode;

class PageContext
{
    private $uriGenerator;

    public function __construct()
    {
        $this->uriGenerator = new UriGenerator();
    }

    public function generateUri($baseUri, $node = null)
    {
        return $this->uriGenerator->generate($baseUri, $node);
    }
}

class UriGenerator
{
    private $uris = array();

    public function __construct()
    {
    }

    public function generate($baseUri, $node)
    {
        if (isset($node))
        {
            //Retrieve text
            $text = $this->nodeToText($node);

            //Normalize text
            $text = WikiUtil::removeWikiEmphasis($text);
            $text = StringUtil::htmlDecode($text);
            $text = preg_replace('/ +/', ' ', $text); //remove duplicate spaces
            $text = str_replace('(', ' ', $text);
            $text = str_replace(')', ' ', $text);
            $text = strip_tags($text);
            $text = substr($text, 0, 50);
            $text = trim($text);
            $text = str_replace(' ', '_', $text);
            $text = urlencode($text);

            //Test if the base URI ends with a prefix of text
            $baseLen = strlen($baseUri);
            $textLen = strlen($text);
            for($i = $baseLen - 1; $i > 0 && $baseLen - $i < $textLen; $i--)
            {
                if(substr_compare($baseUri, $text, $i, $textLen, true) === 0)
                {
                    $text = substr($text, $baseLen - $i);
                    break;
                }
            }

            //Remove leading underscore
            if(!empty($text) && $text[0] === '_')
            {
                $text = substr($text, 1);
            }

            //Generate URI
            $uri = $baseUri . '__' . $text;
        }
        else
        {
            $uri = $baseUri;
        }

        //Resolve collisions
        if(!isset($this->uris[$uri]))
        {
            //No collision
            $this->uris[$uri] = 1;
        }
        else
        {
            //Collision found
            $index = $this->uris[$uri];
            $this->uris[$uri] = $index + 1;
            $uri .= '__' . $index;
        }

        return $uri;
    }

    private static function nodeToText($node)
    {
        if($node == null)
        {
            return '';
        }
        else
        {
            return self::nodesToText($node->getChildren());
        }
    }

    private static function nodesToText($nodes)
    {
        $str = '';

        foreach($nodes as $node)
        {
            if ($node instanceof TextNode)
            {
                $str .= $node->getText();
            }
            else if ($node instanceof LinkNode)
            {
                $str .= self::nodesToText($node->getChildren());         
            }
        }

        return $str;
    }
}
}

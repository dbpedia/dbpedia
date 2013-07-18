<?php
/**
 * Provides a parser that builds an Abstract Syntax Tree from WikiText.
 */
namespace dbpedia\wikiparser
{
use dbpedia\util\WikiUtil;

/**
 * Parses WikiText sources and builds an Abstract Syntax Tree.
 */
class WikiParser
{
    private $commentEnd;
    private $refEnd;
    private $mathEnd;

    private $internalLinkLabelOrEnd;
    private $internalLinkEnd;

    private $externalLinkLabelOrEnd;
    private $externalLinkEnd;

    private $linkEnd;

    private $propertyValueOrEnd;
    private $propertyEnd;

    private $tableRowEnd1;
    private $tableRowEnd2;
    
    private $tableCellEnd1;
    private $tableCellEnd2;
    private $tableCellEnd3;

    private $sectionEnd;

    public function __construct()
    {
        $this->commentEnd = new Matcher(array('-->'));
        $this->refEnd = new Matcher(array('/>', '</ref>'));
        $this->mathEnd = new Matcher(array('/>', '</math>'));
        
        $this->internalLinkLabelOrEnd = new Matcher(array("|", "]]", "\n"));
        $this->internalLinkEnd = new Matcher(array("]]", "\n"), true);

        $this->externalLinkLabelOrEnd = new Matcher(array(" ", "]", "\n"));
        $this->externalLinkEnd = new Matcher(array("]", "\n"), true);

        $this->linkEnd = new Matcher(array(" ", "{","}", "[", "]", "|", "=", "\n"));

        $this->propertyValueOrEnd = new Matcher(array('=', '|', '}}'), true);
        $this->propertyEnd = new Matcher(array('|', '}}'), true);

        $this->tableRowEnd1 = new Matcher(array('|}', '|+', '|-', '|', '!'));
        $this->tableRowEnd2 = new Matcher(array('|}', '|-', '|', '!'));

        $this->tableCellEnd1 = new Matcher(array("\n ", "\n|}", "\n|-", "\n|", "\n!", '||', '!!', '|', '!'), true);
        $this->tableCellEnd2 = new Matcher(array('|}', '|-', '|', '!'));
        $this->tableCellEnd3 = new Matcher(array("\n ", "\n|}", "\n|-", "\n|", "\n!", '||', '!!'), true);

        $this->sectionEnd = new Matcher(array("=\n", "\n"));
    }

    /**
     * Parses WikiText source and builds an Abstract Syntax Tree.
     *
     * @param $title The title of the page.
     * @param $sourceText The source.
     * @return The PageNode which represents the root of the AST
     * @throws WikiParserException if an error occured during parsing
     */
    public function parse(WikiTitle $title, &$sourceText)
    {
        $source = new WikiSource($sourceText);

        $root = new PageNode($title);

        $this->parseUntil(new Matcher(array(), true), $root, $source);

        return $root;
    }

    private function parseUntil($matcher, $node, $source)
    {
        $lastPos = $source->getPos();
        $lastLine = $source->getLine();

        while(true)
        {
            $matchResult = $source->find($matcher);

            //Add text node
            $text = $source->getString($lastPos);
            $text = substr($text, 0, strlen($text) - $matcher->getTagLength());
            if(strlen($text) > 0)
            {
                //If possible, append new text to an existing text node
                $children = $node->getChildren();
                if(count($children) > 0 && $children[count($children) - 1] instanceof TextNode)
                {
                    $lastChild = $children[count($children) - 1];
                    $lastChild->setText($lastChild->getText().$text);
                }
                else
                {
                    $node->addChild(new TextNode($text, $lastLine));
                }
            }

            //Check result of seek
            if($matchResult === false)
            {
                if($node instanceof PageNode)
                {
                    return false;
                }
                else
                {
                    throw new WikiParserException(get_class($node).' not closed.', $node->getLine(), $source->findLine($node->getLine()));
                }
            }
            else if(!$matcher->isStdTag())
            {
                //Trim text
                $children = $node->getChildren();
                if (count($children) > 0)
                {
                    if($children[0] instanceof TextNode)
                    {
                        $children[0]->setText(ltrim($children[0]->getText()));
                    }
                    if($children[count($children)-1] instanceof TextNode)
                    {
                        $children[count($children)-1]->setText(rtrim($children[count($children)-1]->getText()));
                    }
                }

                return $matchResult;
            }
            else
            {
                if($source->lastTag('<!--'))
                {
                    //Skip html comment
                    $source->find($this->commentEnd);
                }
                else if($source->lastTag('<ref'))
                {
                    //Skip reference
                    $source->find($this->refEnd);
                }
                else if($source->lastTag('<math'))
                {
                    //Skip math tag
                    $source->find($this->mathEnd);
                }
                else
                {
                    //Add new node
                    $newNode = $this->createNode($source);
                    if($newNode)
                    {
                        $node->addChild($newNode);
                    }
                }
            }

            $lastPos = $source->getPos();
            $lastLine = $source->getLine();
        }
    }

    private function createNode($source)
    {
        if($source->lastTag('[') || $source->lastTag('http'))
        {
            $linkNode = new LinkNode($source->getLine());
            $this->parseLink($linkNode, $source);
            return $linkNode;
        }
        else if($source->lastTag('{{'))
        {
            $template = new TemplateNode($source->getLine());
            $this->parseTemplate($template, $source);
            return $template;
        }
        else if($source->lastTag('{|'))
        {
            $tableNode = new TableNode($source->getLine());
            $this->parseTable($tableNode, $source);
            return $tableNode;
        }
        else if($source->lastTag("\n="))
        {
            $startPos = $source->getPos();
            $startLine = $source->getLine();

            $sectionNode = $this->parseSection($source);

            if($sectionNode !== null)
            {
                return $sectionNode;
            }
            else
            {
                $source->setPos($startPos - 1);
                $source->setLine($startLine - 1);
            }
        }
        else
            throw new WikiParserException('Unknown element type', $source->getLine(), $source->findLine($source->getLine()));
    }

    private function parseLink($linkNode, $source)
    {
        $startPos = $source->getPos();
        if($source->lastTag("[["))
        {
            $source->find($this->internalLinkLabelOrEnd);

            //Set destination
            $destination = $source->getString($startPos, $source->getPos() - $this->internalLinkLabelOrEnd->getTagLength());
            $linkNode->setDestination(trim($destination), false);

            //Parse label
            if($source->lastTag("|"))
            {
                $this->parseUntil($this->internalLinkEnd, $linkNode, $source);
            }
            else
            {
                //No label found => Use destination as label
                $linkNode->addChild(new TextNode($destination, $source->getLine()));
            }
        }
        else if($source->lastTag("["))
        {
            $tag = $source->find($this->externalLinkLabelOrEnd);

            //Set destination
            $destination = $source->getString($startPos, $source->getPos() - 1);
            $linkNode->setDestination(trim($destination), true);

            //Parse label
            if($source->lastTag(" "))
            {
                $this->parseUntil($this->externalLinkEnd, $linkNode, $source);
            }
            else
            {
                //No label found => Use destination as label
                $linkNode->addChild(new TextNode($destination, $source->getLine()));
            }
        }
        else
        {
            $source->find($this->linkEnd);

            //Set destination
            $destination = $source->getString($startPos, $source->getPos() - 1);
            $linkNode->setDestination(trim($destination), true);
            //Use destination as label
            $linkNode->addChild(new TextNode($destination, $source->getLine()));
        }
    }

    private function parseTemplate($templateNode, $source)
    {
        while(true)
        {
            $propertyNode = new PropertyNode($source->getLine());
            $this->parseProperty($propertyNode, $source);

            //The first entry denotes the name of the template
            if($templateNode->getTitle() == null)
            {
                $templateName = $propertyNode->getText();
                if(!$templateName)
                {
                    throw new WikiParserException("Template name contains invalid elements", $templateNode->getLine(), $source->findLine($templateNode->getLine()));
                }

                $templateNode->setTitle(new WikiTitle(WikiTitle::NS_TEMPLATE, WikiUtil::wikiDecode(trim($templateName))));
            }
            else
            {
                $templateNode->addProperty($propertyNode);
            }

            //Reached template end?
            if($source->lastTag('}}'))
            {
                return $templateNode;
            }
        }
    }

    private function parseProperty($propertyNode, $source)
    {
        $this->parseUntil($this->propertyValueOrEnd, $propertyNode, $source);
        if($source->lastTag("="))
        {
            //The currently parsed node is a key
            $propertyName = $propertyNode->getText();
            if(!$propertyName)
            {
                throw new WikiParserException("Template property key contains invalid elements", $propertyNode->getLine(), $source->findLine($propertyNode->getLine()));
            }
            $key = trim($propertyName);

            $propertyNode->removeChildren();
            $propertyNode->setKey($key);

            //Parse the corresponding value
            $this->parseUntil($this->propertyEnd, $propertyNode, $source);
        }
    }

    private function parseTable($tableNode, $source)
    {
        $startPos = $source->getPos();

        //Parse rows
        while(true)
        {
            //Find first row
            $source->find($this->tableRowEnd1);
            $tag = $this->tableRowEnd1->getTagIndex();

            if($tag === 0)
            {
                //Reached table end
                break;
            }
            else if($tag === 1)
            {
                //Found caption
                $caption = $source->getString($startPos, $source->getPos() - 2);
                $tableNode->setCaption(trim($caption));
                continue;
            }
            else if($tag === 2)
            {
                //Move to first cell
                $tag = $source->find($this->tableRowEnd2);
                $tag = $this->tableRowEnd2->getTagIndex();

                if($tag === 0 || $tag === 1)
                {
                    //Empty row
                    $tableNode->addChild(new Node($source->getLine()));
                    return;
                }
            }

            //Parse row
            $rowNode = new Node($source->getLine());
            $tableNode->addChild($rowNode);
            $this->parseTableRow($rowNode, $source);

            //Reached table end?
            if($source->lastTag('|}'))
            {
                break;
            }
        }
    }

    private function parseTableRow($rowNode, $source)
    {
        while(true)
        {
            //Parse table cell
            $cellNode = new Node($source->getLine());
            $rowNode->addChild($cellNode);
            $this->parseTableCell($cellNode, $source);

            //Reached row end?
            if($source->lastTag('|}') || $source->lastTag('|-'))
            {
                break;
            }
        }
    }

    private function parseTableCell($cellNode, $source)
    {
        $cellNode->setAnnotation('rowspan', 1);
        $cellNode->setAnnotation('colspan', 1);

        $this->parseUntil($this->tableCellEnd1, $cellNode, $source);
        $tag = $this->tableCellEnd1->getTagIndex();

        if($tag === 0)
        {
            $source->find($this->tableCellEnd2);
        }
        else if(($tag === 7 || $tag === 8) && count($cellNode->getChildren()) > 0)
        {
            //This cell contains formatting parameters
            $formattingStr = $cellNode->getText();
            if(!$formattingStr)
            {
                throw new WikiParserException('Invalid table cell formatting', $cellNode->getLine(), $source->findLine($cellNode->getLine()));
            }

            $cellNode->removeChildren();
            $cellNode->setAnnotation('rowspan', $this->parseTableParam('rowspan', $formattingStr));
            $cellNode->setAnnotation('colspan', $this->parseTableParam('colspan', $formattingStr));

            //Parse the cell contents
            $this->parseUntil($this->tableCellEnd3, $cellNode, $source);
            if($this->tableCellEnd3->getTagIndex() === 0)
            {
                $source->find($this->tableCellEnd2);
            }
        }
    }

    private function parseTableParam($name, $str)
    {
        //Find start index of the value
        $start = strpos($str, $name);
        if($start === false)
        {
            return 1;
        }
        $start = strpos($str, '=', $start);
        if($start === false)
        {
            return 1;
        }
        $start += 1;

        //Find end index of the value
        $end = strpos($str, " ", $start);
        if($end === false)
        {
            $end = strlen($str) - 1;
        }

        //Convert to integer
        $valueStr = substr($str, $start, $end - $start + 1);
        $valueStr = str_replace("\"", '', $valueStr);
        $valueStr = trim($valueStr);

        $value = intval($valueStr);
        if($value == 0)
        {
            return 1;
        }

        return $value;
    }

    private function parseSection($source)
    {
        $sectionNode = new SectionNode($source->getLine());

        //Determine level
        $level = 1;
        while($source->nextTag('='))
        {
            $level++;
            $source->seek(1);
        }
        $sectionNode->setLevel($level);

        //Get name
        $startPos = $source->getPos();
        $sectionNameNode = new Node($source->getLine());
        $this->parseUntil($this->sectionEnd, $sectionNameNode, $source);
        $sectionTextNodes = $sectionNameNode->getChildren('TextNode');
        if(count($sectionTextNodes) < 1)
        {
            throw new WikiParserException('Invalid section name', $sectionNode->getLine(), $source->findLine($sectionNode->getLine()));
        }

        if($this->sectionEnd->getTagIndex() === 0)
        {
            $name = $sectionTextNodes[0]->getText();
            $name = preg_replace('<ref(.*?)ref>', '', $name); //remove ref tags
            $name = substr($name, 0, strlen($name) - 1); //Remove trailing =
            $sectionNode->setName(trim($name));
            return $sectionNode;
        }
        else
        {
            return null;
        }
    }
}
}

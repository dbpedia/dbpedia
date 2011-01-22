<?php
class SmartLinkTripleGenerator
    implements ITripleGenerator
{
    private $parser;
    private $basePath;
    private $mediaWikiUtil;

    public function __construct($basePath)
    {
        $this->parser = new SmartLinkValueParser();
        $this->basePath = $basePath;

        $this->mediaWikiUtil = MediaWikiUtil::getInstance("http://en.wikipedia.org/wiki/");
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();
        $links = $this->parser->parse($value);

        foreach($links as $link) {
            $link = $this->mediaWikiUtil->toCanonicalWikiCase($link);
            $link = encodeLocalName($link);
            $resource = $this->basePath . $link;

            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS.$propertyName),
                RDFtriple::URI($resource));
        }

        return $result;
    }
}

<?php
namespace dbpedia\mapping
{
class ExtractionManager implements Mapping
{
    protected $extractors = array();

    public function __construct() {

    }

    public function addExtractor($extractor)
    {
        if ($extractor) {
            $this->extractors[] = $extractor;
        }
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $result = false;

        foreach($this->extractors as $extractor)
        {
            $result |= $extractor->extract($node, $subjectUri, $pageContext);
        }

        return $result;
    }

    public function __toString()
    {
        $str = '';
        foreach($this->extractors as $extractor)
        {
            $str .= $extractor.PHP_EOL;
        }
        return $str;
    }
}
}

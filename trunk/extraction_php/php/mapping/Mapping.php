<?php
namespace dbpedia\mapping
{

// TODO: rename this class to Extractor.
interface Mapping
{
    public function extract($node, $subjectUri, $pageContext);
}
}

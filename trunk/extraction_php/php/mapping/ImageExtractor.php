<?php
namespace dbpedia\mapping
{
class ImageExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext) 
    {
        return true;
    }
}
}

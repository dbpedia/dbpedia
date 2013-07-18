<?php
namespace dbpedia\mapping
{
class DisambiguationExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

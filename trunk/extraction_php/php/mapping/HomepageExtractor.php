<?php
namespace dbpedia\mapping
{
class HomepageExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

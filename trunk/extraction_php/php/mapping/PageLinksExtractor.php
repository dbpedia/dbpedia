<?php
namespace dbpedia\mapping
{
class PageLinksExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

<?php
namespace dbpedia\mapping
{
class ExternalLinksExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

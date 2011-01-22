<?php
namespace dbpedia\mapping
{
class RedirectExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

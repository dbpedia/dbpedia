<?php
namespace dbpedia\mapping
{
class ArticleCategoriesExtractor implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}
}

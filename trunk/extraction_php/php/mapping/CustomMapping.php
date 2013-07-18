<?php

namespace dbpedia\mapping
{

// TODO: remove this class. It serves no purpose.
class CustomMapping extends PropertyMapping implements Mapping
{
    public function extract($node, $subjectUri, $pageContext)
    {
        return true;
    }
}

}
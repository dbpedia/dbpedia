<?php

class TemplateFilter
{

    public function doesAccept($name)
    {
        return !$this->doesIgnore($name);
    }

    private function doesIgnore($name)
    {
        $mediaWikiUtil = MediaWikiUtil::getInstance("http://en.wikipedia.org/wiki/");
        $name = $mediaWikiUtil->toCanonicalWikiCase($name);

        // TODO add patterns to a some config file
        if(fnmatch("*Citation*", $name))
            return true;

        return false;
    }
}


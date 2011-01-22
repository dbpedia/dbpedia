<?php
class LegacyTemplateFilter
    implements IFilter
{
    public function doesAccept($name)
    {
        return !$this->isIgnoredTemplate($name);
    }

    private function isIgnoredTemplate($name)
    {
        $name = strtolower(trim($name));

        if(strlen($name) < 1)
            return true;

        $isGloballyIgnored =
            in_array($name, $GLOBALS['W2RCFG']['ignoreTemplates']);

        // names starting with # are parser functions.
        // (although not all parser functions start with #)
        if($name[0] == '#' || $isGloballyIgnored)
            return true;

        foreach($GLOBALS['W2RCFG']['ignoreTemplatesPattern'] as $pattern)
            if(fnmatch($pattern, $name))
                return true;

        return false;
    }
}
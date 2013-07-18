<?php


/**
 * Parses out all smartlinks within the given value
 *
 */
class SmartLinkValueParser
    implements IValueParser
{
    public function parse($value)
    {
        return self::_parse($value);
    }

    /**
     * TODO Move this function into some Util namespace
     * @param <type> $value
     *
     */
    public static function _parse($value)
    {
        $result = array();
	preg_match_all("/(\[{2})([^\]]+)(\]{2})/", $value, $matches);

        foreach($matches[0] as $match) {
            // remove [[ ]] from the match
            $match = substr($match, 2, -2);

            // The link name is the part before the first '|'
            $parts = explode("|", $match, 1);
            // Fixme: Is this safety check neccessairy?
            //if(sizeof($parts) == 0)
            //    continue;

            $link = $parts[0];
            $link = trim($link);

            if($link == "")
                continue;

            $result[] = $link;
        }

        return $result;
    }
}

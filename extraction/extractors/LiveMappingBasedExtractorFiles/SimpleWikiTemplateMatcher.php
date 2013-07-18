<?php
class SimpleWikiTemplateMatcher
{
    /**
     * Searches and returns all wiki templates in the given text
     *
     * This function returns:
     * Map<String (name), List<TemplateObject>>
     *
     * So templates are already grouped by name
     */
    public static function match($text)
    {
        $result = array();


        // Remove Prettytables
    	$text = preg_replace('~{\|.*\|}~s','',$text);

        // Find all tempaltes (note: this is a recursive regex)
        // FIXME: broken - curly braces are allowed in template values. Example:
        // {{Foobox|motto = I am { curly } }} is rendered with the string value 'I am { curly }'
        preg_match_all('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x', $text, $templates);

        // Process all found templates
        foreach($templates[0] as $item) {
            $current = new TemplateInvocation();

            // Template arguments without a key
            $numAnonymousKeys = 0;

            // Remove preceeding {{ and trailing }}
            $item = substr($item, 2, -2);

            // Remove tags (Fixme: this should not be done in this function?)
            $item = preg_replace('/<\!--[^>]*->/mU', '' , $item);


            // Extract the name
        	$itemName = trim(substr($item, 0, strpos($item, '|')));
            $current->setName($itemName);


            // TODO: add early out on certain names?


            // Repair missing </sup>
            $item = preg_replace('~</sup[^>]~', '</sup>', $item);

            // Remove <ref></ref> tags including content
            $item = preg_replace('/(<ref>.+?<\/ref>)/s', '', $item);

            // Removes forbidden tags (i guess)
            // Known Issue: Might merge words - a<br />b becomes ab
            $item = strip_tags($item ,$GLOBALS['W2RCFG']['allowedtags']);

        	// Replace "|" inside subtemplates with "\\\\" to avoid false splits
            $item = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/",
                'replaceBarInSubtemplate', $item);


            // Gruppe=[[Gruppe-3-Element|3]]  ersetzt durch Gruppe=[[Gruppe-3-Element***3]]
            do {
                $item = preg_replace(
                    '/\[\[([^\]]+)\|([^\]]*)\]\]/', '[[\1***\2]]',
                    $item, -1, $count);
            } while($count);

        	$arguments = explode('|', $item);

            // Remove the first entry which is the template name
            array_shift($arguments);

            foreach($arguments as $argument)
            {
                // Undo recently made replacements
                $argument = str_replace("####", "|", $argument);
                $argument = str_replace("***", "|", $argument);

                $keyValue = explode("=", $argument, 2);

                // Decide depending on wheter there was a =
                switch(count($keyValue)) {
                    case 1: // Generate a new anonymous key
                        $key = (string)++$numAnonymousKeys;
                        $value = $keyValue[0];
                        break;
                    case 2: // key and value are given
                        $key = trim($keyValue[0]);
                        $value = $keyValue[1];
                        break;
                }

                $current->putArgument($key, $value);
            }

            putMultiMap($result, $current->getName(), $current);
        }

        return $result;
    }
}

<?php

/******************************************************************************
 * Template parser code.
 * Don't ask me how good it is (basically refactored existing code).
 *****************************************************************************/
class TemplateInvocation
    //implements IWikiNode
{
    private $name; // String

    // Map<String (key), List<String (value)>>
    // Since the same key may be falsely used multiple times, the value is a
    // list
    // so {{MyTemplate | key = v1 | key = v2 }} becomes
    // key -> array(v1, v2).
    // If a key exists, it is guaranteed that there is at least 1 value
    private $arguments = array();

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    public function putArgument($key, $value)
    {
        putMultiMap($this->arguments, $key, $value);
    }
}

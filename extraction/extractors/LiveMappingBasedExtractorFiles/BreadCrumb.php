<?php


class Breadcrumb
{
    private $root;
    private $nodes;

    public function __construct($root)
    {
        $this->root = $root;
        $this->nodes = array();
    }

    public function createClone()
    {
        return clone $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function push($node)
    {
        array_push($this->nodes, $node);
    }

    public function pop($node)
    {
        array_pop($this->nodes);
    }

    public function peekTop($index)
    {
        $i = sizeof($this->nodes) - 1 - $index;
        if($i < 0 || $i > sizeof($this->nodes))
            return null;

        return $this->nodes[$i];
    }

    public function peekBottom($index)
    {
        if($index < 0 || $index >= sizeof($this->nodes))
            return null;

        return $this->nodes[$index];
    }

    public function getDepth()
    {
        return sizeof($this->nodes);
    }

    public function __toString()
    {
        return $this->root . ":" . implode("/", $this->nodes);
    }
}
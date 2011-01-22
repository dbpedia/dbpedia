<?php
class BreadcrumbNode
{
    private $templateName;
    private $templateIndex;
    private $propertyName;

    public function __construct($templateName, $templateIndex, $propertyName)
    {
        $this->templateName = $templateName;
        $this->templateIndex = $templateIndex;
        $this->propertyName = $propertyName;
    }

    public function getTemplateName()
    {
        return $this->templateName;
    }

    public function getTemplateIndex()
    {
        return $this->templateIndex;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function __toString()
    {
        return "{$this->templateName}/{$this->templateIndex}/{$this->propertyName}";
    }
}
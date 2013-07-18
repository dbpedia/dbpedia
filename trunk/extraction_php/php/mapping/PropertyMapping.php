<?php
namespace dbpedia\mapping
{
abstract class PropertyMapping implements Mapping
{
    public static function load($node, $ontology, $context)
    {
        switch($node->getTitle()->decoded())
        {
        case SimplePropertyMapping::TEMPLATE_NAME:
            return SimplePropertyMapping::load($node, $ontology, $context);
        case IntermediateNodeMapping::TEMPLATE_NAME:
            return IntermediateNodeMapping::load($node, $ontology, $context);
        case GeocoordinatesMapping::TEMPLATE_NAME:
            return GeocoordinatesMapping::load($node, $ontology, $context);
        case CombineDateMapping::TEMPLATE_NAME:
            return CombineDateMapping::load($node, $ontology, $context);
        case DateIntervalMapping::TEMPLATE_NAME:
            return DateIntervalMapping::load($node, $ontology, $context);
        case CalculateMapping::TEMPLATE_NAME:
            return CalculateMapping::load($node, $ontology, $context);
        default:
            throw new \Exception("Unknown Property template: ".$node->getTitle());
        }
    }
}
}

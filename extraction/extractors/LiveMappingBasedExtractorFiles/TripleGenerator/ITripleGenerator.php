<?php

/**
 * Currently, the result-type must be List<RDFtriple>.
 *
 * So the result is no ExtractionResult (in case you were expecting this)
 *
 */
interface ITripleGenerator
{
    function generate($subjectName, $propertyName, $value);
}
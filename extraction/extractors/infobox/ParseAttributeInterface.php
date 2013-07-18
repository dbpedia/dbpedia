<?php

/**
 * Interface for helper functions for datatype recognition.
 * These are called by parseAttributeValue in extractTemplates.php
 *
 */

interface ParseAttributeInterface {

	public function parseValue($object, $subject, $predicate, &$extractor, $language=NULL);

	public function parseSubTemplate($predicate, $object);

}

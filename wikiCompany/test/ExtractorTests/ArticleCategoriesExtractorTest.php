<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the ArticleCategoriesExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class ArticleCategoriesExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new ArticleCategoriesExtractor();
 	}

	function testCategories() {
		$input = "[[Category:DummyCategory]]";
		$output = '<http://dbpedia.org/resource/testResource> <http://www.w3.org/2004/02/skos/core#subject> <http://dbpedia.org/resource/Category:DummyCategory> .';
		$input = $this->extractPage($input);
		$this->assertEqual( trim($input),trim($output) );
	}
	
 }

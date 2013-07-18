<?php
/**
 * Unit Test for the ArticleCategoriesExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class ArticleCategoriesExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new ArticleCategoriesExtractor();
	}

	function testCategories() {
		$output = $this->extractPage("[[Category:DummyCategory]]");
		$expected = '<http://dbpedia.org/resource/TestResource> <http://www.w3.org/2004/02/skos/core#subject> <http://dbpedia.org/resource/Category:DummyCategory> .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual( trim($output),trim($expected) );
	}

}

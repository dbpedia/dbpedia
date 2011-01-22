<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the HomepageExtractor.
 * Add a seperate test for English('en'), French('fr'),German('de')
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class HomepageExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new HomepageExtractor();
 	}

	function testGermanLinks() {
		if ($this->language == "de") {
			$input = '== Weblinks ==
			* [http://www.muenchen.de Offizielle Website der Landeshauptstadt München]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.muenchen.de> .';
			$this->assertEqual(trim($output), trim($expected));		
			
			$input = '== Weblinks ==
			* [http://www.muenchen.de Offizielle Webpräsenz der Landeshauptstadt München]
			* [http://www.muenchen.de Offizielle official homepage der Landeshauptstadt München]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.muenchen.de> .';
			$this->assertEqual(trim($output), trim($expected));	
		}	
	}
	
	function testEnglishLinks() {
		if ($this->language == "en") {
			$input = '==External links==
			*[http://www.domain.com/index.html Official Website]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.domain.com/index.html> .';
			$this->assertEqual(trim($output), trim($expected));		
			
			$input = '== External links ==
			* [http://www.domain.com official website Domain]
			* [http://www.domain.com/abc website test Resource]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.domain.com> .';
			$this->assertEqual(trim($output), trim($expected));	
		}	
	}
	
		function testFrenchLinks() {
		if ($this->language == "fr") {
			$input = '== Lien externe ==
			* [http://www.domain.fr/ site web officiel]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.domain.fr/> .';
			$this->assertEqual(trim($output), trim($expected));		
			
			$input = '== Liens externes ==
			* [http://www.domain.fr/index.html site officiel]
			* [http://www.domain.com/abc website test Reosurce]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.domain.fr/index.html> .';
			$this->assertEqual(trim($output), trim($expected));	
			
			$input = '== Liens et documents externes ==
			* [http://www.domain.fr/ homepage officiel]
			* [http://www.domain.fr/abc website test Reosurce]';
			$output = $this->extractPage($input);
			$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/homepage> <http://www.domain.fr/> .';
			$this->assertEqual(trim($output), trim($expected));	
		}	
	}
	
 }

<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the PersondataExtractor
 * 
 * ToDo: Validate Persondata Extractor manually (wrong Language Template, incomplete Template, missing properties)
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class PersondataExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new PersondataExtractor();
 	}
	
	function testGerman() {
			if ($this->language == 'de') {
				$input = '{{Personendaten
							|NAME=Muster, Max
							|ALTERNATIVNAMEN=Moritz
							|KURZBESCHREIBUNG=Ausgelutschter Name
							|GEBURTSDATUM=22. November 1967
							|GEBURTSORT=Eine Stadt
							|STERBEDATUM= 11.11.[[2011]]
							|STERBEORT=[[Koeln]]
							}}';
				$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/name> "Muster, Max"@de .
<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/givenname> "Max"@de .
<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/surname> "Muster"@de .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/birth> "1967-11-22"^^<http://www.w3.org/2001/XMLSchema#date> .
<http://dbpedia.org/resource/testResource> <http://purl.org/dc/elements/1.1/description> "Ausgelutschter Name"@de .
<http://dbpedia.org/resource/testResource> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .';
				$output = $this->extractPage($input);	
				$this->assertEqual(trim($output), trim($expected));
 			}
	}
	
	function testEnglish() {
			if ($this->language == 'en') {
				$input = '{{Persondata
							|NAME= Sample, Sam
							|ALTERNATIVE NAMES= Sample, Sam
							|SHORT DESCRIPTION= Sergej
							|DATE OF BIRTH=[[22 November]], [[1967]]
							|PLACE OF BIRTH=[[Sample City]], outside the U.S.
							|DATE OF DEATH=November 11, 2011
							|PLACE OF DEATH=Cologne [[Germany]]
							}}';
				$expected = '<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/name> "Sample, Sam"@de .
<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/givenname> "Sam"@de .
<http://dbpedia.org/resource/testResource> <http://xmlns.com/foaf/0.1/surname> "Sample"@de .
<http://dbpedia.org/resource/testResource> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .';
				$output  = $this->extractPage($input);
				$this->assertEqual(trim($output), trim($expected));
			}
	}
}
 

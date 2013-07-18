package filter;

import java.io.File;
import java.util.HashMap;
import java.util.Map;

import junit.framework.Assert;
import oaiReader.AbstractExtraction;
import oaiReader.MediawikiHelper;
import oaiReader.MediawikiTitle;
import oaiReader.MetaDbpediaMetadataFilter;
import oaiReader.RecordMetadata;

import org.ini4j.Ini;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

public class MetaDbpediaMetadataFilterTest
{

	@Test
	public void testDoesAccept()
		throws Exception
	{
		Ini ini = new Ini(new File("config/meta/config.ini"));
		AbstractExtraction.initOai(ini);
		
		
		Map<String, Boolean> testPairs = new HashMap<String, Boolean>();
		
		testPairs.put("User talk:76.115.186.142", false);
		testPairs.put("User talk:DBpedia", false);
		testPairs.put("User talk:DBpediaa", false);
		testPairs.put("User talk:DBpedia/sandbox", false);
		testPairs.put("User talk:DBpedia/any", false);
		testPairs.put("User talk:DBpedia/subpage/shall/pass", false);

		testPairs.put("User:DBpedia", false);
		testPairs.put("User:DBpediaa", false);
		testPairs.put("User:DBpedia/sandbox", false);
		testPairs.put("User:DBpedia/any", false);
		testPairs.put("User:DBpedia/subpage/shall/pass", false);
		
		testPairs.put("Mission:Impossible", false);
		testPairs.put("SomeArticle", false);
		testPairs.put("SomeArticle/SomeSubPage", false);

		testPairs.put("User:DBpedia-Bot", false);
		testPairs.put("User:DBpedia-Bot", false);
		testPairs.put("User:DBpedia-Bot/sandbox", false);
		testPairs.put("User:DBpedia-Bot/any", false);
		testPairs.put("User:DBpedia-Bot/subpage/shall/pass", false);
		
		testPairs.put("User:DBpedia-Bot/ontology", false);
		testPairs.put("User:DBpedia-Bot/ontology/Actor", true);
		testPairs.put("User:DBpedia-Bot/ontology/Actor/subpage", true);
		
		for(Map.Entry<String, Boolean> entry : testPairs.entrySet()) {
			//Map<
			MediawikiTitle title =
				MediawikiHelper.parseTitle("http://en.wikipedia.org/wiki/Special:OAIRepository", entry.getKey());
			
			System.out.println("Title =  " + entry.getKey() + " - " +  "Namespace = " + title.getNamespaceId());
			
			RecordMetadata metadata = new RecordMetadata("en", title, "oai:12345", IRI.create("http://wiki.org"),  "12345", "x", "000.000.000.000", ""); 
			
			MetaDbpediaMetadataFilter filter = new MetaDbpediaMetadataFilter();
			
			boolean expected = (boolean)entry.getValue();
			boolean actual = filter.evaluate(metadata);
			System.out.println("Expected = " + expected + " --- " + "actual = " + actual);
			Assert.assertEquals(expected, actual);
		}
	}

}

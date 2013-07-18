package filter;

import java.io.File;
import java.util.HashMap;
import java.util.Map;

import junit.framework.Assert;
import oaiReader.AbstractExtraction;
import oaiReader.MediawikiHelper;
import oaiReader.MediawikiTitle;
import oaiReader.RecordMetadata;

import org.ini4j.Ini;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

public class DefaultDbpediaMetadataFilterTest
{

	@Test
	public void testDoesAccept()
		throws Exception
	{
		Ini ini = new Ini(new File("config/en/config.ini"));
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
		testPairs.put("User:DBpedia/sandbox", true);
		testPairs.put("User:DBpedia/any", true);
		testPairs.put("User:DBpedia/subpage/shall/pass", true);
		
		testPairs.put("Mission:Impossible", true);
		testPairs.put("SomeArticle", true);
		testPairs.put("SomeArticle/SomeSubPage", true);

		testPairs.put("MediaWiki_talk:SomeArticle", false);
		testPairs.put("MediaWiki_talk:SomeArticle/SomeSubPage", false);
		
		for(Map.Entry<String, Boolean> entry : testPairs.entrySet()) {
			//Map<
			MediawikiTitle title =
				MediawikiHelper.parseTitle("http://en.wikipedia.org/wiki/Special:OAIRepository", entry.getKey());
			
			System.out.println("Title =  " + entry.getKey() + " - " +  "Namespace = " + title.getNamespaceId());
			
			RecordMetadata metadata = new RecordMetadata("en", title, "oai:12345", IRI.create("http://wiki.org"),  "12345", "x", "000.000.000.000", ""); 
			
			DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();
			
			boolean expected = (boolean)entry.getValue();
			boolean actual = filter.evaluate(metadata);
			System.out.println("Expected = " + expected + " --- " + "actual = " + actual);
			Assert.assertEquals(expected, actual);
		}
	}

}

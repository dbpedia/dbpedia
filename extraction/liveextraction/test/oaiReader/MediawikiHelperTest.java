package oaiReader;

import java.net.URI;

import org.junit.Assert;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

import filter.DefaultDbpediaMetadataFilter;

public class MediawikiHelperTest
{
	@Test
	public void testTitleFilter()
		throws Exception
	{
		String[] blacklist = {
				"MediaWiki_talk:David_Bowie",
				"MediaWiki_talk:Blah",
				"MediaWiki:Blah",
				"User:DBpediax",
				"User_talk:DBpediax",
				"User:DBpedix",
				"User_talk:DBpedix",
		};
		
		String[] whitelist = {
				"User:DBpedia",
				"User_talk:DBpedia",
		};
		
		//String t = "DBpedia/ontology/birthplace";
		
		for(String item : blacklist) {
		
			MediawikiTitle title =
				MediawikiHelper.parseTitle2(
						"http://en.wikipedia.org/wiki",
						item);
	
			RecordMetadata metadata = new RecordMetadata("en", title, "oai:12345",
					IRI.create("http://wiki.org"), "12345", "x", "000.000.000.000",
					"");
	
			DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();
			boolean isAccepted = filter.evaluate(metadata);
			System.out.println("isAccepted(" +  title.getFullTitle() + ")? " + isAccepted);
	
			Assert.assertEquals(false, isAccepted);
		}
		
		for(String item : whitelist) {
			
			MediawikiTitle title =
				MediawikiHelper.parseTitle2(
						"http://en.wikipedia.org/wiki",
						item);
	
			RecordMetadata metadata = new RecordMetadata("en", title, "oai:12345",
					IRI.create("http://wiki.org"), "12345", "x", "000.000.000.000",
					"");
	
			DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();
			boolean isAccepted = filter.evaluate(metadata);
			System.out.println("isAccepted(" +  title.getFullTitle() + ")? " + isAccepted);
	
			Assert.assertEquals(true, isAccepted);
		}
		
		
	}
}

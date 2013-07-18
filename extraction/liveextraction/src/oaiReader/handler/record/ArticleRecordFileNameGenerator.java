package oaiReader.handler.record;

import java.net.URLEncoder;

import oaiReader.Record;


public class ArticleRecordFileNameGenerator
	implements IRecordFileNameGenerator
{
	public String generate(Record record)
		throws Exception
	{
		String name = record.getMetadata().getWikipediaURI().toString();
		
		// hack: if there is no wikipediaIRI - e.g. because we are loading
		// from an offline store, just use the title
		if(name.equals(""))
			name = record.getMetadata().getTitle().getFullTitle();

		return URLEncoder.encode(name, "UTF-8");
	}
}

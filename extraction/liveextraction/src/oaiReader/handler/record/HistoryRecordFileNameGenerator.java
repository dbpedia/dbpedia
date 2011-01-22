package oaiReader.handler.record;

import java.net.URLEncoder;

import oaiReader.Record;

public class HistoryRecordFileNameGenerator
	implements IRecordFileNameGenerator
{
	public String generate(Record record)
		throws Exception
	{
		String name = record.getMetadata().getWikipediaURI().toString();
	
		if(name.equals(""))
			name = record.getMetadata().getTitle().getFullTitle();

		name += "___" + record.getMetadata().getRevision();
		
		return URLEncoder.encode(name, "UTF-8");
	}
}

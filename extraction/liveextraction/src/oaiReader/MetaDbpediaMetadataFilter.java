package oaiReader;

import filter.DbpediaMetadataFilter;

public class MetaDbpediaMetadataFilter
	extends DbpediaMetadataFilter
{
	public MetaDbpediaMetadataFilter()
	{
		// Filter for main-namespace (namespace-id = 0)
		//super(2);
		
		super(200, 202);
	}
	
	@Override
	public boolean evaluate(RecordMetadata item)
	{
		if(!super.evaluate(item))
			return false;
		
		// Only /ontology titles are allowed
		//return item.getTitle().getShortTitle().startsWith("DBpedia/ontology/");
		//System.out.println("---------------------------- " + item.getTitle().getFullTitle());
		//return item.getTitle().getFullTitle().startsWith("User:DBpedia-Bot/ontology");
		
		
		//return item.getTitle().getShortTitle().startsWith("DBpedia-Bot/ontology/");

	
		return true;
	}
}

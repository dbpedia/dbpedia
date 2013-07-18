package filter;

import oaiReader.RecordMetadata;


/**
 * The purpose of metadata filters is to reject records before their content
 * is retrieved. But this isn't 100% clean, as retrieving the content may
 * reveal more stuff that should belong to metadata.
 * 
 * So depending on the retrieval strategy, some filters may test for data that
 * isn't actually available yet.
 * 
 * @author raven_arkadon
 *
 */
public class DefaultDbpediaMetadataFilter
	extends DbpediaMetadataFilter
{
	public DefaultDbpediaMetadataFilter()
	{
		super(0, 10, 14);
	}
	
	@Override
	public boolean evaluate(RecordMetadata metadata)
	{
		Integer namespaceId = metadata.getTitle().getNamespaceId();
		if (namespaceId == 2)
			if (metadata.getTitle().getShortTitle().startsWith("DBpedia/"))
				return true;
		
		return super.evaluate(metadata);
	}
}


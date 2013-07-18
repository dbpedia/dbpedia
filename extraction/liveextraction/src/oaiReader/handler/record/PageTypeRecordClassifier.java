package oaiReader.handler.record;

import oaiReader.DeletionRecord;
import oaiReader.IRecord;
import oaiReader.IRecordVisitor;
import oaiReader.MediawikiTitle;
import oaiReader.Record;
import oaiReader.handler.generic.IClassifier;


/**
 * Classifies by namespace id.
 * A special classification are Template docs - they are 10/doc 
 * 
 * @author raven_arkadon
 *
 */
public class PageTypeRecordClassifier<T extends IRecord>
	implements IClassifier<T, String>, IRecordVisitor<String>
{
	@Override
	public String classify(IRecord item)
	{
		return item.accept(this);
	}

	@Override
	public String visit(Record item)
	{
		MediawikiTitle mediawikiTitle = item.getMetadata().getTitle();
		Integer namespaceId = mediawikiTitle.getNamespaceId(); 
		
		if(namespaceId == 10)
			if(mediawikiTitle.getShortTitle().endsWith("/doc"))
				return "10/doc";
		
		return namespaceId.toString();
	}

	@Override
	public String visit(DeletionRecord item)
	{
		return "deleted";
	}
}

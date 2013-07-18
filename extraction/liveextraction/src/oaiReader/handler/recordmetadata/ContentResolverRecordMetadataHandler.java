package oaiReader.handler.recordmetadata;

import oaiReader.IHandler;
import oaiReader.Record;
import oaiReader.RecordContent;
import oaiReader.RecordMetadata;
import oaiReader.RetrievalFacade;

/**
 * Whenever a record metadata record is found, resolve the content
 * immediately and a record is created.
 * 
 */
public class ContentResolverRecordMetadataHandler
	implements IHandler<RecordMetadata>
{
	private RetrievalFacade retrieval;
	private IHandler<Record> recordHandler;

	public ContentResolverRecordMetadataHandler(RetrievalFacade retrieval)
	{
		this.retrieval = retrieval; 
	}
	
	public void setRecordHandler(IHandler<Record> recordHandler)
	{
		this.recordHandler = recordHandler;
	}
	
	public IHandler<Record> getRecordHandler()
	{
		return recordHandler;
	}
	
	@Override
	public void handle(RecordMetadata metadata)
	{
		try {
			RecordContent content =
				this.retrieval.fetchRecordContent(metadata.getOaiId());
			
			Record record = new Record(metadata, content);
			
			if(null != recordHandler)
				recordHandler.handle(record);

		} catch (Exception e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}
}

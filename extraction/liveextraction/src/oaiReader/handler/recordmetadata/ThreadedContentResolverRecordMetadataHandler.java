package oaiReader.handler.recordmetadata;

import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import oaiReader.IHandler;
import oaiReader.Record;
import oaiReader.RecordContent;
import oaiReader.RecordMetadata;
import oaiReader.RetrievalFacade;


class ContentResolverTask
	implements Runnable
{
	private RetrievalFacade retrieval;
	private RecordMetadata metadata;
	private IHandler<Record> recordHandler;

	public ContentResolverTask(RetrievalFacade retrieval,
								RecordMetadata metadata,
								IHandler<Record> recordHandler)
	{
		this.metadata = metadata;
		this.retrieval = retrieval;
		this.recordHandler = recordHandler;
	}

	
	@Override
	public void run()
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


/**
 * Whenever a record metadata record is found, resolve the content
 * immediately and a record is created.
 * 
 */
public class ThreadedContentResolverRecordMetadataHandler
	implements IHandler<RecordMetadata>
{
	private RetrievalFacade retrieval;
	private IHandler<Record> recordHandler;

	private ExecutorService executorService;

	public ThreadedContentResolverRecordMetadataHandler(RetrievalFacade retrieval)
	{
		this.retrieval = retrieval; 
		this.executorService = Executors.newCachedThreadPool();
	}
	
	public ThreadedContentResolverRecordMetadataHandler(RetrievalFacade retrieval, ExecutorService executorService)
	{
		this.retrieval = retrieval; 
		this.executorService =  executorService;
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
		Runnable task =
			new ContentResolverTask(retrieval,
									metadata,
									recordHandler);

		executorService.submit(task);
	}
}


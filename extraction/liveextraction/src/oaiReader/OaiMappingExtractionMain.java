package oaiReader;

import java.io.File;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

import oaiReader.handler.generic.CategoryHandler;
import oaiReader.handler.record.FileWriterRecordHandler;
import oaiReader.handler.record.HistoryRecordFileNameGenerator;
import oaiReader.handler.record.PageTypeRecordClassifier;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

import filter.IFilter;


public class OaiMappingExtractionMain
	extends AbstractExtraction
{
	private static final String DEFAULT_INI_FILENAME =
		"config/oai_meta/config.ini";

	private static final Logger logger = Logger.getLogger(OaiMappingExtractionMain.class);
	
	protected Logger getLogger()
	{
		return logger;
	}
	
	
	public static void main(String[] args)
		throws Exception
	{
		new OaiMappingExtractionMain(args);
	}

	public OaiMappingExtractionMain(String[] args)
		throws Exception
	{
		super(args, DEFAULT_INI_FILENAME);
	}
	
	@Override
	public void run()
		throws Exception
	{
		runOnline(ini);
	}
	

	// metawiki extraction process
	private void runOnline(Ini ini)
		throws Exception	
	{
		Section section = ini.get("HARVESTER");
		int pollInterval = Integer.parseInt(section.get("pollInterval"));
		String lastResponseDateFile = section.get("lastResponseDateFile").trim();	
		
		FetchRecordTask task = createOnlineTask(ini);

		Runnable taskWrapper =
			new SaveLastUtcResponseDateTaskWrapper(
					task,
					lastResponseDateFile);
		
		ScheduledExecutorService es =
			Executors.newSingleThreadScheduledExecutor();
		
		logger.info("Starting online task");
		es.scheduleAtFixedRate(taskWrapper, 0, pollInterval, TimeUnit.SECONDS);		
		logger.info("Online task finished");
	}


	/*************************************************************************/
	/* Workflows and Tasks                                                    */
	/*************************************************************************/	
	private FetchRecordTask createOnlineTask(Ini ini)
		throws Exception	
	{
		// Begin ini section
		Section section = ini.get("HARVESTER");

		String lastResponseDateFile = section.get("lastResponseDateFile");
		String startNow = section.get("startNow");
		
		String startDate;
		if(startNow.equalsIgnoreCase("true"))
			startDate = getStartDateNow();
		else
			startDate = readStartDate(lastResponseDateFile);

		String baseWikiUri = section.get("baseWikiUri");

		String oaiUri = baseWikiUri + "Special:OAIRepository";
		int sleepInterval = Integer.parseInt(section.get("sleepInterval"));
		// End ini section

		
		// Create an instance of the retrieval facade
		RetrievalFacade retrieval = new RetrievalFacade(oaiUri, startDate);

		// Create a task object for retrieving metadata 
		FetchRecordTask task = retrieval.newFetchRecordTask(baseWikiUri);
		task.setSleepInterval(sleepInterval);

		// Filter what to actually retrieve
		IFilter<RecordMetadata> metadataFilter =
			new StatisticWrapperFilter<RecordMetadata>(new MetaDbpediaMetadataFilter());
		task.setMetadataFilter(metadataFilter);
		

		// append the meta extraction workflow to the task
		task.setRecordHandler(createWorkflow(ini));
		//task.setHandler(createWorkflow(ini));
		//task.setDeletionHandler(createDeletionWorkflow());
		//task.setDeletionHandler(getDeletionWorkflow());

		return task;
	}
	
	

	/**
	 * Returns the workflow for extracting property and class definitions
	 * from meta-wiki
	 * 
	 * @return
	 */
	private static IHandler<IRecord> createWorkflow(Ini ini)
		throws Exception
	{
		Section section = ini.get("FILE_OUTPUT");	
		String filename = section.get("filename");

		
		Section historySection = ini.get("HISTORY");
		boolean historyEnabled =
			historySection.get("enabled").trim().equalsIgnoreCase("true");
		boolean historyZipped =
			historySection.get("compression").trim().equalsIgnoreCase("gz");
		String historyDir = historySection.get("dir");
		
		
		// MultiHandler is a multiplexer for IHandler<Record> instances
		MultiHandler<IRecord> handlerList = new MultiHandler<IRecord>(); 

		// Attach a category delegation handler - this handler delegates
		// to other handlers depending on a classification
		CategoryHandler<IRecord, String> classifiedHandler =
			new CategoryHandler<IRecord, String>(new PageTypeRecordClassifier<IRecord>());
		handlerList.handlers().add(classifiedHandler);
	
		// for articles
		MultiHandler<IRecord> articleHandlerList = new MultiHandler<IRecord>();
		MultiHandler<IRecord> deletionHandlerList = new MultiHandler<IRecord>();

		classifiedHandler.addHandler(articleHandlerList, "0");
		classifiedHandler.addHandler(deletionHandlerList, "deleted");

		
		OaiMappingFileWriterRecordHandler mappingHandler =
			new OaiMappingFileWriterRecordHandler(new File(filename));

		articleHandlerList.handlers().add(mappingHandler);
		deletionHandlerList.handlers().add(mappingHandler);
		
		
		if(historyEnabled) {
			Files.mkdir(historyDir);
			
			FileWriterRecordHandler fileWriter =
				new FileWriterRecordHandler(historyDir, historyZipped, new HistoryRecordFileNameGenerator());
			articleHandlerList.handlers().add(new RecordAdaptor2(fileWriter));
		}
		
		return handlerList;
	}
}


// the inverse side to RecordAdaptor
class RecordAdaptor2
	implements IHandler<IRecord>
{
	private IHandler<Record> handler;
	
	public RecordAdaptor2(IHandler<Record> handler)
	{
		this.handler = handler;
	}
	@Override
	public void handle(IRecord item)
	{
		if(item instanceof Record)
			handler.handle((Record)item);
	}
}

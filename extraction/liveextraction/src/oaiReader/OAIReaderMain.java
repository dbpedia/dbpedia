package oaiReader;

import filter.DefaultDbpediaMetadataFilter;
import filter.IFilter;
import helpers.DBPediaXMLUtil;
import helpers.DBpediaQLUtil;
import helpers.ExceptionUtil;
import helpers.OAIUtil;
import helpers.SQLUtil;
import helpers.XMLUtil;
import iterator.TimeWindowIterator;

import java.io.File;
import java.io.FileWriter;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

import oaiReader.handler.generic.CategoryHandler;
import oaiReader.handler.record.ArticleRecordFileNameGenerator;
import oaiReader.handler.record.FileWriterRecordHandler;
import oaiReader.handler.record.PageTypeRecordClassifier;

import org.apache.commons.collections15.iterators.TransformIterator;
import org.apache.log4j.Logger;
import org.hibernate.Session;
import org.hibernate.Transaction;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import sparql.ISparulExecutor;
import templatedb.HibernateUtil;
import transformer.NodeToRecordTransformer;
import virtuoso.jdbc4.VirtuosoException;

import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.rdf.model.Resource;

import connection.VirtuosoFailSafeSimpleConnection;



/**
 *
 * 
 * @author raven_arkadon
 */
public class OAIReaderMain
	extends AbstractExtraction
{
    private static final String DEFAULT_INI_FILENAME = "config/en/config.ini";
	
    private static Logger logger = Logger.getLogger(OAIReaderMain.class);
    
	protected Logger getLogger()
	{
		return logger;
	}

    //private static ExecutorService executorService = Executors.newSingleThreadExecutor();
    
	public OAIReaderMain(String[] args, String iniFile)
		throws Exception
	{
		super(args, iniFile);
	}
	

	
	/**
	 * Note: the recordMetadataDirectory is currently used for storing
	 * information about deleted records. We could move deleted records
	 * into the ordinary oaidir(?)
	 * 
	 * @param args
	 * @throws Exception
	 */
	public static void main(String[] args)
		throws Exception
	{		
		String iniFilename = DEFAULT_INI_FILENAME;
		
		OAIReaderMain main = new OAIReaderMain(args, iniFilename);
		main.run();
	}
	
	
	
	private void downloadSingleArticle(String name)
		throws Exception
	{ //"dbpedia.org/resource/International_reactions_to_the_Jyllands-Posten_Muhammad_cartoons_controversy"
		String baseUri = "http://en.wikipedia.org/";
		String baseWikiUri = "http://en.wikipedia.org/wiki/";
		String oaiPrefix = "oai:en.wikipedia.org:enwiki:";
		String oaiUri = "http://en.wikipedia.org/wiki/Special:OAIRepository";
		String directory = "oairecords";
		
		WhatLinksHereArticleLoader loader = new WhatLinksHereArticleLoader(baseWikiUri, oaiPrefix);
		
		Document document = loader.export(name);
		IRecord record = DBPediaXMLUtil.exportToRecord(document, baseWikiUri, oaiUri, oaiPrefix);
		
		if(record instanceof Record) {
			Files.mkdir(directory);
			FileWriterRecordHandler handler = new FileWriterRecordHandler(directory, false, new ArticleRecordFileNameGenerator());
		
			handler.handle((Record)record);
		}
		else {
			logger.error("Not an instance of Record");
		}
	}
	
	
	
	public void run()
		throws Exception
	{			
		Section section = ini.get("OFFLINE");
		String offline = section.get("enabled").trim();
		

		if(false) {
			String article = "List_of_accidents_and_incidents_involving_military_aircraft_%282000%E2%80%93present%29";
			// String article = "International_reactions_to_the_Jyllands-Posten_Muhammad_cartoons_controversy";
				
			downloadSingleArticle(article);
			System.exit(0);
		}
		
		
		if(offline.equalsIgnoreCase("true"))
			runOffline();
		else
			runOnline();
	}

	
	
	public void runOffline()
	{
		Section section = ini.get("OFFLINE");
		String  dir = section.get("dir");
		
		Files.mkdir(dir);
		DirectoryRecordCollection records = new DirectoryRecordCollection(dir);
		Iterator<IRecord> iterator = records.iterator();

		IFilter<RecordMetadata> metadataFilter = new DefaultDbpediaMetadataFilter(); 		
		StatisticWrapperFilter<RecordMetadata> filterStats = new StatisticWrapperFilter<RecordMetadata>(metadataFilter);

		IHandler<Record> workflow = getWorkflow(ini);
		
		runWorkflow(iterator, workflow, metadataFilter, null, filterStats, null, logger);
	}
	
	
	public static Map<String, String> getPropertiesFromHibernateXML(File file)
		throws Exception
	{
		Map<String, String> result = new HashMap<String, String>();
		
		Document doc = XMLUtil.openFile(file);
		
		NodeList nodes = doc.getElementsByTagName("property");
		for(int i = 0; i < nodes.getLength(); ++i) {
			Node node = nodes.item(i);
			
			String key = node.getAttributes().getNamedItem("name").getNodeValue();
			String value = node.getTextContent().trim();
			
			result.put(key, value);
		}
		
		/*
		result.put("url",      XPathUtil.evalToString(doc, "//*[local-name()='property']/@connection.url"));
		result.put("username", XPathUtil.evalToString(doc, "//property/@connection.username"));
		result.put("password", XPathUtil.evalToString(doc, "//property/@connection.password"));
		result.put("driver",   XPathUtil.evalToString(doc, "//property/@connection.driver_class"));
		 */
		return result; 
	}

	
	
	public void runOnline()
		throws Exception
	{
		Section section = ini.get("HARVESTER");
		int pollInterval = Integer.parseInt(section.get("pollInterval"));

		String lastResponseDateFile = section.get("lastResponseDateFile");
		String startNow = section.get("startNow").trim();
		
		String startDate;
		if(startNow.equalsIgnoreCase("true"))
			startDate = getStartDateNow();
		else
			startDate = readStartDate(lastResponseDateFile);

		String baseWikiUri = section.get("baseWikiUri");

		String oaiUri = baseWikiUri + "Special:OAIRepository";
		int sleepInterval = Integer.parseInt(section.get("sleepInterval"));

		Integer articleDelay = Integer.parseInt(section.get("articleDelay")) * 1000;
		Boolean articleRenewal = Boolean.parseBoolean(section.get("articleRenewal"));
		
		String oaiPrefix = section.get("oaiPrefix").trim();
		
		Section throughputSection = ini.get("THROUGHPUT_STATS_FILE_OUPUT");
		boolean throughputEnabled = Boolean.parseBoolean(throughputSection.get("enabled"));
		boolean throughputAppend = Boolean.parseBoolean(throughputSection.get("append"));
		String throughputFile = throughputSection.get("file");
		int throughputInterval = Integer.parseInt(throughputSection.get("interval"));

		// Set up a sparul executor for deleting articles
		
		
		Iterator<Document> recordIterator =
			OAIUtil.createEndlessRecordIterator(oaiUri, startDate, pollInterval * 1000, sleepInterval * 1000, new File(lastResponseDateFile));

		// Filter elements using the DefaultDBPediaMetadataFilter
		//Iterator<Document> filterIterator = new FilterIterator<Document>(recordIterator, DBP
		
		TimeWindowIterator timeWindowIterator = null;
		if(articleDelay != 0) {
			timeWindowIterator = new TimeWindowIterator(recordIterator, articleDelay, false, articleRenewal);
			recordIterator = timeWindowIterator;
		}
		
		// Transform the documents into record-objects
		Iterator<IRecord> iterator = new TransformIterator<Document, IRecord>(recordIterator, new NodeToRecordTransformer(baseWikiUri, oaiUri, oaiPrefix));
		
		
		
		
		IHandler<DeletionRecord> deletionWorkflow = getDeletionWorkflow(ini);
		IHandler<Record> workflow = getWorkflow(ini);
		
		
		// Set up the filter
		IFilter<RecordMetadata> metadataFilter = new DefaultDbpediaMetadataFilter(); 
		
		StatisticWrapperFilter<RecordMetadata> filterStats = new StatisticWrapperFilter<RecordMetadata>(metadataFilter);
		metadataFilter = filterStats;
		
		
		if(throughputEnabled) {
			metadataFilter =
				new StatisticWriterFilter<RecordMetadata>(
						metadataFilter,
						new FileWriter(
								throughputFile,
								throughputAppend), throughputInterval);
		}
		
		
		runWorkflow(iterator, workflow, metadataFilter, timeWindowIterator, filterStats, deletionWorkflow, logger);
		/*
		Transformer<Record, RecordMetadata> recordToMetadata = new Transformer<Record, RecordMetadata>() {
			@Override
			public RecordMetadata transform(Record item)
			{
				return item.getMetadata();
			}
		};
 
		
		Predicate<Record> filter = new TransformedPredicate<Record, RecordMetadata>(
				recordToMetadata,
				metadataFilter);
		
		Iterator<Record> finalIterator = new FilterIterator<Record>(iterator, filter);
		*/
		

		
		
		//Runnable onlineTask = createOnlineTask(ini);
		
		//TaskListTask task = new TaskListTask();
		//task.add(onlineTask);
		
		//ScheduledExecutorService executorService = Executors.newSingleThreadScheduledExecutor();
		//executorService.scheduleWithFixedDelay(task, 0, pollInterval, TimeUnit.SECONDS);		
	}
	
	
	/**
	 * metaDataFilter should be obtained by trying to unwrap it from iterator.
	 * TODO I think apache.collections has some abstract iterator which provides
	 * that functionality
	 * 
	 * @param iterator
	 * @param workflow
	 */
	public static void runWorkflow(Iterator<IRecord> iterator, IHandler<Record> workflow, IFilter<RecordMetadata> metadataFilter, TimeWindowIterator timeWindowIterator, StatisticWrapperFilter<RecordMetadata> filterStats, IHandler<DeletionRecord> deletionWorkflow, Logger logger)
	{
		int counter = 0;
		int deletionCount = 0;
		while(iterator.hasNext()) {
			IRecord record = iterator.next();
			
			if(record instanceof Record) {
				Record r = (Record)record;
			
				if(r.getMetadata().getTitle().getFullTitle().trim().isEmpty()) {
					throw new RuntimeException("Shouldn't happen - X");
				}
				
				
				if(metadataFilter.evaluate(r.getMetadata())) {
					logger.trace("Accepted: '" + r.getMetadata().getTitle().getFullTitle() + "'");
					workflow.handle(r);
				}
				else
					logger.trace("Rejected: '" + r.getMetadata().getTitle().getFullTitle() + "'");
					
			}
			else if(record instanceof DeletionRecord) {
				DeletionRecord r = (DeletionRecord)record;

				//logger.debug("Submitting task: Delete record: '" + r.getOaiId() + "'");
				++deletionCount;
				
				logger.debug("Deleted#" + r.getOaiId() + "#");
				
				
				//logger.warn("Deletion currently not supported");
				//executorService.submit(new DeletionTask(r, deletionWorkflow));
				if(deletionWorkflow != null)
					deletionWorkflow.handle(r);
			}
			else
				throw new RuntimeException("Shouldn't happen");

			++counter;
			if(counter % 100 == 0) {
				int queued = 0;
				if(timeWindowIterator != null)
					queued = timeWindowIterator.getQueued().size();
				
				logger.info(
						"Seen records: " + counter + ", " +
						"accepted: " + filterStats.getAcceptCount() + ", " +
						"rejected: " + filterStats.getRejectCount() + ", " +
						"deleted: " + deletionCount + ", " +
						"queued: " + queued);
			}
		}		
	}
	
	
	/**
	 * This type always requests the content in a single request.
	 * So content doesn't have to be retrieved separately
	 * 
	 * @param args
	 */
	public Runnable createOnlineTask(Ini ini)
		throws Exception
	{
		Section section = ini.get("HARVESTER");

		String lastResponseDateFile = section.get("lastResponseDateFile");
		String startNow = section.get("startNow").trim();
		
		String startDate;
		if(startNow.equalsIgnoreCase("true"))
			startDate = getStartDateNow();
		else
			startDate = readStartDate(lastResponseDateFile);

		String baseWikiUri = section.get("baseWikiUri");

		String oaiUri = baseWikiUri + "Special:OAIRepository";
		int sleepInterval = Integer.parseInt(section.get("sleepInterval"));

		
		Section throughputSection = ini.get("THROUGHPUT_STATS_FILE_OUPUT");
		boolean throughputEnabled = Boolean.parseBoolean(throughputSection.get("enabled"));
		boolean throughputAppend = Boolean.parseBoolean(throughputSection.get("append"));
		String throughputFile = throughputSection.get("file");
		int throughputInterval = Integer.parseInt(throughputSection.get("interval"));
		
		
		// Create an instance of the retrieval facade
		RetrievalFacade retrieval = new RetrievalFacade(oaiUri, startDate);

		// Create a task object for retrieving metadata 
		FetchRecordTask task = retrieval.newFetchRecordTask(baseWikiUri);
		task.setSleepInterval(sleepInterval);

		// Filter what to actually retrieve
		IFilter<RecordMetadata> metadataFilter =
			new StatisticWrapperFilter<RecordMetadata>(new DefaultDbpediaMetadataFilter());
		
		
		
		if(throughputEnabled)
			metadataFilter =
				new StatisticWriterFilter<RecordMetadata>(
						metadataFilter,
						new FileWriter(
								throughputFile,
								throughputAppend), throughputInterval);
		
		task.setMetadataFilter(metadataFilter);

		
		task.setHandler(getWorkflow(ini));
		task.setDeletionHandler(getDeletionWorkflow(ini));
		
		// Schedule the task for periodic execution
		// (The task keeps terminating if there are no more records to process)

		SaveLastUtcResponseDateTaskWrapper taskWrapper =
			new SaveLastUtcResponseDateTaskWrapper(
					task,
					lastResponseDateFile);
		
		return taskWrapper;
	}

	
	
	
	public static IHandler<DeletionRecord> getDeletionWorkflow(Ini ini)
		throws Exception
	{
		MultiHandler<DeletionRecord> handlerList =
			new MultiHandler<DeletionRecord>(); 

		/**
		 * We reuse the hibernate config in the template annotation
		 * extractor.
		 * 
		 */
		Section taeSection = ini.get("TEMPLATE_ANNOTATION_EXTRACTOR");
		String configFile = taeSection.get("hibernateConfigFile");
		
		Map<String, String> props = getPropertiesFromHibernateXML(new File(configFile));
		
		
		Section section = ini.get("DELETIONS");
		boolean enabled = Boolean.parseBoolean(section.get("enabled"));
		String dataGraphName = section.get("graphNameData");
		String metaGraphName = section.get("graphNameMeta");

		
		if(enabled) {
			//Class.forName("virtuoso.jdbc4.Driver");
			//System.out.println(props);
			//System.out.println("SDASDASDASD" + x);
			
			// Load the driver
			Class.forName(props.get("connection.driver_class"));
			
			// Make the connection more or less fail safe
			ConnectionWrapper connectionWrapper =
				new ConnectionWrapper(
						props.get("connection.url"),
						props.get("connection.username"),
						props.get("connection.password"));
	
			ISimpleConnection simpleConnection =
				new VirtuosoFailSafeSimpleConnection(connectionWrapper);
			

			// Test if we have access privileges
			logger.info("Testing for access privileges on dbpedia_triples table");
			String query =
				"SELECT resource FROM dbpedia_triples WHERE oaiid = '0'";
			try {
				simpleConnection.query(query);
			}
			catch(VirtuosoException e) {
				String msg = ExceptionUtil.toString(e);

				// FIXME: Find a clean way to get the cause of an SQL-Error
				if(msg.contains("access denied"))
					throw e;
				
				logger.warn(
						"\n\n\n***********************\n" +
						"Table 'dbpedia_triples' does not exist yet\n" +
						"Please make sure we have the right access privileges once it is created\n" +
						"***********************\n\n\n");
			}			
			/*
			ISparulExecutor nullSparulExecutor = 
				new SparulStatisticExecutorWrapper(
						new VirtuosoSimpleSparulExecutor(simple
								connectionWrapper,
								new VirtuosoJdbcSparulExecutor(null)));
			*/
	
			DeletionHandler handler =
				new DeletionHandler(simpleConnection, dataGraphName, metaGraphName);

			handlerList.handlers().add(handler);
		}

		/*
		if(deletionRecordFileWriterEnabled) {
			MyDeletionRecordHandler fileWriterHandler = 
				new MyDeletionRecordHandler(recordMetadataDirectory);
			handlerList.handlers().add(fileWriterHandler);		
		}

		if(deletionSparulWriterEnabled) {
			DbpediaFacade dbpedia =
				new DbpediaFacade(
						sparulDeleteService,
						sparulDeleteDefaultGraph, 
						sparulDeletePrefix);
		
			DbpediaDeleterRecordDeletionHandler sparulHandler =
				new DbpediaDeleterRecordDeletionHandler(sparulDirectory, dbpedia);
			handlerList.handlers().add(sparulHandler);
		}
		*/
		return handlerList;
	}
	
	
	

	public static IHandler<Record> getWorkflow(Ini ini)
	{
		Section foSection = ini.get("FILE_OUTPUT");
		boolean foEnabled =
			foSection.get("enabled").trim().equalsIgnoreCase("true");
		boolean foZipped =
			foSection.get("compression").trim().equalsIgnoreCase("gz");
		String foDir = foSection.get("dir");
		
		
		
		Section taeSection = ini.get("TEMPLATE_ANNOTATION_EXTRACTOR");
		boolean taeEnabled =
			taeSection.get("enabled").trim().equalsIgnoreCase("true"); 
		String hibernateConfigFile = taeSection.get("hibernateConfigFile");
		
		
		
		// MultiHandler is a multiplexer for IHandler<Record> instances
		MultiHandler<Record> handlerList = new MultiHandler<Record>(); 

		// Attach a category delegation handler - this handler delegates
		// to other handlers depending on a classification
		CategoryHandler<Record, String> classifiedHandler =
			new CategoryHandler<Record, String>(new PageTypeRecordClassifier<Record>());
		handlerList.handlers().add(classifiedHandler);
		
		
		// Attach a multi handler for template/docs
		MultiHandler<Record> templateDocHandlerList = new MultiHandler<Record>();
		classifiedHandler.addHandler(templateDocHandlerList, "10/doc");
	
		// for articles
		MultiHandler<Record> articleHandlerList = new MultiHandler<Record>();
		classifiedHandler.addHandler(articleHandlerList, "0");

		
		// This is for the User/_talk:DBpedia exception
		classifiedHandler.addHandler(articleHandlerList, "2");
		classifiedHandler.addHandler(articleHandlerList, "3");
		
		// and for categories
		MultiHandler<Record> categoryHandlerList = new MultiHandler<Record>();
		classifiedHandler.addHandler(categoryHandlerList, "14");

		
		// Attach a media wiki updater for storing templates
		//MediawikiEditRecordHandler templateEdit =
		//	new MediawikiEditRecordHandler(targetWikiApiUri);		
		//classifiedHandler.addHandler(templateEdit, "Template");
		
		
		// Attach a content parser for Template/Doc
		if(taeEnabled) {
			HibernateUtil.initialize(hibernateConfigFile);
			initTemplateDb();
			
			ParseContentRecordHandler parser = new ParseContentRecordHandler();
			templateDocHandlerList.handlers().add(new RecordAdapter(parser));
		
			// Attach the handler which updates the database
			// !Requires the content parser to be run before!
			TemplateAnnotationExtractor templateAnnotationExtractor =
				new TemplateAnnotationExtractor();
			templateDocHandlerList.handlers().add(templateAnnotationExtractor);
		}
		
		
		// Warning: Make sure that targetWikiApiUri points to the right
		// mediawiki - would be bad if we overwrote e.g. wikipedia 
		//MediawikiEditRecordHandler editRh =
		//	new MediawikiEditRecordHandler(targetWikiApiUri);
		
		//recordHandler.add(editRh);
		
		// FileWriterRecordHandler serializes records into files
		// in the specified directory
		
		if(foEnabled) {
			Files.mkdir(foDir);
			
			FileWriterRecordHandler fileWriter =
				new FileWriterRecordHandler(foDir, foZipped, new ArticleRecordFileNameGenerator());
			articleHandlerList.handlers().add(fileWriter);
			categoryHandlerList.handlers().add(fileWriter);
		}
		
		// Write articles and categories to files
		/*
		MetadataWriterRecordHandler fileMrh =
			new MetadataWriterRecordHandler(recordMetadataDirectory);
		articleHandlerList.handlers().add(fileMrh);
		categoryHandlerList.handlers().add(fileMrh);
		*/
		return handlerList;
	}
	
	private static void initTemplateDb()
	{
		logger.info("Creating/Updating database schema for TemplateDb");
		// The config should be set to update - then this statement will
		// create the schema
		//for(;;) {
		try{
			Session s = HibernateUtil.getSessionFactory().getCurrentSession();
	
			@SuppressWarnings("unused")
			Transaction tx = s.beginTransaction();
			tx.rollback();
			//break;
		}catch (Exception e) {
			logger.error("Connection to DB not working, probably due to bad login, see config/I18N/TemplateDb.*.cfg.xml "
					+ExceptionUtil.toString(e));
			e.printStackTrace();
			System.exit(0);
		}
		
		
		/*
			System.out.println("Retrying");
			try {
				Thread.sleep(1000);
			}
			catch(Exception e1) {
			}
		}
		*/
		logger.info("Done.");
	}

}


class DeletionTask
	implements Runnable
{
	private IHandler<DeletionRecord> handler;
	private DeletionRecord record;
	
	public DeletionTask(DeletionRecord record, IHandler<DeletionRecord> handler)
	{
		this.record = record;
		this.handler = handler;
	}
	
	@Override
	public void run()
	{
		handler.handle(record);
	}
	
	@Override
	public String toString()
	{
		return this.getClass().getSimpleName() + "(" + record.getOaiId() + ")";
	}
}

class DeletionHandler
	implements IHandler<DeletionRecord>
{
	private static Logger logger = Logger.getLogger(DeletionHandler.class);
	
	private ISparulExecutor executor;
		
	private ISimpleConnection connection;

	
	private String dataGraphName;
	private String metaGraphName;
	
	
	public DeletionHandler(ISimpleConnection connection,
			String dataGraphName, String metaGraphName)
	{
		this.connection = connection;
		this.dataGraphName = dataGraphName;
		this.metaGraphName = metaGraphName;
		
		this.executor = new VirtuosoSimpleSparulExecutor(connection);
	}
	
	
	
	@Override
	public void handle(DeletionRecord item)
	{
		try {
			myHandle(item);
		}
		catch(Throwable e) {
			logger.error(ExceptionUtil.toString(e));
		}
	}
	
	private static Resource singleResource(String varName, List<QuerySolution> qss)
	{
		if(qss.size() == 0)
			return null;
		
		if(qss.size() > 1)
			return null;
		
		QuerySolution qs = qss.get(0);
		
		/*
		Iterator<String> varNames = qs.varNames();
		if(!varNames.hasNext())
			return null;
		
		String var = qs.varNames().next(); 
		*/
		
		return qs.getResource(varName);
		//return qs.get(var);
	}
	
	private void myHandle(DeletionRecord item)
		throws Exception
	{
		logger.debug("Deleting page '" + item.getOaiId() + "'");
		
		// retrieve the source-page for the given oai-id (if there is one)
		int lastColonIndex = item.getOaiId().lastIndexOf(':');
		int pageId = Integer.parseInt(item.getOaiId().substring(lastColonIndex + 1));
		
				
		// If the table does not exist, what do we do?
		// Also, don't forget to delete the table row.
		
		String sourceResource = null;
		try {
			String query =
				"SELECT resource FROM dbpedia_triples WHERE oaiid = '" + pageId + "'";

			ResultSet rs;
			rs = connection.query(query);

			sourceResource = SQLUtil.single(rs, String.class);
			
			
			if(sourceResource == null) {
				String dataQuery = DBpediaQLUtil.getResourceByPageId(pageId, dataGraphName);
				List<QuerySolution> qss = executor.executeSelect(dataQuery);
				
				Resource r = singleResource("?s", qss);				
				sourceResource = r == null ? null : r.toString();
			}
			logger.info("resource was "+sourceResource);
		}
		catch(Exception e) {
			// If this happens we assume the table doesn't exisit yet
			// since the underlying connection is 'fail-safe'
			// So if there is a problem with the server it will try to
			// reconnect for all eternity but never throw an exception
			
			logger.warn(ExceptionUtil.toString(e));
			logger.warn("Table 'dbpedia_triples' does not seem to exist yet");
			return;
		}
		

		if(sourceResource == null) {
			logger.debug("PageId '" + pageId + "' not found in 'dbpedia_triples' - nothing to delete");
			return;
		}


		String q1 = DBpediaQLUtil.deleteSubResourcesByPageId(sourceResource, dataGraphName);
		executor.executeUpdate(q1);

		String q2 = DBpediaQLUtil.deleteDataByPageId(sourceResource, dataGraphName);
		executor.executeUpdate(q2);
		
		
		
		/*
		String q1 = DBpediaQLUtil.deleteDataBySourcePage(sourceResource, dataGraphName, metaGraphName);		
		executor.executeUpdate(q1);

		String q2 = DBpediaQLUtil.deleteMetaBySourcePage(sourceResource, metaGraphName);		
		executor.executeUpdate(q2);
		*/
		
		String qd1 = "DELETE FROM dbpedia_triples WHERE oaiid = '" + pageId + "'";
		connection.update(qd1);
	}
	
}



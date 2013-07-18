
package oaiReader;

import filter.IFilter;
import helpers.ExceptionUtil;
import helpers.RDFUtil;

import java.io.File;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.Authenticator;
import java.net.PasswordAuthentication;
import java.net.URI;
import java.sql.Connection;
import java.util.ArrayList;
import java.util.Collection;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

import oaiReader.handler.generic.CategoryHandler;
import oaiReader.handler.record.PageTypeRecordClassifier;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.GnuParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.log4j.ConsoleAppender;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.apache.log4j.SimpleLayout;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;
import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.vocab.OWLRDFVocabulary;

import sparql.ISparulExecutor;
import sparql.SparulStatisticExecutorWrapper;
import sparql.VirtuosoJdbcSparulExecutor;
import virtuoso.jdbc4.VirtuosoException;
import ORG.oclc.oai.harvester2.verb.ListRecords;

import com.hp.hpl.jena.query.QuerySolution;


/**
 * Note: Reconnect if the database is not available:
 * Option 1: Check if the connection is valid before a set of calls.
 * Option 2: Validate connection at each statement
 * Option 3: Validate connection before a task
 * 	If task fails, retry the whole task.
 * Task would have prequisites (e.g. a valid database connection)
 *  We could throw an exception if the task fails because a prequisite failed
 *  during execution and then retry.
 *  
 * 
 * 
 * 
 * 
 * @author raven
 *
 */
interface IConnectionWrapper
{
	Connection getConnection()
		throws Exception;
}







class VirtuosoJdbcSparulExecutorPreconditionWrapper
	implements ISparulExecutor
{	
	private static Logger logger = Logger.getLogger(VirtuosoJdbcSparulExecutorPreconditionWrapper.class);
	private VirtuosoJdbcSparulExecutor sparulExecutor;
	private ConnectionWrapper connectionWrapper;

	
	public VirtuosoJdbcSparulExecutorPreconditionWrapper(
			ConnectionWrapper connectionWrapper,
			VirtuosoJdbcSparulExecutor sparulExecutor)
	{
		this.connectionWrapper = connectionWrapper;
		this.sparulExecutor = sparulExecutor;
		
		this.sparulExecutor.setConnection(connectionWrapper.getConnection());
	}

	
	private void handleException(VirtuosoException e)
		throws VirtuosoException
	{
		switch(e.getErrorCode()) {
		case VirtuosoException.SQLERROR:
			throw e;
		}

		logger.warn(ExceptionUtil.toString(e));
		
		reconnectLoop();
	}
	
	private void reconnectLoop()
	{
		for(;;) {
			try {
				logger.info("Attempting to reconnect in 30 seconds");
				Thread.sleep(30000);
				connectionWrapper.reconnect();
				
				return;
			}
			catch(Exception e) {
				logger.debug(ExceptionUtil.toString(e));
			}
		}
		
	}
	
	@Override
	public void executeUpdate(final String query)
		throws Exception
	{
		for(;;) {
			try {
				sparulExecutor.setConnection(connectionWrapper.getConnection());
				sparulExecutor.executeUpdate(query);
				return;
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}	
	}

	@Override
	public boolean executeAsk(final String query)
		throws Exception
	{
		for(;;) {
			try {
				sparulExecutor.setConnection(connectionWrapper.getConnection());
				return sparulExecutor.executeAsk(query);
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}	
	}

	@Override
	public List<QuerySolution> executeSelect(final String query)
		throws Exception
	{
		for(;;) {
			try {
				sparulExecutor.setConnection(connectionWrapper.getConnection());
				return sparulExecutor.executeSelect(query);
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}	
	}

	@Override
	public String getGraphName()
	{
		return sparulExecutor.getGraphName();
	}


	@Override
	public Object getConnection()
	{
		return sparulExecutor.getConnection();
	}


	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		for(;;) {
			try {
				sparulExecutor.setConnection(connectionWrapper.getConnection());
				return sparulExecutor.insert(triples, graphName);
				
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}	
	}


	@Override
	public boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		throw new RuntimeException("Not implemented yet");
		// TODO Auto-generated method stub
		//return false;
	}
}


/**
 * Amphibien subclass Fish
 * 
 *  
 * 
 * 
 * 
 * @author raven
 *
 */








// Test class was used for tracing a logging issue
// can be removed now
class PseudoTask
	implements Runnable
{
	private Logger logger = Logger.getLogger(PseudoTask.class);
	@Override
	public void run()
	{
		logger.info("Pseudo task executed");
	}
}


/**
 * Is this a task? or a factory?
 * 
 * Probably a factory: it takes an ini file and returns a configured task
 * 
 * @author raven
 *
 */
public class MetaExtractionMain
{
    private static final String DEFAULT_INI_FILENAME = "config/meta/config.ini";

    private static Logger logger = Logger.getLogger(MetaExtractionMain.class);

	// Command line options
	private static org.apache.commons.cli.Options cliOptions;

	static
	{
		cliOptions = new org.apache.commons.cli.Options();
		
		cliOptions
			.addOption("i", "ini", true, "Ini file. Default is: '" + DEFAULT_INI_FILENAME + '"');
	}

	
	
	/**
	 * Creates a MetaExtraction object based on the given arguments
	 * 
	 * @param args
	 * @throws Exception
	 */
	public static void main(String[] args)
		throws Exception
	{
		Class.forName("virtuoso.jdbc4.Driver");
		
		
		String iniFilename = DEFAULT_INI_FILENAME;
		
		printHelp();

		CommandLineParser cliParser = new GnuParser();
		CommandLine commandLine = cliParser.parse(cliOptions, args);
		
		
		if (commandLine.hasOption("i"))
			iniFilename = commandLine.getOptionValue("i");

		System.out.println("Loading ini: '" + iniFilename + "'");
		Ini ini = loadIni(iniFilename);
	
		initLoggers(ini);
		initOai(ini);
		
		run(ini);

		logger.info("This thread is now terminating.");
	}
	
	private static void run(Ini ini)
		throws Exception
	{
		Section section = ini.get("OFFLINE");
		
		String enabled = section.get("enabled").trim();
		
		if(enabled.equalsIgnoreCase("false"))
			runOnline(ini);
		else if(enabled.equalsIgnoreCase("true"))
			runOffline(ini);
		else
			throw new RuntimeException("Offline mode must be either true or false - current value = " + enabled);
	}
	

	// metawiki extraction process
	private static void runOnline(Ini ini)
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
		//es.scheduleAtFixedRate(taskWrapper, 0, pollInterval, TimeUnit.SECONDS);		
		es.scheduleWithFixedDelay(taskWrapper, 0, pollInterval, TimeUnit.SECONDS);
		logger.info("Online task finished");
	}

	// metawiki extraction process
	private static void runOffline(Ini ini)
		throws Exception
	{
		Runnable task = createOfflineTask(ini);
		
		logger.info("Starting offline task");
		task.run();
	}

	/*************************************************************************/
	/* Workflows and Tasks                                                    */
	/*************************************************************************/	
	private static DirectoryRecordReader createOfflineTask(Ini ini)
		throws Exception	
	{
		throw new RuntimeException("Not implemented yet");
		/*
		Section section = ini.get("OFFLINE");
		
		String offlineRecordCachePath = section.get("offlineRecordCachePath");
		
		logger.info("Starting metaOffline");
		Files.mkdir(offlineRecordCachePath);

		DirectoryRecordReader reader =
			new DirectoryRecordReader(offlineRecordCachePath);

		reader.setHandler(handler)(createWorkflow(ini));
		
		return reader;
		*/
	}


	private static FetchRecordTask createOnlineTask(Ini ini)
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
		Section backendSection = ini.get("BACKEND_VIRTUOSO");	
		String dataGraphName = backendSection.get("graphNameData");
		String metaGraphName = backendSection.get("graphNameMeta");
		String uri       = backendSection.get("uri");
		String username  = backendSection.get("username");
		String password  = backendSection.get("password");
			
		
		//Class.forName("virtuoso.jdbc4.Driver").newInstance();
		//Connection con = DriverManager.getConnection(uri, username, password);
		
		ConnectionWrapper connectionWrapper =
			new ConnectionWrapper(uri, username, password);
		
		
		Section extractorSection = ini.get("PROPERTY_DEFINITION_EXTRACTOR");
		String expressionPrefix = extractorSection.get("expressionPrefix");
		String propertyPrefix = extractorSection.get("propertyPrefix");
		String reifierPrefix = extractorSection.get("reifierPrefix");
		
		Section namespaceMappingSection = ini.get("NAMESPACE_MAPPING");
		String filename = namespaceMappingSection.get("filename");

		Section harvesterSection = ini.get("HARVESTER");
		String technicalBaseUri = harvesterSection.get("technicalWikiUri");
		
		/*
		VirtGraph dataGraph = new VirtGraph (graphNameData, uri, username, password);
		ISparulExecutor dataSparulExecutor = new VirtuosoJenaSparulExecutor(dataGraph);

		VirtGraph metaGraph = new VirtGraph (graphNameMeta, uri, username, password);
		ISparulExecutor metaSparulExecutor = new VirtuosoJenaSparulExecutor(metaGraph);
		 */

		/*
		ISparulExecutor dataSparulExecutor =
			new SparulStatisticExecutorWrapper(
					new VirtuosoJdbcSparulExecutorPreconditionWrapper(
						connectionWrapper,
						new VirtuosoJdbcSparulExecutor(dataGraphName)));

		ISparulExecutor metaSparulExecutor =
			new SparulStatisticExecutorWrapper(
					new VirtuosoJdbcSparulExecutorPreconditionWrapper(
							connectionWrapper,
							new VirtuosoJdbcSparulExecutor(metaGraphName)));
		*/
		// Sparul executor with default graph set to null
		ISparulExecutor nullSparulExecutor = 
			new SparulStatisticExecutorWrapper(
					new VirtuosoJdbcSparulExecutorPreconditionWrapper(
							connectionWrapper,
							new VirtuosoJdbcSparulExecutor(null)));

		logger.info("Sending a test query to check TTLP privileges");
		try {
			nullSparulExecutor.insert(new ArrayList<RDFTriple>(), dataGraphName);
		}
		catch(Exception e) {
			logger.fatal(ExceptionUtil.toString(e));
			throw e;
		}
		logger.info("Success");
		
		
		insertSystemTriples(nullSparulExecutor, dataGraphName, metaGraphName);
		
		
		
		// Just for testing... remove this when done.
		//dataSparulExecutor.executeSelect("Select * {?s ?p ?o . Filter(?o = \"Birthplace\") . }");
		
		PrefixResolver prefixResolver = new PrefixResolver(new File(filename));
		//System.out.println(prefixResolver.resolve("rdf:sameAs"));
		//System.exit(0);
		
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

		//classifiedHandler.addHandler(articleHandlerList, "2");
		classifiedHandler.addHandler(articleHandlerList, "200");
		classifiedHandler.addHandler(articleHandlerList, "202");

		classifiedHandler.addHandler(deletionHandlerList, "deleted");

		// Attach the parsers for class and property definitions
		ParseContentRecordHandler parser = new ParseContentRecordHandler();			
		articleHandlerList.handlers().add(parser);
	
		/*
		ComplexGroupTripleManager sys =
			new ComplexGroupTripleManager(dataSparulExecutor, metaSparulExecutor);
		*/
		PropertyDefinitionCleanUpExtractor cleanUp =
			new PropertyDefinitionCleanUpExtractor(
					propertyPrefix,
					dataGraphName,
					metaGraphName,
					nullSparulExecutor);
		
		articleHandlerList.handlers().add(cleanUp);
		
		TBoxExtractor x =
			new TBoxExtractor(
					technicalBaseUri,
					nullSparulExecutor,
					dataGraphName,
					metaGraphName,
					reifierPrefix,
					propertyPrefix,
					expressionPrefix,
					prefixResolver
					);
		articleHandlerList.handlers().add(x);
		deletionHandlerList.handlers().add(x);

		// Set up the extractor, which renames resources when a page is moved
		// This extractor needs to do alot of more work than what is currently
		// implemented - it basically needs to genereate tasks which update
		// all affected wiki pages which reference the resources being renamed
		/*
		RedirectRenameExtractor y =
			new RedirectRenameExtractor(
					nullSparulExecutor,
					metaGraphName,
					new Predicate<String>() {
						@Override
						public boolean evaluate(String arg)
						{
							return arg == null ? null : arg.startsWith("User:DBpedia-Bot/ontology/");
						}
					},
					new Transformer<String, RDFNode>() {

						@Override
						public RDFNode transform(String arg)
						{
							String tmp = arg.substring("User:DBpedia-Bot/ontology/".length());
							return new RDFResourceNode(IRI.create("http://dbpedia.org/ontology/" + tmp));
						}
						
					}
			);
		articleHandlerList.handlers().add(y);
		*/
		
		return handlerList;
	}


	public static void insertSystemTriples(ISparulExecutor executor, String dataGraphName, String metaGraphName)
		throws Exception
	{
		logger.info("Inserting Annotation Properties.");
		
		List<RDFTriple> triples = new ArrayList<RDFTriple>();

		MyVocabulary[] vocabs = {
				MyVocabulary.DC_MODIFIED,
				MyVocabulary.DBM_EDIT_LINK,
				MyVocabulary.DBM_PAGE_ID,
				MyVocabulary.DBM_REVISION,
				MyVocabulary.DBM_OAIIDENTIFIER};
				
		
		for(MyVocabulary item : vocabs) {
			triples.add(
					new RDFTriple(
							new RDFResourceNode(item.getIRI()),
							new RDFResourceNode(OWLRDFVocabulary.RDF_TYPE.getIRI()),
							new RDFResourceNode(OWLRDFVocabulary.OWL_ANNOTATION_PROPERTY.getIRI())));		
		}

		
		List<RDFTriple> metaTriples = new ArrayList<RDFTriple>();
		for(RDFTriple item : triples) {
			URI uri = RDFUtil.generateMD5HashUri("http://dbpedia.org/sysvocab/", item);
			
			RDFResourceNode reifier = new RDFResourceNode(IRI.create(uri));
			
			metaTriples.add(new RDFTriple(
					reifier,
					new RDFResourceNode(OWLRDFVocabulary.OWL_ANNOTATED_SOURCE.getIRI()),
					item.getSubject()));
			metaTriples.add(new RDFTriple(
					reifier,
					new RDFResourceNode(OWLRDFVocabulary.OWL_ANNOTATED_PROPERTY.getIRI()),
					item.getProperty()));
			metaTriples.add(new RDFTriple(
					reifier,
					new RDFResourceNode(OWLRDFVocabulary.OWL_ANNOTATED_TARGET.getIRI()),
					item.getObject()));
			metaTriples.add(new RDFTriple(
					reifier,
					new RDFResourceNode(MyVocabulary.DBM_EXTRACTED_BY.getIRI()),
					new RDFResourceNode(IRI.create(TBoxExtractor.extractorUri))));
		}
		
		executor.insert(metaTriples, metaGraphName);
		executor.insert(triples, dataGraphName);
	}
	
	/*************************************************************************/
	/* Init                                                                  */
	/*************************************************************************/	
	private static Ini loadIni(String filename)
		throws InvalidFileFormatException, IOException
	{
		File file = new File(filename);
		if(!file.exists())
			throw new RuntimeException("Ini file '" + filename + "' not found");
	
		return new Ini(file);
	}


	private static void initLoggers(Ini ini)
	{
		// A hack to get rid of double initialization caused by OAI-Harvester
		new ListRecords();
		Logger.getRootLogger().removeAllAppenders();
		
		Section section = ini.get("LOGGING");

		String log4jConfigFile = section.get("log4jConfigFile");
		
		if(log4jConfigFile != null) {
			System.out.println("Loading log config from file: '" + log4jConfigFile + "'");
			PropertyConfigurator.configure(log4jConfigFile);
		}
		else {
			System.out.println("No log config file specified - using default settings");			
			SimpleLayout layout = new SimpleLayout();
		    ConsoleAppender consoleAppender = new ConsoleAppender(layout);
			Logger.getRootLogger().addAppender(consoleAppender);
		}
		
	}

	
	private static void initOai(Ini ini)
		throws Exception
	{
		Section section = ini.get("HARVESTER");
		
		String username = section.get("username");
		String passwordFile = section.get("passwordFile");
		
		File file = new File(passwordFile);
		String password = (Files.readFile(file)).trim();
		
		authenticate(username, password);
	}

	/*************************************************************************/
	/* Various methods                                                       */
	/*************************************************************************/	
	private static String getStartDateNow()
	{
		return UtcHelper.transformToUTC(System.currentTimeMillis());	
	}
	
	private static String readStartDate(String filename)
	{ 
		File file = new File(filename);
		if(!file.exists())
			return getStartDateNow();
		else {
			try {
				return Files.readFile(file).trim();
			}
			catch(Exception e) {
				logger.warn("Error reading " + filename + " - using current time");
				return getStartDateNow();
			}
		}
	}

	private static void authenticate(final String username, final String password)
	{		
		Authenticator.setDefault(new Authenticator() {
		    @Override
			protected PasswordAuthentication getPasswordAuthentication() {
		        return new PasswordAuthentication(username,
		        								  password.toCharArray());
		    }
		});
	}

	
	/*************************************************************************/
	/* Command line helpers                                                  */
	/*************************************************************************/	
	public static void printHelp()
	{
		HelpFormatter helpFormatter = new HelpFormatter();
		helpFormatter.printHelp("TODO", cliOptions);
	}

	/**
	 * Print usage information to provided OutputStream.
	 */
	public static void printUsage()
	{
		String applicationName = MetaExtractionMain.class.getName();
		
		PrintWriter writer = new PrintWriter(System.out);
		HelpFormatter usageFormatter = new HelpFormatter();
		usageFormatter.printUsage(writer, 80, applicationName, cliOptions);
		writer.close();
	}
}

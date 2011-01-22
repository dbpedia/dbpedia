package main;

import filter.DefaultDbpediaMetadataFilter;
import filter.IFilter;
import helpers.DBPediaXPathUtil;
import helpers.DBpediaQLUtil;
import helpers.ExceptionUtil;
import helpers.XMLUtil;
import helpers.XPathUtil;
import iterator.WikiDumpPageIterator;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.URL;
import java.util.Collections;
import java.util.Iterator;
import java.util.Map;

import oaiReader.AbstractExtraction;
import oaiReader.ConnectionWrapper;
import oaiReader.DeletionRecord;
import oaiReader.Files;
import oaiReader.IHandler;
import oaiReader.IRecord;
import oaiReader.ISimpleConnection;
import oaiReader.OAIReaderMain;
import oaiReader.Record;
import oaiReader.RecordMetadata;
import oaiReader.StatisticWrapperFilter;
import oaiReader.VirtuosoSimpleSparulExecutor;
import oaiReader.WhatLinksHereArticleLoader;

import org.apache.commons.collections15.BidiMap;
import org.apache.commons.collections15.Transformer;
import org.apache.commons.collections15.bidimap.DualHashBidiMap;
import org.apache.commons.compress.compressors.bzip2.BZip2CompressorInputStream;
import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Profile.Section;
import org.w3c.dom.Document;

import sparql.ISparulExecutor;
import transformer.AbstractSafeTransformer;
import transformer.NodeToRecordTransformer;
import virtuoso.jdbc4.VirtuosoException;
import connection.VirtuosoFailSafeSimpleConnection;


class PageNamePageExportTransformer
	extends AbstractSafeTransformer<String, Document>
{
	private String baseWikiId;
	
	private WhatLinksHereArticleLoader loader;
	
	public PageNamePageExportTransformer(String baseWikiId, String oaiPrefix)
	{
		loader = new WhatLinksHereArticleLoader(baseWikiId, oaiPrefix);
	}

	@Override
	public Document _transform(String pageName)
		throws Exception
	{
		return loader.export(pageName);
	}
	
}


public class TaskProcessor
	extends AbstractExtraction
{
	private static final String DEFAULT_INI_FILENAME = "config/en/config.ini";

	private static Logger logger = Logger.getLogger(TaskProcessor.class);
	
	protected Logger getLogger()
	{
		return logger;
	}
	
	
	public static void main(String[] args)
		throws Exception
	{
		TaskProcessor o = new TaskProcessor(args);
		o.run();
		
		// Kill other threads that might be running
		// E.g. there is a task queue thread
		System.exit(0);
	}

	public TaskProcessor(String[] args)
		throws Exception
	{
		super(args, DEFAULT_INI_FILENAME);
	}
	
	private static void testTransferRate(BufferedReader reader)
		throws Exception
	{
		String x;
		int data = 0;
		StopWatch sw = new StopWatch();
		sw.start();
		int del = 0;
		
		int meg = 1024 * 1024;
		
		while((x = reader.readLine()) != null)
		{
			data += x.length() + 1;

			del += x.length() + 1;
			
//System.out.println(x);

			if(del > (meg)) {
				del = 0;

				float mb = data / (float)meg;
				float seconds = sw.getTime() / 1000.0f;
				float hours = seconds / 3600.0f;
				
				float rate = mb / seconds;
				
				String msg =
					"Statistics after " + mb + "MB: \n" +
					"    Elapsed time    (hours)    : " + hours + "\n" +
					"    Avg. throughput (MB/sec): " + rate;
	
				logger.info(msg);
			}
			
		}
		sw.stop();
		reader.close();
	}

	
	private static void checkDump(BufferedReader reader)
		throws Exception
	{
		WikiDumpPageIterator it = new WikiDumpPageIterator(reader);
		
		// Set up wiki namespaces
		BidiMap<Integer, String> namespaces = new DualHashBidiMap<Integer, String>();
		namespaces.put(0, "");
		
		//NodeToRecordTransformer transformer = new NodeToRecordTransformer(baseWikiUri, oaiUri, oaiPrefix)
		
		logger.info("Process started.");
		StopWatch sw = new StopWatch();
		sw.start();
		int i = 0;
		while(it.hasNext()) {
			String content = it.next();
			
			Document doc = XMLUtil.createFromString(content);		

			++i;
			
			
			if(i % 1000 == 0) {
				float seconds = sw.getTime() / 1000.0f;
				float hours = seconds / 3600.0f;
				
				float rate = i / seconds;
				
				String msg =
					"Statistics after " + i + " pages: \n" +
					"    Elapsed time    (hours)    : " + hours + "\n" +
					"    Avg. throughput (pages/sec): " + rate;

				logger.info(msg);
			}
			
			/*
			System.out.println();
			System.out.println();
			System.out.println(XMLUtil.toString(doc));
			System.out.println();
			System.out.println();
			*/
		}
		sw.stop();
		logger.info("Process finished");
	}
	
	
	
	@Override
	public void run()
		throws Exception
	{
		String loggerConfigFileName = "config/TaskProcessor/log4j.properties";
		System.out.println("Switching config to '" + loggerConfigFileName + "'");
		PropertyConfigurator.configure(loggerConfigFileName);
		
		Section exportSection = ini.get("WIKI_EXPORT");
		int delay = Integer.parseInt(exportSection.get("delay")) * 1000;

		logger.info("Delay between consecutive exports in ms: " + delay);
		
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
		
		String oaiPrefix = section.get("oaiPrefix").trim();		
		
		String dirName = "tasks";
		Files.mkdir(dirName);
		
		File dir = new File(dirName);
		if(!dir.isDirectory()) {
			throw new RuntimeException("Must be directory");
		}
		
		
		WhatLinksHereArticleLoader loader =
			new WhatLinksHereArticleLoader(baseWikiUri, oaiPrefix);
		
		Transformer<Document, IRecord> nodeToRecord = new NodeToRecordTransformer(baseWikiUri, oaiUri, oaiPrefix);
		
		IHandler<Record> workflow = OAIReaderMain.getWorkflow(ini);
		IHandler<DeletionRecord> deletionWorkflow = OAIReaderMain.getDeletionWorkflow(ini);

		IFilter<RecordMetadata> metadataFilter = new DefaultDbpediaMetadataFilter(); 		
		StatisticWrapperFilter<RecordMetadata> filterStats = new StatisticWrapperFilter<RecordMetadata>(metadataFilter);

		
		Section taeSection = ini.get("TEMPLATE_ANNOTATION_EXTRACTOR");
		String configFile = taeSection.get("hibernateConfigFile");
		
		Map<String, String> props = OAIReaderMain.getPropertiesFromHibernateXML(new File(configFile));


		Section delSection = ini.get("DELETIONS");
		boolean enabled = Boolean.parseBoolean(delSection.get("enabled"));
		String dataGraphName = delSection.get("graphNameData");
		String metaGraphName = delSection.get("graphNameMeta");

		
		ISimpleConnection simpleConnection = null;
		ISparulExecutor executor = null;
		
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
	
			simpleConnection =
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
			executor = new VirtuosoSimpleSparulExecutor(simpleConnection);
		}		
		
		//PageNamePageExportTransformer pageExporter = new PageNamePageExportTransformer(baseWikiId, oaiPrefix)
		
		// Iterator over all files
		for(File file : dir.listFiles()) {
			BufferedReader reader = new BufferedReader(new FileReader(file));
			
			String line;
			while((line = reader.readLine()) != null) {
				line = line.trim();
				
				logger.info("Processing: " + line);
				
				String[] parts = line.split("\\s+", 2);
				if(parts.length == 0) {
					logger.warn("Ignoring line: " + line);
					continue;
				}
				
				if(parts.length != 2) {
					logger.warn("Invalid number of arguments in line: " + line);
				}
				
				String command = parts[0].trim();
				String pageName = parts[1].trim();
				
				if(command.equalsIgnoreCase("update")) {
				
					
					Thread.sleep(delay);
					Document document = loader.export(pageName);
					
					String text = XPathUtil.evalToString(document, DBPediaXPathUtil.getTextExpr());
					if(text == null || text.trim().equals("")) {
						
						logger.info("Seems deleted: " + pageName);
						
						if(executor != null)
						{
							String sourceResource = "http://dbpedia.org/resource/" + pageName;

							String q1 = DBpediaQLUtil.deleteSubResourcesByPageId(sourceResource, dataGraphName);
							executor.executeUpdate(q1);

							String q2 = DBpediaQLUtil.deleteDataByPageId(sourceResource, dataGraphName);
							executor.executeUpdate(q2);
							
							String qd1 = "DELETE FROM dbpedia_triples WHERE resource = '" + sourceResource + "'";
							simpleConnection.update(qd1);
						}
						
						continue;
					}
					
					
					IRecord record = nodeToRecord.transform(document);
					
					Iterator<IRecord> it = Collections.singleton(record).iterator();
					
					try {
						OAIReaderMain.runWorkflow(it, workflow, metadataFilter, null, filterStats, deletionWorkflow, logger);
					} catch(Throwable t) {
						logger.error(ExceptionUtil.toString(t));
					}
					
				}
				else if(command.equalsIgnoreCase("delete")) {
					String oaiId = parts[1];
					
					IRecord record = new DeletionRecord(oaiId, "");
					
					Iterator<IRecord> it = Collections.singleton(record).iterator();
					OAIReaderMain.runWorkflow(it, workflow, metadataFilter, null, filterStats, deletionWorkflow, logger);
				}				
			}
			
			
			//file.delete();
		}
	}
	
	
	public void testDump()
		throws Exception
	{
		System.out.println("Current Java version is: " + System.getProperty("java.version"));
		//String id = "http://dwarf.local/files/enwiki-2010-02-27-pages-articles.xml";
		String id = "http://dwarf.local/files/enwiki-latest-pages-articles.xml.bz2";
	
		logger.info("Connecting to: " + id);
		URL url = new URL(id);
		InputStream is = url.openStream();
		
		logger.info("    success");
		
		BZip2CompressorInputStream zis = new BZip2CompressorInputStream(is);

		InputStream in = zis;
		
		BufferedReader reader = new BufferedReader(new InputStreamReader(in));

		//testTransferRate(reader);
		checkDump(reader);
	}
	
}

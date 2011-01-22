package oaiReader;
import helpers.DBPediaXMLUtil;
import helpers.XMLUtil;
import oaiReader.handler.record.ArticleRecordFileNameGenerator;
import oaiReader.handler.record.FileWriterRecordHandler;

import org.apache.log4j.Logger;
import org.hibernate.util.XMLHelper;
import org.w3c.dom.Document;


public class TestEncoding
	extends AbstractExtraction
{
	private static final Logger logger = Logger.getLogger(TestEncoding.class);
	
	protected Logger getLogger()
	{
		return logger;
	}

	public TestEncoding()
		throws Exception
	{
		super(new String[0], "config/en/config.ini");
		// TODO Auto-generated constructor stub
	}

//	private static Logger logger = Logger.getLogger(TestEncoding.class);
	
	

	public static void main(String[] args)
		throws Exception
	{
		TestEncoding x = new TestEncoding();
		
		if(args.length < 1) {
			System.out.println("Missing article name");
			return;
		}
		
		String articleName = args[0];
		
		System.out.println("Downloading article: " + articleName);
		x.downloadSingleArticle(articleName);		
	}

	
	private void downloadSingleArticle(String name)
		throws Exception
	{
		String baseUri = "http://en.wikipedia.org/";
		String baseWikiUri = "http://en.wikipedia.org/wiki/";
		String oaiPrefix = "oai:en.wikipedia.org:enwiki:";
		String oaiUri = "http://en.wikipedia.org/wiki/Special:OAIRepository";
		String directory = "oairecords";
		
		WhatLinksHereArticleLoader loader = new WhatLinksHereArticleLoader(baseWikiUri, oaiPrefix);
		
		Document document = loader.export(name);
		System.out.println(XMLUtil.toString(document));
		
		
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

	@Override
	protected void run()
		throws Exception
	{
	}
}

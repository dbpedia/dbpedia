package oaiReader;

import helpers.ExceptionUtil;
import helpers.XMLUtil;

import org.apache.log4j.Logger;
import org.semanticweb.owlapi.model.IRI;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import ORG.oclc.oai.harvester2.verb.ListRecords;
import filter.IFilter;


/**
 * This task attempt to fetch data from an oai repository, starting from a
 * given date.
 * 
 * This task uses the mediawiki metadataprefix which means that text is
 * also retrieved.
 * 
 * @author raven_arkadon
 *
 */
public class FetchRecordTask
	implements Runnable, IProducer<Record>
{
	private Logger logger = Logger.getLogger(FetchRecordTask.class);

	// This is the new handler method, the others are deprecated
	private IHandler<IRecord> recordHandler;
	
	// FIXME: handler should be renamed to something like
	// "newOrModifiedRecordHandler"
	private IHandler<Record> handler; 
	
	private IHandler<DeletionRecord> deletionHandler;
	
	private IFilter<RecordMetadata> metadataFilter;
	

	
	// The base uri cannot be obtained from listRecords-metadataprefix=mediawiki 
	private String wikiBaseUri;
	// The language also needs to be given :/ - defaults to en
	//private String language = "en";
	
	private String oaiBaseUri;
	private String lastUTCresponseDate;

	private int sleepInterval = 0;
	
	public int getSleepInterval()
	{
		return sleepInterval;
	}
	
	public void setSleepInterval(int sleepInterval)
	{
		this.sleepInterval = sleepInterval;
	}

	FetchRecordTask(String oaiBaseUri, String wikiBaseUri, String startTime)
	{
		this.oaiBaseUri = oaiBaseUri;
		this.wikiBaseUri = wikiBaseUri;
		this.lastUTCresponseDate = startTime;
		
		// Early out in case of error
		if(oaiBaseUri == null || wikiBaseUri == null)
			throw new NullPointerException();
	}
	
	public void setRecordHandler(IHandler<IRecord> recordHandler)
	{
		this.recordHandler = recordHandler;
	}
	
	public IHandler<IRecord> getRecordHandler()
	{
		return recordHandler;
	}
	
	public void setHandler(IHandler<Record> handler)
	{
		this.handler = handler;
	}
	
	public IHandler<Record> getHandler()
	{
		return handler;
	}
	
	public void setDeletionHandler(IHandler<DeletionRecord> deletionHandler)
	{
		this.deletionHandler = deletionHandler;
	}
	
	public IHandler<DeletionRecord> getDeletionHandler()
	{
		return deletionHandler;
	}
	
	public String getLastUTCresponseDate()
	{
		return lastUTCresponseDate;
	}
	
	public IFilter<RecordMetadata> getMetadataFilter()
	{
		return metadataFilter;
	}
	
	public void setMetadataFilter(IFilter<RecordMetadata> metadataFilter)
	{
		this.metadataFilter = metadataFilter;
	}
	
	public void run()
	{
		logger.debug("Task started");
		
		try {
			fetchRecords();
		} catch(Exception e) {
			logger.warn(ExceptionUtil.toString(e));
		}

		StatisticWrapperFilter o =
			(StatisticWrapperFilter) metadataFilter.getNestedFilter(StatisticWrapperFilter.class);
		if(o != null)
			logger.info("Task finished: " + o.toString());
		else
			logger.debug("Task finished");
	}
	
	private void fetchRecords()
		throws Exception
	{
		// Keep fetching record information from the oia repository until there
		// is no more - indicated by an empty resumption token
		ListRecords currentRecordList=null;
		Document currentDoc = null;
		String resumptionToken = null;
		do {
			// Note: We crash if ListRecords fail.
			if(resumptionToken == null) {
				currentRecordList =
					new ListRecords(this.oaiBaseUri,
									this.lastUTCresponseDate,
									null,
									null,
									"mediawiki");
		
				//System.out.println(currentRecord.getRequestURL());
			} else {
				currentRecordList = new ListRecords(oaiBaseUri, resumptionToken);
			}
			logger.debug("executed: "+ currentRecordList.getRequestURL());
			
			
			resumptionToken = currentRecordList.getResumptionToken();
			currentDoc = currentRecordList.getDocument(); 

			// TODO: Issue errors as notice
			// Also differ between lethal and non lethal errors
			if(currentRecordList.getErrors().getLength() > 0) {
				logger.debug("OAI-Notice: " + XMLUtil.toString(currentRecordList.getErrors()));
				break;
			}

			try {				
				extractRecordMetadata(currentDoc);
			}
			catch(Throwable e) {
				logger.warn(ExceptionUtil.toString(e));
			}
			
			if(resumptionToken != null && !resumptionToken.trim().isEmpty()) {
				logger.debug("Fetching more entries in " + sleepInterval + " seconds");
				Thread.sleep(sleepInterval * 1000);
			}

			// Update responseDate
			this.lastUTCresponseDate = currentDoc.getElementsByTagName("responseDate").item(0).getTextContent();

		} while(resumptionToken != null && resumptionToken.length() > 0);
	}

	
	
	private String getText(Element element, String tagName)
	{
		NodeList nodes = element.getElementsByTagName(tagName);
		if(nodes.getLength() == 0)
			return "";

		return nodes.item(0).getTextContent();
	}

	/**
	 * Extracts records from an xml document created with
	 * metadataprefix=mediawiki
	 * 
	 * 
	 * @param retMap
	 * @param doc
	 */
    private void extractRecordMetadata(Document doc)
    {
		String oaiId = "";
		IRI    wikiUri = null;
		String title = "";
		String text = "";
		String revision = "";
		String language = "";
		String username = "";
		String userIp   = "";
		String userId   = "";
		
		Element meta = (Element)doc.getElementsByTagName("mediawiki").item(0);
		
		if(null != meta)
			language = meta.getAttribute("xml:lang");
		
		
		NodeList recordList = doc.getElementsByTagName("record");
		for (int i = 0; i < recordList.getLength(); i++) {
			try {
				// Check for records about deleted items
				Element oneRecord = (Element)recordList.item(i);
				
				// Extract oaiId (onRecord is the corrent node)
				oaiId = oneRecord.getElementsByTagName("identifier").item(0).getTextContent();
				
				
				// Treat deleted records
				if(isDeleted(oneRecord)) {
					String dateStamp = oneRecord.getElementsByTagName("datestamp").item(0).getTextContent();
					
					DeletionRecord deletionRecord =
						new  DeletionRecord(oaiId, dateStamp);
					
					if(deletionHandler != null)
						deletionHandler.handle(deletionRecord);
					
					if(recordHandler != null)
						recordHandler.handle(deletionRecord);
					
					continue;
				}

				Element pageElement =
					(Element)oneRecord.getElementsByTagName("page").item(0);
				
				// Extract title and check against a filter
				title = pageElement.getElementsByTagName("title").item(0).getTextContent();
				
				// FIXME: is it ok to replace spaces with underscores in
				// the title?
				title = title.replace(' ', '_');

				// combine wikiBaseUri and Title to a wikiUri
				wikiUri = IRI.create(wikiBaseUri + title);

				MediawikiTitle mediawikiTitle = MediawikiHelper.parseTitle(oaiBaseUri, title);

				// Extract title, revision and text
				Element revisionElement =
					(Element)pageElement.getElementsByTagName("revision").item(0);
				
				
				revision = revisionElement.getElementsByTagName("id").item(0).getTextContent();
				text = pageElement.getElementsByTagName("text").item(0).getTextContent();

				Element contributorElement = 
					(Element)pageElement.getElementsByTagName("contributor").item(0);

		        username = getText(contributorElement, "username");
		        userIp = getText(contributorElement, "ip");
		        userId = getText(contributorElement, "id");

		        /*
		        System.out.println("username = " + username);
		        System.out.println("ip = " + userIp);
		        System.out.println("id = " + userId);
				*/
				
				RecordMetadata metadata =
					new RecordMetadata(
							language,
							mediawikiTitle,
							oaiId,
							wikiUri,
							revision,
							username,
							userIp,
							userId);

				if(metadataFilter != null && !metadataFilter.evaluate(metadata))
				{
					logger.trace("Rejected Title: " + title);
					continue;
				}
				logger.debug("Accepted Title: " + title);
				
				
				RecordContent content = new RecordContent(text, revision, "");

				Record record = new Record(metadata, content);
				
				if(this.handler != null)
					this.handler.handle(record);

				if(recordHandler != null)
					recordHandler.handle(record);
				
			}catch (Throwable e) {
				logger.warn("Exception thrown while processing: " +
						oaiId + " | " + wikiBaseUri + "\n" +
						ExceptionUtil.toString(e));					
			}
		}//end for
	}
	

	private boolean isDeleted(Element oneRecord)
	{
		Element header = (Element)(oneRecord.getElementsByTagName("header").item(0));
		return (header.getAttribute("status").equalsIgnoreCase("deleted"));
	}
}

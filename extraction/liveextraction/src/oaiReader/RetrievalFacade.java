package oaiReader;

import java.util.HashSet;
import java.util.Set;

import org.apache.log4j.Logger;
import org.semanticweb.owlapi.model.IRI;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import ORG.oclc.oai.harvester2.verb.GetRecord;
import ORG.oclc.oai.harvester2.verb.ListRecords;
import filter.IFilter;


/**
 * This task attempt to fetch data from an oai repository, starting from a
 * given date.
 * 
 * @author raven_arkadon
 *
 */
class FetchRecordMetadataTask
	implements Runnable
{
	private Logger logger = Logger.getLogger(FetchRecordMetadataTask.class);
	
	private IHandler<RecordMetadata> handler; 
	private IFilter<RecordMetadata> metadataFilter;
	
	private String oaiBaseUri;
	private String lastUTCresponseDate;
	
	FetchRecordMetadataTask(String oaiBaseUri, String startTime)
	{
		this.oaiBaseUri = oaiBaseUri;
		this.lastUTCresponseDate = startTime;
	}
	
	public void setHandler(IHandler<RecordMetadata> handler)
	{
		this.handler = handler;
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
		logger.debug("FetchRecordMetadataTask started");
		
		try {
			fetchRecordMetadata();
		} catch(Exception e) {
			e.printStackTrace();
		}

		logger.debug("FetchRecordMetadataTask finished");
	}
	
	private void fetchRecordMetadata()
		throws Exception
	{
		// Keep fetching record information from the oia repository until there
		// is no more - indicated by an empty resumption token
		ListRecords currentRecordList=null;
		Document currentDoc = null;
		String resumptionToken = null;
		do {
			if(resumptionToken == null) {
				currentRecordList =
					new ListRecords(this.oaiBaseUri,
									this.lastUTCresponseDate,
									null,
									null,
									"oai_dc");
				
				//System.out.println(currentRecord.getRequestURL());
			} else {
				currentRecordList = new ListRecords(oaiBaseUri, resumptionToken);
			}
						
			logger.debug("executed: "+ currentRecordList.getRequestURL());
			
			resumptionToken = currentRecordList.getResumptionToken();
			currentDoc = currentRecordList.getDocument(); 
		
			extractRecordMetadata(currentDoc);			
		} while(resumptionToken != null && resumptionToken.length() > 0);
		
		
		// Backup value for log-message
		//String from = this.lastUTCresponseDate;
		
		// Update responseDate - note: currentDoc refers to the last seen doc
		this.lastUTCresponseDate = currentDoc.getElementsByTagName("responseDate").item(0).getTextContent();
	}

	/**
	 * Extracts record metadata from an xml document.
	 * 
	 * 
	 * @param retMap
	 * @param doc
	 */
    private Set<RecordMetadata> extractRecordMetadata(Document doc)
    {
		Set<RecordMetadata> resultSet = new HashSet<RecordMetadata>();

		String oaiId = "";
		String language = "";
		IRI    wikipediaIRI = null;
		String title = "";
		
		try{
			NodeList recordList = doc.getElementsByTagName("record");
			for (int i = 0; i < recordList.getLength(); i++) {
				try{
					// Check for records about deleted items
					Element oneRecord = (Element)recordList.item(i);
					if(isDeleted(oneRecord))
						continue;

					// The dc:identifier field of the record corresponds to
					// the IRI of the (wiki) page.
					Element oai_dc = (Element)oneRecord.getElementsByTagName("oai_dc:dc").item(0);
					title = oai_dc.getElementsByTagName("dc:title").item(0).getTextContent();
					
					// Extract more fields...
					wikipediaIRI = IRI.create(oai_dc.getElementsByTagName("dc:identifier").item(0).getTextContent());
					oaiId    =  oneRecord.getElementsByTagName("identifier").item(0).getTextContent();
					language =  oai_dc.getElementsByTagName("dc:language").item(0).getTextContent();

					
					// FIXME: is it ok to replace spaces with underscores in
					// the title?
					title = title.replace(' ', '_');
					
					MediawikiTitle mediawikiTitle = MediawikiHelper.parseTitle(oaiBaseUri, title);
					
					// ... and store into an object
					RecordMetadata metadata =
						new RecordMetadata(language, mediawikiTitle, oaiId, wikipediaIRI, "unknown", "", "", "");

					// Pass that IRI to a filter
					if(metadataFilter != null && !metadataFilter.evaluate(metadata))
					{
						logger.trace("Rejected Title: " + title);
						continue;
					}
					logger.debug("Accepted Title: " + title);
					
					
					if(this.handler != null)
						this.handler.handle(metadata);				
				}catch (Exception e) {
					logger.warn("Exception in: Retrieval.extractRecords, in loop"+e.toString());
					logger.warn(oaiId+"|"+wikipediaIRI);
					
				}
			}//end for
		}catch (Exception e) {
			logger.warn("Exception in: Retrieval.extractRecords, document does not contain element record "+e.toString());
			logger.warn(oaiId+"|"+wikipediaIRI);
		}
		
		return resultSet;
	}
	

	private boolean isDeleted(Element oneRecord)
	{
		Element header = (Element)(oneRecord.getElementsByTagName("header").item(0));
		return (header.getAttribute("status").equalsIgnoreCase("deleted"));
	}
}




public class RetrievalFacade
{
	private static Logger logger = Logger.getLogger(RetrievalFacade.class);

	private final String oaiBaseIRI;
	private String defaultStartTime;

	
	@Deprecated
	private String lastUTCresponseDate;

	public RetrievalFacade(String oaiBaseIRI)
	{
		this.oaiBaseIRI = oaiBaseIRI;
	}

	public RetrievalFacade(String oaiBaseIRI, String defaultStartTime)
	{
		this.oaiBaseIRI = oaiBaseIRI;
		this.defaultStartTime = defaultStartTime;
	}
	
	public String getDefaultStartTime()
	{
		return this.defaultStartTime;
	}
	
	
	public FetchRecordMetadataTask newFetchRecordMetadataTask()
	{
		return new FetchRecordMetadataTask(oaiBaseIRI, defaultStartTime);
	}
	
	public FetchRecordMetadataTask newFetchRecordMetadataTask(String startTime)
	{
		return new FetchRecordMetadataTask(oaiBaseIRI, startTime);
	}
	
	public FetchRecordTask newFetchRecordTask(String wikiBaseUri)
	{
		return new FetchRecordTask(oaiBaseIRI, wikiBaseUri, defaultStartTime);
	}
	
	public FetchRecordTask newFetchRecordTask(String wikiBaseUri, String startTime)
	{
		return new FetchRecordTask(oaiBaseIRI, wikiBaseUri, startTime);
	}

	
	
	/**
	 * Retrieves the content of an record. 
	 * To be exact: retrieves revision, text and xml 
	 * 
	 * @param rec
	 * @throws Exception
	 */
	public RecordContent fetchRecordContent(String oaiId)
		throws Exception
	{
		GetRecord getRecord = null;
		RecordContent resultContent = new RecordContent();

		try {
			// Fetch data from the oai repository
		    getRecord = new GetRecord(this.oaiBaseIRI, oaiId, "mediawiki");
			
			testNull(getRecord, "getRecord");
			logger.debug("executed: "+getRecord.getRequestURL());
			
			// Extract the document
			Document doc = getRecord.getDocument();
			testNull(doc, "doc");
			
			Element rev = (Element)(doc.getElementsByTagName("revision").item(0));
			testNull(rev, "revision");
			String revision = rev.getElementsByTagName("id").item(0).getTextContent();
			
			String text = doc.getElementsByTagName("text").item(0).getTextContent();
			testNull(text, "wikiSource");
			
			// Fill in the result
			resultContent.setText(text);
			resultContent.setRevision(revision);
			resultContent.setXml(doc.toString());
			
			logger.trace("Retrieved source for: "+oaiId);
		} catch (Exception e) {
			logger.warn("error in getRecord");
			logger.warn(""+getRecord.getRequestURL());
			logger.warn("getRecord\n"+getRecord);
		}
		
		return resultContent;
	}


	/**
	 * Fetches (xml) documents about record-metadata from the oai repository.
	 * For each document, the records are then extracted.
	 * No content is fetched.
	 * 
	 * @return
	 * @throws Exception
	 */
	@Deprecated
	public Set<RecordMetadata> fetchRecordMetadata()
		throws Exception
	{
		Set<RecordMetadata> resultSet = new HashSet<RecordMetadata>();
			
		// Keep fetching record information from the oia repository until there
		// is no more - indicated by an empty resumption token
		ListRecords currentRecordList=null;
		Document currentDoc = null;
		String resumptionToken = null;
		do {
			if(resumptionToken == null) {
				currentRecordList =
					new ListRecords(this.oaiBaseIRI,
									this.lastUTCresponseDate,
									null,
									null,
									"oai_dc");
				
				//System.out.println(currentRecord.getRequestURL());
			} else {
				currentRecordList = new ListRecords(oaiBaseIRI, resumptionToken);
			}
						
			logger.debug("executed: "+ currentRecordList.getRequestURL());
			
			resumptionToken = currentRecordList.getResumptionToken();
			currentDoc = currentRecordList.getDocument();
			Set<RecordMetadata> fragmentSet = extractRecordMetadata(currentDoc);
			
			resultSet.addAll(fragmentSet);
			
		} while(resumptionToken != null && resumptionToken.length() > 0);
		
		
		// Backup value for log-message
		String from = lastUTCresponseDate;
		
		// Update responseDate - note: currentDoc refers to the last seen doc
		this.lastUTCresponseDate = currentDoc.getElementsByTagName("responseDate").item(0).getTextContent();
		
		logger.info("finished ListRecords ("+resultSet.size()+" records ) \n" +
				"from: "+from+"\n" +
				"until: "+lastUTCresponseDate+"\n");
				//"diff: "+OAIReaderMainOld.timeDiffUTC(lastUTCresponseDate, from)+" seconds");
		return resultSet;
	}


	/**
	 * A packet-level visibility helper function.
	 * Extracts record metadata from an xml document.
	 * 
	 * 
	 * @param retMap
	 * @param doc
	 */
	@Deprecated
	Set<RecordMetadata> extractRecordMetadata(Document doc) {
		Set<RecordMetadata> resultSet = new HashSet<RecordMetadata>();

		String oaiId = "";
		String language = "";
		IRI    wikipediaIRI = null;
		String title = "";
		
		try{
			NodeList recordList = doc.getElementsByTagName("record");
			for (int i = 0; i < recordList.getLength(); i++) {
				try{
					// Check for records about deleted items
					Element oneRecord = (Element)recordList.item(i);
					if(isDeleted(oneRecord))
						continue;

					// The dc:identifier field of the record corresponds to
					// the IRI of the (wiki) page.
					Element oai_dc = (Element)oneRecord.getElementsByTagName("oai_dc:dc").item(0);
					wikipediaIRI = IRI.create(oai_dc.getElementsByTagName("dc:identifier").item(0).getTextContent());

					// Pass that IRI to a filter
					if(!isAllowed(wikipediaIRI.toString()))
						continue;
					
					// Extract more fields...
					oaiId    =  oneRecord.getElementsByTagName("identifier").item(0).getTextContent();
					title    =  oai_dc.getElementsByTagName("dc:title").item(0).getTextContent();
					language =  oai_dc.getElementsByTagName("dc:language").item(0).getTextContent();
					
					MediawikiTitle mediawikiTitle = MediawikiHelper.parseTitle(oaiBaseIRI, title);
					
					// ... and store into an object
					RecordMetadata recordMetadata =
						new RecordMetadata(language, mediawikiTitle, oaiId, wikipediaIRI, "unknown", "", "", "");
					
					resultSet.add(recordMetadata);//put(wikipediaIRI, rec);
					//System.out.println("added");
				
				
				}catch (Exception e) {
					logger.warn("Exception in: Retrieval.extractRecords, in loop"+e.toString());
					logger.warn(oaiId+"|"+wikipediaIRI);
					
				}
			}//end for
		}catch (Exception e) {
			logger.warn("Exception in: Retrieval.extractRecords, document does not contain element record "+e.toString());
			logger.warn(oaiId+"|"+wikipediaIRI);
		}
		
		return resultSet;
	}
	

	@Deprecated
	private boolean isDeleted(Element oneRecord) {
		Element header = (Element)(oneRecord.getElementsByTagName("header").item(0));
		return (header.getAttribute("status").equalsIgnoreCase("deleted"));
	}
	
	@Deprecated
	private boolean isAllowed(String wikiPage){
		if(true)
			return true;
		
		String ns = "http://en.wikipedia.org/wiki/";
		String[] forbidden = new String[]{
				"Talk",
				"User",
				"User_talk",
				"Wikipedia",
				"Wikipedia_talk",
				"File",
				"File_talk",
				"MediaWiki",
				"MediaWiki_talk",
				//"Template",
				//"Template_talk",
				"Help",
				"Help_talk",
				"Category",
				"Category_talk",
				"Portal",
				"Portal_talk"};

		if(wikiPage.startsWith(ns+"Template")){
			return true;
		}
		
		//if(true)
		//	return false;
		
		if(wikiPage.startsWith(ns+"User_talk:DBpedia")){
			return true;
		}

		System.out.println("Wikipage = " + wikiPage);

		for (int i = 0; i < forbidden.length; i++) {
			if(wikiPage.startsWith(ns+forbidden[i]+":")){
				return false;
			}
		}
		
		return true;
	}
	
	private void testNull(Object o, String s){
		if(o==null){
			logger.warn("tested for null: "+s);
		}

	}
	
	/*<record>
−
<header>
<identifier>oai:en.wikipedia.org:enwiki:126689</identifier>
<datestamp>2009-01-06T00:00:01Z</datestamp>
</header>
−
<metadata>
−
<oai_dc:dc xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
<dc:title>East Garden City, New York</dc:title>
<dc:language>en</dc:language>
<dc:type>Text</dc:type>
<dc:format>text/html</dc:format>
−
<dc:identifier>
http://en.wikipedia.org/wiki/East_Garden_City,_New_York
</dc:identifier>
<dc:contributor>Acntx</dc:contributor>
<dc:date>2009-01-06T00:00:01Z</dc:date>
</oai_dc:dc>
</metadata>
</record>*/
	
	
}

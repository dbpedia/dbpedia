package statistic;

import filter.DefaultDbpediaMetadataFilter;
import helpers.OAIUtil;
import iterator.DuplicateOAIRecordRemoverIterator;
import iterator.XPathQueryIterator;

import java.io.Serializable;
import java.io.StringReader;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Comparator;
import java.util.Date;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Set;

import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpression;
import javax.xml.xpath.XPathFactory;

import oaiReader.AbstractExtraction;
import oaiReader.IHandler;
import oaiReader.IRecord;
import oaiReader.Record;
import oaiReader.RecordMetadata;
import oaiReader.WhatLinksHereArticleLoader;

import org.apache.commons.collections15.iterators.TransformIterator;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

import transformer.CastTransformer;
import transformer.NodeToDocumentTransformer;
import collections.IDistanceFunc;
import collections.PersistentQueue;
import collections.TimeStampSet;



class DateIdentifierTitle
	implements Serializable
{
	/**
	 * 
	 */
	private static final long	serialVersionUID	= -5721674722158156959L;

	private Date				date;
	private String				identifier;
	private String              title;
	
	public DateIdentifierTitle(Date date, String identifier, String title)
	{
		this.date = date;
		this.identifier = identifier;
		this.title = title;
	}

	public Date getDate()
	{
		return date;
	}

	public void setDate(Date date)
	{
		this.date = date;
	}

	public String getIdentifier()
	{
		return identifier;
	}

	public void setIdentifier(String identifier)
	{
		this.identifier = identifier;
	}

	public String getTitle()
	{
		return title;
	}
	
	public void setTitle(String title)
	{
		this.title = title;
	}
	
	@Override
	public String toString()
	{
		return date + ": " + identifier + ": " + title;
	}

}


public class EditStatisticExtraction
	extends AbstractExtraction
{
	private static final String	DEFAULT_INI_FILENAME	= "config/en/config.ini";

	private static final Logger logger = Logger.getLogger(EditStatisticExtraction.class);
	
	protected Logger getLogger()
	{
		return logger;
	}
	
	public static void main(String[] args)
		throws Exception
	{
		EditStatisticExtraction extraction = new EditStatisticExtraction(args);
		extraction.run();
	}
	

	public EditStatisticExtraction(String[] args)
		throws Exception
	{
		super(args, DEFAULT_INI_FILENAME);
	}

	@Override
	public void run()
		throws Exception
	{
		System.out.println("STARTING");
		//generateDataset(ini);
		readDataPreprocessed(0, false);
		// testPersistentQueue();
		// runOnline(ini);
	}

	// metawiki extraction process
	private void generateDataset(Ini ini)
		throws Exception
	{
		// Begin ini section
		Section section = ini.get("HARVESTER");

		String lastResponseDateFile = section.get("lastResponseDateFile");
		String startNow = section.get("startNow");

		String startDate;
		if (startNow.equalsIgnoreCase("true"))
			startDate = getStartDateNow();
		else
			startDate = readStartDate(lastResponseDateFile);

		// startDate = "2009-09-30T01:53:29Z";

		String baseWikiUri = section.get("baseWikiUri");

		String oaiUri = baseWikiUri + "Special:OAIRepository";
		int sleepInterval = Integer.parseInt(section.get("sleepInterval"));
		// End ini section
		/*
		 * Section section = ini.get("HARVESTER"); int pollInterval =
		 * Integer.parseInt(section.get("pollInterval")); String
		 * lastResponseDateFile = section.get("lastResponseDateFile") .trim();
		 */

		// Thanks to <http://jcooney.net/archive/2005/08/09/6517.aspx>
		// i finally got the xpath working (although its a bit hacked)
		// Since deletion information is kept in the record node, we
		// can't use the mediawiki node
		XPath xpath = XPathFactory.newInstance().newXPath();
		XPathExpression expr = xpath.compile("//*[local-name()='record']");

		/**
		 * The first iterator fetched documents from the oai repo The second
		 * iterator iterates over the records (nodes) The third iterator
		 * transforms each node into a document again
		 */
		/*
		 * Iterator<Document> itOai = new OAIRecordIterator(oaiUri, startDate);
		 */

		// The endless iterator does not stop end when there is no
		// resumption token - it blocks unitl new data becomes
		// available
		Iterator<Document> itOai = OAIUtil.createEndlessIterator(oaiUri,
				startDate, 30000, 5000, null);

		Iterator<Node> nodeIterator = new XPathQueryIterator(itOai, expr);
		Iterator<Document> dirtyRecordIterator = new TransformIterator<Node, Document>(
				nodeIterator, new NodeToDocumentTransformer());

		
		Iterator<Document> recordIterator = new DuplicateOAIRecordRemoverIterator(dirtyRecordIterator);
		
		PersistentQueue queue = new PersistentQueue("DateIdentifierList.dat");
		queue.clear();

		/**
		 * Note that entries are sorted by date asc already
		 * 
		 */
		DateFormat dateFormat = new SimpleDateFormat("yyyy-mm-dd'T'HH:mm:ss'Z'");
		DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();
		int c = 0;
		while (recordIterator.hasNext()) {
			Document document = recordIterator.next();

			Record record = WhatLinksHereArticleLoader.extractRecord(document,
					"http://en.wikipedia.org", "oai:");
			RecordMetadata metadata = record.getMetadata();

			if (!filter.evaluate(metadata)) {
				System.out.println("Rejected: "
						+ metadata.getTitle().getFullTitle());
				continue;
			}
			else
				System.out.println("Accepted: "
						+ metadata.getTitle().getFullTitle());
			
			
			String identifier = WhatLinksHereArticleLoader
					.evalToSTring(xpath, document,
							"//*[local-name()='header']/*[local-name()='identifier']/text()");

			String timestamp = WhatLinksHereArticleLoader
					.evalToSTring(xpath, document,
							"//*[local-name()='header']/*[local-name()='datestamp']/text()");

			Date date = dateFormat.parse(timestamp);
			DateIdentifierTitle item = new DateIdentifierTitle(date, identifier, metadata.getTitle().getFullTitle());
			
			System.out.println("#" + c + ": " + item);

			queue.push(item);
			++c;
		}

		// readDataSet();
	}

	private void readDataPreprocessed(int timeWindow, boolean renewal)
		throws Exception
	{
		PersistentQueue queue = new PersistentQueue("data/DateIdentifierListNov3.dat");
		Iterator<Object> x = queue.iterator();
		Iterator<DateIdentifierTitle> it = new TransformIterator<Object, DateIdentifierTitle>(
				x, new CastTransformer<Object, DateIdentifierTitle>());

		TimeStampSet<String, Date, Long> set = createSet(timeWindow, renewal);

		System.out.println("Objects in queue: " + queue.getObjectCount());

		Thread.sleep(5000);
		Set<String> allIdentifiers = new HashSet<String>();
		
		int savedEdits = 0;
		int counter = 0;
		int acceptCounter = 0;
		
		Date startDate = null;
		while (it.hasNext()) {

			DateIdentifierTitle item = it.next();

			// Oh, our dataset was already generated with filtering
			// only the main namespace
			//MediawikiTitle t = MediawikiHelper.parseTitle("http://en.wikipedia.org/w/api.php", item.getTitle());
			/*
			MediawikiTitle t = MediawikiHelper.parseTitle2("http://en.wikipedia.org/wiki/", item.getTitle());
			
			int namespaceId = t.getNamespaceId();
			
			if(namespaceId != 0)
				continue;
			*/	
			
			++counter;

			if(startDate == null)
				startDate = item.getDate();

			long delta = item.getDate().getTime() - startDate.getTime();
			float ddd = delta / 1000.0f;
			//System.out.println();
			
			set.setCurrentTime(item.getDate());

			//System.out.println(item);
//System.exit(0);
			
			// If the identifier already exists, we saved an edit
			Date seenTime = set.getKeyTime(item.getIdentifier());
			if (seenTime != null) {
				++savedEdits;
				//System.out.println("'" + item.getIdentifier() + "' first seen on " + seenTime);
			}
			else {
				++acceptCounter;
				set.add(item.getIdentifier());
			}

			
			allIdentifiers.add(item.getIdentifier());
			
			if(counter % 1000 == 0)
			//if(counter == 10000)
			//	break;
			System.out
					.println(item.getDate() + ": Total = " + counter +
							"Accepted = " + acceptCounter +
							" Saved = "
							+ savedEdits + " Ratio = "
							+ (savedEdits / (float) counter) +
							" " + "Distinct ids = " + allIdentifiers.size() + 
							" Total /s: " + counter / ddd +
							" Accepted /s: " + acceptCounter / ddd);

		}
	}

	private void readDatasetDocument(int timeWindow, boolean renewal)
		throws Exception
	{
		PersistentQueue queue = new PersistentQueue("queue.dat");
		Iterator<Object> x = queue.iterator();
		Iterator<Document> it = new TransformIterator<Object, Document>(x,
				new CastTransformer<Object, Document>());

		System.out.println("Objects in queue: " + queue.getObjectCount());

		TimeStampSet<String, Date, Long> map = createSet(timeWindow, renewal);

		calcEditStats(it, 5, renewal);
	}

	public void calcEditStats(Iterator<Document> it, int timeWindow, boolean renewal)
		throws Exception
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		DateFormat dateFormat = new SimpleDateFormat("yyyy-mm-dd'T'HH:mm:ss'Z'");

		TimeStampSet<String, Date, Long> set = createSet(timeWindow, renewal);

		DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();

		int savedEdits = 0;
		int counter = 0;
		while (it.hasNext()) {

			Document document = it.next();

			Record record = WhatLinksHereArticleLoader.extractRecord(document,
					"http://en.wikipedia.org", "oai:");
			RecordMetadata metadata = record.getMetadata();

			if (!filter.evaluate(metadata)) {
				System.out.println("Rejected: "
						+ metadata.getTitle().getFullTitle());
				continue;
			}
			else
				System.out.println("Accepted: "
						+ metadata.getTitle().getFullTitle());
			// IClassifier<Record, String> classifier = new
			// PageTypeRecordClassifier<Record>();
			// classifier.classify(record);

			String timestamp = WhatLinksHereArticleLoader
					.evalToSTring(xpath, document,
							"//*[local-name()='header']/*[local-name()='datestamp']/text()");
			String identifier = WhatLinksHereArticleLoader
					.evalToSTring(xpath, document,
							"//*[local-name()='header']/*[local-name()='identifier']/text()");

			// System.out.println(timestamp);
			Date date = dateFormat.parse(timestamp);

			set.setCurrentTime(date);

			System.out.println(date + ": " + identifier);

			// If the identifier already exists, we saved an edit
			Date seenTime = set.getKeyTime(identifier);
			if (seenTime != null) {
				++savedEdits;
				System.out.println("'" + identifier + "' first seen on "
						+ seenTime);
			}
			else
				set.add(identifier);

			System.out
					.println(date + ": Total = " + counter + " Saved = "
							+ savedEdits + " Ratio = "
							+ (savedEdits / (float) counter));

			/*
			 * System.out.println(timestamp + " " + identifier);
			 * WhatLinksHereArticleLoader.printDom(document);
			 * System.out.println(); System.out.println(); System.out.println();
			 */
			++counter;
		}

		// For our statistic dataset we need:
		// page-id, title and timestamp

		// (If an article gets deleted, we only get his page-id)

		/*
		 * System.out.println("FOUND ITEM");
		 * WhatLinksHereArticleLoader.printDom(document);
		 * System.out.println(""); System.out.println("");
		 * System.out.println(""); System.out.println("");
		 */
	}

	private TimeStampSet<String, Date, Long> createSet(int minutes, boolean allowRenewal)
	{
		// compute in ms
		long maxDistance = minutes * 60 * 1000;

		return new TimeStampSet<String, Date, Long>(
				new IDistanceFunc<Date, Long>() {
					@Override
					public Long distance(Date a, Date b)
					{
						return b.getTime() - a.getTime();
					}
				}, maxDistance, new Comparator<Long>() {
					@Override
					public int compare(Long a, Long b)
					{
						return a.compareTo(b);
					}
				}, false, allowRenewal);
	}

	private void testPersistentQueue()
		throws Exception
	{
		PersistentQueue queue = new PersistentQueue("queue.dat");

		for (int i = 0; i < 1000; ++i)
			queue.push(Integer.toString(i));

		Iterator<Object> x = queue.iterator();

		Iterator<String> it = new TransformIterator<Object, String>(x,
				new CastTransformer<Object, String>());

		while (it.hasNext()) {
			System.out.println(it.next());
		}
		System.out.println(queue.getObjectCount());

	}

	private void deleteme()
		throws Exception
	{

		String xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		xml += "<list>";
		xml += "<OAI-PMH>"; // xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\">";
		// xml +=
		// "<mediawiki xsi:schemaLocation=\"http://www.mediawiki.org/xml/export-0.3/ http://www.mediawiki.org/xml/export-0.3.xsd\" version=\"0.3\" xml:lang=\"en\">";
		xml += "<record selected=\"selected\">Monday</record>";
		xml += "<record>Tuesday</record>";
		xml += "<record>Wednesday</record>";
		// xml += "</mediawiki>";
		xml += "</OAI-PMH>";
		xml += "</list>";

		XPath xpath = XPathFactory.newInstance().newXPath();
		XPathExpression expr = xpath.compile("//*[local-name()='mediawiki']");

		NodeList nodeList = (NodeList) expr.evaluate(new InputSource(
				new StringReader(xml)), XPathConstants.NODESET);
		for (int i = 0; i < nodeList.getLength(); ++i) {
			Node n = nodeList.item(i);
			/*
			 * System.out.println("////////"); System.out.println("////////");
			 * System.out.println(); WhatLinksHereArticleLoader.printDom(n);
			 * System.out.println(); System.out.println("$$$$$$$$");
			 * System.out.println("$$$$$$$$");
			 */
		}

	}

	/*************************************************************************/
	/* Workflows and Tasks */
	/*************************************************************************/
}

// the inverse side to RecordAdaptor
class RecordAdaptor2
	implements IHandler<IRecord>
{
	private IHandler<Record>	handler;

	public RecordAdaptor2(IHandler<Record> handler)
	{
		this.handler = handler;
	}

	@Override
	public void handle(IRecord item)
	{
		if (item instanceof Record)
			handler.handle((Record) item);
	}
}

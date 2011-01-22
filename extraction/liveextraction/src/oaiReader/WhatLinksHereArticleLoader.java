package oaiReader;

import helpers.ExceptionUtil;
import helpers.StringUtil;
import helpers.XMLUtil;

import java.io.ByteArrayOutputStream;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.xml.transform.Result;
import javax.xml.transform.Source;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpression;
import javax.xml.xpath.XPathFactory;

import org.apache.log4j.Logger;
import org.semanticweb.owlapi.model.IRI;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;


public class WhatLinksHereArticleLoader
{
	private static Logger logger = Logger.getLogger(WhatLinksHereArticleLoader.class);
	
	// e.g. http:/en.wikipedia.org/wiki
	private String baseUri;

	// the domain - e.g. http://en.wikipdia.org
	private String domainUri;
	
	// oai-prefix - e.g. oai:en.wikipedia.org:enwiki:12345
	private String oaiPrefix;
	
	
	private int charSkipCount;


	private int limit = 1;
	
	
	
	
	public WhatLinksHereArticleLoader(String baseUri, String oaiPrefix)
	{
		// TODO append an index.php to the baseUri
		
		// Remove trailing slash from base uri
		this.baseUri = baseUri.trim().replaceAll("/$", "");

		int searchStartIndex = baseUri.indexOf("//") + 2;
		int endIndex = baseUri.indexOf('/', searchStartIndex);
		

		this.domainUri = baseUri;
		if(endIndex > 0)
			this.domainUri = baseUri.substring(0, endIndex);
		
		// The +1 is the slash between base and rest
		charSkipCount = this.baseUri.length() - domainUri.length() + 1;
		
		//System.out.println("D = " + domainUri);
		//System.out.println("B = " + baseUri);
		//System.out.println(charSkipCount);
		
		this.oaiPrefix = oaiPrefix;
	}
	
	public static String domNodeToString(Node node)
		//throws TransformerFactoryConfigurationError, TransformerException
	{
		try {
			ByteArrayOutputStream bos = new ByteArrayOutputStream();
			Transformer transformer = TransformerFactory.newInstance().newTransformer();
			Source source = new DOMSource(node);
			Result output = new StreamResult(bos);
			transformer.transform(source, output);
			return bos.toString();
		}
		catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
		}
		
		return null;
	}
	
	public static void printDom(Node node)
		//throws Exception
	{
		try {
			Transformer transformer = TransformerFactory.newInstance().newTransformer();
			Source source = new DOMSource(node);
			Result output = new StreamResult(System.out);
			transformer.transform(source, output);
		}
		catch(Exception e) {
			e.printStackTrace();
		}
	}
	
	
	private String openUrlText(String location)
		throws Exception
	{
		return MediawikiHelper.getText(location);
	}
	
	public static NodeList evalXPath(Document doc, String query)
		throws Exception
	{
        XPath xpath = XPathFactory.newInstance().newXPath();        
    	return (NodeList)xpath.evaluate(query, doc, XPathConstants.NODESET);
	}
	
	public static String evalToSTring(XPath xpath, Document doc, String query)
		throws Exception
	{
        return (String)xpath.evaluate(query, doc, XPathConstants.STRING);		
	}
	
	public static String evalToString(XPathExpression xPathExpr, Document doc)
	{
		try {
			return (String)xPathExpr.evaluate(doc, XPathConstants.STRING);
		}
		catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
		}
		
		return null;
	}
	
	
	
	public static String evalXPathToString(Document doc, String query)
		throws Exception
	{
        XPath xpath = XPathFactory.newInstance().newXPath();
        return (String)xpath.evaluate(query, doc, XPathConstants.STRING);		
	}
	
	private String getNextPageLink(Document doc)
		throws Exception
	{
    	NodeList nodes = evalXPath(doc, "//*[@id='bodyContent']/a");

    	// We only need to check the first 2 links as the next link
    	// must be among them 
    	// (previous) (next) (20) (50) (100)
    	for (int i = 0; i < Math.min(2, nodes.getLength()); i++) {
    		Node node = nodes.item(i);
    		
    		if(!node.getTextContent().contains("next"))
    			continue;
    		
    		NamedNodeMap attributes = node.getAttributes();
    		String value = attributes.getNamedItem("href").getTextContent();
    	
    		return domainUri + value;
    	}
    	
    	return null;
	}
	

	private List<String> getLinkedArticles(Document doc)
		throws Exception
	{
		List<String> result = new ArrayList<String>();
    	NodeList nodes = evalXPath(doc, "//*[@id='mw-whatlinkshere-list']/li/a");

        for (int i = 0; i < nodes.getLength(); i++) {
        	NamedNodeMap attributes = nodes.item(i).getAttributes(); 
        	String value = attributes.getNamedItem("href").getTextContent();
        	//System.out.println(charSkipCount);
        	//System.out.println(value);
        	result.add(value.substring(charSkipCount));
        }
        
        return result;
	}
	

	public List<String> getUris(String article)
		throws Exception
	{
		List<String> result = new ArrayList<String>();
		
		article = WikiParserHelper.toWikiCase(article);
		
		String url = baseUri + "?title=Special:WhatLinksHere/" + article + "&limit=" + limit;
		System.out.println(url);
		
		while(url != null) {

			Document doc = XMLUtil.openUrl(url);

			result.addAll(getLinkedArticles(doc));

			url = getNextPageLink(doc);        
        }
        
        return result;
	}
	
	
	public Document export(String articleName)
		throws Exception
	{
		articleName = WikiParserHelper.toWikiCase(articleName);
		
		String location = baseUri + "/Special:Export/" + articleName;
		return XMLUtil.openUrl(location);
		
	}
	
	public Record exportRecord(String articleName)
		throws Exception
	{
		return extractRecord(export(articleName), this.domainUri, this.oaiPrefix);
	}
	//*[local-name()='header']/*[local-name()='datestamp']/text()

	public static Record extractRecord(Document document, String domainUri, String oaiPrefix)
	throws Exception
{
	//Document document = export(articleName);
	//printDom(document);
	
	String language = evalXPathToString(document, "//@lang");
	String t = evalXPathToString(document, "//*[local-name()='title']");

	MediawikiTitle title = MediawikiHelper.parseTitle(domainUri + "/wiki/Special:OAIRepository", t);
	//http://en.wikipedia.org/wiki/Special:OAIRepository
	//http://en.wikipedia.org/wiki/Special:OAIRepository
	String oaiId = oaiPrefix + evalXPathToString(document, "//page/id");
	String wikipediaUri = domainUri + "/wiki/" + title;
	String revision = evalXPathToString(document, "//revision/id");
	String username = evalXPathToString(document, "//contributor/username");
	String ip = evalXPathToString(document, "//contributor/ip");
	String userId = evalXPathToString(document, "//contributor/id");

	String text = evalXPathToString(document, "//text");
	/*
	System.out.println(language);
	System.out.println(title);
	System.out.println(oaiId);
	System.out.println(wikipediaUri);
	System.out.println(revision);
	System.out.println(username);
	System.out.println(ip);
	System.out.println(userId);
	return null;
	*/

	RecordMetadata metadata =
		new RecordMetadata(language, title, oaiId, IRI.create(wikipediaUri), revision, username, ip, userId);
	
	RecordContent content = new RecordContent(text, revision, null);
	
	return new Record(metadata, content);
}
	
	
	public static Record _extractRecord(Document document, String domainUri, String oaiPrefix)
		throws Exception
	{
		//Document document = export(articleName);
		printDom(document);
		
		String language = evalXPathToString(document, "//@lang");
		String t = evalXPathToString(document, "//title");

		MediawikiTitle title = MediawikiHelper.parseTitle(domainUri + "/wiki/Special:OAIRepository", t);
		//http://en.wikipedia.org/wiki/Special:OAIRepository
		//http://en.wikipedia.org/wiki/Special:OAIRepository
		String oaiId = oaiPrefix + evalXPathToString(document, "//page/id");
		String wikipediaUri = domainUri + "/wiki/" + title;
		String revision = evalXPathToString(document, "//revision/id");
		String username = evalXPathToString(document, "//contributor/username");
		String ip = evalXPathToString(document, "//contributor/ip");
		String userId = evalXPathToString(document, "//contributor/id");

		String text = evalXPathToString(document, "//text");
		/*
		System.out.println(language);
		System.out.println(title);
		System.out.println(oaiId);
		System.out.println(wikipediaUri);
		System.out.println(revision);
		System.out.println(username);
		System.out.println(ip);
		System.out.println(userId);
		return null;
		*/

		RecordMetadata metadata =
			new RecordMetadata(language, title, oaiId, IRI.create(wikipediaUri), revision, username, ip, userId);
		
		RecordContent content = new RecordContent(text, revision, null);
		
		return new Record(metadata, content);
	}
	
	public String exportText(String articleName)
		throws Exception	
	{
		return extractWikiText(export(articleName));
	}
	
	public String extractWikiText(Document doc)
		throws Exception
	{
		return evalXPathToString(doc, "//text");
	}
	
	
	public static List<String> generateCaseVersions(String name)
	{
		List<String> result = new ArrayList<String>();

		// Convert name to canonical wiki case
		name = WikiParserHelper.toWikiCase(name);
		
		String[] parts = name.split(":");
		
		String namespace;
		
		if(parts.length == 2) {
			namespace = parts[0];
			name = parts[1];
		}
		else {
			namespace = "";
			name = parts[0];
		}
		
		String[] items = name.split("_");
		int bitSet = 0;
		
		int n = (int)Math.pow(2, items.length - 1);
		System.out.println("Possibility count for '" + name + "' : " + n);
		//if(n > 5)
		//	throw new RuntimeException("Too many possibilities");
		
		for(int i = 0; i < n; ++i) {
			String version = items[0];
			for(int j = 0; j < items.length - 1; ++j) {
				boolean type = ((i >> j) & 1) == 1;

				//if(j > 1)
					version += "_";
				
				if(type == true)
					version += StringUtil.ucFirst(items[j + 1]);
				else
					version += StringUtil.lcFirst(items[j + 1]);
			}
	
			if(!namespace.isEmpty())
				version = namespace + ":" + version;
			
			result.add(version);
		}
		
		return result;
	}
	
	
	private static Pattern redirectPattern = Pattern.compile("\\s*\\#REDIRECT\\s*\\[\\[(.*)\\]\\]", Pattern.CASE_INSENSITIVE);
	
	/**
	 * Returns an uri if exists - otherwise null.
	 * Note: the returned uri may differ from the original uri due to redirects
	 * 
	 * @param baseUri
	 * @param name
	 * @return
	 */
	public WikiLink checkExistence(String name)
		//throws Exception
	{		
		//Template:Infobox_Musical_Artist
		//#REDIRECT [[Template:Infobox Musical artist]]
		
		try {
			String text = extractWikiText(export(name));

			
			// FIXME This is a hack - actually the text should be null if
			// the site doesn't exist, but this it seems it is a "feature" that
			// xpath returns an empty string even for non-existent nodes
			if(text.isEmpty())
				return null;

			Matcher matcher = redirectPattern.matcher(text);
			String redirect = null;
			if(matcher.find()) {
				redirect = name;
				name = WikiParserHelper.toWikiCase(matcher.group(1));
			}
			
			return new WikiLink(name, redirect);
		}
		catch (Exception e1) {
			return null;
		}
		//System.out.println(text);
		//return !text.contains("Wikipedia does not have an article with this exact name");
	}
	
	/*
	public Record getRecord(Document doc)
	{
		String language =;
		String title = ;
		String oaiId = ;
		String uri = ;
			
		RecordMetadata m = new RecordMetadata();
		
	}

	public Map<String, String> export(Collection<String> articleNames)
		throws Exception
	{
		Map<String, String> result = new HashMap<String, String>();
		for(String articleName : articleNames)
			result.put(articleName, export(articleName));
		
		return result;
	}
	
	
	public Record downloadRecord(String articleName)
	{
		//String download
	}
	*/
	
	/*
	public Map<String, String> checkExistence(Collection<String> names)
	{
		Map<String, String> result = new HashMap<String, String>();

		for(String name : names) {
			List<String> list = generateCaseVersions(name);
			for(String item : list) {
				String mapped = checkExistence(item);
	
				System.out.println(item + " ---> " + mapped);
				result.put(item, mapped);
				
				try {
					Thread.sleep(1000);
				}
				catch (Exception e1)
				{
				}
	
				if(mapped != null)
					break;
			}
		}
		return result;
	}
	*/
	
	public WikiLink resolveExistence(String name)
	{
		List<String> list = generateCaseVersions(name);
		for(String item : list) {
			WikiLink mapped = null;
			try {
				mapped = checkExistence(item);
				Thread.sleep(1000);
			}
			catch(Exception e1) {
			}

			if(mapped != null)
				return mapped;
		}
		
		return null;
	}
	
	
	public static void main(String[] args)
		throws Exception
	{
		String baseUri = "http://en.wikipedia.org/w/index.php?title=";
		baseUri = "http://en.wikipedia.org/w/index.php/";
		String oaiPrefix = "oai:en.wikipedia.org:enwiki:";
		//baseUri = "http://localhost/wiki/index.php/";
		WhatLinksHereArticleLoader loader = 
			new WhatLinksHereArticleLoader(baseUri, oaiPrefix);
		
		loader.exportRecord("London");
		System.exit(0);
		
		//System.out.println("blubbi = " + loader.getUris("Hello World"));
		
		//System.out.println("Result = '" + loader.exportText("Template:Infobox_Musical_Artist") + "'");
		
		//if(true)
			//return;
		
		List<String> list = generateCaseVersions("Template:infobox_serbia_municipality");
		for(String item : list) {
			WikiLink mapped = loader.checkExistence(item);

			System.out.println(item + " ---> " + mapped);
			Thread.sleep(1000);

			if(mapped != null)
				break;
		}
	}
}

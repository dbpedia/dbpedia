package oaiReader;

import helpers.StringUtil;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Pattern;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.TransformerException;

import org.apache.commons.collections15.BidiMap;
import org.apache.commons.collections15.bidimap.DualHashBidiMap;
import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import ORG.oclc.oai.harvester2.verb.ListRecords;


public class MediawikiHelper
{
	private static Logger logger = Logger.getLogger(MediawikiHelper.class);

	private static Pattern redirectPattern =
		Pattern.compile("\\s*\\#REDIRECT\\s*\\[\\[(.*)\\]\\]", Pattern.CASE_INSENSITIVE);

	public static Pattern getRedirectPattern()
	{
		return redirectPattern;
	}
	
	public static List<String> getRedirects(String text)
	{
		return StringUtil.matchAll(text, getRedirectPattern());
	}
	
	
	// cache the mediawiki namespace mappings
	// map<oaiBaseUri, IOneToOneMap<name, id>>
	private static Map<String, BidiMap<Integer, String>> wikiToNamespaceMapping =
		new HashMap<String, BidiMap<Integer, String>>();
	
	public static MediawikiTitle parseTitle2(String wikiBaseUri, String title)
		throws Exception
	{
		BidiMap<Integer, String> mapping = getNamespaceInfo2(wikiBaseUri);

		return parseTitle(title, mapping);
	}

	
	public static MediawikiTitle parseTitle(String wikiBaseUri, String title)
		throws Exception
	{
		BidiMap<Integer, String> mapping = getNamespaceInfo(wikiBaseUri);

		return parseTitle(title, mapping);
	}

	public static MediawikiTitle parseTitle(String title, BidiMap<Integer, String> mapping)
		throws Exception
	{
		NamespaceArticleName parseResult =
			WikiParserHelper.parseTitle(title, mapping.values());
	
		// map the namespace
		Integer namespaceId = mapping.getKey(parseResult.getNamespaceName());
		
		// if the namespace could not be mappped, use the id of ""
		if(namespaceId == null) {
			namespaceId = mapping.getKey(""); 
			if(namespaceId == null)
				throw new NullPointerException();
		}
		
		return new MediawikiTitle(parseResult.toString(), parseResult.getArticleName(), namespaceId, parseResult.getNamespaceName());
	}	
	/*
	public static void extractTemplates(String string)
	{
		int start = string.indexOf("{{");
		
		
		
		string.indexOf("}}");
	}
	
	
	public static void 
	*/

	/**
	 * url-encodes a key-value pair
	 * 
	 * @param key
	 * @param value
	 * @return
	 * @throws Exception
	 */
	private static String urlEncodeProperty(String key, String value)
		throws Exception
	{
		return URLEncoder.encode(key, "UTF-8") + "=" +
			URLEncoder.encode(value, "UTF-8");
	}
	
	/**
	 * seperates a list of strings with &s.
	 * E.g. [a, b, c] becomes a&b&c
	 * 
	 * @param items
	 * @return
	 */
	private static String andify(Object ... items)
	{
		boolean first = true;
		String result = "";
		
		for(Object item : items) {
			if(!first)
				result += "&";
			
			result += item;
			
			first = false;
		}
		
		return result;
	}
	
	/**
	 * Expands all templates in 'text' against the given wikiApi.
	 * 
	 * @param wikiApiUri
	 * @param text
	 * @return
	 * @throws Exception
	 */
	public static String expandTemplates(String wikiApiUri, String text)
		throws Exception
	{
		String data = andify( 
				urlEncodeProperty("format", "xml"),
				urlEncodeProperty("action", "expandtemplates"),
				urlEncodeProperty("text"  , text)
				);

        Document doc = postXml(wikiApiUri, data);

        return doc.getElementsByTagName("expandtemplates").item(0).getTextContent();
	}

	
	public static BidiMap<Integer, String> getNamespaceInfo2(String apiBaseUri)
		throws Exception
	{
		// check if the mapping has already been loaded
		BidiMap<Integer, String> result = wikiToNamespaceMapping.get(apiBaseUri);
		if(result != null)
			return result;
	
		result = getNamespaceInfoNoCache2(apiBaseUri);
	
		// Cache the result
		wikiToNamespaceMapping.put(apiBaseUri, result);
	
		return result;
	}

	
	public static BidiMap<Integer, String> getNamespaceInfo(String oaiBaseUri)
		throws Exception
	{
		// check if the mapping has already been loaded
		BidiMap<Integer, String> result = wikiToNamespaceMapping.get(oaiBaseUri);
		if(result != null)
			return result;
		
		result = getNamespaceInfoNoCache(oaiBaseUri);
		
		// Cache the result
		wikiToNamespaceMapping.put(oaiBaseUri, result);
		
		return result;
	}
	
	
	public static BidiMap<Integer, String> getNamespaceInfoNoCache2(String baseUri)
		throws Exception
	{
		WhatLinksHereArticleLoader mediaWiki = new WhatLinksHereArticleLoader(baseUri, "oai:not:set:");
		logger.info("Retrieving template mappings for: " + baseUri);
	

		Document doc = mediaWiki.export("London");

		return processDoc(doc);
	}

	/**
	 * Extracts the namespace info via oai.
	 * We hope that the languages 
	 * @throws TransformerException 
	 * @throws SAXException 
	 * @throws ParserConfigurationException 
	 * @throws IOException 
	 * 
	 */
	public static BidiMap<Integer, String> getNamespaceInfoNoCache(String oaiBaseUri)
		throws IOException, ParserConfigurationException, SAXException, TransformerException
	{
		logger.info("Retrieving template mappings for: " + oaiBaseUri);
		
		ListRecords currentRecordList =
			new ListRecords(oaiBaseUri,
							null,
							null,
							null,
							"mediawiki");
				
		Document doc = currentRecordList.getDocument();
		
		return processDoc(doc);
	}
		
	public static BidiMap<Integer, String> processDoc(Document doc)
		throws IOException, ParserConfigurationException, SAXException, TransformerException
	{
		BidiMap<Integer, String> result =
			new DualHashBidiMap<Integer, String>();

		NodeList namespaces = doc.getElementsByTagName("namespace");
		for(int i = 0; i < namespaces.getLength(); ++i) {
			Element element = (Element)namespaces.item(i); 
			
			Integer key = Integer.parseInt(element.getAttribute("key"));
			String value = element.getTextContent();
			
			// Replace ' ' with '_'
			value = value.replace(' ', '_');
			value = StringUtil.ucFirst(value.toLowerCase());

			result.put(key, value);
			logger.info("Found mapping: [" + key + " - " + value + "]");
		}
		logger.info("Done.");

		return result;
	}


	/**
	 * Executes the edit action against a mediawiki api
	 * 
	 * FIXME: Don't ask me what the token is for...
	 * 
	 * @param wikiApiUri
	 * @param title
	 * @param text
	 * @return
	 * @throws Exception
	 */
	public static String edit(String wikiApiUri, String title, String text)
		throws Exception
	{	
		String data = andify(
				urlEncodeProperty("action", "edit"),
				urlEncodeProperty("title" , title),
				urlEncodeProperty("text"  , text),
				urlEncodeProperty("token" , "+\\")
				);

		return postText(wikiApiUri, data);
	}
	
	
	public static Document parse(String wikiApiUri, String text)
		throws Exception
	{
		String data = andify(
				urlEncodeProperty("format", "xml"),
				urlEncodeProperty("action", "parse"),
				urlEncodeProperty("text"  , text)
				);

		return postXml(wikiApiUri, data);		
	}
	
	public static String parseText(String wikiApiUri, String text)
		throws Exception
	{
		String data = andify(
				urlEncodeProperty("format", "xml"),
				urlEncodeProperty("action", "parse"),
				urlEncodeProperty("text"  , text)
				);
	
		return postText(wikiApiUri, data);		
	}
	
	/**
	 * Returns the result as an XML document (dom)
	 * 
	 * 
	 * @param wikiApiUri
	 * @param data
	 * @return
	 * @throws Exception
	 */
	private static Document postXml(String wikiApiUri, String data)
		throws Exception
	{
		InputStream responseStream = postCore(wikiApiUri, data);
        
        Document doc = DocumentBuilderFactory.newInstance().
    		newDocumentBuilder().parse(responseStream);

        responseStream.close();

        return doc;		
	}
	
	
	public static String postText(String wikiApiUri, String data)
		throws Exception
	{
		StopWatch sw = new StopWatch();
		sw.start();
		InputStream responseStream = postCore(wikiApiUri, data);
		
		BufferedReader reader =
			new BufferedReader(new InputStreamReader(responseStream));

		String result = "";
		String line;
		while(null != (line = reader.readLine()))
			result += line;

		reader.close();
        sw.stop();
        
        System.out.println("Reading took: " + sw.getTime());
		return result;
	}
	
	public static String getText(String uri)
		throws Exception
	{
		InputStream responseStream = getCore(uri);
		String result = toString(responseStream);
		responseStream.close();
		
		return result;
	}
	
	

	public static String toString(InputStream in)
		throws IOException
	{
		BufferedReader reader = new BufferedReader(new InputStreamReader(in));

		String result = "";
		String line;
		while(null != (line = reader.readLine()))
			result += line + "\n";

		return result;
	}
	
	public static InputStream getCore(String uri)
		throws Exception
	{
		String cutData = StringUtil.cropString(uri, 200, 50);

		String logMessage = "Getting: " + cutData;
		System.out.println(logMessage);
		
		Logger.getLogger(MediawikiHelper.class).debug(logMessage);

        URL url = new URL(uri);
        URLConnection conn = url.openConnection();

        return conn.getInputStream();		
	}


	/**
	 * Returns the response as a stream. Don't forget to close.
	 * 
	 * @param wikiApiUri
	 * @param args
	 * @return
	 */
	private static InputStream postCore(String wikiApiUri, String data)
		throws Exception
	{
		String cutData = StringUtil.cropString(data, 200, 50);
		
		String logMessage = "Posting: " + wikiApiUri + "?" + cutData; 
		Logger.getLogger(MediawikiHelper.class).debug(logMessage);

		StopWatch sw = new StopWatch();
		sw.start();
        URL url = new URL(wikiApiUri);
        URLConnection conn = url.openConnection();
        conn.setDoOutput(true);
        OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream());
        wr.write(data);
        wr.close();
        
        sw.stop();
        
        System.out.println("Post Took: " + sw.getTime());
        
        return conn.getInputStream();
	}
}

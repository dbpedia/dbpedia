package helpers;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.StringReader;
import java.net.URL;
import java.net.URLConnection;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.Result;
import javax.xml.transform.Source;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.TransformerFactoryConfigurationError;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.apache.log4j.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.EntityResolver;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;


public class XMLUtil
{
	private static Logger logger = Logger.getLogger(XMLUtil.class);
	
	public static String toString(NodeList nodeList)
	{
		String result = "";
		
		for(int i = 0; i < nodeList.getLength(); ++i) {
			result += toString(nodeList.item(i));
		}
		
		return result;
	}
	
	public static String toString(Node node)
	{
		ByteArrayOutputStream baos = new ByteArrayOutputStream();

		try {
			toText(node, baos);
		}
		catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
			return null;
		}

		return baos.toString();
	}
	
	public static Document createFromString(String text)
		throws Exception
	{
		return DocumentBuilderFactory.newInstance().newDocumentBuilder().parse
			(new InputSource(new StringReader(text)));
	}

	public static void toText(Node node, OutputStream out)
		throws TransformerFactoryConfigurationError, TransformerException
	{
		Transformer transformer = TransformerFactory.newInstance().newTransformer();
		Source source = new DOMSource(node);
		Result output = new StreamResult(out);
		transformer.transform(source, output);
	}

	public static Document openFile(File file)
		throws Exception
	{
		InputStream inputStream = new FileInputStream(file);
		
		Document result = loadFromStream(inputStream);		
		inputStream.close();
		return result;
	}
	
	public static Document openUrl(String location)
		throws Exception
	{
		URL url = new URL(location);
		//System.out.println(url);
	
		URLConnection con = url.openConnection();
		InputStream responseStream = con.getInputStream();
		
		Document result = loadFromStream(responseStream);		
		responseStream.close();
		return result;
	}
	
	public static Document loadFromStream(InputStream inputStream)
		throws Exception
	{
		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		factory.setValidating(false);
		factory.setNamespaceAware(false);
	    
		DocumentBuilder builder = factory.newDocumentBuilder();
		builder.setEntityResolver(new EntityResolver() {
	
	        @Override
	        public InputSource resolveEntity(String publicId, String systemId)
	                throws SAXException, IOException {
	            //System.out.println("Ignoring " + publicId + ", " + systemId);
	            return new InputSource(new StringReader(""));
	        }
	    });
		
		//System.out.println(StringUtil.toString(inputStream));
		
		Document doc = builder.parse(inputStream);		
		return doc;
	}
	
}

package helpers;

import iterator.ChainIterator;
import iterator.DelayIterator;
import iterator.DuplicateOAIRecordRemoverIterator;
import iterator.EndlessOAIMetaIterator;
import iterator.OAIRecordIterator;
import iterator.XPathQueryIterator;

import java.io.File;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Iterator;

import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathExpression;
import javax.xml.xpath.XPathFactory;

import oaiReader.UtcHelper;

import org.apache.commons.collections15.iterators.TransformIterator;
import org.w3c.dom.Document;
import org.w3c.dom.Node;

import transformer.NodeToDocumentTransformer;


public class OAIUtil
{
	private static String getStartDate(String date)
	{
		return date == null ? UtcHelper.transformToUTC(System
				.currentTimeMillis()) : date;
	}

	public static Iterator<Document> createEndlessRecordIterator(
			String oaiBaseUri, String startDate, int pollDelay,
			int resumptionDelay, File file)
	{
		XPathExpression expr = DBPediaXPathUtil.getRecordExpr();
		
		Iterator<Document> metaIterator = createEndlessIterator(oaiBaseUri,
				startDate, pollDelay, resumptionDelay, file);

		Iterator<Node> nodeIterator = new XPathQueryIterator(metaIterator, expr);
		
		// 'Dirty' because it can contain duplicates.
		Iterator<Document> dirtyRecordIterator = new TransformIterator<Node, Document>(
				nodeIterator, new NodeToDocumentTransformer());

		// This iterator removed them
		Iterator<Document> recordIterator = new DuplicateOAIRecordRemoverIterator(
				dirtyRecordIterator);
		
		return recordIterator;
	}

	public static Iterator<Document> createEndlessIterator(String oaiBaseUri,
			String startDate, int pollDelay, int resumptionDelay, File file)
	{
		startDate = getStartDate(startDate);

		// This iterator always fetches fresh data from the oai repo
		// when next() is called
		Iterator<Iterator<Document>> metaIterator = new EndlessOAIMetaIterator(
				oaiBaseUri, startDate, resumptionDelay, file);

		// This iterator puts a minimum delay between two next calls
		if (pollDelay > 0)
			metaIterator = new DelayIterator<Iterator<Document>>(metaIterator,
					pollDelay);

		// This iterator makes the multiple iterators look like a single one
		ChainIterator<Document> chainIterator = new ChainIterator<Document>(
				metaIterator);

		return chainIterator;
	}

	public static Iterator<Document> createIterator(String oaiBaseUri,
			String startDate, int resumptionDelay)
	{
		startDate = getStartDate(startDate);

		Iterator<Document> iterator = new OAIRecordIterator(oaiBaseUri,
				startDate);

		if (resumptionDelay > 0)
			iterator = new DelayIterator<Document>(iterator, resumptionDelay);

		return iterator;
	}

	public static DateFormat getOAIDateFormat()
	{
		return new SimpleDateFormat("yyyy-mm-dd'T'HH:mm:ss'Z'");
	}

}

package iterator;

import helpers.DBPediaXPathUtil;
import helpers.OAIUtil;
import helpers.XPathUtil;

import java.util.Date;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Map;
import java.util.Set;

import org.w3c.dom.Document;

import collections.TimeStampMap;


/**
 * An iterator which only returns elements if they reach a certain age.
 * 
 * 
 * 
 * @author raven
 *
 */
public class TimeWindowIterator
	extends PrefetchIterator<Document>
{
	private TimeStampMap<String, Document, Date, Long> map;

	private Iterator<Document> iterator;
	
	public TimeWindowIterator(Iterator<Document> iterator, long maxDistance, boolean inclusive, boolean allowRenewal)
	{
		map = TimeStampMap.create(String.class, Document.class, maxDistance, inclusive, allowRenewal);
		this.iterator = iterator;
	}
	
	public TimeStampMap<String, Document, Date, Long> getQueued()
	{
		return map;
	}

	@Override
	protected Iterator<Document> prefetch()
		throws Exception
	{
		while(iterator.hasNext()) {
			Document node = iterator.next();
			
			String id = XPathUtil.evalToString(node, DBPediaXPathUtil.getPageIdExpr());
			String dateString = XPathUtil.evalToString(node, DBPediaXPathUtil.getTimestampExpr());
			Date time = OAIUtil.getOAIDateFormat().parse(dateString);

			Map<String, Document> ids = map.setCurrentTime(time);
			//System.out.println("Item count = " + map.size());

			map.put(id, node);
			
			if(ids.isEmpty())
				continue;
			
			return ids.values().iterator();
		}

		return null;
	}
}

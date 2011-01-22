package mywikiparser;

import java.util.TreeMap;

/**
 * A Map which uses wiki strings as keys.
 * So it's just a tree map with an appropriate comparator set.
 * 
 * @author raven
 *
 * @param <V>
 */
public class WikiTreeMap<V>
	extends TreeMap<String, V>
{
	public WikiTreeMap()
	{
		super(new WikiCaseStringComparator());
	}
}

package mywikiparser;

import java.util.ArrayList;
import java.util.List;

/**
 * If the same key is used multiple times, then the LAST value is returned 
 *  (This is the way mediawiki seems to handle it)
 */
public class IndexMap<TKey, TValue>
{
	private List<TValue> values = new ArrayList<TValue>();
	private List<TKey>  keys    = new ArrayList<TKey>();
	
	public TValue getValue(Object key)
	{
		int index = keys.lastIndexOf(key);
		if(index < 0)
			return null;
		
		return values.get(index);
	}
	
	public TValue getValue(int index)
	{
		return values.get(index);
	}
	
	public TKey getKey(int index)
	{
		return keys.get(index);
	}
	
	public void add(TKey key, TValue value)
	{
		keys.add(key);
		values.add(value);
	}
	
	@Override
	public String toString()
	{
		String result = "";
		
		for(int i = 0; i < keys.size(); ++i)
			result += ", " + i + " "+ keys.get(i) + " : " + values.get(i); 
		
		return result;
	}
}
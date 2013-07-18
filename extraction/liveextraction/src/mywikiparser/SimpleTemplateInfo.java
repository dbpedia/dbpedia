package mywikiparser;


/**
 * A class representing a simple template info - just index/key -> value
 * 
 * 
 * @author raven_arkadon
 */
public class SimpleTemplateInfo
{
	private String name;
	private IndexMap<String, String> indexMap = new IndexMap<String, String>();
	
	public void setName(String name)
	{
		this.name = name;
	}
	
	public String getName()
	{
		return name;
	}

	public IndexMap<String, String> indexMap()
	{
		return indexMap;
	}
}

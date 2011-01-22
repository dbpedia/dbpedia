package filter;

import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import oaiReader.RecordMetadata;


public class DbpediaMetadataFilter
	implements IFilter<RecordMetadata>
{
	//private int[]	allowed;
	private Set<Integer> allowed = new HashSet<Integer>();

	@SuppressWarnings("unchecked")
	@Override
	public <U extends IFilter<RecordMetadata>> U getNestedFilter(Class<U> clazz)
	{
		if (this.getClass().isAssignableFrom(clazz))
			return (U) this;
		else
			return null;
	}

	public DbpediaMetadataFilter(int... allowed)
	{
		for(int item : allowed)
			this.allowed.add(item);

	}

	/*
	 * private static final String[] forbidden = new String[] { "Talk", "User",
	 * "User_talk", "Wikipedia", "Wikipedia_talk", "File", "File_talk",
	 * "MediaWiki", "MediaWiki_talk", //"Template", //"Template_talk", "Help",
	 * "Help_talk", "Category", "Category_talk", "Portal", "Portal_talk" };
	 */

	public boolean evaluate(RecordMetadata metadata)
	{
		Integer namespaceId = metadata.getTitle().getNamespaceId();
		return allowed.contains(namespaceId);
	}

	/*
	 * public boolean doesAccept(RecordMetadata metadata) { // User_talk:DBpedia
	 * is allowed if(metadata.getTitle().getFullTitle().equals("User:DBpedia")
	 * || metadata.getTitle().getFullTitle().equals("User_talk:DBpedia")) return
	 * true;
	 * 
	 * for(String item : forbidden)
	 * if(metadata.getTitle().getNamespaceName().equals(item)) return false;
	 * 
	 * return true; }
	 */

	/*
	 * public boolean doesAccept(RecordMetadata metadata) { // User_talk:DBpedia
	 * is allowed if(metadata.getTitle().startsWith("User_talk:DBpedia")) return
	 * true;
	 * 
	 * for(String item : forbidden) if(metadata.getType().equals(item)) return
	 * false;
	 * 
	 * return true; }
	 */
}

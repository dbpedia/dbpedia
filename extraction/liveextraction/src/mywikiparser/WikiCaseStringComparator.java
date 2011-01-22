package mywikiparser;

import java.util.Comparator;

import oaiReader.WikiParserHelper;

public class WikiCaseStringComparator
	implements Comparator<String>
{
	
	@Override
	public int compare(String a, String b)
	{
		String aa = WikiParserHelper.toWikiCase(a);
		String bb = WikiParserHelper.toWikiCase(b);
		
		return aa.compareTo(bb);
	}

}

package oaiReader;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import org.junit.Assert;
import org.junit.Test;

public class WikiParserHelperTest
{

	@Test
	public void testToCanonicalWikiCase()
	{
		Set<String> namespaces = new HashSet<String>();
		namespaces.add("Media");
		namespaces.add("Special");
		namespaces.add("Talk");
		namespaces.add("User");
		namespaces.add("User_talk");
		namespaces.add("Wikipedia");
		namespaces.add("Wikipedia_talk");
		
		
		Map<String, String> inputToExpected = new HashMap<String, String>();
		inputToExpected.put("   uSeR taLK   :   tESt ", "User_talk:TESt");
		inputToExpected.put("   uSeR  taLK   :   tESt ", "User_talk:TESt");

		inputToExpected.put("  mIssIon :   impossible ", "MIssIon_:_impossible");

		inputToExpected.put("Hannah Montana: The Movie", "Hannah_Montana:_The_Movie");
		
		inputToExpected.put("List of Bakugan: New Vestroia episodes", "List_of_Bakugan:_New_Vestroia_episodes");
		inputToExpected.put("List of Bakugan:New Vestroia episodes", "List_of_Bakugan:New_Vestroia_episodes");
		
		for(Map.Entry<String, String> entry : inputToExpected.entrySet()) {
			String title = 
				WikiParserHelper.toCanonicalWikiCase(entry.getKey(), namespaces);
			
			System.out.println(entry.getKey() + " >> " + title);
			
			Assert.assertEquals(entry.getValue(), title);
		}		
	}

}

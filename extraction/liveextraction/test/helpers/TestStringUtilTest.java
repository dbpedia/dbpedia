package helpers;

import java.util.HashSet;
import java.util.Set;

import junit.framework.Assert;

import org.junit.Test;

public class TestStringUtilTest
{

	@Test
	public void testZip()
		throws Exception
	{
		Set<String> testSet = new HashSet<String>();
		testSet.add("test");
		
		for(String expected : testSet) {
			byte[] bytes = StringUtil.zip(expected);
		
			System.out.println("Uncompressed length: " + expected.length());
			System.out.println("Compressed length: " + bytes.length);
			
			String actual = StringUtil.unzip(bytes);
			System.out.println("Expected: " + expected);
			System.out.println("Actual: " + actual);
			if(!expected.equals(actual))
				Assert.assertEquals(expected, actual);
			
			System.out.println("----------------------");
		}
	}

}

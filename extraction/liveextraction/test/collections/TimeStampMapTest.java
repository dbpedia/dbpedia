package collections;

import java.util.Date;
import java.util.HashMap;
import java.util.Map;

import junit.framework.Assert;

import org.junit.Test;

public class TimeStampMapTest
{

	@Test
	public void testSetCurrentTime()
	{
		TimeStampMap<Integer, String, Date, Long> map = 
			TimeStampMap.create(Integer.class, String.class, 1000, false, false);
		
		map.setCurrentTime(new Date(0));		
		map.put(1, "one");

		Map<Integer, String> x = map.setCurrentTime(new Date(1000));
		map.put(2, "two");
		
		Map<Integer, String> expected = new HashMap<Integer, String>();
		expected.put(1, "one");
		
		Assert.assertEquals(expected, x);

		x = map.setCurrentTime(new Date(10000));
		expected.clear();
		expected.put(2, "two");
		Assert.assertEquals(expected, x);

		x = map.setCurrentTime(new Date(10000));
		expected.clear();
		Assert.assertEquals(expected, x);
		
	}

}

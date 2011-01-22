package collections;

import java.util.Collections;
import java.util.Comparator;
import java.util.HashSet;
import java.util.Set;

import junit.framework.Assert;

import org.junit.Test;

public class TimeStampSetTest
{
	private TimeStampSet<Integer, Integer, Integer> set;
	
	
	public TimeStampSet<Integer, Integer, Integer> create(int maxDistance, boolean inclusive, boolean allowRenewal)
	{
		return
			new TimeStampSet<Integer, Integer, Integer>(
					new IDistanceFunc<Integer, Integer>() {
						@Override
						public Integer distance(Integer a, Integer b) {
							return b - a;
						}
					},
					maxDistance,
					new Comparator<Integer>() {
						@Override
						public int compare(Integer a, Integer b)
						{
							return a - b;
						}
					},
					inclusive,
					allowRenewal
				);
	}
	
	@Test
	public void testB()
	{
		TimeStampSet<Integer, Integer, Integer> set = create(10, false, false);

		for(int i = 0; i < 100; ++i) {
			set.setCurrentTime(i);
			set.add(i);
		}

		
		set.setCurrentTime(1000);
		
		Assert.assertEquals(Collections.emptySet(), set);
	}
	
	private void testLinear(int maxDistance, int count)
	{
		TimeStampSet<Integer, Integer, Integer> set = create(count, false, false);
		
		Set<Integer> expected = new HashSet<Integer>();
		for(int i = maxDistance - count; i < maxDistance; ++i)
			expected.add(i);
		
		for(int i = 0; i < maxDistance; ++i) {
			set.setCurrentTime(i);
			set.add(i);
		}
		
		Assert.assertEquals(expected, set);
		
		
		for(Integer item : set) {
			System.out.println(item);
		}

	}
	
	@Test
	public void testRenewal()
	{
		TimeStampSet<Integer, Integer, Integer> set = create(5, true, true);
		
		Set<Integer> tmp = null;
			
		int i = 0;
		for(i = 0; i < 10; ++i) {
		
			tmp = set.setCurrentTime(i * 5);		
			Assert.assertEquals(tmp, Collections.emptySet());
	
			set.add(100);
		}
	
		tmp = set.setCurrentTime(i * 5 + 1);		
		Assert.assertEquals(tmp, Collections.singleton(100));
	}
	
	/**
	 * CurrentTime must not be set to a point in the past.
	 * 
	 * (Or does it make sense to allow this?)
	 * 
	 */
	@Test
	public void testCurrentTime()
	{
		System.out.println("Test not implemented");
	}
	
	@Test
	public void test()
	{
		testLinear(10, 1);
		testLinear(100, 10);
		testLinear(100, 20);
	}
}
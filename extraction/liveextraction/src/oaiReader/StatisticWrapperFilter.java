package oaiReader;

import org.apache.log4j.Logger;

import filter.IFilter;

/**
 * A filter wrapper that counts acceptions and rejections of the underlying
 * filter.
 * 
 */
public class StatisticWrapperFilter<T>
	implements IFilter<T>
{
	private IFilter<T> filter;
	
	private int rejectCount = 0;
	private int acceptCount = 0;
	
	//private Logger logger = Logger.getLogger(StatisticWrapperFilter.class);
	@SuppressWarnings("unchecked")
	@Override
	public <U extends IFilter<T>> U getNestedFilter(Class<U> clazz)
	{
		if(this.getClass().isAssignableFrom(clazz))
			return (U)this;
		else
			return filter.getNestedFilter(clazz);
	}
	
	public StatisticWrapperFilter(IFilter<T> filter)
	{
		this.filter = filter;
	}
	
	public int getRejectCount()
	{
		return rejectCount;
	}
	
	public int getAcceptCount()
	{
		return acceptCount;
	}
	
	public void resetCounts()
	{
		rejectCount = 0;
		acceptCount = 0;
	}
	
	@Override
	public boolean evaluate(T item)
	{
		boolean result = filter.evaluate(item);
		
		if(result == true)
			++acceptCount;
		else
			++rejectCount;
		
		/*
		logger.debug(
				"AcceptCount = " + acceptCount + ", " +
				"RejectCount = " + rejectCount + ", " + 
				"Total = " + (acceptCount + rejectCount));
		 */
		return result;
	}
	
	@Override
	public String toString()
	{
		return
			"AcceptCount = " + acceptCount + ", " +
			"RejectCount = " + rejectCount + ", " + 
			"Total = " + (acceptCount + rejectCount);
	}
}

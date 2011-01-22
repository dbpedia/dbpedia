package oaiReader;

import java.io.PrintWriter;
import java.io.Writer;

import org.apache.commons.lang.time.StopWatch;

import filter.IFilter;


public class StatisticWriterFilter<T>
	implements IFilter<T>
{
	private IFilter<T> filter;
	private PrintWriter out;
	private int interval; // in seconds
	
	private StopWatch stopWatch;
	private int acceptCount = 0;
	private int rejectCount = 0;

	public IFilter<T> getNestedFilter()
	{
		return filter;
	}
	
	@SuppressWarnings("unchecked")
	@Override
	public <U extends IFilter<T>> U getNestedFilter(Class<U> clazz)
	{
		if(this.getClass().isAssignableFrom(clazz))
			return (U)this;
		else
			return filter.getNestedFilter(clazz);
	}
	
	public StatisticWriterFilter(
			IFilter<T> filter,
			Writer writer,
			int interval)
		throws Exception
	{
		this.filter = filter;
		this.out = new PrintWriter(writer);
		this.interval = interval;
	}
	
	@Override
	public boolean evaluate(T item)
	{
		if(stopWatch == null) {
			stopWatch = new StopWatch();
			stopWatch.start();
		}
		
		boolean result = filter.evaluate(item);
		
		if(result)
			++acceptCount;
		else
			++rejectCount;
		
		if(stopWatch.getTime() / 1000 > interval) {

			out.println(acceptCount + "\t" + rejectCount);
			out.flush();

			acceptCount = 0;
			rejectCount = 0;
			stopWatch.stop();
			stopWatch.reset();
			stopWatch.start();
		}

		return result;
	}
}


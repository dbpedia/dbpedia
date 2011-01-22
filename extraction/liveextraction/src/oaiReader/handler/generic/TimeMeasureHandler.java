package oaiReader.handler.generic;

import oaiReader.IHandler;

import org.apache.commons.lang.time.StopWatch;


/**
 * 
 * @author raven_arkadon
 *
 * @param <T>
 */
public class TimeMeasureHandler<T>
	implements IHandler<T>
{
	private int nCalls = 0;
	private IHandler<T> handler;
	private StopWatch stopWatch = new StopWatch();

	
	public TimeMeasureHandler(IHandler<T> handler)
	{
		this.handler = handler;
		
		stopWatch.start();
		stopWatch.suspend();
	}
	
	@Override
	public void handle(T item)
	{
		++nCalls;
		stopWatch.resume();

		handler.handle(item);
		
		stopWatch.suspend();		
	}
	
	public long getTime()
	{
		return stopWatch.getTime();
	}
	
	public int getCallCount()
	{
		return nCalls;
	}
	
	@Override
	public String toString()
	{
		long time = stopWatch.getTime();
		double ratio = nCalls / (double)time * 1000.0f;
		return "calls = " + nCalls + " time = " + stopWatch.getTime() + " calls/sec= " + ratio; 
	}
}

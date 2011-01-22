package oaiReader.handler.generic;

import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import oaiReader.IHandler;

import org.apache.log4j.Logger;



class HandlerTask<T>
	implements Runnable
{
	private T item;
	private IHandler<T> handler;

	public HandlerTask(T item, IHandler<T> handler)
	{
		this.item = item;
		this.handler = handler;
	}

	
	@Override
	public void run()
	{
		Logger.getLogger(HandlerTask.class).debug("HandlerTask started");

		handler.handle(item);
		
		Logger.getLogger(HandlerTask.class).debug("HandlerTask finished");
	}	
}


/**
 * Whenever a record metadata record is found, resolve the content
 * immediately and a record is created.
 * 
 */
public class ThreadedHandler<T>
	implements IHandler<T>
{
	private ExecutorService executorService;
	private IHandler<T> handler;
	
	public ThreadedHandler()
	{
		this.executorService = Executors.newCachedThreadPool();
	}
	
	public ThreadedHandler(ExecutorService executorService)
	{
		this.executorService =  executorService;
	}
	
	public void setHandler(IHandler<T> handler)
	{
		this.handler = handler;
	}
	
	public IHandler<T> getHandler()
	{
		return handler;
	}
	
	@Override
	public void handle(T item)
	{
		Runnable task = new HandlerTask<T>(item, handler);

		executorService.submit(task);
	}
}


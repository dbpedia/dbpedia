package oaiReader;

import java.io.File;
import java.io.OutputStream;
import java.io.PrintWriter;
import java.util.ArrayList;
import java.util.List;

import org.apache.commons.lang.time.StopWatch;

import filter.IFilter;


/**
 * A wrapper for a task where you can add pre and post processing
 * 
 * Actually this is an EventSource for task-start and task-finished
 * 
 * @author raven
 *
 * @param <I>
 */
class PrePostTaskWrapper<I extends Runnable>
	implements Runnable
{
	private Runnable mainTask;

	private List<Runnable> preTasks = new ArrayList<Runnable>();
	private List<Runnable> postTasks = new ArrayList<Runnable>();
	
	@Override
	public void run()
	{
		for(Runnable task : preTasks)
			task.run();
		
		
		mainTask.run();
		
		for(Runnable task : postTasks)
			task.run();		
	}
	
	public List<Runnable> preTasks()
	{
		return this.preTasks;
	}

	public List<Runnable> postTasks()
	{
		return this.postTasks;
	}	
}


/**
 * This class wrapps a fetch record task and writes out the lastUtcResponceDate
 * to the given file.
 * 
 * TODO move this class to its own file
 * 
 * @author raven
 *
 */
class SaveLastUtcResponseDateTask
	implements Runnable
{
	private FetchRecordTask source; // the time is taken from this task
	private String fileName;
	
	public SaveLastUtcResponseDateTask(
			FetchRecordTask source,
			String fileName)
	{
		this.source = source;
		this.fileName = fileName;
	}
	
	@Override
	public void run()
	{
		Files.createFile(new File(fileName), source.getLastUTCresponseDate());
	}
}



/**
 * This class wrapps a fetch record task and writes out the lastUtcResponceDate
 * to the given file.
 * 
 * TODO move this class to its own file
 * 
 * @author raven
 *
 */
public class SaveLastUtcResponseDateTaskWrapper
	implements Runnable
{
	private FetchRecordTask task;
	private String fileName;
	
	public SaveLastUtcResponseDateTaskWrapper(
			FetchRecordTask task,
			String fileName)
	{
		this.task = task;
		this.fileName = fileName;
	}
	
	private void write()
	{
		Files.createFile(new File(fileName), task.getLastUTCresponseDate());
	}
	
	@Override
	public void run()
	{
		write();
		
		task.run();

		write();
	}
}



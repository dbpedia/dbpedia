package oaiReader;

import java.util.ArrayList;

/**
 * A task composed of a list of tasks
 * There is probably some standard class already for this
 * 
 * @author raven
 *
 */
public class TaskListTask
	extends ArrayList<Runnable>
	implements Runnable
{
	private static final long serialVersionUID = 1L;

	@Override
	public void run()
	{
		for (Runnable task : this)
			task.run();
	}
}

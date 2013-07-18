package oaiReader;

/**
 * Just saves the lastUtcResponceTime of the given task.
 * 
 * @author raven_arkadon
 *
 */
public class SaveStateShutdownHook
	extends Thread
{
	//private 
	
	public void run()
	{
		System.out.println("Test Shutdownhook");
	}
}

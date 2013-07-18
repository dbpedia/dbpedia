package oaiReader;

import java.io.File;

import org.apache.log4j.Logger;




/**
 * Creates record events for all file in a directory.
 * This class first creates a list of all files before starting processing.
 * 
 * 
 * @author raven_arkadon
 *
 */
public class DirectoryRecordReader
	implements Runnable, IProducer<Record>
{
	private File dir;
	private IHandler<Record> handler;
	
	private Logger logger = Logger.getLogger(DirectoryRecordReader.class);
	

	public DirectoryRecordReader(String path)
	{
		this.dir = new File(path);
	}

	public DirectoryRecordReader(String path, IHandler<Record> handler)
	{
		this.dir = new File(path);
		this.handler = handler;
	}
	
	public void setHandler(IHandler<Record> handler)
	{
		this.handler = handler;
	}
	
	public IHandler<Record> getHandler()
	{
		return handler;
	}
	
	/*
	public void setHandler(IHandler<Record> handler)
	{
		this.handler = handler;
	}
	 */

	@Override
	public void run()
	{
		File[] files = dir.listFiles();
		
		if(files == null)
			throw new RuntimeException("Not a directory");

		if(handler == null)
			throw new RuntimeException("Useless action: no handler specified");
		
		for(File file : files)
		{
			String path = file.getAbsolutePath();
			System.out.println("Loading: " + path);
			try {
				Record record = RawRecordSerializer.read(path);
				handler.handle(record);
				logger.info("Success deserializing record:" + path);
			} catch(Exception e) {
				e.printStackTrace();
				logger.info("Failed deserializing record: " + path);
			}
		}
	}
}

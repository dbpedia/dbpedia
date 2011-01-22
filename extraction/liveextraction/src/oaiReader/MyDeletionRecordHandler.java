package oaiReader;

import java.io.File;
import java.io.FileOutputStream;
import java.net.URLEncoder;
import java.util.Properties;



/**
 * Creates a file for each deletion record.
 * Filename is the url encoded oai identifier + .meta
 * 
 * 
 * @author raven_arkadon
 *
 */
public class MyDeletionRecordHandler
	implements IHandler<DeletionRecord>
{
	private String directory;
	
	public MyDeletionRecordHandler(String directory)
	{
		this.directory = directory;
	}
	
	public String getDirectory()
	{
		return directory;
	}
	
	public void handle(DeletionRecord item)
	{
		try {
			String name = item.getOaiId();

			String uri =
				URLEncoder.encode(name, "UTF-8");

			Properties properties = new Properties();
			
			properties.setProperty("identifier", item.getOaiId());
			properties.setProperty("datestamp", item.getDateStamp());
			properties.setProperty("deleted", "true");
			
			String filename = directory + File.separator + uri + ".deleted";

			properties.store(new FileOutputStream(filename), null);
		}catch (Exception e) {
			e.printStackTrace();
			//rootLogger.warn("Error writing: " + uri);
			//rootLogger.warn("Record: " + rec);
		}

	}	
}

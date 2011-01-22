package oaiReader.handler.record;

import java.io.File;
import java.io.FileOutputStream;
import java.net.URLEncoder;
import java.util.Properties;

import oaiReader.IHandler;
import oaiReader.Record;
import oaiReader.RecordMetadata;



public class MetadataWriterRecordHandler
	implements IHandler<Record>
{
	private String directory;
	
	public MetadataWriterRecordHandler(String directory)
	{
		this.directory = directory;
	}
	
	public String getDirectory()
	{
		return directory;
	}
	
	public void handle(Record item)
	{
		try {
			String name = item.getMetadata().getWikipediaURI().toString();
			
			if(name.equals(""))
				name = item.getMetadata().getTitle().getFullTitle();

			String uri =
				URLEncoder.encode(name, "UTF-8");

			RecordMetadata m = item.getMetadata();
			
			Properties properties = new Properties();

			//properties.setProperty("title", m.getTitle());
			//properties.setProperty("type", m.getType());
			properties.setProperty("identifier", m.getOaiId());
			properties.setProperty("uri", m.getWikipediaURI().toString());
			properties.setProperty("language", m.getLanguage());
			
			String filename = directory + File.separator + uri + ".meta";

			properties.store(new FileOutputStream(filename), null);
		}catch (Exception e) {
			e.printStackTrace();
			//rootLogger.warn("Error writing: " + uri);
			//rootLogger.warn("Record: " + rec);
		}

	}	
}

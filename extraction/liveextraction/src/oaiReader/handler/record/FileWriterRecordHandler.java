package oaiReader.handler.record;

import helpers.ExceptionUtil;

import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStream;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.util.zip.GZIPOutputStream;

import org.apache.log4j.Logger;

import oaiReader.IHandler;
import oaiReader.RawRecordSerializer;
import oaiReader.Record;



/**
 * Writes records to files
 * 	Uses RawRecordSerializer
 * 
 * @author raven_arkadon
 *
 */
public class FileWriterRecordHandler
	implements IHandler<Record>
{
	private static Logger logger = Logger.getLogger(FileWriterRecordHandler.class);
	
	private String directory;
	private boolean zip;
	//private String compression;

	private IRecordFileNameGenerator fileNameGenerator;
	
	public FileWriterRecordHandler(String directory, boolean zip, IRecordFileNameGenerator fileNameGenerator)
	{
		this.directory = directory;
		this.zip = zip;
		this.fileNameGenerator = fileNameGenerator;
	}
	
	public String getDirectory()
	{
		return directory;
	}
	
	public void handle(Record item)
	{
		try {			
			String filename =
				directory + File.separator + fileNameGenerator.generate(item);
			
			OutputStream out = null;
			if(zip == true) {
				filename += ".gz";				
				out = new GZIPOutputStream(new FileOutputStream(filename));
			}
			else
				out = new FileOutputStream(filename);

			RawRecordSerializer.write(item, out);

			out.close();
		}catch (Exception e) {
			logger.error(ExceptionUtil.toString(e));
			//e.printStackTrace();
			//rootLogger.warn("Error writing: " + uri);
			//rootLogger.warn("Record: " + rec);
		}

	}	
}

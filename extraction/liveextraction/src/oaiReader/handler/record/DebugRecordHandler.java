package oaiReader.handler.record;

import org.apache.log4j.Logger;

import oaiReader.IHandler;
import oaiReader.Record;


/**
 * A handler for record objects which simply prints out some data.
 * @author raven_arkadon
 *
 */
public class DebugRecordHandler
	implements IHandler<Record>
{
	private Logger logger = Logger.getLogger(DebugRecordHandler.class);
	
	@Override
	public void handle(Record item)
	{
		String msg = 
			"Got a record: " + item.getMetadata().getTitle() + "\n" + 
			"Content: " + item.getContent().getText();
		
		System.out.println(msg);
		//logger.debug(
	}
}

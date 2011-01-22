package oaiReader.handler.record;

import org.apache.log4j.Logger;

import oaiReader.IHandler;
import oaiReader.MediawikiHelper;
import oaiReader.Record;

/**
 * This record handler calls the edit method of the mediawiki-api for each
 * encountered record.
 * 
 */
 public class MediawikiEditRecordHandler
 	implements IHandler<Record>
 {
	 private Logger logger = Logger.getLogger(MediawikiEditRecordHandler.class);
	 private String wikiApiUri;
	 
	 public MediawikiEditRecordHandler(String wikiApiUri)
	 {
		 this.wikiApiUri = wikiApiUri;
	 }
	 
	@Override
	public void handle(Record item)
	{
		try {
			logger.debug("Writing " +
					item.getMetadata().getTitle() + " to " + wikiApiUri);

			MediawikiHelper.edit(
					wikiApiUri,
					item.getMetadata().getTitle().getFullTitle(),
					item.getContent().getText());
			
			logger.debug("Done");
		} catch(Exception e) {
			logger.debug("Failed");
			e.printStackTrace();
		}
	}
 }

package oaiReader;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.net.URLEncoder;
import java.util.Set;

import org.apache.log4j.Logger;


public class DbpediaDeleterRecordDeletionHandler
	implements IHandler<DeletionRecord>
{
	private Logger logger = Logger.getLogger(DbpediaDeleterRecordDeletionHandler.class);
	private DbpediaFacade dbpedia;
	private String directory;
	
	public DbpediaDeleterRecordDeletionHandler(
			String directory,
			DbpediaFacade dbpedia)
	{
		this.dbpedia = dbpedia;
		this.directory = directory;
	}
	

	public void handle(DeletionRecord item)
	{
		try {
			String oaiId = item.getOaiId();
			String n = URLEncoder.encode(oaiId, "UTF-8");
			String filename = directory + File.separator + n + ".delete.sparul";
			
			Set<String> uris = dbpedia.findBlankNodesByOaiId(oaiId);
			
			// if nothing found write to log
			if(uris.isEmpty()) {
				logger.warn("No subjects found for deleting oai-id: " + oaiId);
				return;
			}
			
			FileWriter fw = new FileWriter(filename);
			BufferedWriter bw = new BufferedWriter(fw);
	
			for(String uri : uris)
				bw.write(dbpedia.createDeleteStatement(uri) + "\n");
			
			bw.close();
		}
		catch(Exception e) {
			logger.error("Error", e);
			e.printStackTrace();
		}
	}
}

package oaiReader.handler.record;

import oaiReader.IHandler;
import oaiReader.MediawikiHelper;
import oaiReader.Record;

import org.apache.commons.lang.time.StopWatch;

public class ResolveWikiRecordHandler
	implements IHandler<Record>
{
	private String targetWikiApiUri;
	
	public ResolveWikiRecordHandler(String targetWikiApiUri)
	{
		this.targetWikiApiUri = targetWikiApiUri;
	}
	
	@Override
	public void handle(Record item)
	{
		try {
			StopWatch sw = new StopWatch();
			sw.start();
			String text = item.getContent().getText();
			
			
			//text = text.substring(0, Math.min(3800, text.length() - 1));
			text += text;
			text += text;
			//Document doc = MediawikiHelper.parse(targetWikiApiUri, text);
			//System.out.println(targetWikiApiUri);
			//MediawikiHelper.
			String doc = MediawikiHelper.parseText(targetWikiApiUri, text);
			System.out.println(item.getMetadata().getTitle());
			sw.stop();
			
			System.out.println("XXXTook: " + sw.getTime());
		}
		catch(Exception e)
		{
			e.printStackTrace();
		}
	}
	

}

package iterator;

import java.io.File;

import oaiReader.Files;

import org.apache.commons.collections15.iterators.AbstractIteratorDecorator;
import org.w3c.dom.Document;


public class SaveResponseTimeIterator
	extends AbstractIteratorDecorator<Document>
{
	private File file;
	
	public SaveResponseTimeIterator(OAIRecordIterator iterator, File file)
	{
		super(iterator);
		this.file = file;
	}
	
	@Override
	public Document next()
	{
		Document result = super.next();
		
		String responseDate = 
			((OAIRecordIterator)super.getIterator()).getLastResponseDate();
		
		Files.createFile(file, responseDate);
		
		return result;
	}
}

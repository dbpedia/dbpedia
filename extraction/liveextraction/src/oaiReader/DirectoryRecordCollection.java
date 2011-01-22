package oaiReader;

import helpers.ExceptionUtil;

import java.io.File;
import java.util.Arrays;
import java.util.Iterator;

import org.apache.commons.collections15.Predicate;
import org.apache.commons.collections15.Transformer;
import org.apache.commons.collections15.iterators.FilterIterator;
import org.apache.commons.collections15.iterators.TransformIterator;
import org.apache.log4j.Logger;


/**
 * A collection initialized from files in a directory.
 * 
 * @author raven
 */
public class DirectoryRecordCollection
	implements Iterable<IRecord>
//	implements Collection<IRecord>
{
	private File dir;
	
	private Logger logger = Logger.getLogger(DirectoryRecordReader.class);
	

	public DirectoryRecordCollection(String path)
	{
		this.dir = new File(path);
	}

	@Override
	public Iterator<IRecord> iterator()
	{
		File[] files = dir.listFiles();
		
		if(files == null)
			throw new RuntimeException("Not a directory");
		
		Iterator<File> filtered = new FilterIterator<File>(
				Arrays.asList(files).iterator(),
				new Predicate<File>() {
					@Override
					public boolean evaluate(File file)
					{
						return !file.isDirectory();
					}					
				});
		
		// Return an iterator which deserializes the array of files into
		// records
		return
			new TransformIterator<File, IRecord>(filtered,
				new Transformer<File, IRecord>() {
					@Override
					public IRecord transform(File file)
					{
						String path = file.getAbsolutePath();
						Record record = null;
						try {
							record = RawRecordSerializer.read(path);
							logger.debug("Success deserializing record:" + path); 
							
						} catch(Exception e) {
							logger.error(ExceptionUtil.toString(e));
						}
						return record;
					}
				}
			);
	}
}

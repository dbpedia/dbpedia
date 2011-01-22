package oaiReader;

/**
 * handler for Record which delegates to a handler for IRecord
 * 
 * 
 * @author raven
 *
 */
public class RecordAdapter
	implements IHandler<Record>
{
	private IHandler<IRecord> handler;
	
	public RecordAdapter(IHandler<IRecord> handler)
	{
		if(handler == null)
			throw new NullPointerException();

		this.handler = handler;
	}

	@Override
	public void handle(Record item)
	{
		handler.handle(item);
	}
}

package oaiReader;

import java.util.ArrayList;
import java.util.List;

/**
 * A multiplexer for record handlers.
 * Calls to the handle method are delegated to all registered handlers.
 * 
 * Note that handlers are kept in a list, so they are called in order
 * 
 * @author raven_arkadon
 *
 */
public class MultiHandler<T> 
	implements IHandler<T>
{
	private List<IHandler<T>> handlers = new ArrayList<IHandler<T>>();
	
	@Override
	public void handle(T item)
	{
		for(IHandler<T> handler : this.handlers)
			handler.handle(item);
	}
	
	public List<IHandler<T>> handlers()
	{
		return handlers;
	}
}

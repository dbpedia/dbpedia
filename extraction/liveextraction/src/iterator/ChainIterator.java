package iterator;

import java.util.Collection;
import java.util.Iterator;

public class ChainIterator<T>
	extends PrefetchIterator<T>
{
	private Iterator<? extends Iterator<T>>	metaIterator;

	public ChainIterator(Iterator<? extends Iterator<T>> metaIterator)
	{
		this.metaIterator = metaIterator;
	}

	public ChainIterator(Collection<? extends Iterator<T>> metaContainer)
	{
		this.metaIterator = metaContainer.iterator();
	}

	@Override
	protected Iterator<T> prefetch()
	{
		if (!metaIterator.hasNext())
			return null;

		return metaIterator.next();
	}
}
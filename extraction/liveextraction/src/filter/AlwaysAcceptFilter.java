package filter;


public class AlwaysAcceptFilter<T>
	implements IFilter<T>
{
	@SuppressWarnings("unchecked")
	@Override
	public <U extends IFilter<T>> U getNestedFilter(Class<U> clazz)
	{
		if(this.getClass().isAssignableFrom(clazz))
			return (U)this;
		else
			return null;
	}

	
	@Override
	public boolean evaluate(T item)
	{
		return true;
	}	
}

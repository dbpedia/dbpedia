package filter;

import org.apache.commons.collections15.Predicate;

/**
 * Simple filter interface.
 * 
 * FIXME Maybe get rid of getNestedFilter method, and replace IFilter with
 * predicate directly
 * 
 * @author raven_arkadon
 *
 * @param <T>
 */
public interface IFilter<T>
	extends Predicate<T>
{
	/**
	 * Returns the first nested filter which is a subclass of clazz.
	 * null if no such filter exists.
	 * 
	 * @param <U>
	 * @param clazz
	 * @return
	 */
	<U extends IFilter<T>> U getNestedFilter(Class<U> clazz);
	
	//boolean evaluate(T item);
}

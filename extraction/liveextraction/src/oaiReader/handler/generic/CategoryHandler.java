package oaiReader.handler.generic;

import java.util.HashMap;
import java.util.Map;

import oaiReader.IHandler;
import oaiReader.MultiHandler;


/**
 * A handler which delegates handling based on a classification of the item.
 * A classifier object must be given, which (as the same suggests ;) 
 * classifies the items.
 * 
 * 
 * @author raven_arkadon
 *
 * @param <TItem>
 * @param <TCategory>
 */
public class CategoryHandler<TItem, TCategory>
	implements IHandler<TItem>
{
	private Map<TCategory, MultiHandler<TItem>> categoryToHandler =
		new HashMap<TCategory, MultiHandler<TItem>>();
	
	private IClassifier<TItem, TCategory> classifier;
	
	public CategoryHandler(IClassifier<TItem, TCategory> classifier)
	{
		this.classifier = classifier;
	}
	
	@Override
	public void handle(TItem item)
	{
		TCategory category = classifier.classify(item);
		MultiHandler<TItem> handler = categoryToHandler.get(category);
		
		if(handler != null)
			handler.handle(item);
	}
	
	
	/**
	 * Adds a handler to a set of categories.
	 * 
	 * @param handler
	 * @param categories
	 */
	public void addHandler(IHandler<TItem> handler, TCategory ... categories)
	{
		for(TCategory category : categories) {
			MultiHandler<TItem> handlers = categoryToHandler.get(category);
			
			if(handlers == null) {
				handlers = new MultiHandler<TItem>();
				categoryToHandler.put(category, handlers);
			}

			handlers.handlers().add(handler);
		}
	}
	
	public void removeHandler(IHandler<TItem> handler, TCategory ... categories)
	{
		for(TCategory category : categories) {
			MultiHandler<TItem> handlers = categoryToHandler.get(category);
			
			handlers.handlers().remove(handlers);
			if(handlers.handlers().isEmpty())
				categoryToHandler.remove(category);
		}		
	}
}

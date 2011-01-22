package oaiReader;

import helpers.ExceptionUtil;

import java.util.ArrayDeque;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Deque;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;

import mywikiparser.SimpleTemplateParser;
import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.Pair;
import mywikiparser.ast.TemplateWikiNode;

import org.apache.log4j.Logger;
import org.hibernate.Session;

import templatedb.HibernateUtil;
import templatedb.dao.DaoFactory;
import templatedb.dao.ITemplateAnnotationDao;
import templatedb.dao.impl.HibernateDaoFactory;
import templatedb.entity.PropertyAnnotation;
import templatedb.entity.PropertyMapping;
import templatedb.entity.TemplateAnnotation;

/**
 * Requires a wiki node representation of the record content
 * 
 * @author raven_arkadon
 *
 */
public class TemplateAnnotationExtractor
	implements IHandler<Record>
{	
	private Logger logger = Logger.getLogger(TemplateAnnotationExtractor.class);

	private TaskQueue taskQueue = new TaskQueue();
	
	//private ITemplateAnnotationDao taDao = null;
	//private IPropertyAnnotationDao paDao = null;
	//private IPropertyMappingDao    pmDao = null;
	
	public TemplateAnnotationExtractor()
	{
		logger.info("Starting task queue thread");
		taskQueue.start();
	}



	
	
	
	/**
	 * Do a shallow parse of all 
	 * 
	 * {{Entry | k = v | ... }} 
	 * 
	 * @param node
	 */
	private List<Map<String, String>> handleMappings(IWikiNode listNode)
	{
		List<Map<String, String>> result = new ArrayList<Map<String, String>>();
		
		//System.out.println("Here Mappings");
		
		for(IWikiNode item : listNode.getChildren()) {
			Map<String, String> map = new HashMap<String, String>();
			
			// Only accept template nodes with name "entry"
			TemplateWikiNode entry = WikiParserHelper.getAsTemplateNode("DBpedia_attribute", item);
			if(entry == null)
				continue;

			for(Pair<IWikiNode, IWikiNode> argument : entry.getArguments()) {
			
				// get the name of the key
				String key = SimpleTemplateParser.nodeToText(argument.getFirst());
				if(key == null)
					continue;
				
				String value = SimpleTemplateParser.nodeToText(argument.getSecond());
				if(value == null)
					continue;
		
				map.put(key, value);
			}
	
			if(!map.isEmpty())
				result.add(map);
		}
		
		return result;
	}
	
	private Set<String> handleRelatedClasses(IWikiNode listNode)
	{
		Set<String> result = new HashSet<String>();
		
		String text = SimpleTemplateParser.nodeToText(listNode);
		if(text == null)
			return result;
		
		//String[] items = text.split("\\s+");
		Set<String> items = Collections.singleton(text);
		
		for(String item : items) {
			item = item.trim();
		
			if(!item.isEmpty())
				result.add(item);
			
		}

		return result;
	}
	
	
	public void handle(Record item)
	{
		// Create a task which calls the __handle function
		taskQueue.addTask(new UpdateTemplateDbTask(this, item));
	}
	
    void __handle(Record item)
	{
		IWikiNode root =
			item.getContent().getRepresentations().getSingle(IWikiNode.class);

		String title = item.getMetadata().getTitle().getFullTitle();

		if(root == null) {
			logger.warn("No wiki-node representation for item: " + title);
			return;
		}

		Session session = HibernateUtil.getSessionFactory().getCurrentSession();
		session.beginTransaction();
		
		HibernateDaoFactory daoFactory =
			DaoFactory.instance(HibernateDaoFactory.class);
		daoFactory.setSession(session);
		
		
		ITemplateAnnotationDao taDao = daoFactory.getTemplateAnnotationDao();
		
		logger.debug("Looking up: " + title);
		
		// Retrieve the current template annotation if there is one
		// otherwise create a new one
		TemplateAnnotation currentTa = taDao.findByName(title);
		if(currentTa != null) {
			// Remove the existing template annotation...
			taDao.makeTransient(currentTa);
			
			// And recreate the session
			session.getTransaction().commit();
			session = HibernateUtil.getSessionFactory().getCurrentSession();
			session.beginTransaction();
			daoFactory.setSession(session);			
			taDao = daoFactory.getTemplateAnnotationDao();
			
			logger.debug("Template found in database");
		}
		else
			logger.debug("Template not found in database");

		currentTa = new TemplateAnnotation(title);
		
		// set the oai-identifier
		currentTa.setOaiId(item.getMetadata().getOaiId());
		

		//System.out.println("Here");
		// Iterate over all template nodes and parse template content 
    	for(IWikiNode node : root.getChildren())
    	{
    		TemplateWikiNode templateNode =
    			WikiParserHelper.getAsTemplateNode("DBpedia_template", node);

    		if(templateNode == null)
    			continue;

    		for(Pair<IWikiNode, IWikiNode> argument : templateNode.getArguments()) {
    			String key = SimpleTemplateParser.nodeToText(argument.getFirst());

    			//System.out.println("ArgumentOuter");
    			
    			if(key.equals("mapping")) {
    				List<Map<String, String>> mappingList =
    					handleMappings(argument.getSecond());
    				     				
    				for(Map<String, String> map : mappingList) {
    					String name =
    						WikiParserHelper.getAlternatives(map, "1", "name");
    					String renamedValue = 
    						WikiParserHelper.getAlternatives(map, "2", "renamedValue");
    					String parseHint = 
    						WikiParserHelper.getAlternatives(map, "3", "parseHint");

    					
    					logger.trace("Template Mapping: " + name + ", " + renamedValue + ", " + parseHint);
    					/*
    					String name =
    						WikiParserHelper.getAlternatives(map, "name", "1");
    					String renamedValue = 
    						WikiParserHelper.getAlternatives(map, "renamedValue", "2");
    					String parseHint = 
    						WikiParserHelper.getAlternatives(map, "parseHint", "3");
    					*/
    					
    					
    					// Skip if parseHint and renamedValue not given?
    					// Maybe not, as this way we can generated error report
    					//if(parseHint == null && renamedValue == null)
    					//	continue;

    					//if(parseHint 
    					
    					//PropertyAnnotation pa = new PropertyAnnotation(currentTa, name, renamedValue, parseHint);
    						
    					PropertyAnnotation pa = currentTa.getPropertyAnnotations().get(name);
    					if(pa == null) {
    						pa = new PropertyAnnotation(currentTa, name);
        					//paDao.makePersistent(pa);

        					currentTa.getPropertyAnnotations().put(name, pa);
    					}

    					pa.getPropertyMappings().add(
    							new PropertyMapping(pa, renamedValue, parseHint));
       				}
    					
    				
    			}
    			else if(key.startsWith("relatesToClass")) {
    				Set<String> s = handleRelatedClasses(argument.getSecond());
    				currentTa.getRelatedClasses().addAll(s);
    			}

    			// silently ignore other cases
    		}
       	}
		
    	// Do not persist empty annotations?
    	if(!(currentTa.getRelatedClasses().isEmpty() &&
    			currentTa.getPropertyAnnotations().isEmpty()))
    		taDao.makePersistent(currentTa);

    	session.getTransaction().commit();    	
	}
}


class UpdateTemplateDbTask
	implements Runnable
{
	private TemplateAnnotationExtractor extractor;
	private Record item;
	
	public UpdateTemplateDbTask(
			TemplateAnnotationExtractor extractor, Record item)
	{
		this.extractor = extractor;
		this.item = item;
	}
	
	@Override
	public void run()
	{
		extractor.__handle(item);
	}
	
	@Override
	public String toString()
	{
		return "Template Extraction from: " +
			item.getMetadata().getTitle().getFullTitle();
	}
}


class TaskQueue
	extends Thread
{
	private static Logger logger = Logger.getLogger(TaskQueue.class); 
	
	private int maxSize = 1000;
	private Deque<Runnable> taskQueue = new ArrayDeque<Runnable>();
	
	private boolean shutdownRequested = false;
	
	public void addTask(Runnable task)
	{
		synchronized(taskQueue) {
			taskQueue.add(task);

			while(taskQueue.size() > maxSize)
				taskQueue.poll();

			logger.debug("TemplateDbUpdateTasks in Queue = " + taskQueue.size());
		
			taskQueue.notify();
		}
	}

	
	@Override
	public void run()
	{
		while(shutdownRequested == false) {
			Runnable task;

			synchronized (taskQueue) {
				while (taskQueue.isEmpty()) {
					try {
						logger.info("Task queue empty - going to sleep");
						taskQueue.wait();
					}
					catch(Exception e) {
						logger.warn(ExceptionUtil.toString(e));
					}
				}
					
				task = taskQueue.poll();
				if(task == null) {
					logger.error("Null task detected - shouldn't happen");
					continue;
				}
			}

			for(;;) {
				try {
					logger.info("Starting task [" + task + "]");
					task.run();
					logger.info("Task completed [" + task + "]");
					break;
				}
				catch (Exception e) {
					//System.out.println("Exception: " + e1.getClass());
					//System.out.println(MyCommonHelper.exceptionToString(e1));
					logger.warn(ExceptionUtil.toString(e));

					//logger.info("Task failed [" + task + "]");
					//return;

					try {
						int s = 30;
						logger.info("Retrying Task [" + task + "] in " + s + " seconds");
						Thread.sleep(s * 1000);
						HibernateUtil.reinit();
						//HibernateUtil.getSessionFactory().openSession();
					}
					catch(Exception e2) {
						logger.warn(ExceptionUtil.toString(e2));
					}
				}
			}
		}
	}
	
}



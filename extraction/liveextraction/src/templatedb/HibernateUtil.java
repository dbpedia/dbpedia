package templatedb;


import java.io.File;

import org.hibernate.SessionFactory;
import org.hibernate.cfg.AnnotationConfiguration;


/**
 *
 * @author raven_arkadon
 */
public class HibernateUtil
{
	private static SessionFactory sessionFactory;
	private static AnnotationConfiguration cfg;
	//private static String filename;
	
    public static SessionFactory getSessionFactory()
    {
        return sessionFactory;
    }

    public static void initialize(String filename)
    {
    	//HibernateUtil.filename = filename;
    	//filename = _filename;
    	
    	//cfg.
        cfg = new AnnotationConfiguration();
     	cfg.configure(new File(filename));
		sessionFactory = cfg.buildSessionFactory();    
    }
    
    public static void reinit()
    {
    	if(sessionFactory != null)
    		sessionFactory.close();

		sessionFactory = cfg.buildSessionFactory();    
    }
}

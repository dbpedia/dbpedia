package iddb;


import java.io.File;

import org.hibernate.*;
import org.hibernate.cfg.*;


/**
 *
 * @author raven_arkadon
 */
public class IdDbUtil
{
	private static final SessionFactory sessionFactory;

    public static SessionFactory getSessionFactory()
    {
        return sessionFactory;
    }

    static
    {
    	final AnnotationConfiguration cfg = new AnnotationConfiguration();
 
		//cfg.configure(new File("TemplateDb.mysql.cfg.xml"));
    	cfg.configure(new File("IdDb.virtuoso.cfg.xml"));
		cfg.buildSessionFactory();
		sessionFactory = cfg.buildSessionFactory();
    }   
}

package templatedb.dao.impl;

import org.hibernate.Session;

import templatedb.dao.DaoFactory;
import dao.hibernate.GenericHibernateDao;


public class HibernateDaoFactory
	extends DaoFactory
{
	private Session session = null;

	
	//private HibernateDaoFactory(S
	
	public void setSession(Session session)
	{
		this.session = session;
	}
	
    // You could override this if you don't want HibernateUtil for lookup
    protected Session getCurrentSession()
    {
    	if(session == null)
    		throw new RuntimeException("Session is null - probably not set");
    	//	return HibernateUtil.getSessionFactory().getCurrentSession();
    	//else
    		return session;
    }

    public TemplateAnnotationHibernateDao getTemplateAnnotationDao()
    {
        return instantiateDao(TemplateAnnotationHibernateDao.class);
    }

    public PropertyAnnotationHibernateDao getPropertyAnnotationDao()
    {
        return instantiateDao(PropertyAnnotationHibernateDao.class);
    }

    public PropertyMappingHibernateDao getPropertyMappingDao()
    {
        return instantiateDao(PropertyMappingHibernateDao.class);
    }

    @SuppressWarnings("unchecked")
    private <T extends GenericHibernateDao> T instantiateDao(Class<T> daoClass) {
        try {
        	GenericHibernateDao dao = (GenericHibernateDao)daoClass.newInstance();
            dao.setSession(getCurrentSession());
            return (T)dao;
        } catch (Exception ex) {
            throw new RuntimeException("Can not instantiate DAO: " + daoClass, ex);
        }
    }
}

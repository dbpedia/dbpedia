package templatedb.dao.impl;

import org.hibernate.criterion.Restrictions;

import dao.hibernate.GenericHibernateDao;
import templatedb.dao.ITemplateAnnotationDao;
import templatedb.entity.TemplateAnnotation;


public class TemplateAnnotationHibernateDao
	extends GenericHibernateDao<TemplateAnnotation, Integer>
	implements ITemplateAnnotationDao
{
	public TemplateAnnotation findByName(String name)
	{
		return findUniqueByCriteria(Restrictions.eq("name" , name));
	}
}

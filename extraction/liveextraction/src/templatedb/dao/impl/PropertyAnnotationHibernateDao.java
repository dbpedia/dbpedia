package templatedb.dao.impl;

import org.hibernate.criterion.Restrictions;

import dao.hibernate.GenericHibernateDao;
import templatedb.dao.IPropertyAnnotationDao;
import templatedb.entity.PropertyAnnotation;
import templatedb.entity.TemplateAnnotation;


public class PropertyAnnotationHibernateDao
    extends GenericHibernateDao<PropertyAnnotation, Integer>
    implements IPropertyAnnotationDao
{
	public PropertyAnnotation findByParentAndName(TemplateAnnotation parent, String name)
	{
		return findUniqueByCriteria(
				Restrictions.and(
						Restrictions.eq("parent" , parent),
						Restrictions.eq("name" , name)));
	}
}

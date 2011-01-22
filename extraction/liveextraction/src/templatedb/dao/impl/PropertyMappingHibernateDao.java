package templatedb.dao.impl;

import templatedb.dao.IPropertyMappingDao;
import templatedb.entity.PropertyAnnotation;
import dao.hibernate.GenericHibernateDao;

public class PropertyMappingHibernateDao
extends GenericHibernateDao<PropertyAnnotation, Integer>
implements IPropertyMappingDao
{
	/*
	public PropertyAnnotation findByParentAndName(TemplateAnnotation parent, String name)
	{
		return findUniqueByCriteria(
				Restrictions.and(
						Restrictions.eq("parent" , parent),
						Restrictions.eq("name" , name)));
	}
	*/
}

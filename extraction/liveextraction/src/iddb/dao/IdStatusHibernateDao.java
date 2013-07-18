package iddb.dao;

import iddb.entity.IdStatus;

import org.hibernate.criterion.Restrictions;

import dao.hibernate.GenericHibernateDao;


public class IdStatusHibernateDao
	extends GenericHibernateDao<IdStatus, Integer>
	//implements IIdStatusDao
{
	public IdStatus findByName(String name)
	{
		return findUniqueByCriteria(Restrictions.eq("name" , name));
	}
}

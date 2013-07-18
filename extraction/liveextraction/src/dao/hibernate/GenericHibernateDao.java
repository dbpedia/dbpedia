package dao.hibernate;

import java.io.Serializable;
import java.lang.reflect.ParameterizedType;
import java.util.List;

import org.hibernate.Criteria;
import org.hibernate.LockMode;
import org.hibernate.Session;
import org.hibernate.criterion.Criterion;
import org.hibernate.criterion.Example;

import dao.generic.IGenericDao;

// Code taken from https://www.hibernate.org/328.html

public abstract class GenericHibernateDao<T, TId extends Serializable>
	implements IGenericDao<T, TId>
{

	private Class<T> persistentClass;
	private Session session;

	@SuppressWarnings("unchecked")
	public GenericHibernateDao()
	{
		this.persistentClass = (Class<T>) ((ParameterizedType) getClass()
				.getGenericSuperclass()).getActualTypeArguments()[0];
	}

	public void setSession(Session s)
	{
		this.session = s;
	}

	protected Session getSession()
	{
		if (session == null)
			throw new IllegalStateException(
					"Session has not been set on DAO before usage");
		return session;
	}

	public Class<T> getPersistentClass()
	{
		return persistentClass;
	}

	@SuppressWarnings("unchecked")
	public T findById(TId id, boolean lock)
	{
		T entity;
		if(lock)
			entity = (T) getSession().load(getPersistentClass(), id,
					LockMode.UPGRADE);
		else
			entity = (T) getSession().load(getPersistentClass(), id);

		return entity;
	}

	public List<T> findAll()
	{
		return findByCriteria();
	}


	@SuppressWarnings("unchecked")
	public List<T> findByExample(T exampleInstance)
	{
		Criteria crit = getSession().createCriteria(getPersistentClass());
		Example example = Example.create(exampleInstance);

		crit.add(example);
		return crit.list();	
	}

	@SuppressWarnings("unchecked")
	public List<T> findByExample(T exampleInstance, String[] excludeProperty)
	{
		Criteria crit = getSession().createCriteria(getPersistentClass());
		Example example = Example.create(exampleInstance);

		for (String exclude : excludeProperty)
			example.excludeProperty(exclude);

		crit.add(example);
		return crit.list();
	}


	public T makePersistent(T entity)
	{
		getSession().saveOrUpdate(entity);
		return entity;
	}

	public void makeTransient(T entity)
	{
		getSession().delete(entity);
	}

	public void flush()
	{
		getSession().flush();
	}

	public void clear()
	{
		getSession().clear();
	}

	/**
	 * Use this inside subclasses as a convenience method.
	 */
	@SuppressWarnings("unchecked")
	protected List<T> findByCriteria(Criterion... criterion)
	{
		Criteria crit = getSession().createCriteria(getPersistentClass());
		
		for (Criterion c : criterion)
			crit.add(c);

		return crit.list();
	}

	/**
	 * Use this inside subclasses as a convenience method.
	 */
	@SuppressWarnings("unchecked")
	protected T findUniqueByCriteria(Criterion... criterion)
	{
		Criteria crit = getSession().createCriteria(getPersistentClass());
		
		for (Criterion c : criterion)
			crit.add(c);

		return (T)crit.uniqueResult();
	}
}

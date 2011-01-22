package dao.generic;

import java.io.Serializable;
import java.util.List;

// Code taken from https://www.hibernate.org/328.html

public interface IGenericDao<T, TId extends Serializable>//, TNaturalId extends Serializable>
{

    T findById(TId id, boolean lock);
    //T findByNaturalId(TNaturalId naturalId);

    List<T> findAll();
    List<T> findByExample(T exampleInstance);

    T makePersistent(T entity);
    void makeTransient(T entity);
}

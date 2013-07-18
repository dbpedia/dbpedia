package templatedb.dao;

import templatedb.entity.PropertyAnnotation;
import templatedb.entity.TemplateAnnotation;
import dao.generic.IGenericDao;

public interface IPropertyAnnotationDao
	extends IGenericDao<PropertyAnnotation, Integer>
{
	PropertyAnnotation findByParentAndName(TemplateAnnotation parent, String name);
}

package templatedb.dao;

import templatedb.entity.PropertyAnnotation;
import templatedb.entity.TemplateAnnotation;
import dao.generic.IGenericDao;

public interface IPropertyMappingDao
	extends IGenericDao<PropertyAnnotation, Integer>
{
	//PropertyMapping findByParentAndName(TemplateAnnotation parent, String name);
}

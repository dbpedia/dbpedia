package templatedb.dao;

import templatedb.entity.TemplateAnnotation;
import dao.generic.IGenericDao;

public interface ITemplateAnnotationDao
	extends IGenericDao<TemplateAnnotation, Integer>
{
	TemplateAnnotation findByName(String name);
}

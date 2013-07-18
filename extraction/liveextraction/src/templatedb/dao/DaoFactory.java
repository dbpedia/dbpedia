package templatedb.dao;



public abstract class DaoFactory
{
    /**
     * Factory method for instantiation of concrete factories.
     */
    public static <T extends DaoFactory> T instance(Class<T> factory)
    {
        try {
            return (T)factory.newInstance();
        } catch (Exception ex) {
            throw new RuntimeException("Couldn't create DAOFactory: " + factory);
        }
    }

    public abstract ITemplateAnnotationDao getTemplateAnnotationDao();
    public abstract IPropertyAnnotationDao getPropertyAnnotationDao();
    public abstract IPropertyMappingDao    getPropertyMappingDao();
}

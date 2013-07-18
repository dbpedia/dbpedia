package helpers;

import java.sql.ResultSet;
import java.sql.SQLException;

public class SQLUtil
{
	/**
	 * Returns the 1st column of the first row or null of there is no row.
	 * Also throws exception if there is more than 1 row and 1 column.
	 * 
	 * @param connection
	 * @param query
	 * @return
	 * @throws SQLException
	 */
	@SuppressWarnings("unchecked")
	public static <T> T single(ResultSet rs, Class<T> clazz)
		throws SQLException
	{
		if(rs.getMetaData().getColumnCount() != 1)
			throw new RuntimeException("only a single column expected");
		
		T result = null;

		if(rs.next()) {
			Object o = rs.getObject(1);;
			//System.out.println("Result = " + o);
			result = (T)o;

			if(rs.next()) 
				throw new RuntimeException("only at most 1 row expected");
		}
		
		return result;
	}
}

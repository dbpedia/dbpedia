package oaiReader;

import java.sql.ResultSet;

public interface ISimpleConnection
{
	ResultSet query(String query)
		throws Exception;
	
	boolean update(String query)
		throws Exception;
}

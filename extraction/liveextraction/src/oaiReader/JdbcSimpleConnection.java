package oaiReader;

import java.sql.Connection;
import java.sql.ResultSet;

public class JdbcSimpleConnection
	implements ISimpleConnection
{
	private Connection connection;

	public JdbcSimpleConnection(Connection connection)
	{
		this.connection = connection;
	}

	@Override
	public ResultSet query(String query)
		throws Exception
	{
		return connection.createStatement().executeQuery(query);
	}

	@Override
	public boolean update(String query)
		throws Exception
	{
		return connection.createStatement().execute(query);
	}
}

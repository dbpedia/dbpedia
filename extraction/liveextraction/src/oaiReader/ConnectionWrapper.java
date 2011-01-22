package oaiReader;

import java.sql.Connection;
import java.sql.DriverManager;

import org.apache.log4j.Logger;

public class ConnectionWrapper
{
	private Logger					logger	= Logger
													.getLogger(ConnectionWrapper.class);

	private String					url;
	private String					username;
	private String					password;

	transient private Connection	connection;

	public ConnectionWrapper(String url, String username, String password)
		throws Exception
	{
		this.url = url;
		this.username = username;
		this.password = password;

		reconnect();
	}

	public void reconnect()
		throws Exception
	{
		try {
			logger.debug("Connecting to " + url);
			connection = DriverManager.getConnection(url, username, password);
			//DriverManager.getConnection(url, info)
		}
		catch (Exception e) {
			logger.info("Could not connect to " + url);
			throw e;
		}

		// return false;
	}

	public Connection getConnection()
	// throws SQLException
	{
		return connection;
	}
}

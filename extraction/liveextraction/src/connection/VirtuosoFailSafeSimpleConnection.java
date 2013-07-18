package connection;

import helpers.ExceptionUtil;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

import oaiReader.ConnectionWrapper;
import oaiReader.ISimpleConnection;

import org.apache.log4j.Logger;

import virtuoso.jdbc4.VirtuosoException;


public class VirtuosoFailSafeSimpleConnection
	implements ISimpleConnection
{	
	private static Logger logger = Logger.getLogger(VirtuosoFailSafeSimpleConnection.class);

	private ConnectionWrapper cw;

	public VirtuosoFailSafeSimpleConnection(ConnectionWrapper cw)
	{
		this.cw = cw;
	}
	
	private void handleException(VirtuosoException e)
		throws VirtuosoException
	{
		switch(e.getErrorCode()) {
			case VirtuosoException.SQLERROR:
				throw e;
		}

		logger.warn(ExceptionUtil.toString(e));
	
		reconnectLoop();
	}

	private void reconnectLoop()
	{
		for(;;) {
			try {				
				logger.info("Attempting to reconnect in 30 seconds");
				Thread.sleep(30000);
				cw.reconnect();
				
				return;
			}
			catch(Exception e) {
				logger.debug(ExceptionUtil.toString(e));
			}
		}
	}
	
	public ResultSet query(String query)
		throws SQLException
	{
		for(;;) {
			try {
				Connection connection = cw.getConnection();
				
				
				logger.trace("Querying: " + query);
				
				Statement stmt = connection.createStatement();
				return stmt.executeQuery(query);
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}
	}
	
	public boolean update(String query)
		throws SQLException
	{
		for(;;) {
			try {
				Connection connection = cw.getConnection();
				
				logger.trace("Executing: " + query);

				Statement stmt = connection.createStatement();
				return stmt.execute(query);
			}
			catch(VirtuosoException e) {
				handleException(e);
			}
		}
	}
}

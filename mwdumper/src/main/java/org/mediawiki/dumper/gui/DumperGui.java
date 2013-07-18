package org.mediawiki.dumper.gui;

import java.sql.SQLException;
import javax.swing.JFrame;
import javax.swing.JOptionPane;
import java.io.IOException;
import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.Statement;

import org.mediawiki.dumper.Tools;
import org.mediawiki.importer.DumpWriter;
import org.mediawiki.importer.SqlServerStream;
import org.mediawiki.importer.SqlWriter;
import org.mediawiki.importer.SqlWriter14;
import org.mediawiki.importer.SqlWriter15;
import org.mediawiki.importer.XmlDumpReader;

public class DumperGui {
	private DumperWindow gui;
	
	// status
	public boolean running = false;
	public boolean connected = false;
	public boolean schemaReady = false;
	
	public static final int
		  DBTYPE_MYSQL = 0
		, DBTYPE_PGSQL = 1
	;

	// other goodies
	String host = "localhost";
	String port = "3306";
	String username = "root";
	String password = "";
	
	String schema = "1.5";
	String dbname = "wikidb";
	String prefix = "";
	
	XmlDumpReader reader;
	Connection conn;
	private int dbtype;

	String driverForDatabase(int dbtype) {
		switch (dbtype) {
		case DBTYPE_MYSQL:
			return "com.mysql.jdbc.Driver";
		case DBTYPE_PGSQL:
			return "org.postgresql.Driver";
		default:
			return null;
		}
	}

	String urlForDatabase(int dbtype, String host, String port, String username, String password) {
		switch (dbtype) {
		case DBTYPE_MYSQL:
			return
				"jdbc:mysql://" + host +
				":" + port +
				"/" + // dbname +
				"?user=" + username + 
				"&password=" + password +
				"&useUnicode=true" +
				"&characterEncoding=UTF-8" +
				"&jdbcCompliantTruncation=false";
		case DBTYPE_PGSQL:
			return "jdbc:postgresql://" + host +
				":" + port +
				"/" +
				"?user=" + username +
				"&password=" + password;
		default:
			return null;
		}
	}

	void connect(int dbtype, String host, String port, String username, String password) {
		assert !connected;
		assert conn == null;
		assert !running;
		assert !schemaReady;
		
		try {
			Class.forName(driverForDatabase(dbtype)).newInstance();
		} catch (ClassNotFoundException ex) {
			ex.printStackTrace();
		} catch (InstantiationException ex) {
			ex.printStackTrace();
		} catch (IllegalAccessException ex) {
			ex.printStackTrace();
		}
		gui.setDatabaseStatus("Connecting...");
		try {
			// fixme is there escaping? is this a url? fucking java bullshit
			String url = urlForDatabase(dbtype, host, port, username, password);
			System.err.println("Connecting to " + url);
			conn = DriverManager.getConnection(url);
			connected = true;
			this.dbtype = dbtype;
			gui.setDatabaseStatus("Connected.");
			gui.showFields();
			checkSchema();
		} catch (SQLException ex) {
			JOptionPane.showMessageDialog(gui,
				"Failed to connect to database: " + ex.getMessage(),
				"Database Connection Error",
				JOptionPane.ERROR_MESSAGE);
			gui.setDatabaseStatus("Failed to connect.");
			ex.printStackTrace();
		}
		
		assert (connected == (conn != null));
	}
	
	void disconnect() {
		assert connected;
		assert conn != null;
		assert !running;
		try {
			conn.close();
			conn = null;
			connected = false;
			gui.setDatabaseStatus("Disconnected.");
			gui.showFields();
			checkSchema();
		} catch (SQLException ex) {
			ex.printStackTrace();
		}
		assert !connected;
		assert conn == null;
	}
	
	void setDbname(String dbname) {
		this.dbname = dbname;
		checkSchema();
	}
	
	void setPrefix(String prefix) {
		this.prefix = prefix;
		checkSchema();
	}
	
	void setSchema(String schema) {
		this.schema = schema;
		checkSchema();
	}
	
	void checkSchema() {
		schemaReady = false;
		if (connected) {
			gui.setSchemaStatus("Checking...");
			try {
				conn.setCatalog(dbname);
				String[] tables = testTables();
				for (int i = 0; i < tables.length; i++) {
					Statement sql = conn.createStatement();
					sql.execute("SELECT 1 FROM " + tables[i] + " LIMIT 0");
				}
				schemaReady = true;
				gui.setSchemaStatus("Ready");
			} catch (SQLException e) {
				gui.setSchemaStatus("Error: " + e.getMessage());
			}
		} else {
			gui.setSchemaStatus("Not connected.");
		}
		gui.showFields();
		assert !(schemaReady && !connected) : "Schema can't be ready if disconnected.";
	}
	
	String[] testTables() {
		if (schema.equals("1.4"))
			return new String[] {
				prefix + "cur",
				prefix + "old"};
		else
			return new String[] {
				prefix + "page",
				prefix + "revision",
				prefix + (this.dbtype == DBTYPE_MYSQL ? "text" : "pagecontent")};
	}

	void startImport(String inputFile) throws IOException, SQLException {
		assert connected;
		assert conn != null;
		assert schemaReady;
		assert !running;
		
		// TODO work right ;)
		final InputStream stream = Tools.openInputFile(inputFile);
		//DumpWriter writer = new MultiWriter();
		conn.setCatalog(dbname);
		DumpWriter writer = openWriter();
		DumpWriter progress = gui.getProgressWriter(writer, 1000);
		reader = new XmlDumpReader(stream, progress);
		new Thread() {
			public void run() {
				running = true;
				gui.showFields();
				gui.setProgress("Starting import...");
				try {
					reader.readDump();
					stream.close();
				} catch(IOException e) {
					e.printStackTrace();
					gui.setProgress("FAILED: " + e.getMessage());
				}
				running = false;
				reader = null;
				gui.showFields();
			}
		}.start();
	}
	
	private SqlWriter.Traits getTraits() {
		switch (dbtype) {
		case DBTYPE_MYSQL:
			return new SqlWriter.MySQLTraits();
		case DBTYPE_PGSQL:
			return new SqlWriter.PostgresTraits();
		default:
			return null;
		}
	}

	DumpWriter openWriter() {
		SqlServerStream sqlStream = new SqlServerStream(conn);
		/* XXX should have mysql/postgres selection */
		if (schema.equals("1.4"))
			return new SqlWriter14(getTraits(), sqlStream, prefix);
		else
			return new SqlWriter15(getTraits(), sqlStream, prefix);
	}
	
	void abort() {
		// Request an abort!
		gui.setProgress("Aborting import...");
		reader.abort();
	}

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		// Set up some prettification if we're on Mac OS X
		System.setProperty("apple.laf.useScreenMenuBar", "true");
		System.setProperty("com.apple.mrj.application.apple.menu.about.name", "MediaWiki Import");
		
		DumperGui manager = new DumperGui();
	}
	
	public DumperGui() {
		gui = new DumperWindow(this);
		gui.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		gui.setVisible(true);
	}
}

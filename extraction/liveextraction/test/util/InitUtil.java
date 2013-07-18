package util;

import java.io.File;
import java.sql.Connection;
import java.sql.DriverManager;

import oaiReader.ComplexGroupTripleManager;
import oaiReader.IPrefixResolver;
import oaiReader.PrefixResolver;

import org.apache.log4j.ConsoleAppender;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.apache.log4j.SimpleLayout;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

import sparql.ISparulExecutor;
import sparql.VirtuosoJdbcSparulExecutor;
import test.TripleManagerTest;
import triplemanagement.IGroupTripleManager;

public class InitUtil
{
	public static void initLoggers(Ini ini)
	{
		Section section = ini.get("LOGGING");		
		String log4jConfigFile = section.get("log4jConfigFile");		

		if(log4jConfigFile != null) {
			System.out.println("Loading log config from file: '" + log4jConfigFile + "'");
			PropertyConfigurator.configure(log4jConfigFile);
		}
		else {
			System.out.println("No log config - using default");			
			SimpleLayout layout = new SimpleLayout();
		    ConsoleAppender consoleAppender = new ConsoleAppender(layout);
			Logger.getRootLogger().addAppender(consoleAppender);
		}
	}
}

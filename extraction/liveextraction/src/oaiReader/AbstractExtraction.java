package oaiReader;

import java.io.File;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.Authenticator;
import java.net.PasswordAuthentication;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.GnuParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.log4j.ConsoleAppender;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.apache.log4j.SimpleLayout;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

import ORG.oclc.oai.harvester2.verb.ListRecords;



public abstract class AbstractExtraction
{
    //protected final Logger logger;
    protected Ini ini;
    
	// Command line options
    protected org.apache.commons.cli.Options cliOptions;

    protected abstract Logger getLogger();
	
	public <T> AbstractExtraction(
			String[] args,
			String iniFilename)
		throws Exception
	{
		initCliOptions(iniFilename);
		
		printHelp();

		CommandLineParser cliParser = new GnuParser();
		CommandLine commandLine = cliParser.parse(cliOptions, args);
				
		if (commandLine.hasOption("i"))
			iniFilename = commandLine.getOptionValue("i");

		
		System.out.println("Loading ini: '" + iniFilename + "'");
		ini = loadIni(iniFilename);
		
		initLoggers();
		//logger = Logger.getLogger(getClass());		
		//debug(ini);
		
		initOai(ini);
		
		//run(ini);
		//logger.info("This thread is now existing.");
	}
	
	
	protected abstract void run() throws Exception;
	

	
	/*************************************************************************/
	/* Init                                                                  */
	/*************************************************************************/	
	private void initCliOptions(String iniFilename)
	{
		cliOptions = new org.apache.commons.cli.Options();
		
		cliOptions
			.addOption("i", "ini", true, "Ini file. Default is: '" + iniFilename + '"');		
	}
	
	private Ini loadIni(String filename)
		throws InvalidFileFormatException, IOException
	{
		File file = new File(filename);
		if(!file.exists())
			throw new RuntimeException("Ini file '" + filename + "' not found");
	
		return new Ini(file);
	}


	private void initLoggers()
	{
		// A hack to get rid of double initialization caused by OAI-Harvester
		new ListRecords();
		Logger.getRootLogger().removeAllAppenders();
		
		Section section = ini.get("LOGGING");

		String log4jConfigFile = section.get("log4jConfigFile");
		
		if(log4jConfigFile != null) {
			System.out.println("Loading log config from file: '" + log4jConfigFile + "'");
			PropertyConfigurator.configure(log4jConfigFile);
		}
		else {
			System.out.println("Not log config - using default");			
			SimpleLayout layout = new SimpleLayout();
		    ConsoleAppender consoleAppender = new ConsoleAppender(layout);
			Logger.getRootLogger().addAppender(consoleAppender);
		}
		
	}

	
	public static void initOai(Ini ini)
		throws Exception
	{
		Section section = ini.get("HARVESTER");
		
		String username = section.get("username");
		String passwordFile = section.get("passwordFile");
		
		File file = new File(passwordFile);
		String password = (Files.readFile(file)).trim();
		
		authenticate(username, password);
	}

	/*************************************************************************/
	/* Various methods                                                       */
	/*************************************************************************/	
	protected String getStartDateNow()
	{
		return UtcHelper.transformToUTC(System.currentTimeMillis());	
	}
	
	protected String readStartDate(String filename)
	{ 
		File file = new File(filename);
		if(!file.exists())
			return getStartDateNow();
		else {
			try {
				return Files.readFile(file).trim();
			}
			catch(Exception e) {
				getLogger().warn("Error reading " + filename + " - using current time");
				return getStartDateNow();
			}
		}
	}

	public static void authenticate(final String username, final String password)
	{		
		Authenticator.setDefault(new Authenticator() {
		    @Override
			protected PasswordAuthentication getPasswordAuthentication() {
		        return new PasswordAuthentication(username,
		        								  password.toCharArray());
		    }
		});
	}

	
	/*************************************************************************/
	/* Command line helpers                                                  */
	/*************************************************************************/	
	public void printHelp()
	{
		HelpFormatter helpFormatter = new HelpFormatter();
		helpFormatter.printHelp("TODO", cliOptions);
	}

	/**
	 * Print usage information to provided OutputStream.
	 */
	public void printUsage()
	{
		String applicationName = getClass().getName();
		
		PrintWriter writer = new PrintWriter(System.out);
		HelpFormatter usageFormatter = new HelpFormatter();
		usageFormatter.printUsage(writer, 80, applicationName, cliOptions);
		writer.close();
	}
}

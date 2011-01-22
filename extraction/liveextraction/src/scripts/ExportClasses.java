package scripts;

import helpers.StringUtil;

import java.io.File;
import java.io.Serializable;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.Iterator;

import oaiReader.MediawikiHelper;
import oaiReader.WhatLinksHereArticleLoader;

import org.apache.commons.collections15.iterators.TransformIterator;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

import transformer.CastTransformer;
import collections.PersistentQueue;




public class ExportClasses
{
	private static Logger logger = Logger.getLogger(ExportClasses.class);
	
	public static void main(String[] args)
		throws Exception
	{
		String filename = "classes.dat";
		
		//create(filename);
		upload(filename);
	}

	/**
	 * Makes sure we don't overwrite anything
	 * 
	 * @param wiki
	 * @param edit
	 */
	private static void safeEdit(Wiki wiki, WikiEdit edit)
		throws Exception
	{
		String export = wiki.export(edit.getTitle());
		System.out.println("Export is: " + export);
		//WhatLinksHereArticleLoader.
		
		
		
		
		wiki.edit(edit.getTitle(), edit.getText(), edit.getSummary(), edit.isMinor());		
	}
	
	public static void upload(String filename)
		throws Exception
	{
		Ini ini = new Ini(new File("src/scripts/config.ini"));
		Section section = ini.get("WIKIEDIT");

		String username = section.get("username");
		String password = section.get("password");
		String domain   = section.get("domain");
		//String domain   = section.get("domain");
		
		Wiki wiki = new Wiki("meta.wikimedia.org");
		//Wiki wiki = new Wiki("localhost/wiki", "");
		//Wiki wiki = new Wiki(domain, "");
		wiki.login(username, password.toCharArray());
		
		wiki.setAssertionMode(Wiki.ASSERT_BOT);
		
		System.out.println("THIS IS YOUR LAST CHANCE TO ABORT - AUTO-COMMENCING IN 10 SECONDS");
		Thread.sleep(10000);
		
		PersistentQueue queue = new PersistentQueue(filename);
		Iterator<WikiEdit> it = new TransformIterator<Object, WikiEdit>(queue.iterator(), new CastTransformer<Object, WikiEdit>());
		
		int counter = 0;
		while(it.hasNext()) {			
			++counter;
			WikiEdit edit = it.next();
			
			if(counter < 178) {
				continue;
			}

			//if(edit.getTitle().equalsIgnoreCase("DBpedia/ontology/Actor"))
			//	continue;

			System.out.println("Counter = " + counter);
			System.out.println(edit.getTitle());
			System.out.println(edit.getText());
			System.out.println(edit.getSummary());
			System.out.println(edit.isMinor());
		
			System.out.println("---------------------------");
			
			safeEdit(wiki, edit);
			
			Thread.sleep(60000);
			//*
			/*
			if(counter >= 5) {
				break;
			}
			*/
			//*/
			//break;
		}
		
		logger.info("Uploaded " + counter + " records");
	}
	
	public static void create(String filename)
		throws Exception
	{		
		Ini ini = new Ini(new File("src/scripts/config.ini"));
		Section section = ini.get("BERLIN_DB");

		String connectionIRI = section.get("uri");
		String username = section.get("username");
		String password = section.get("password");

		Class.forName("com.mysql.jdbc.Driver").newInstance();
		Connection con = DriverManager.getConnection(connectionIRI, username, password);
	
		String query = "SELECT t1.name, t1.label, t2.name AS superClass FROM class t1 LEFT JOIN class t2 ON t1.parent_id=t2.id ORDER BY t1.name"; 

		Statement stmt = con.createStatement();
		ResultSet rs = stmt.executeQuery(query);
		

		PersistentQueue queue = new PersistentQueue(filename);
		// Overwrite if file exists
		queue.clear();
		
		int counter = 0;
		while(rs.next()) {
			++counter;
			
			//String superClass = StringUtil.ucFirstOnly(rs.getString("superClass"));
			String superClass = StringUtil.trim(rs.getString("superClass"));
			String label      = StringUtil.trim(rs.getString("label"));
			//String title      = "DBpedia/ontology/" + StringUtil.ucFirstOnly(rs.getString("name"));
			String title      = "User:DBpedia-Bot/ontology/" + rs.getString("name");
			
			if(superClass.equalsIgnoreCase("thing"))
				superClass = "";
			
			String text =
				"{{DBpedia Class\n" +
				//"| rdfs:label = " + label + "\n" +
				"| rdfs:label@en = " + label + "\n" +
				"| rdfs:label@de =\n" +
				"| rdfs:label@fr =\n" +
				//"| rdfs:comment =\n" +
				"| rdfs:comment@en =\n" +
				"| rdfs:comment@de =\n" +
				"| rdfs:comment@fr =\n" +
				"| owl:disjointWith =\n" +
				"| owl:equivalentClass =\n" +
				"| rdfs:seeAlso =\n" +
				"| rdfs:subClassOf = " + superClass + "\n" +
				"}}";
		
			WikiEdit edit = new WikiEdit(title, text, "Added initial Class-definition", false); 
			queue.push(edit);
			logger.info("Wrote class definition for '" + title + "'");
		}
		
		//logger.info("Wrote " + counter + " records");
	}	
}

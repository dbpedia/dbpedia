package scripts;

import helpers.StringUtil;

import java.io.File;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;

import org.apache.commons.collections15.iterators.TransformIterator;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

import transformer.CastTransformer;
import collections.PersistentQueue;

//http://www.w3.org/TR/owl-ref/#Datatype


public class ExportProperties
{
	private static Logger logger = Logger.getLogger(ExportProperties.class);
	
	public static void main(String[] args)
		throws Exception
	{
		String filename = "properties.dat";
		
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
		//System.out.println(edit.getText());
		//String export = wiki.export(edit.getTitle());
		//System.out.println("Export is: " + export);
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

		//System.out.println("Domain = " + domain);

		//Wiki wiki = new Wiki("localhost/wiki", "");
		//Wiki wiki = new Wiki(domain);
		Wiki wiki = new Wiki("meta.wikimedia.org");
		wiki.login(username, password.toCharArray());

		System.out.println("THIS IS YOUR LAST CHANCE TO ABORT - AUTO-COMMENCING IN 10 SECONDS");
		Thread.sleep(10000);
		
		
		PersistentQueue queue = new PersistentQueue(filename);
		Iterator<WikiEdit> it = new TransformIterator<Object, WikiEdit>(queue.iterator(), new CastTransformer<Object, WikiEdit>());
		
		int offset = 9;

		
		int counter = 0;
		int index = 0;
		while(it.hasNext()) {
			if(index++ < offset)
				continue;
			

			WikiEdit edit = it.next();
			
			System.out.println(edit.getTitle());
			System.out.println(edit.getText());
			System.out.println(edit.getSummary());
			System.out.println(edit.isMinor());
		
			System.out.println("---------------------------");
			
			safeEdit(wiki, edit);
			Thread.sleep(60000);
			//Thread.sleep(5000);
			++counter;
			
			//if(counter >= 200)
			//	break;

			logger.info("Uploaded " + counter + " records");
		}
		
		logger.info("Done");
	}


	public static void create(String filename)
		throws Exception
	{
		System.out.println("Export started...");

		Ini ini = new Ini(new File("src/scripts/config.ini"));
		Section section = ini.get("BERLIN_DB");

		String connectionIRI = section.get("uri");
		String username = section.get("username");
		String password = section.get("password");

		Class.forName("com.mysql.jdbc.Driver").newInstance();
		Connection con = DriverManager.getConnection(connectionIRI, username, password);

		PersistentQueue queue = new PersistentQueue(filename);
		queue.clear();
		
		// Get all properties and their domains and ranges
		
		String query =
		    "SELECT \n" +
		    	"cp.id, " +
	    		"cp.uri, " +
		    	"cp.name, " +
		    	"cp.type, " +
		    	"cp.datatype_range, " +
		    	"cp.label, " +
		    	"domain.name as domain_name " + 
		    "FROM \n" +
		    	"class_property cp\n" +
		    	"LEFT JOIN class domain ON (domain.id = cp.class_id)\n";
		    	
		//System.out.println(query);
		Statement stmt = con.createStatement();
		ResultSet rs = stmt.executeQuery(query);
		
		int counter = 0;
		while(rs.next()) {
			++counter;

			String propertyId     = rs.getString("id");
			String uri            = rs.getString("uri");
			//String name           = rs.getString("name").toLowerCase();
			String name           = rs.getString("name");
			String type                   = rs.getString("type");
			String datatype     = rs.getString("datatype_range");
			String label      = rs.getString("label");
			//String domainName      = StringUtil.ucFirstOnly(rs.getString("domain_name"));
			String domainName      = StringUtil.noNull(rs.getString("domain_name"));
			
			if(domainName.equals("Thing"))
				domainName = "";
			
			if(label == null)
				label = "";
			
			// Ignore properties outside of the dbpedia ontology namespace
			if(uri != null)
				continue;
			
			String subQuery =
				"SELECT\n" +
					"name " +
				"FROM \n" +
		    		"class_property_range cpr\n" +
		    		"LEFT JOIN class r ON (r.id = cpr.range_class_id)\n" +
		    	"WHERE\n" +
		    		"cpr.property_id = " + propertyId;

			//System.out.println(subQuery);
			
			Statement stmt2 = con.createStatement();
			ResultSet subRs = stmt2.executeQuery(subQuery);

			String title = "User:DBpedia-Bot/ontology/" + name;
			//System.out.println("name = ");
			
			
			String text = type.equals("datatype")
				? "{{DBpedia DatatypeProperty"
				: "{{DBpedia ObjectProperty";
			
			// label
			text += "\n| rdfs:label@en = " + label;
			text += "\n| rdfs:label@de = ";
			text += "\n| rdfs:label@fr = ";

			List<String> ranges = new ArrayList<String>(); 

			if(!type.equals("datatype")) {
				// ranges
				while(subRs.next()) {
					
					//String rangeName = StringUtil.ucFirstOnly(subRs.getString("name"));
					String rangeName = subRs.getString("name");
					
					if(rangeName.equals("Thing"))
						continue;
					
					ranges.add(rangeName);
				}
			}
			else
				if(datatype != null)
					ranges.add("xsd:" + datatype.toLowerCase());

			//content += "\n| rdfs:comment = ";
			text += "\n| rdfs:comment@en = ";
			text += "\n| rdfs:comment@de = ";
			text += "\n| rdfs:comment@fr = ";
			text += "\n| owl:equivalentProperty = ";
			text += "\n| rdfs:seeAlso = ";
			text += "\n| rdfs:subPropertyOf = ";
			text += "\n| rdfs:domain = " + domainName;

			text += "\n| rdfs:range = " + StringUtil.implode(", ", ranges);
			text += "\n| rdfs:type = ";
			
			text += "\n}}";
				
			//System.out.println(text);
			
			WikiEdit edit = new WikiEdit(title, text, "Added initial Property definition", false); 
			queue.push(edit);
			logger.info("Wrote class definition for '" + title + "'");			
		}
	}
}


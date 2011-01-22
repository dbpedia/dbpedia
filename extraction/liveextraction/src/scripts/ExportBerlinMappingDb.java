package scripts;

import helpers.EqualsUtil;
import helpers.StringUtil;

import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStream;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.Arrays;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import oaiReader.Files;
import oaiReader.MediawikiTitle;
import oaiReader.RawRecordSerializer;
import oaiReader.Record;
import oaiReader.RecordContent;
import oaiReader.RecordMetadata;
import oaiReader.WhatLinksHereArticleLoader;
import oaiReader.WikiLink;

import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.semanticweb.owlapi.model.IRI;


// About the DBpedia Attribute template
//http://meta.wikimedia.org/wiki/Help:Newlines_and_spaces

class Property
	implements Comparable<Property>
{
	private String originalName;
	private String mappedName;
	private String parseHint;

	
	public Property(String originalName, String mappedName, String parseHint)
	{
		this.originalName = originalName;
		this.mappedName = mappedName;
		this.parseHint = parseHint;
	}
	
	public String getOriginalName()
	{
		return originalName;
	}
	
	public String getMappedName()
	{
		return mappedName;
	}
	
	public String getParseHint()
	{
		return parseHint;
	}
	
	@Override
	public int compareTo(Property o)
	{
		int result;
		
		result = EqualsUtil.compareTo(originalName, o.originalName);
		if(result != 0)
			return result;
		
		result = EqualsUtil.compareTo(mappedName, o.mappedName);
		if(result != 0)
			return result;
		
		return EqualsUtil.compareTo(parseHint, o.parseHint);
	}
	
	@Override
	public String toString()
	{
		return
			"[" + 
			StringUtil.implode(", ", originalName, mappedName, parseHint) +
			"]";
	}
}


public class ExportBerlinMappingDb
{
	private static Map<String, String> unitToParseHint = new HashMap<String, String>(); 
	private static Set<String> acceptRawParseHint = new HashSet<String>();
	private static Pattern pattern = Pattern.compile("Template:.+");

	
	/**
	 * Mainly intended to get the raw template name from an wiki-IRI.
	 * Just Matches: Template:.+
	 * 
	 * @param name
	 * @return
	 */
	public static String extractTemplateName(String templateUri)
	{
		Matcher matcher = pattern.matcher(templateUri);
		if(!matcher.find())
			return null;
		
		return matcher.group();
	}
	
	static {
		//unitToParseHint.put("km2", "km2");
		//unitToParseHint.put("PD/sqkm", "pop/km3");
		//unitToParseHint.put("PD/sqmi", "pop/mi3");
		
		unitToParseHint.put("mile", "mi");
		unitToParseHint.put("kilometre", "km");
		unitToParseHint.put("km", "km");
		unitToParseHint.put("degree celsius", "C");
		unitToParseHint.put("sqmi", "mi2");
		unitToParseHint.put("km2", "km2");
		//unitToParseHint.put("ft³/s", "pop/sqmi");
		unitToParseHint.put("m³/s", "m3/s");
		unitToParseHint.put("m", "m");
		unitToParseHint.put("ft", "ft");
		unitToParseHint.put("cuft/s", "ft3/s");
		unitToParseHint.put("m3", "m3");
		//unitToParseHint.put("AU", "pop/sqmi");
		//unitToParseHint.put("day", "pop/sqmi");
		//unitToParseHint.put("MB", "pop/sqmi");
		unitToParseHint.put("mi", "mi");
		unitToParseHint.put("K", "K");
		unitToParseHint.put("C", "C");
		unitToParseHint.put("F", "F");
		unitToParseHint.put("kg", "kg");
		unitToParseHint.put("pound", "lb");
		unitToParseHint.put("in", "in");
		unitToParseHint.put("m2", "m2");
		unitToParseHint.put("PD/sqkm", "pop/km2");
		unitToParseHint.put("PD/sqmi", "pop/mi2");
		unitToParseHint.put("km²", "km2");
		unitToParseHint.put("miles", "mi");
		unitToParseHint.put("lb", "lb");
		//unitToParseHint.put("st", "pop/sqmi"); // stone?
		
		
		
		acceptRawParseHint.add("date");
		acceptRawParseHint.add("currency");
		acceptRawParseHint.add(null);
	}

	public static void resolveTemplateUris(Connection con)
		throws Exception
	{
		String baseUri = "http://en.wikipedia.org/w/index.php?title=";
		baseUri = "http://en.wikipedia.org/w/index.php/";
		String oaiPrefix = "oai:en.wikipedia.org:enwiki:";
		//baseUri = "http://localhost/wiki/index.php/";
		WhatLinksHereArticleLoader loader = 
			new WhatLinksHereArticleLoader(baseUri, oaiPrefix);
		
		String query =
		    "Select \n" +
		        "a.uri as template_uri \n" +
		    "From \n" +
		        "template_uri a \n";
		
		Statement stmt = con.createStatement();
		ResultSet rs = stmt.executeQuery(query);
		
		Map<String, WikiLink> result = new HashMap<String, WikiLink>();
		while(rs.next()) {
			String templateUri = rs.getString("template_uri");

			String templateName = extractTemplateName(templateUri);

		
			WikiLink resolved = loader.resolveExistence(templateName);
			
			if(resolved == null)
				resolved = null;
			else {
				/**
				String redirect = resolved.getRedirect() == null
					? null
					: "http://en.wikipedia.org/wiki/" + resolved.getRedirect();
				resolved =
					new WikiLink(
						"http://en.wikipedia.org/wiki/" + resolved.getPage(),
						redirect);
				*/
			}
			result.put(templateUri, resolved);
			System.out.println(templateUri + " ---> " + resolved);
			Files.writeMap("resolvedTemplateUrisNew.xml", result);
		}
		
	}

	
	public static void main(String[] args)
		throws Exception
	{
		System.out.println("Export started...");
		/*
		Property a = new Property("a", "b", "");
		Property b = new Property("a", "b", "x");
		TreeSet<Property> x = new TreeSet<Property>();
		x.add(a);
		x.add(b);
		
		System.out.println(x);
		
		if(true)
			return;
		*/
		Ini ini = new Ini(new File("src/scripts/config.ini"));
		Section section = ini.get("BERLIN_DB");
		
		String uri      = section.get("uri");
		String username = section.get("username");
		String password = section.get("password");
		
		Class.forName("com.mysql.jdbc.Driver").newInstance();
		Connection con = DriverManager.getConnection(uri, username, password);
		
		
		/*
		 * Uncomment this if you want to resolve all uris against
		 * wikipedia again.
		 * Since this has already been done, use the resolvedTemplateUris2.xml
		 * file
		if(true) {
			resolveTemplateUris(con);
			return;
		}
		*/
		
		Map<String, WikiLink> uriToWikiLink =
			(HashMap<String, WikiLink>)Files.readMap("src/scripts/resolvedTemplateUrisNew.xml");
		
		
		// Retrieve all related classes
		String query =
		    "Select \n" +
		        "a.uri as template_uri, \n" +
		        "y.name as related_class_name \n" +
		    "From \n" +
		        "template_uri a \n" +
		        "Left Join \n" +
		        "template_class x \n" +
		        "On (x.template_id = a.template_id) \n" +
		        "Left Join \n" +
		        "class y \n" +
		        "On (y.id = x.class_id)";

		Statement stmt = con.createStatement();
		ResultSet rs = stmt.executeQuery(query);
		
		// Note: we use a tree set to get have the items sorted
		// (Alternatively we could use an apache multimap and sort the items
		// later
		Map<String, TreeSet<String>> templateToClasses =
			new HashMap<String, TreeSet<String>>();
		while(rs.next()) {		
			String templateUri      = rs.getString("template_uri");
			String relatedClassName = rs.getString("related_class_name");
			
			//String templateName = extractTemplateName(templateUri);
			
			TreeSet<String> classes = templateToClasses.get(templateUri);
			if(classes == null) {
				classes = new TreeSet<String>();
				templateToClasses.put(templateUri, classes);
			}
			classes.add(relatedClassName);
		}
		
		//System.out.println(templateToClasses);
		
		
		// retrieve all properties
		query =
		    "Select \n" +
		        "a.uri as template_uri, \n" +
		        "b.name as property_name, \n" +
		        "c.unit_exact_type as unit, \n" +
		        "e.type as property_type, \n" +
		        "e.name as property_mapped_name, \n" +
		        "g.name as property_range_class_name, \n" +
		        "h.parser_type as raw_parse_hint, \n" +
		        "h.unit_type as quantity \n" +
		    "From \n" +
		        "template_uri a \n" +                       // template
		        "Left Join \n" +
		        "template_property b \n" +                  // original prop
		        "On (b.template_id = a.template_id) \n" +
		        "Left Join \n" +

		        "template_parser_type_rule c \n" +          // original prop unit
		        "On (c.template_property_id = b.id) \n" +
		        "Left Join \n" +
		        
		        "template_property_class_property d \n" +   // mapping table
		        "On (d.template_property_id = b.id) \n" +
		        "Left Join \n" +
		        "class_property e \n" +                     // remapped prop
		        "On (e.id = d.class_property_id) \n" +
		        "Left Join \n" +		        

		        "class_property_range f \n" +               // mapping table
		        "On (f.property_id = e.id) \n" +
		        "Left Join \n" +
		        "class g \n" +                              // range-class
		        "On (g.id = f.range_class_id) \n" +
		        "Left Join \n" +
		        
		        
		        "parser_type_rule h \n" +					// unit, date, currency..
		        "On (h.class_property_id = e.id) \n";

		//System.out.println(query);
		
		Map<String, TreeSet<Property>> templateToProperties =
			new HashMap<String, TreeSet<Property>>();
		
		stmt = con.createStatement();
		rs = stmt.executeQuery(query);
		
		int counter = 0;
		while(rs.next()) {
			++counter;
			
			String templateUri            = rs.getString("template_uri");
			String propertyName           = rs.getString("property_name");
			String unit                   = rs.getString("unit");
			String propertyMappedName     = rs.getString("property_mapped_name");
			String propertyRangeClassName = rs.getString("property_range_class_name");
			String rawParseHint           = rs.getString("raw_parse_hint");
			String quantity               = rs.getString("quantity");

			String propertyType           = rs.getString("property_type");
//System.out.println(propertyType);
			// Determine wheter its a datatype or object property
			// if is a object property, use the parsehint links
			// otherwise use the parsehint text
			
			//System.out.println(rawParseHint);
			
			// Display what we get from the db
			System.out.println(StringUtil.implode(", ",
					counter, templateUri, propertyName, unit, propertyMappedName, propertyRangeClassName, rawParseHint, quantity)); 
					
			
			//String templateName = extractTemplateName(templateUri);
			String parseHint = null;
			
			if(rawParseHint != null) {
				// camelCase
				//rawParseHint = rawParseHint.trim();

				// lower-case
				rawParseHint = rawParseHint.trim().toLowerCase();
			}
			
			// if there is no unit, use the parseHint
			if(unit == null || unit.trim().isEmpty()) {
				if(!acceptRawParseHint.contains(rawParseHint))
					continue;
				
				parseHint = rawParseHint;
			}
			else {
				// //if there is no mapping, the unit is already the parse hint
				parseHint = unitToParseHint.get(unit);
				//if(parseHint == null)
				//	parseHint = unit;
			}

			TreeSet<Property> properties = templateToProperties.get(templateUri);
			if(properties == null) {
				properties = new TreeSet<Property>();
				templateToProperties.put(templateUri, properties);
			}
			
			// avoid explicit identity mappings
			if(propertyName == null)
				continue;

			if(propertyName.equals(propertyMappedName))
				propertyMappedName = null;
			
			// if there is no parseHint, generate
			// links for object- and text for datatype properties
			if(parseHint == null) {
				if(propertyType.equalsIgnoreCase("object"))
						parseHint = "links";
				//else if(propertyType.equalsIgnoreCase("datatype"))
				//		parseHint = "text";
			}
			
			properties.add(new Property(propertyName, propertyMappedName, parseHint));
		}
		
		
		String all = "";
		int itemId = 0;
		int packId = 0;
		for(Map.Entry<String, TreeSet<Property>> item : templateToProperties.entrySet()) {
			if(!uriToWikiLink.containsKey(item.getKey()))
				throw new Exception("No entry found for: " + item.getKey());
				
				
			WikiLink wikiLink = uriToWikiLink.get(item.getKey());
			if(wikiLink == null)
				wikiLink = new WikiLink();
			
			//Matcher matcher = pattern.matcher(item.getKey());
			//if(!matcher.find())
			//	continue;
				
			String templateName = item.getKey();
			//String templateName = matcher.group();
			
			String result = "";
			//result += "http://en.wikipedia.org/wiki/" + templateName + "/doc\n";
			
			result += "== DBpedia Template Annotation ==\n";
			result += "{{DBpedia Template\n";
			
			int relatedClassIndex = 0;
			for(String relatedClass : templateToClasses.get(templateName)) {
				String suffix = relatedClassIndex > 0
					? Integer.toString(relatedClassIndex)
					: "";

				// Camel case
				result += "| relatesToClass" + suffix + " = " + relatedClass + "\n";

				// ucFirstOnly-case
				//result += "| relatesToClass" + suffix + " = " + StringUtil.ucFirst(relatedClass.toLowerCase()) + "\n";
			}
			
			
			
			//System.out.println(templateName);
			//System.out.println(StringUtil.implode(", ", templateToClasses.get(templateName)));
			
			result += "| mapping =\n";
			for(Property prop : item.getValue()) {
				
				// default case
				String mappedName = prop.getMappedName() == null ? null : prop.getMappedName();
				
				// lower-case
				//mappedName = mappedName.toLowerCase();
				
				List<String> m = optional(trans(prop.getOriginalName()), trans(mappedName), trans(prop.getParseHint()));
				if(m.isEmpty())
					continue;

				// if the second argument is null (remappedValue)
				// we must add the parseHint= key
				if(m.size() == 3 && m.get(1) == null && m.get(2) != null)
					result += "{{DBpedia Attribute | " + m.get(0) + " | parseHint = " + m.get(2) + "}}\n";
				else
					result += "{{DBpedia Attribute | " + StringUtil.implode(" | ", m) + "}}\n";
			}
			
			result += "}}\n";
			
			
			
			//Build a fake record 
			if(wikiLink.getPage() == null)
				continue;
			
			String fullTitle = wikiLink.getPage() + "/doc";
			String _uri = "http://en.wikipedia.org/wiki/" + fullTitle;
			String shortTitle = fullTitle.substring(fullTitle.indexOf(':') + 1);
			RecordMetadata metadata = new RecordMetadata(
					"en",
					new MediawikiTitle(fullTitle, shortTitle, 10, "Template"),
					"noOaiPresent",
					IRI.create(_uri),
					"",
					"",
					"",
					"");
			Record record =
				new Record(metadata, new RecordContent(result, "", null));
			
			String filename = URLEncoder.encode(_uri, "UTF-8");
			Files.mkdir("OfflineRecordCache");
			File file = new File("OfflineRecordCache/" + filename);
			
			OutputStream out = new FileOutputStream(file);
			RawRecordSerializer.write(record, out);
			out.close();
			
			System.out.println("Wrote: " + file.getCanonicalPath());
			//WikiLink wikiLink = uriToWikiLink.get(templateName)
			
			//System.out.println(result);
			//Files.createFile(new File("(templateName), result);
			
			/*
			// Uncomment this to generate the map packs
			
			//result += "\n\n\n";
			all += generateXHtmlFragment(itemId, item.getKey(), wikiLink.getPage(), wikiLink.getRedirect(), result);

			// write a file every x items or at the last item
			++itemId;
			if((itemId % 10 == 0 && itemId != 0) || itemId == templateToProperties.entrySet().size()) {
				++packId;
				
				String content = generateXHtmlHeader(packId);
				
				content += all;
				content += generateXHtmlFooter();
				all = "";
				
				String fileName = "src/scripts/WebContent/MappingsPackNew" + packId + ".html";
				//System.out.println(fileName);
				Files.createFile(new File(fileName), content);
			}
		*/
		
		}
		//System.out.println(all);

	}
	
	
	private static String generateXHtmlHeader(int packId)
	{
		return
			"<?xml version='1.0' encoding='UTF-8' ?>\n" + 
			"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n" +
			"<html xmlns='http://www.w3.org/1999/xhtml'>\n" +

				"<head>\n" +
				"<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />\n" +

				"<script type='text/javascript' src='jquery-1.3.2.js'></script>\n" +
				"<link rel='stylesheet' type='text/css' href='class.css' />\n" +

				"<script type='text/javascript'>\n" +

				"function toggle(id)\n" +
				"{\n" +
				"$('#' + id).toggle();\n" +
				"}\n" +

				"</script>\n" +

				"<title>Pack #" + packId + "</title>\n" + 
				"</head>\n" +

				"<body>\n" +
				"<h1> Pack #" + packId + "</h1>";
	}
	
	
	private static String generateXHtmlFooter()
	{
		return
			"</body>\n" +
			"</html>\n";
	}
	
	/**
	 * 
	 * 
	 * @param id just some count
	 * @param resource the dbpedia resource (http://dbpedia.org/resource/...
	 * @param page The name of the target wiki article
	 * @param redirect Redirect, if there was one
	 * @param text The template content
	 * @return
	 */
	private static String generateXHtmlFragment(int id, String resource, String page, String redirect, String text)
	{
		String result =
			"<div class='box'>\n" + 
			"<h1>#" + id + " <a target = '_blank' href='http://en.wikipedia.org/wiki/" + page + "'>" + page + "</a>" +
			"<a target = '_blank' href = 'http://en.wikipedia.org/w/index.php?title=" + page + "/doc&action=edit'>/edit doc</a> <a onclick='toggle(\"content" + id + "\");'>[toggle]</a> </h1>\n" + 
			"<div id='content" + id + "'>\n" + 
			"<p>\n";
		if(redirect != null) {
			result +=
				"via: <a target = '_blank'href='http://en.wikipedia.org/wiki/" + redirect + "'>" + redirect + "</a>" + 
				"<a target = '_blank' href = 'http://en.wikipedia.org/w/index.php?title=" + redirect + "/doc&action=edit'>/edit doc</a>";
		}
		result +=
			" <br/>original: " + resource + "\n" +
			"</p>\n" +
			"<textarea style='width:600px; height:300px; color:#000000;' >\n" +
				text +
			"</textarea>\n" +
			"</div>\n" +
			"</div>\n" +
			"<p>\n" +
			"</p>\n";
		
		return result;
	}
	
	private static <T> List<T> optional(T ... items)
	{
		return optional(Arrays.asList(items));
	}
	
	// Removes trailing null values in a list
	private static <T> List<T> optional(List<T> items)
	{
		int i = items.size() - 1;
		for(; i >= 0; --i)
			if(items.get(i) != null)
				break;

		++i;
		
		return items.subList(0, i);
	}
	
	
	// Returns null on null, otherwise returns the trimed string unless
	// its empty - in that case returns null.
	private static String trans(String str)
	{
		if(str == null)
			return null;
		else {
			String result = str.trim();
			
			if(result.isEmpty())
				return null;
			else
				return result;
		}
	}
}

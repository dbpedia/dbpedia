package oaiReader;

import java.io.BufferedReader;
import java.io.ByteArrayInputStream;
import java.io.FileReader;
import java.io.IOException;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.util.Properties;

import org.semanticweb.owlapi.model.IRI;


public class RawRecordSerializer
{	
	public static void write(Record item, OutputStream out) //String filename)
		throws IOException
	{
		OutputStreamWriter osw = new OutputStreamWriter(out, "UTF-8");
		//FileWriter fw = new FileWriter(filename);
		//BufferedWriter bw = new BufferedWriter(fw);
	
		
		// Write the metadata as properties
		RecordMetadata metadata = item.getMetadata();
		/*
		if(metadata.getTitle().getShortTitle().trim().length() == 0 ||
			metadata.getTitle().getFullTitle().trim().length() == 0 ||
			metadata.getWikipediaURI().toString().trim().length() == 0) {
			System.err.println("SOMETHING WENT WRONG: OAIID = " + metadata.getOaiId());
		}
		*/

		
		Properties properties = new Properties();
		properties.setProperty("oaiId", metadata.getOaiId());
		properties.setProperty("revision", metadata.getRevision());
		properties.setProperty("language", metadata.getLanguage());
		properties.setProperty("pageTitle", metadata.getTitle().getFullTitle());
		properties.setProperty("shortTitle", metadata.getTitle().getShortTitle());
		properties.setProperty("namespaceId", metadata.getTitle().getNamespaceId().toString());
		properties.setProperty("namespaceName", metadata.getTitle().getNamespaceName());
		properties.setProperty("uri", metadata.getWikipediaURI().toString());
		properties.setProperty("username", metadata.getUsername());
		properties.setProperty("ip", metadata.getIp());
		properties.setProperty("userId", metadata.getUserId());
		//properties.setProperty("articleUri", metadata.);
		properties.store(osw, null);

		osw.write("**********\n");
	
		// Write the content
		osw.write(item.getContent().getText());
		
		osw.flush();
		// Done.
		//osw.close();
	}


	public static Record read(String filename)
		throws IOException
	{
		FileReader fr = new FileReader(filename);
		BufferedReader br = new BufferedReader(fr);
		
		String line;
		String text = "";
		while(null != (line = br.readLine()))
			text += line + '\n';
		
		br.close();
		
		String[] parts = text.split("\\*{10}\n");
		if(parts.length != 2) {
			throw new IllegalStateException(
					"Error parsing file: " + filename + ", " + 
					"A record file must consist of 2 parts, separated by 10 *'s, " +
					"Parts found: " + parts.length);
		}
		
		
		Properties properties = new Properties();
		properties.load(new ByteArrayInputStream(parts[0].getBytes()));

		MediawikiTitle mediawikiTitle =
			new MediawikiTitle(
					properties.getProperty("pageTitle", ""),
					properties.getProperty("shortTitle", ""),
					Integer.parseInt(properties.getProperty("namespaceId", "")),
					properties.getProperty("namespaceName", ""));
		
		RecordMetadata metadata = new RecordMetadata(
				properties.getProperty("language", ""),
				mediawikiTitle,
				properties.getProperty("oaiId", ""),
				IRI.create(properties.getProperty("uri", "")),
				properties.getProperty("revision", "unknown"),
				properties.getProperty("username", "unknown"),
				properties.getProperty("ip", "0.0.0.0"),
				properties.getProperty("userId", "unknown"));


		return new Record(
			    metadata,
				new RecordContent(parts[1], "", ""));
	}
	
	/*	
	public static void writeOld(Record item, String filename)
		throws IOException
	{
		FileWriter fw = new FileWriter(filename);
		BufferedWriter bw = new BufferedWriter(fw);

		// Write the title
		bw.write(item.getMetadata().getTitle().getFullTitle());

		// Write newline only with \n - otherwise we might break legacy code 
		bw.write('\n');

		// Write the content
		bw.write(item.getContent().getText());
		
		// Done.
		bw.close();
	}
	
	public static Record readOld(String filename)
		throws IOException
	{
		FileReader fr = new FileReader(filename);
		BufferedReader br = new BufferedReader(fr);

		String title = br.readLine();
		
		String line;
		String text = "";
		while(null != (line = br.readLine()))
			text += line;
		
		br.close();
		
		MediawikiTitle mediawikiTitle = new MediawikiTitle(title); 
		
		return new Record(
				new RecordMetadata("en", mediawikiTitle, "", ""),
				new RecordContent(text, "", ""));
	}
	*/
}

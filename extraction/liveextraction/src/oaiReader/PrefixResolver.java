package oaiReader;

import helpers.ExceptionUtil;

import java.io.File;
import java.net.URLEncoder;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.semanticweb.owlapi.model.IRI;


/**
 * Notes: Maybe the prefix resolver should really only resolve the prefix
 * and should not try to parse strings like foaf:Person.
 * 
 * Anyway, absolute uris are ignored anyway (spam protection)
 * and the value is not assumed to be url encoded.
 * So this class does the encoding.
 * 
 * @author raven
 *
 */
public class PrefixResolver
	implements IPrefixResolver
{
	private Logger logger = Logger.getLogger(PrefixResolver.class);
	
	private File file;

	private Ini ini;
	private long lastModified;


	public PrefixResolver(File file)
	{
		this.file = file;
	}
	
	/*
	public IRI resolve(String str)
	{
		IRI result = _resolve(str);
		logger.trace("Resolved: " + str + " to " + result);
		return result;
	}*/
	
	/**
	 *
	 */
	public IRI transform(String str)
	{
		if(ini == null || file.lastModified() != lastModified) {
			logger.info("(Re)loading File '" + file.getAbsolutePath() + "'.");
			try {
				lastModified = file.lastModified();
				ini = new Ini(file);
			}
			catch(Exception e) {
				logger.error(ExceptionUtil.toString(e));
				return null;
			}
		}

		try {
			Section genericSection = ini.get("GENERIC");
			String defaultPrefix  = genericSection.get("defaultPrefix");
			
			
			Section mappingSection = ini.get("MAPPING");
			
			// get the prefix - if there is any
			// check for :// as it appears in absolute uris http://
			
			// SPAM-PREVENTION: ignore absolute uris
			if(str.contains("://"))
				//return IRI.create(str);
				return null;
			
			String[] parts = str.split(":", 2);
			
			if(parts.length == 0)
				return null;
			else if(parts.length == 1) { // If there is no part
				if(defaultPrefix == null)
					return null;
			
				String value = URLEncoder.encode(parts[0], "UTF-8");
				return IRI.create(defaultPrefix + value);
			}
			// else:
			
			String prefix = mappingSection.get(parts[0]);
			if(prefix == null)
				return null;
			
			String value = URLEncoder.encode(parts[1], "UTF-8");
			return IRI.create(prefix + value);
		} catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
			return null;
		}
	}
	
}

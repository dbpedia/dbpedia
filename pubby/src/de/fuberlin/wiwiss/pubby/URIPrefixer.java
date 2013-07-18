package de.fuberlin.wiwiss.pubby;

import java.util.Iterator;
import java.util.Map.Entry;

import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.ResourceFactory;
import com.hp.hpl.jena.shared.PrefixMapping;

/**
 * Helper class that splits URIs into prefix and local name
 * according to a Jena PrefixMapping.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class URIPrefixer {
	private final Resource resource;
	private final String prefix;
	private final String localName;

	public URIPrefixer(String uri, PrefixMapping prefixes) {
		this(ResourceFactory.createResource(uri), prefixes);
	}
	
	public URIPrefixer(Resource resource, PrefixMapping prefixes) {
		this.resource = resource;
		String uri = resource.getURI();
		Iterator it = prefixes.getNsPrefixMap().entrySet().iterator();
		while (it.hasNext()) {
			Entry entry = (Entry) it.next();
			String entryPrefix = (String) entry.getKey();
			String entryURI = (String) entry.getValue();
			if (uri.startsWith(entryURI)) {
				prefix = entryPrefix;
				localName = uri.substring(entryURI.length());
				return;
			}
		}
		prefix = null;
		localName = null;
	}
	
	public boolean hasPrefix() {
		return prefix != null;
	}
	
	public String getPrefix() {
		return prefix;
	}
	
	public String getLocalName() {
		if (localName == null) {
			return resource.getLocalName();
		}
		return localName;
	}
	
	public String toN3() {
		if (hasPrefix()) {
			return getPrefix() + ":" + getLocalName();
		}
		return "<" + resource.getURI() + ">";
	}
}

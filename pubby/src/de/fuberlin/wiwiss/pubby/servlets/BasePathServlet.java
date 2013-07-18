package de.fuberlin.wiwiss.pubby.servlets;
import java.io.IOException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.ResourceFactory;

import de.fuberlin.wiwiss.pubby.Configuration;
import de.fuberlin.wiwiss.pubby.MappedResource;

public abstract class BasePathServlet extends BaseServlet {
	private static Pattern pattern = Pattern.compile("(-?)([^:/]*):([^:/]*)/(.*)");

	public abstract boolean doGet(MappedResource resource, Property property, boolean isInverse,
			HttpServletRequest request,
			HttpServletResponse response,
			Configuration config) throws IOException, ServletException;
	
	public boolean doGet(String relativeURI, 
			HttpServletRequest request,
			HttpServletResponse response,
			Configuration config) throws IOException, ServletException {
		
		Matcher matcher = pattern.matcher(relativeURI);
		if (!matcher.matches()) {
			return false;
		}
		boolean isInverse = "-".equals(matcher.group(1));
		String prefix = matcher.group(2);
		String localName = matcher.group(3);
		if (config.getPrefixes().getNsPrefixURI(prefix) == null) {
			return false;
		}
		Property property = ResourceFactory.createProperty(
				config.getPrefixes().getNsPrefixURI(prefix), localName);
		MappedResource resource = config.getMappedResourceFromRelativeWebURI(
				matcher.group(4), false);
		doGet(resource, property, isInverse, request, response, config);
		return true;
	}
}
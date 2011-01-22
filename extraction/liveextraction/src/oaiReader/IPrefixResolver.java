package oaiReader;

import org.apache.commons.collections15.Transformer;
import org.semanticweb.owlapi.model.IRI;

/**
 * TODO Actually this class should only have a "getNamespaceForPrefix"
 * method. And the resolving should be done in a separate class.
 * 
 */
public interface IPrefixResolver
	extends Transformer<String, IRI>
{
	// attempts to turn the given string into an uri
	//IRI transform(String str);
}

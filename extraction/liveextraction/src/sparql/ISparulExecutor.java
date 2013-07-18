package sparql;

import java.util.Collection;

import org.coode.owlapi.rdf.model.RDFTriple;

public interface ISparulExecutor
	extends ISparqlExecutor
{
	void executeUpdate(String query)
		throws Exception;
	
	boolean insert(Collection<RDFTriple> triples, String graphName)
		throws Exception;

	boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception;
	
	// Get the underlying connection (if there is one, null otherwise)
	@Deprecated
	Object getConnection();
}

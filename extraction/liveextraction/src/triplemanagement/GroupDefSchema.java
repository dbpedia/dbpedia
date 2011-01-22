package triplemanagement;

import java.util.ArrayList;
import java.util.List;

import oaiReader.SparqlHelper;

import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.semanticweb.owlapi.model.IRI;

public class GroupDefSchema
{
	private List<RDFResourceNode>	identityPredicates	= new ArrayList<RDFResourceNode>();

	public GroupDefSchema(IRI... uris)
	{
		identityPredicates = new ArrayList<RDFResourceNode>();
		for (IRI uri : uris)
			identityPredicates.add(new RDFResourceNode(uri));
	}

	public List<RDFResourceNode> getIdentityPredicates()
	{
		return identityPredicates;
	}

	public String generateProjection(String prefix)
	{
		String result = "";
		for (int i = 0; i < identityPredicates.size(); ++i)
			result += prefix + i + " ";

		return result;
	}

	public String generateSelection(String varSymbol, String prefix)
	{
		String result = "";

		int i = 0;
		for (RDFResourceNode item : identityPredicates) {
			result += varSymbol + " " + SparqlHelper.toSparqlString(item) + " "
					+ prefix + (i++) + " . \n";
		}

		return result;
	}
}

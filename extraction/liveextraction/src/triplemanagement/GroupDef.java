package triplemanagement;

import helpers.EqualsUtil;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import oaiReader.SparqlHelper;

import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.semanticweb.owlapi.model.IRI;



public class GroupDef
{
	private IRI								identity;
	private GroupDefSchema					schema;

	private List<RDFNode>					values		= new ArrayList<RDFNode>();
	// private IMultiMap<IRI, IRI> identityProperties = new MultiMap<IRI,
	// IRI>();

	private Map<RDFResourceNode, RDFNode>	properties	= new HashMap<RDFResourceNode, RDFNode>();

	public GroupDef(IRI identity, GroupDefSchema schema, List<RDFNode> values)
	{
		this.identity = identity;
		this.schema = schema;
		this.values = new ArrayList<RDFNode>(values);
	}

	public GroupDef(IRI identity, GroupDefSchema schema, RDFNode... values)
	{
		this.identity = identity;
		this.schema = schema;
		this.values = new ArrayList<RDFNode>(Arrays.asList(values));
	}

	public IRI getIdentity()
	{
		return identity;
	}

	public GroupDefSchema getSchema()
	{
		return this.schema;
	}

	public List<RDFNode> getValues()
	{
		return values;
	}

	public void putProperty(RDFResourceNode predicate, RDFNode value)
	{
		properties.put(predicate, value);
	}

	public String generateProperties(String varSymbol)
	{
		String result = "";

		// result += " <" + RDF_TYPE + "> <" + DBP_GROUP + "> .\n";
		for (Map.Entry<RDFResourceNode, RDFNode> entry : properties.entrySet()) {
			result += varSymbol + " "
					+ SparqlHelper.toSparqlString(entry.getKey()) + " "
					+ SparqlHelper.toSparqlString(entry.getValue()) + " .\n";
		}

		return result;
	}

	public String generateReference(String varSymbol)
	{
		String result = "";

		// result += " <" + RDF_TYPE + "> <" + DBP_GROUP + "> .\n";
		int index = 0;
		for (RDFNode item : values) {
			result += varSymbol
					+ " "
					+ SparqlHelper.toSparqlString(schema
							.getIdentityPredicates().get(index++)) + " "
					+ SparqlHelper.toSparqlString(item) + " .\n";
		}

		return result;
	}

	@Override
	public int hashCode()
	{
		return 123 * EqualsUtil.hashCode(identity) + 456
				* EqualsUtil.hashCode(values);
	}

	@Override
	public boolean equals(Object o)
	{
		if (this == o)
			return true;

		if (!(o instanceof GroupDef))
			return false;

		GroupDef other = (GroupDef) o;

		return EqualsUtil.equals(this.identity, other.identity)
				&& EqualsUtil.equals(this.values, other.identity);
	}
}

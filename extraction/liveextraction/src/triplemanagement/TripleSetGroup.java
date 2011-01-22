package triplemanagement;

import java.util.Set;

import org.coode.owlapi.rdf.model.RDFTriple;

/**
 * It's recommended to NOT add reification statements to the container
 * 
 * @author raven
 *
 */
public class TripleSetGroup
{
	//private IRI extractor;
	//private IRI target;
	private GroupDef group;
	private Set<RDFTriple> triples = null;// = new HashSet<RDFTriple>();
	
	public TripleSetGroup(GroupDef group)
	{
		this.group = group;
	}
	
	public GroupDef getGroup()
	{
		return group;
	}
	/*
	public IRI getExtractor()
	{
		return extractor;
	}

	public IRI getTarget()
	{
		return target;
	}
*/
	public void setTriples(Set<RDFTriple> triples)
	{
		this.triples = triples;
	}
	
	public Set<RDFTriple> getTriples()
	{
		return triples;
	}
}

package oaiReader;

import helpers.EqualsUtil;

import java.util.Set;

import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.hibernate.util.EqualsHelper;

public class RDFExpression
{
	private RDFResourceNode rootSubject;
	private Set<RDFTriple> triples;

	public RDFExpression(RDFResourceNode rootSubject, Set<RDFTriple> triples)
	{
		this.rootSubject = rootSubject;
		this.triples = triples;
	}
	
	public RDFResourceNode getRootSubject()
	{
		return rootSubject;
	}
	
	public Set<RDFTriple> getTriples()
	{
		return triples;
	}
	
	@Override
	public int hashCode()
	{
		return
			EqualsUtil.hashCode(rootSubject) +
			31 * EqualsUtil.hashCode(triples);
	}
	
	@Override
	public boolean equals(Object other)
	{
		if(!(other instanceof RDFExpression))
			return false;
		
		RDFExpression o = (RDFExpression)other;
		
		return
			EqualsHelper.equals(this.getRootSubject(), o.getRootSubject()) &&
			EqualsHelper.equals(this.getTriples(), o.getTriples());
	}
	
	@Override 
	public String toString()
	{
		return
			"root = " + rootSubject + " " +
			"rest = " + triples;
	}
}
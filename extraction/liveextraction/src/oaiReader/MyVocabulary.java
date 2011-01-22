package oaiReader;

import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.vocab.Namespaces;

public enum MyVocabulary
{
	DBM("http://dbpedia.org/meta/"),
	DBM_EXTRACTED_BY(DBM + "origin"),
	DBM_TARGET(DBM + "pageid"),
	DBM_MEMBER_OF(DBM + "memberof"),
	DBM_GROUP(DBM + "group"),
	
	DBM_ERROR(DBM + "error"),
	
	DBM_SOURCE_PAGE(DBM + "sourcepage"),

	DBM_ASPECT(DBM + "aspect"),
	
	//OWL("http://www.w3.org/2002/07/owl#"),

	OWL_AXIOM(Namespaces.OWL + "Axiom"),
	OWL_ANNOTATED_SOURCE(Namespaces.OWL + "annotatedSource"),
	OWL_ANNOTATED_PROPERTY(Namespaces.OWL + "annotatedProperty"),
	OWL_ANNOTATED_TARGET(Namespaces.OWL + "annotatedTarget"),

	DC("http://purl.org/dc/terms/"),
	DC_MODIFIED(DBM + "modified"),
	DC_CREATED(DC + "created"),

	// following predicates are used in the default graph
	// and are attached directly to a subject
	DBM_REVISION(DBM + "revisionlink"),
	
	// Note: using the oiaidentifier instead of the page id makes wiki pages
	// unique acroos multiple wikis
	DBM_OAIIDENTIFIER(DBM + "oaiidentifier"),
	DBM_PAGE_ID(DBM + "pageid"),
	DBM_EDIT_LINK(DBM + "editlink"),

	
	DBM_TEMP_ID(DBM + "temp_id"),
	DBM_TEMP_ID_NS(DBM + "id/");
	//OWL_AXIOM(OWL2 + "Axiom"),
	//RDF("http://www.w3.org/1999/02/22-rdf-syntax-ns#"),
	//RDF_TYPE(RDF + "type"),

	private String name;
	
	public IRI getUri()
	{
		return IRI.create(name);
	}
	
	public IRI getIRI()
	{
		return IRI.create(name);
	}
	
	public String toString()
	{
		return name;
	}
	
	public String getName()
	{
		return name;
	}
	
	private MyVocabulary(String name)
	{
		this.name = name;
	}
}

package templatedb.entity;

import java.io.Serializable;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import javax.persistence.CascadeType;
import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.FetchType;
import javax.persistence.GeneratedValue;
import javax.persistence.GenerationType;
import javax.persistence.Id;
import javax.persistence.JoinColumn;
import javax.persistence.JoinTable;
import javax.persistence.MapKey;
import javax.persistence.OneToMany;

import org.hibernate.annotations.Cascade;
import org.hibernate.annotations.CollectionOfElements;
import org.hibernate.annotations.NaturalId;


/**
 * So far a resource has a name
 * 
 * @author raven_arkadon
 *
 */
@Entity
public class TemplateAnnotation
	implements Serializable
{
	private static final long serialVersionUID = 1L;

	@Id
	@GeneratedValue(strategy=GenerationType.IDENTITY)
	private int id;
	
	@NaturalId
	private String name;

	@CollectionOfElements
	@JoinTable(joinColumns=@JoinColumn(name="parent_id"))
	@Column(name = "name")
	private Set<String> relatedClasses = new HashSet<String>();
	
	@OneToMany(fetch=FetchType.EAGER, mappedBy="parent", cascade={CascadeType.ALL})
	@Cascade({org.hibernate.annotations.CascadeType.DELETE_ORPHAN})
	@MapKey(name="name")
	private Map<String, PropertyAnnotation> propertyAnnotations =
		new HashMap<String, PropertyAnnotation>();

	// We need the oaiIdentifier in order to be able to delete templates
	private String oaiId;


	@Column(nullable=false)
	private boolean isIgnored = false;

	public TemplateAnnotation()
	{
	}
	
	public TemplateAnnotation(String name)
	{
		this.name = name;
	}

	public int getId()
	{
		return id;
	}
	
	public void setId(int id)
	{
		this.id = id;
	}
	
	public boolean isIgnored()
	{
		return isIgnored;
	}
	
	public void setIgnored(boolean isIgnored)
	{
		this.isIgnored = isIgnored;
	}
	
	public String getName()
	{
		return name;
	}
	
	public void setName(String name)
	{
		this.name = name;
	}

	public Set<String> getRelatedClasses()
	{
		return relatedClasses;
	}
	
	public void setImpliedClasses(Set<String> relatedClasses)
	{
		this.relatedClasses = relatedClasses;
	}

	
	public Map<String, PropertyAnnotation> getPropertyAnnotations()
	{
		return propertyAnnotations;
	}

	public void setPropertyAnnotations(Map<String, PropertyAnnotation> propertyAnnotations)
	{
		this.propertyAnnotations = propertyAnnotations;
	}
	
	
	public void setOaiId(String oaiId)
	{
		this.oaiId = oaiId;
	}
	
	public String getOaiId()
	{
		return this.oaiId;
	}

	/*
	public Set<PropertyAnnotation> getPropertyAnnotations()
	{
		return propertyAnnotations;
	}

	public void setPropertyAnnotations(Set<PropertyAnnotation> propertyAnnotations)
	{
		this.propertyAnnotations = propertyAnnotations;
	}
	*/
	

	public int hashCode()
	{
		return 52153 * id;
	}
	
	public boolean equals(Object o)
	{
		if(this == o)
			return true;
		
		if(!(o instanceof TemplateAnnotation))
			return false;
		
		TemplateAnnotation other = (TemplateAnnotation)o;
		
		return this.id == other.id;
	}
}

package templatedb.entity;

import helpers.EqualsUtil;

import java.io.Serializable;
import java.util.HashSet;
import java.util.Set;

import javax.persistence.CascadeType;
import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.GeneratedValue;
import javax.persistence.GenerationType;
import javax.persistence.Id;
import javax.persistence.ManyToOne;
import javax.persistence.OneToMany;

import org.hibernate.annotations.Cascade;
import org.hibernate.annotations.NaturalId;
import org.hibernate.annotations.OnDelete;
import org.hibernate.annotations.OnDeleteAction;


@Entity
public class PropertyAnnotation
	implements Serializable
{
	private static final long serialVersionUID = 1L;

	@Id
	@GeneratedValue(strategy=GenerationType.IDENTITY)
	private int id;
	
	@ManyToOne
	@OnDelete(action = OnDeleteAction.CASCADE)
	@NaturalId
	private TemplateAnnotation parent;

	@NaturalId
	private String name;
	
	@Column(nullable=false)
	private boolean isIgnored = false;

	@OneToMany(mappedBy="parent", cascade={CascadeType.ALL})
	@Cascade({org.hibernate.annotations.CascadeType.DELETE_ORPHAN})
	private Set<PropertyMapping> propertyMappings =
		new HashSet<PropertyMapping>();
	
	public PropertyAnnotation()
	{
	}

	public PropertyAnnotation(TemplateAnnotation parent, String name)
	{
		this.parent = parent;
		this.name   = name;
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
	
	public TemplateAnnotation getParent()
	{
		return parent;
	}
	
	public void setParent(TemplateAnnotation parent)
	{
		this.parent = parent;
	}
	
	public String getName()
	{
		return name;
	}
	
	public void setName(String name)
	{
		this.name = name;
	}
	
	public Set<PropertyMapping> getPropertyMappings()
	{
		return propertyMappings;
	}
	
	public void setPropertyMappings(Set<PropertyMapping> propertyMappings)
	{
		this.propertyMappings = propertyMappings;
	}
	/*
	public Set<String> getUris()
	{
		return uris;
	}
	
	public void setUris(Set<String> uris)
	{
		this.uris = uris;
	}
	*/

	/*
	public String getParseAs()
	{
		return parseAs;
	}
	
	public void setParseAs(String parseAs)
	{
		this.parseAs = parseAs;
	}
	*/
	
	public int hashCode()
	{
		return
			123 * EqualsUtil.hashCode(this.parent) *
			456 * EqualsUtil.hashCode(this.name);
	}

	public boolean equals(Object o)
	{
		if(this == o)
			return true;
		
		if(!(o instanceof PropertyAnnotation))
			return true;
		
		PropertyAnnotation other = (PropertyAnnotation)o;		
		
		return
			EqualsUtil.equals(this.parent, other.parent) &&
			EqualsUtil.equals(this.name, other.name);
	}
	
	/*
	@Override
	public String toString()
	{
		return this.getClass().getName() + ":" + getName() + propertyMappings.toString();
	}
	*/
}

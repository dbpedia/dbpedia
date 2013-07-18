package templatedb.entity;

import helpers.EqualsUtil;

import javax.persistence.Entity;
import javax.persistence.GeneratedValue;
import javax.persistence.GenerationType;
import javax.persistence.Id;
import javax.persistence.ManyToOne;

@Entity
public class PropertyMapping
{
	@ManyToOne
	private PropertyAnnotation parent;
	
	@Id
	@GeneratedValue(strategy=GenerationType.IDENTITY)
	private long id;

	private String renamedValue;
	private String parseHint;

	public PropertyMapping()
	{
	}
	
	public PropertyMapping(
			PropertyAnnotation parent,
			String renamedValue,
			String parseHint)
	{
		this.parent       = parent;
		this.renamedValue = renamedValue;
		this.parseHint    = parseHint;
	}

	public long getId()
	{
		return id;
	}
	
	public void setId(long id)
	{
		this.id = id;
	}
	
	public PropertyAnnotation getParent()
	{
		return parent;
	}
	
	public void setParent(PropertyAnnotation parent)
	{
		this.parent = parent;
	}
	
	public String getRenamedValue()
	{
		return renamedValue;
	}
	
	public void setRenamedValue(String renamedValue)
	{
		this.renamedValue = renamedValue; 
	}
	
	public String getParseHint()
	{
		return parseHint;
	}
	
	public void setParseHint(String parseHint)
	{
		this.parseHint = parseHint;
	}
	
	public int hashCode()
	{
		return
			123 * EqualsUtil.hashCode(this.parent) *
			456 * EqualsUtil.hashCode(this.renamedValue) *
			789 * EqualsUtil.hashCode(this.parseHint);
	}

	public boolean equals(Object o)
	{
		if(this == o)
			return true;
		
		if(!(o instanceof PropertyMapping))
			return true;
		
		PropertyMapping other = (PropertyMapping)o;

		return 
			this.parent == other.parent &&
			EqualsUtil.equals(this.renamedValue, other.renamedValue) &&
			EqualsUtil.equals(this.parseHint, other.parseHint);
	}
	

	@Override
	public String toString()
	{
		return "[" + renamedValue + ", " + parseHint + "]"; 
	}

}

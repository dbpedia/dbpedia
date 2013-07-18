package iddb.entity;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.GeneratedValue;
import javax.persistence.GenerationType;
import javax.persistence.Id;

import org.hibernate.annotations.NaturalId;

@Entity
public class IdStatus
{
	@Id
	@GeneratedValue(strategy=GenerationType.IDENTITY)
	private int id;
	
	@NaturalId
	private String name;
	
	@Column(name="val")
	private long value;
	
	public IdStatus()
	{
	}
	
	public IdStatus(String name, long value)
	{
		this.name = name;
		this.value = value;
	}
	
	public int getId()	
	{
		return id;
	}
	
	public void setId(int id)
	{
		this.id = id;
	}

	public String getName()
	{
		return name;
	}
	
	public void setName(String name)
	{
		this.name = name;
	}
	
	public long getValue()
	{
		return value;
	}
	
	public void setValue(long value)
	{
		this.value = value;
	}
}

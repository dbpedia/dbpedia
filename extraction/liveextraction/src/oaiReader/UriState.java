package oaiReader;

import java.io.Serializable;

public class UriState
	implements Serializable
{
	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	private String uri;
	private boolean isDeleted;
	
	public UriState()
	{
	}
	
	public UriState(String uri, boolean isDeleted)
	{
		this.uri = uri;
		this.isDeleted = isDeleted;
	}
	
	public String getUri()
	{
		return uri;
	}
	
	public boolean isDeleted()
	{
		return isDeleted;
	}
	
	// Meh, actually i wanted this object to be immutable, but the xml
	// serializer then complains about missing functions
	
	public void setUri(String uri)
	{
		this.uri = uri;
	}
	
	public void setDeleted(boolean isDeleted)
	{
		this.isDeleted = isDeleted;
	}
	
	@Override
	public String toString()
	{
		String state = "existing";
		if(isDeleted)
			state = "deleted";
		
		return uri + " [" + state + "]";
	}
	
	/*
	@Override
	public int hashCode()
	{
		return
			123 * EqualsHelper.hashCode(uri) +
			456 * EqualsHelper.hashCode(isDeleted);
	}
	
	@Override
	public boolean equals(Object o)
	{
		if(this == o)
			return true;
		
		if(!(o instanceof UriState))
			return false;
		
		UriState other = (UriState)o;
		
		return
			EqualsHelper.equals(uri, other.uri) &&	
			EqualsHelper.equals(isDeleted, other.isDeleted);	
	}
	*/
}

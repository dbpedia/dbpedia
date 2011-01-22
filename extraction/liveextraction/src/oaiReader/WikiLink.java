package oaiReader;

import java.io.Serializable;


public class WikiLink
	implements Serializable
{
	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	private String page;
	private String redirect; // may be null
	
	public WikiLink()
	{
	}
	
	public WikiLink(String page, String redirect)
	{
		this.page = page;
		this.redirect = redirect;
	}
	
	public String getPage()
	{
		return page;
	}
	
	public String getRedirect()
	{
		return redirect;
	}
	
	public void setPage(String page)
	{
		this.page = page;
	}
	
	public void setRedirect(String redirect)
	{
		this.redirect = redirect;
	}
	
	@Override
	public String toString()
	{
		if(redirect != null)
			return page + " via " + redirect;
		else
			return page;
	}
}


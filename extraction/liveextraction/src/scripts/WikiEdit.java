package scripts;

import java.io.Serializable;

public class WikiEdit
	implements Serializable
{
	/**
	 * 
	 */
	private static final long	serialVersionUID	= -1289168296773840444L;
	
	private String title; // The target name
	private String summary; // Edit summary
	private String text; // The wiki text
	private boolean isMinor; // Wheter the edit is meaning-preserving
	
	public WikiEdit(String title, String text, String summary, boolean isMinor)
	{
		this.title = title;
		this.text = text;
		this.summary = summary;
		this.isMinor = isMinor;
	}

	public String getTitle()
	{
		return title;
	}

	public void setTitle(String title)
	{
		this.title = title;
	}

	public String getSummary()
	{
		return summary;
	}

	public void setSummary(String summary)
	{
		this.summary = summary;
	}

	public String getText()
	{
		return text;
	}

	public void setText(String content)
	{
		this.text = content;
	}

	public boolean isMinor()
	{
		return isMinor;
	}

	public void setMinor(boolean isMinor)
	{
		this.isMinor = isMinor;
	}
}
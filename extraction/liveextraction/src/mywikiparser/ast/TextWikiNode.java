package mywikiparser.ast;


public class TextWikiNode
	extends AbstractNode
{
	private String text = "";
	
	public TextWikiNode(String text)
	{
		this.text = text;
	}
	
	public void setText(String text)
	{
		this.text = text;
	}
	
	public String getText()
	{
		return text;
	}
	
	@Override
	public String toString()
	{
		return "[" + this.getClass().getName() + ":" + text + "]";
	}
	
	@Override
	public <T> void accept(IWikiNodeVisitor<T> visitor)
	{
		visitor.visit(this);
	}
}
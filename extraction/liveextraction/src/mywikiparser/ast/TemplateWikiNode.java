package mywikiparser.ast;

import java.util.ArrayList;
import java.util.List;


public class TemplateWikiNode
	extends AbstractNode
{
	private IWikiNode name;
	private List<Pair<IWikiNode, IWikiNode>> arguments =
		new ArrayList<Pair<IWikiNode, IWikiNode>>();
	
	public IWikiNode getName()
	{
		return name;
	}

	public List<Pair<IWikiNode, IWikiNode>> getArguments()
	{
		return arguments;
	}
	
	public void setName(IWikiNode name)
	{
		this.name = name;
	}

	/*
	public List<Pair<IWikiNode, IWikiNode>> getArguments()
	{
		return arguments;
	}
	*/
	
	
	@Override
	public <T> void accept(IWikiNodeVisitor<T> visitor)
	{
		visitor.visit(this);
	}
	
	@Override
	public String toString()
	{
		return "TemplateWikiNode: Name = " + name + " Arguments = " + arguments; 
	}
}
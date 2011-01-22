package mywikiparser.ast;

import java.util.List;

public class RootWikiNode
	extends AbstractNode
{
	public RootWikiNode()
	{
	}
	
	
	public RootWikiNode(List<IWikiNode> children)
	{
		super(children);
	}
	
	@Override
	public <T> void accept(IWikiNodeVisitor<T> visitor)
	{
		visitor.visit(this);
	}
}

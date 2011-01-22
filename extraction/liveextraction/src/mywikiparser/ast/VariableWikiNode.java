package mywikiparser.ast;


public class VariableWikiNode
	extends AbstractNode
{
	@Override
	public <T> void accept(IWikiNodeVisitor<T> visitor)
	{
		visitor.visit(this);
	}
}
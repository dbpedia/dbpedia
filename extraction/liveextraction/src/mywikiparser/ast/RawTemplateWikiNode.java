package mywikiparser.ast;


public class RawTemplateWikiNode
	extends AbstractNode
{
	@Override
	public <T> void accept(IWikiNodeVisitor<T> visitor)
	{
		visitor.visit(this);
	}
}
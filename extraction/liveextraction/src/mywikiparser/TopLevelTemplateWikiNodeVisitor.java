package mywikiparser;

import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.IWikiNodeVisitor;
import mywikiparser.ast.RootWikiNode;
import mywikiparser.ast.RawTemplateWikiNode;
import mywikiparser.ast.TemplateWikiNode;
import mywikiparser.ast.TextWikiNode;
import mywikiparser.ast.VariableWikiNode;


public class TopLevelTemplateWikiNodeVisitor
	implements IWikiNodeVisitor<Void>
{

	private SimpleTemplateParser p = new SimpleTemplateParser();
	
	@Override
	public Void visit(RootWikiNode node)
	{
		for(IWikiNode n : node.getChildren())
			n.accept(this);
	
		return null;
	}

	@Override
	public Void visit(TemplateWikiNode node)
	{
		//p.parse(node);
		return null;
	}
	
	@Override
	public Void visit(RawTemplateWikiNode node)
	{
		//p.parse(node);
		return null;
	}
	
	@Override
	public Void visit(VariableWikiNode node) {
		// TODO Auto-generated method stub
		return null;
	}
	
	@Override
	public Void visit(TextWikiNode node) {
		// TODO Auto-generated method stub
		return null;
	}

}

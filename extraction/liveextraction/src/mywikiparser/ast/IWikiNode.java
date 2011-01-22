package mywikiparser.ast;

import java.util.List;


public interface IWikiNode
{
	IWikiNode getParent();	
	void setParent(IWikiNode parent);
	public void addChild(IWikiNode child);
	public void addChildren(List<IWikiNode> children);
	public List<IWikiNode> getChildren();
	
	<T> void accept(IWikiNodeVisitor<T> visitor);
}

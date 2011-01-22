package mywikiparser.ast;

import java.util.ArrayList;
import java.util.List;

public abstract class AbstractNode
	implements IWikiNode
{
	private IWikiNode parent;
	private List<IWikiNode> children;

	public AbstractNode()
	{
		this.children = new ArrayList<IWikiNode>();
	}
	
	public AbstractNode(List<IWikiNode> children)
	{
		this.children = children;
	}
	
	/**
	 * replaces this node in the parent
	 * (parent must be set)
	 * @param node
	 */
	public void replaceWith(IWikiNode node)
	{
		int index = parent.getChildren().indexOf(this);
		parent.getChildren().set(index, node);		
		
		this.setParent(null);
		node.setParent(parent);
	}
	
	public IWikiNode getParent()
	{
		return parent;
	}

	public void setParent(IWikiNode parent)
	{
		this.parent = parent;
	}

	public void addChild(IWikiNode child)
	{
		child.setParent(this);
		children.add(child);
	}

	public void addChildren(List<IWikiNode> children)
	{
		for (IWikiNode child : children)
			this.addChild(child);
	}

	public List<IWikiNode> getChildren()
	{
		return children;
	}

	@Override
	public String toString()
	{
		String result = "[" + this.getClass().getName() + ":";

		for (IWikiNode item : children)
			result += item.toString();

		result += "]";

		return result;
	}

	public abstract <T> void accept(IWikiNodeVisitor<T> visitor);
}
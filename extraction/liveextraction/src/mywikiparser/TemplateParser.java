package mywikiparser;

import java.util.ArrayList;
import java.util.List;

import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.Pair;
import mywikiparser.ast.RawTemplateWikiNode;
import mywikiparser.ast.RootWikiNode;
import mywikiparser.ast.TemplateWikiNode;
import mywikiparser.ast.TextWikiNode;


/**
 * A simple template parser which only accepts templates which consist
 * of text only.
 *
 * A more complex parser would be able to deal with sub nodes
 * Note: Trims keys and values
 * 
 * A raw template node only contains text, template and variable nodes
 * 
 * A processed template node
 * 
 * @author raven_arkadon
 *
 */
public class TemplateParser
{
	
	public static List<List<IWikiNode>> split(List<IWikiNode> nodes, String regex, int limit)
	{
		List<List<IWikiNode>> result = new ArrayList<List<IWikiNode>>();
		
		List<IWikiNode> current = new ArrayList<IWikiNode>();
		
		// if limit is 0 or less any number of splits is allowed
		// (indicated here using a high number)
		int remainingLimit = limit;
		if(limit <= 0)
			remainingLimit = 32767;
		
		for(IWikiNode node : nodes) {
			
			// Things are easy if it's not a text node or if there are no
			// more splits allowed
			if(!(node instanceof TextWikiNode) || remainingLimit <= 0) {
				current.add(node);
				continue;
			}
			
			// If its a text node, split the text by the given regex
			// and create new nodes for the split text
			TextWikiNode textNode = (TextWikiNode)node;
			
			String text = textNode.getText();
			String[] parts = text.split(regex, remainingLimit);

			remainingLimit -= parts.length;
			
			for(String part : parts) {
				TextWikiNode newNode = new TextWikiNode(part);
				current.add(newNode);
				
				
				// do not start a new list after the last part
				if(part != parts[parts.length - 1])
				{
					result.add(current);
					current = new ArrayList<IWikiNode>();
				}
			}		
		}
		
		if(current.size() != 0)
			result.add(current);
		
		return result;
	}
	
	
	/**
	 * This method was first intended to do some magic:
	 * if there is only one element, return that elemnt
	 * if there are more, create a new node which holds all element
	 * 
	 * But this magic is yucky because its easier if one can be certain
	 * that the result is always a list-node again
	 * 
	 * @param nodes
	 * @return
	 */
	public static IWikiNode wrapNodeList(List<IWikiNode> nodes)
	{
		if(nodes.size() == 0)
			return null;
		//else if(nodes.size() == 1)
		//	return nodes.get(0);
		else
			return new RootWikiNode(nodes);
	}


	public static TemplateWikiNode process(RawTemplateWikiNode node)
	{
		TemplateWikiNode result = new TemplateWikiNode();
		
		List<IWikiNode> children = node.getChildren();		
		List<List<IWikiNode>> arguments = split(children, "\\|", 0);
		
		// counter for template arguments without keys
		// if a template argument is used without key, a key is automatically
		// assigned keys are 1, 2, 3, 4, ...
		int nNoKey = 0;
		
		boolean isFirst = true;
		for(List<IWikiNode> argument : arguments)
		{
			if(isFirst) {
				result.setName(wrapNodeList(argument));

				isFirst = false;
				continue;
			}
			
			List<List<IWikiNode>> keyValue = split(argument, "=", 2);
			
			IWikiNode key;
			IWikiNode value;
			
			if(keyValue.size() == 1) {
				key = new RootWikiNode();
				key.addChild(new TextWikiNode(Integer.toString(++nNoKey)));

				value = wrapNodeList(keyValue.get(0));
			}
			else {
				key = wrapNodeList(keyValue.get(0));
				value = wrapNodeList(keyValue.get(1));
			}
			
			result.getArguments().add(new Pair<IWikiNode, IWikiNode>(key, value));
		}
		
		return result;
	}

}

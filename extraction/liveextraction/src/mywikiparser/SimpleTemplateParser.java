package mywikiparser;

import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.Pair;
import mywikiparser.ast.TemplateWikiNode;
import mywikiparser.ast.TextWikiNode;




/**
 * A simple template parser which only accepts templates which consist
 * of text only.
 *
 * A more complex parser would be able to deal with sub nodes
 * 
 * Note: Trims keys and values
 * 
 * @author raven_arkadon
 *
 */
public class SimpleTemplateParser
{
	/**
	 *  TODO Add this method to some dedicated wiki-template-node helper class
	 */
	public static String nodeToText(IWikiNode node)
	{
		if(node.getChildren().size() != 1)
			return null;
		
		IWikiNode n = node.getChildren().get(0);
			
		if(!(n instanceof TextWikiNode))
			return null;
		
		return ((TextWikiNode)n).getText().trim();
	}
	

	public SimpleTemplateInfo parse(TemplateWikiNode node)
	{
		SimpleTemplateInfo result = new SimpleTemplateInfo();
		
		String name = nodeToText(node.getName());
		if(name == null)
			return null;
		
		result.setName(name);
		
		// Iterate over all arguments
		for(Pair<IWikiNode, IWikiNode> argument : node.getArguments()) {
			String key = nodeToText(argument.getFirst());
			String value = nodeToText(argument.getSecond());
			
			if(key == null)
				continue;
			
			result.indexMap().add(key, value);
		}
		
		return result;
	}
}

/*
public class SimpleTemplateParser
{
	public SimpleTemplateInfo parse(TemplateWikiNode node)
	{
		SimpleTemplateInfo result = new SimpleTemplateInfo();
		
		if(node.getChildren().size() != 1) {
			//System.out.println("Template rejected because not 1 node");
			return null;
		}
		
		IWikiNode n = node.getChildren().get(0);
		if(!(n instanceof TextWikiNode)) {
			//System.out.println("Not a TextWikiNode");
			return null;
		}
			
		TextWikiNode textNode = (TextWikiNode)n;
		
		String text = textNode.getText();
		
		
		String[] parts = text.split("\\|");
		
		//if(parts.length == 0)
			//System.out.println("Not parts");
	
		boolean isFirstPart = true;
		for(String part : parts) {
			part = part.trim();
			//for(int i = 0; i < parts.length; ++i) {
		//	String part = parts[i].trim();
			
			
			if(isFirstPart) {
				result.setName(part);
				//System.out.println("Template name = " + part);
				isFirstPart = false;
				
				continue;
			}
			
			int splitIndex = part.indexOf('=');
			
			if(splitIndex == -1) {
				result.indexMap().add(null, part);
			}
				//System.out.println("Only value given: " + part);
			else {
				String key = part.substring(0, splitIndex).trim();;
				String value = part.substring(splitIndex + 1).trim();

				result.indexMap().add(key, value);
				//System.out.println("Key: " + key + ", Value: " + value);
			}
		}
		
		return result;
	}
}
*/
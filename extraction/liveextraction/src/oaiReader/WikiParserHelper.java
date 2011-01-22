package oaiReader;

import helpers.StringUtil;

import java.io.PushbackReader;
import java.io.Reader;
import java.io.StringReader;
import java.util.Collection;
import java.util.List;
import java.util.Map;
import java.util.Set;

import mywikiparser.GenerateWikiAstDepthFirstAdapter;
import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.Pair;
import mywikiparser.ast.RootWikiNode;
import mywikiparser.ast.TemplateWikiNode;
import mywikiparser.ast.TextWikiNode;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;

import wikiparser.lexer.Lexer;
import wikiparser.node.EOF;
import wikiparser.node.Token;
import collections.IMultiMap;
import collections.MultiMap;

class NamespaceArticleName
{
	private String namespaceName;
	private String articleName;
	
	public NamespaceArticleName(String namespaceName, String articleName)
	{
		this.namespaceName = namespaceName;
		this.articleName = articleName;
	}
	
	public String getNamespaceName()
	{
		return namespaceName;
	}
	public String getArticleName()
	{
		return articleName;
	}
	
	public String toString()
	{
		if(namespaceName.isEmpty())
			return articleName;
		else
			return namespaceName + ":" + articleName;
	}
}

/**
 * This is not a helper for parsing wiki markup - it's a helper for
 * processing the abstract-syntax-tree represenation.
 * So the class name is subject to renaming.
 * 
 * 
 * Also the methods firstLetter... should be renamed to ucfirst and lcfirst
 * and moved to a StringHelper class
 * 
 * @author raven
 *
 */
public class WikiParserHelper
{
	private static Logger logger = Logger.getLogger(WikiParserHelper.class);
	
	public static RootWikiNode parse(String text)
		throws Exception	
	{
		RootWikiNode root = null;
		
		StopWatch sw = new StopWatch();
		sw.start();
		
    	Reader sourceStream = new StringReader(text);	    	 
    	Lexer lexer = new Lexer(new PushbackReader(sourceStream, 1024));	
    	GenerateWikiAstDepthFirstAdapter a = new GenerateWikiAstDepthFirstAdapter();
    	
    	Token t;
    	do {
    		t = lexer.next();
    		t.apply(a);
    	} while(!(t instanceof EOF));
    	
    	root = a.getResult();
		
		sw.stop();
		logger.trace("Parsing took: " + sw.getTime() + " text was: " + StringUtil.cropString(text, 128, 0));
		
		return root;
	}
	
	
	/**
	 * Converts wiki-name to canonical wiki case:
	 * 
	 * A wiki name consists of [namespace:]name
	 * 
	 * general:
	 * namespaceName and name will be trimmed by whitespaces
	 * All remaining white spaces will be replaced by underscores
	 * 
	 * 
	 * namespace:
	 * namespace names are case insensitive - they will be converted to
	 * first letter upper case, the rest lower case.
	 * 
	 * name:
	 * the name is case senstive except for the first letter which will be
	 * converted to upper case
	 * 
	 * 
	 * 
	 * @param str
	 * @return
	 */
	public static String toWikiCase(String str)
	{		
		String[] parts = str.split(":", 2);

		for(int i = 0; i < parts.length; ++i) {
			parts[i] = parts[i].trim();
			parts[i] = parts[i].replace(' ', '_');
		}

		// The name is the last part
		String name = StringUtil.ucFirst(parts[parts.length - 1]);
		
		// Handle namespaceName
		if(parts.length == 2)
		{
			String namespaceName = parts[0];
			
			namespaceName =
				namespaceName.substring(0, 1).toUpperCase() +
				namespaceName.substring(1).toLowerCase();
			
			return namespaceName + ":" + name;
		}
		
		return name;
	}
	
	
	public static NamespaceArticleName parseTitle(String name, Set<String> namespaces)
	{
	       String[] parts = name.split(":", 2);
	       String namespaceName = "";
	       String articleName = null;
	       
	       // Note: Just because there are 2 parts, it doesn't mean that we have
	       // namespace. e.g. Mission:Impossible - 'Mission' is not a namespace.
	       if(parts.length == 2) {
	           namespaceName = canonicalWikiTrim(parts[0]);
	           namespaceName = StringUtil.ucFirst(namespaceName.toLowerCase());

	           if(namespaces.contains(namespaceName))
	               articleName = StringUtil.ucFirst(canonicalWikiTrim(parts[1]));
	       }
	       
	       // if there is no articleName yet, the whole name is the articleName
	       if(articleName == null) {
	    	   namespaceName = "";
	    	   articleName = StringUtil.ucFirst(canonicalWikiTrim(name));
	       }
	       
	       return new NamespaceArticleName(namespaceName, articleName);
	}
	
   /**
    *
    * Converts string to canonical wiki representation
    * Namespace is only recognized if there is an entry in namespaces
    * Namespace part and name part will be trimmed
    * Remaining whitespaces will be replaced by underscores
    * TODO Multiple consequtive underscores will be replaced by a single underscore
    * The whole namespace name will be turned lowercase except for the first letter
    * The first letter of the name will be made uppercase
    *
    * Example
    *    mYnameSPACE  :     wHat     EVER
    * will currently become:
    * MYnameSPACE:WHat_____EVER
    * should become
    * MYnameSPACE:WHat_EVER
    *
    *
    * @param <type> $str The source string
    * @param <type> $namespaces An array containing the names of namespaces
    * @return <type> A canonical representation of the wiki name
    *
    */
   public static String toCanonicalWikiCase(String name, Set<String> namespaces)
   {
       return parseTitle(name, namespaces).toString();
   }

	
    /**
     * Removes heading and trailing whitespaces
     * Replaces remaining white spaces with underscore
     * Replaces consecutive underscores with a single underscore
     *
     * @param <type> $name
     * @return <type>
     */
    public static String canonicalWikiTrim(String name)
    {
    	String result = name.trim();
    	result = result.replace(' ', '_');
    	result = result.replaceAll("_+", "_");
        
        return result;
    }

	
	/**
	 * Given a map and a set of keys, this function iterates over all given
	 * keys in order and returns the first non-null value for a key in the map.
	 * 
	 * 
	 * @param <K>
	 * @param <V>
	 * @param map
	 * @param keys
	 * @return
	 */
	public static <K, V> V getAlternatives(Map<? extends K, ? extends V> map, K ... keys)
	{
		V value;
		for(K key : keys)
			if((value = map.get(key)) != null)
				return value;
		
		return null;
	}

    /*
	public static List<V> getAlternatives(Map<? extends K, ? extends V> map, K ... keys)
	{
		V value = null;
		for(K key : keys) {
			if((value = map.get(key)) != null)
				
				return value;
		}
		
		return null;
	}
	*/
	
	/**
	 * 
	 * @param node
	 */
	public static String toWikiName(IWikiNode node)
	{
		if(node.getChildren().size() != 1)
			return null;
		
		// The name may only consist of a single text node
		IWikiNode n = node.getChildren().get(0);
		if(!(n instanceof TextWikiNode))
			return null;
				
		TextWikiNode textNode = (TextWikiNode)n;
		
		return WikiParserHelper.toWikiCase(textNode.getText());
	}
	
	/**
	 * Attempts to interpret the given node as a tempate node
	 * 
	 * The given name should be in wiki case which can be obtained
	 * through WikiParserHelper.toWikiCase()
	 * 
	 * Note: Method is now case insensitive
	 * 
	 * @param name
	 * @param node
	 * @return
	 */
	public static TemplateWikiNode getAsTemplateNode(String name, IWikiNode node)
	{
		if(!(node instanceof TemplateWikiNode))
			return null;

		TemplateWikiNode templateNode = (TemplateWikiNode)node;

		String templateName = toWikiName(templateNode.getName());
		if(templateName == null)
			return null;
		
		// Not case-insensitive
		if(!templateName.equalsIgnoreCase(name))
			return null;
		
		return templateNode;
	}


	/**
	 * indexes arguments - but only those that have a text-name
	 * (that should be 99% if not 100% or all arguments)
	 * 
	 * @param node
	 * @return
	 */
	public static IMultiMap<String, IWikiNode>
		indexArguments(TemplateWikiNode node)
	{
		MultiMap<String, IWikiNode> result =
			new MultiMap<String, IWikiNode>();
		
		for(Pair<IWikiNode, IWikiNode> argument : node.getArguments()) {
			IWikiNode key = argument.getFirst();
			IWikiNode value = argument.getSecond();
			
			if(key.getChildren().size() != 1)
				continue;
			
			IWikiNode keyNode = key.getChildren().get(0);
			if(!(keyNode instanceof TextWikiNode))
				continue;
			
			TextWikiNode keyTextNode = (TextWikiNode)keyNode;
			
			String name = keyTextNode.getText().trim();
			
			result.put(name, value);
		}
		
		return result;
	}
	
	/**
	 * Indexes all templates
	 * 
	 * @param nodes
	 * @return
	 */
	public static IMultiMap<String, TemplateWikiNode>
		indexTemplates(Collection<IWikiNode> nodes)
	{
		MultiMap<String, TemplateWikiNode> result =
			new MultiMap<String, TemplateWikiNode>();
		
		for(IWikiNode node : nodes) {
			if(!(node instanceof TemplateWikiNode))
				continue;
			
			TemplateWikiNode templateNode = (TemplateWikiNode)node;
			
			String name = toWikiName(templateNode.getName());
			if(name == null)
				continue;
			
			result.put(name, templateNode);
		}		
		
		return result;
	}

	
	/**
	 * The subpage name is part after the last slash
	 * 
	 * @param name
	 */
	public static String extractSubPageName(String name)
	{
		name = toWikiCase(name);
		
		int slashIndex = name.lastIndexOf('/');
		if(slashIndex < 0)
			return name;
		
		return name.substring(slashIndex + 1);
	}
}

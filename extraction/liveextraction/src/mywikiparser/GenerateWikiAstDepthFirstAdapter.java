package mywikiparser;


import java.util.ArrayList;
import java.util.List;

import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.IWikiNodeVisitor;
import mywikiparser.ast.RawTemplateWikiNode;
import mywikiparser.ast.RootWikiNode;
import mywikiparser.ast.TemplateWikiNode;
import mywikiparser.ast.TextWikiNode;
import mywikiparser.ast.VariableWikiNode;
import wikiparser.analysis.AnalysisAdapter;
import wikiparser.node.EOF;
import wikiparser.node.TLBraceSeq;
import wikiparser.node.TRBraceSeq;
import wikiparser.node.TText;


/**
 * This visitor merges adjacing text nodes.
 * 
 * This is more a hack, but i guess it simplifies the wiki tree generation
 * if we deal with merging afterwards
 * 
 * 
 * @author raven_arkadon
 *
 */
class TextMergerVisitor
	implements IWikiNodeVisitor<Void>
{
	private Void merge(IWikiNode node)
	{		
		List<IWikiNode> nodes = node.getChildren();
	
		for(IWikiNode child : nodes)
			child.accept(this);
		
		if(nodes.size() < 2)
			return null;
		
		int current = nodes.size() - 1;
		int prev    = current - 1;
		
		while(prev >= 0) {
			
			IWikiNode b = nodes.get(current);
			IWikiNode a = nodes.get(prev);
			
			
			if(a instanceof TextWikiNode && b instanceof TextWikiNode) {
				TextWikiNode t = (TextWikiNode)b;
				TextWikiNode s = (TextWikiNode)a;

				s.setText(s.getText() + t.getText());
				nodes.remove(current);
			}
			
			--current;
			--prev;
		}
		
		return null;
	}
	
	
	@Override
	public Void visit(RootWikiNode node)
	{
		return merge(node);
	}

	@Override
	public Void visit(TemplateWikiNode node)
	{
		throw new RuntimeException("Should never come here");
		//return null;
	}
	
	@Override
	public Void visit(RawTemplateWikiNode node)
	{
		merge(node);
		
		TemplateWikiNode newNode = TemplateParser.process(node);
		node.replaceWith(newNode);
		
		return null;
	}

	@Override
	public Void visit(VariableWikiNode node)
	{
		return merge(node);
	}

	@Override
	public Void visit(TextWikiNode node)
	{
		return merge(node);
	}
	
}













class TokenInfo
{
	private char token;
	private int count;
	
	private StringBuilder textBuilder = new StringBuilder();
	
	private List<IWikiNode> nodes = new ArrayList<IWikiNode>();
	
	public List<IWikiNode> wikiNodes()
	{
		return nodes;
	}
	
	public TokenInfo(char token, int initialCount)
	{
		this.token = token;
		this.count = initialCount;
	}
	
	public char getToken()
	{
		return token;
	}
	
	public int getCount()
	{
		return count;
	}
	
	public void setCount(int count)
	{
		this.count = count;
	}
	
	public void setTextBuilder(StringBuilder textBuilder)
	{
		this.textBuilder = textBuilder;
	}
	
	public StringBuilder textBuilder()
	{
		return textBuilder;
	}
	
	@Override
	public String toString()
	{
		String result = "Token = '" + getTokenString() + "'";
		
		return result;
	}
	
	public String getTokenString()
	{
		String result = "";

		for(int i = 0; i < count; ++i)
			result += token;
		
		return result;
	}
	
	public void clearText()
	{
		this.textBuilder.delete(0, this.textBuilder.length());
	}
}





public class GenerateWikiAstDepthFirstAdapter
	extends AnalysisAdapter
{
	private ArrayList<TokenInfo> stack = new ArrayList<TokenInfo>();
	
	
	private RootWikiNode result;

	
	public RootWikiNode getResult()
	{
		return result;
	}

	public GenerateWikiAstDepthFirstAdapter()
	{
		super();
		
		// push a token onto the stack which can't be removed
		stack.add(new TokenInfo('\0', 0)); 
	}
	
	private TokenInfo getTos()
	{
		return stack.get(stack.size() - 1);
	}

	private TokenInfo pop()
	{
		return stack.remove(stack.size() - 1);
	}

	
	private void finalizeTos()
	{
		TokenInfo old = getTos();

		// finalize existing text in the current tos into a text wiki node
		String lastText = old.textBuilder().toString(); 
		if(lastText.length() != 0) {
			TextWikiNode textNode = new TextWikiNode(lastText);
			old.wikiNodes().add(textNode);
			old.clearText();
		}
		
	}
	
	private void push(TokenInfo tokenInfo)
	{
		finalizeTos();
		stack.add(tokenInfo);
	}
	
	private String repeat(char character, int n)
	{
		String result = "";
		
		for(int i = 0; i < n; ++i)
			result += character;
		
		return result;
	}
	
	
	/**
	 * 
	 * 
	 * @param count
	 * /
	private void removeTosTokens(int count)
	{
		TokenInfo tos = getTos();
		int remaining = tos.getCount() - count;

		if(remaining == 0) { 
			popTos();
			//System.out.println("Text gathered: " + getTos().textBuilder().toString());
			getTos().textBuilder().delete(0, getTos().textBuilder().length());
		}
		else
			tos.setCount(remaining);
	}
	
	
	/**
	 * Reduce the tos into a single wikiNode (this pops the tos)
	 * 
	 * Any matched text in the tos becomes a 
	 * 
	 * removes count tokens from the tos, popping the tos if no tokens remain
	 * 
	 * creates a textwikinode for any text matched so far and
	 * clears the text
	 * 
	 * appends the textnode to the new tos (after removing count tokens)
	 * 
	 * @param count
	 */
	private void reduce(int count, IWikiNode reduceNode)
	{		
		finalizeTos();
		TokenInfo tos = getTos();
		int remaining = tos.getCount() - count;

		// Move all nodes of the current tos into the reduceNode
		// FIXME: reduceNode can be assumed to be empty - so
		// the list is just put into the node
		//reduceNode.setChilren(tos.wikiNodes);
		//List<IWikiNode> result = new ArrayList<IWikiNode>(tos.wikiNodes());

		for(IWikiNode tosWikiNode : tos.wikiNodes())
			reduceNode.addChild(tosWikiNode);
		
		tos.wikiNodes().clear();
		
		if(remaining == 0) { 
			pop();
		}
		else
			tos.setCount(remaining);

		// Append the reduce node to the previous level
		getTos().wikiNodes().add(reduceNode);
	}
	
	
	
	private void reduceTosToText()
	{
		reduceTosToText(getTos().getCount());
	}
	/**
	 * Moves tos tokens to text
	 * 
	 * This happens when a candidate is not resolved e.g.
	 * when {{{{ is matched by }}}} or not matched at all
	 * 
	 * All nodes gathered in the tos are moved down to the parent
	 *           {{                           }}
	 *       [[
	 *               text   variable template 
	 * text
	 * 
	 * Reduces the tos to text (instead of a node like variable or template)
	 * 
	 * Appends the current tos token to the text of the previous
	 * 
 * This visitor merges adjacing text nodes.
 * 
 * This is more a hack, but i gue
	 * If there are no nodes, appends the current text to the text of the previous
	 * Otherwise forms a text node of the combined text, appends all nodes, and sets the text to that of the current node
	 * 
	 * 
	 */
	private void reduceTosToText(int count)
	{
		finalizeTos();
		TokenInfo tos = getTos();
		
		TextWikiNode tokenTextWikiNode =
			new TextWikiNode(repeat(tos.getToken(), count));
		
		int remaining = tos.getCount() - count;
		if(remaining == 0) {
			// Remove the current tos, and move to the next one
			pop();
			TokenInfo previous = getTos();
			
			previous.wikiNodes().add(tokenTextWikiNode);
			previous.wikiNodes().addAll(tos.wikiNodes());
		}
		else {
			// Transform token into text
			tos.setCount(remaining);
			tos.wikiNodes().add(0, tokenTextWikiNode);
		}		
	}
	
	
	@Override
	public void caseTLBraceSeq(TLBraceSeq node)
	{
		int count = node.getText().length();

		//System.out.println("Got { * " + count);
		
		// Treat a single token as text
		if(count == 1)
			getTos().textBuilder().append(node.getText());
		else	
			push(new TokenInfo('{', count));
	}

	/**
	 * Process closing braces.
	 * 
	 * > -> text (remove as many opening braces)
	 * 3 -> variable
	 * 2 -> template
	 * < -> text
	 * 
	 */
	@Override
	public void caseTRBraceSeq(TRBraceSeq node)
	{
		int count = node.getText().length();
		//System.out.println("Got } * " + count);
		
		// Scan the stack for matching opening }		
		TokenInfo tos = getTos();
		
		if(tos.getToken() != '{') {
			tos.textBuilder().append(node.getText());
			return;
		}
		
		int nMatched = Math.min(tos.getCount(), count);
		if(nMatched > 3) {
			// error: treat as text
			reduceTosToText(nMatched);
			
		}
		else if(nMatched == 3) {
			// variable
			//System.out.println("Got Variable: " + tos.textBuilder().toString());
			reduce(nMatched,  new VariableWikiNode());
		}
		else if(nMatched == 2) {
			// template
			//System.out.println("Got Template: " + tos.textBuilder().toString());
			reduce(nMatched,  new RawTemplateWikiNode());
			
		}
		else {
			// treat single token as text
			tos.textBuilder().append(node.getText());
		}
	}
	

	/*
	public void caseTLBracketSeq(TLBoxBracketSeq node)
	{
		int count = node.getText().length();
		
		// Treat a single token as text
		if(count == 1)
			getTos().textBuilder().append(node.getText());
		else	
			push(new TokenInfo('[', count));
	}


	public void caseTRBracketSeq(TRBoxBracketSeq node)
	{
		int count = node.getText().length();
		
		// Scan the stack for matching '['		
		TokenInfo tos = getTos();		
		if(tos.getToken() != '[') {
			tos.textBuilder().append(node.getText());
			return;
		}
		
		int nMatched = Math.min(tos.getCount(), count);
		if(nMatched > 2) {
			// error: treat as text
			reduceTosToText(nMatched);
			
		}
		else if(nMatched == 2) {
			// template
			reduceTosToText(nMatched);
			
			System.out.println("Got SmartLink: " + tos.textBuilder().toString());
		}
		else {
			// treat single token as text
			tos.textBuilder().append(node.getText());
		}
	}

	
	public void caseTLChevronSeq(TLChevronSeq node)
	{
		/*
		int count = node.getText().length();
		stack.add(new TokenInfo('<', count));
		* /
	}

	public void caseTRChevronSeq(TRChevronSeq node)
	{
		/*
		int count = node.getText().length();
		TokenInfo tos = getTos();
		
		// if the token doesnt match treat as text
		if(tos.getToken() != '<') {
			tos.textBuilder().append(node.getText());
			return;
		}
		
		int delta = tos.getCount();

		
		// if there are still characters left, refire the event
		if(
		
		//tos.getC
		
		System.out.println("Got > x " + node.getText().length());
		* /
	}
*/
	
	/**
	 * Upon encountering the end of a file, treat all unmatched input as text
	 * 
	 */
	@Override
	public void caseEOF(EOF node)
	{
		while(stack.size() > 1)
			reduceTosToText();

		// Ooops, almost forgot to treat remaining text
		
		finalizeTos();
		//System.out.println("Remaining nodes = " + getTos().wikiNodes());
		
		//RootWikiNode root = new RootWikiNode();
		result = new RootWikiNode();
		result.addChildren(getTos().wikiNodes());
		
		// hack: clean up some mess (merge adjacing text nodes)
		TextMergerVisitor visitor = new TextMergerVisitor();
		result.accept(visitor);
		
		
		//System.out.println("Node at root level: " + root.toString());
		
		/*
		String remainingText = "";
		for(TokenInfo item : stack) {
			if(item.getToken() == '\0')
				continue;
			
			remainingText += item.getTokenString() + item.textBuilder().toString();
		}
		
		if(remainingText.equals(""))
			System.out.println("No remaining text");
		else
			System.out.println("Remaining Text = " + remainingText);
			*/
	}
	
	/**
	 * Append the text to the TOS (top of stack)
	 * 
	 * 
	 */
	@Override
	public void caseTText(TText node)
	{
		TokenInfo tokenInfo = this.getTos();
		
		tokenInfo.textBuilder().append(node.getText());
	}
}

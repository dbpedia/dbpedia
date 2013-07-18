package mywikiparser.ast;

public interface IWikiNodeVisitor<T>
{
	T visit(RootWikiNode node);
	T visit(RawTemplateWikiNode node);
	
	T visit(TemplateWikiNode node);
	T visit(VariableWikiNode node);
	T visit(TextWikiNode node);
}
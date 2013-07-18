package oaiReader.handler.generic;

public interface IClassifier<TItem, TCategory>
{
	TCategory classify(TItem item);
}

package oaiReader;

public interface IRecordVisitor<T>
{
	T visit(Record record);
	T visit(DeletionRecord record);
}

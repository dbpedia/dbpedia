package oaiReader;

public interface IRecord
{
	<T> T accept(IRecordVisitor<T> visitor);
}

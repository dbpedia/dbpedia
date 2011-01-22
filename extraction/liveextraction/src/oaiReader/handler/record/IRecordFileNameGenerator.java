package oaiReader.handler.record;

import oaiReader.Record;

public interface IRecordFileNameGenerator
{
	String generate(Record filename)
		throws Exception;
}

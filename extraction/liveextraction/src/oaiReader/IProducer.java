package oaiReader;

public interface IProducer<T>
{
	IHandler<T> getHandler();
	void setHandler(IHandler<T> handler);
}

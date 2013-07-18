package connection 
{
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public interface ISPARQLResultParser 
	{
		function handleSPARQLResultEvent(event:SPARQLResultEvent):void;
	}
	
}
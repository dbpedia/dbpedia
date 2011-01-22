package connection 
{
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class SPARQLResultTracer implements ISPARQLResultParser
	{
		
		public function SPARQLResultTracer() 
		{
			
		}
		
		public function handleSPARQLResultEvent(event:SPARQLResultEvent):void {
			trace(event.result);
		}
		
	}
	
}
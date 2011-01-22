package
{
	import flash.display.Graphics;  
	import mx.skins.ProgrammaticSkin;

	public class CustomSkin extends ProgrammaticSkin 
	{
		private static const RADIUS:int = 20;

		override protected function updateDisplayList( w:Number, h:Number ):void 
		{	
			var color:uint;
			
			switch (name) 
			{
				case "upSkin":
					color = 0xFFFFFF;
					break;
				case "overSkin":
					color = 0xEEEEEE;
					break;
				case "downSkin":
				case "selectedUpSkin":
				case "selectedOverSkin":
				case "selectedDownSkin":
					color = 0xCCCCCC;
			}
			
			var g:Graphics = graphics;
			g.clear();
			g.beginFill( color );
			g.lineStyle( 2, 0x999999 );
			g.drawRoundRect( 1, 1, w-2, h-2, RADIUS );
			g.endFill();
		}
	}
}
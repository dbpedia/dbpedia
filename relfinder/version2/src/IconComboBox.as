package
{
	import mx.controls.ComboBox;
	import mx.core.Application;
	
	public class IconComboBox extends ComboBox {
		
		public function IconComboBox() 
		{
			super();
		}
		
		override protected function updateDisplayList(unscaledWidth:Number, unscaledHeight:Number):void {
			super.updateDisplayList(unscaledWidth, unscaledHeight);
			
			drawRestricted();
			
        }
        
        public function drawRestricted():void{
        	graphics.clear();
        	
        	var col:uint = 0xFF6A00;
        	
        	graphics.beginFill(col)
			graphics.drawCircle(15, 11, 9);
			graphics.drawCircle(15, 11, 7);
			
			graphics.lineStyle(2, col);
			graphics.moveTo(15 - 5, 11 - 5);
			graphics.lineTo(15 + 5, 11 + 5);
			graphics.endFill();
        }
        
        private function app():Main {
			return Application.application as Main;
		}
		
	}

}
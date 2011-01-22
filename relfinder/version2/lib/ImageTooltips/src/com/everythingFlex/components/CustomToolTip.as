package com.everythingFlex.components
{
       import mx.containers.*;
       import mx.controls.ToolTip;
       import mx.core.*;

       public class CustomToolTip extends VBox implements IToolTip
       {
               public function CustomToolTip()
               {
                   mouseEnabled = false;
                   mouseChildren=false;
                   setStyle("paddingLeft", 2);
                   setStyle("paddingTop", 2);
                   setStyle("paddingBottom", 2);
                   setStyle("paddingRight", 2);
               }
               
               public function get text():String {     
               		return null; 
               }
               public function set text(value:String):void {}
       }
}
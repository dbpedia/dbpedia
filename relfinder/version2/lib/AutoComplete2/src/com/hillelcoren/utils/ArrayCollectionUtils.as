package com.hillelcoren.utils
{
	import flash.utils.Dictionary;
	
	import mx.collections.ArrayCollection;
	
	public class ArrayCollectionUtils
	{
		public static const MOVE_TOP:String 	= "MOVE_TOP";
		public static const MOVE_UP:String 		= "MOVE_UP";
		public static const MOVE_DOWN:String 	= "MOVE_DOWN";
		public static const MOVE_BOTTOM:String 	= "MOVE_BOTTOM";

		public static function moveItems( arrayCollection:ArrayCollection, selectedIndices:Array, moveTo:String ):void
		{
			var item:Object;
			var currentIndex:uint;
			var newIndex:uint;
			var count:uint = 0;
			
			var newIndicesArr:Array = [];
			var newIndicesDict:Dictionary = new Dictionary();
			
			// first thing we'll do is figure out the new indices 
			// for the selected items
			for each (currentIndex in selectedIndices)
			{
				item = arrayCollection.getItemAt( currentIndex );
				
				switch (moveTo)
				{
					case MOVE_TOP:							
						newIndex = count;
						break;
					case MOVE_UP:
						newIndex = getNewIndex( arrayCollection, item, MOVE_UP );
						break;
					case MOVE_DOWN:
						newIndex = getNewIndex( arrayCollection, item, MOVE_DOWN );
						break;
					case MOVE_BOTTOM:
						newIndex = arrayCollection.length - (selectedIndices.length - count);
						break;
				}
				
				newIndicesArr.push( newIndex );
				newIndicesDict[newIndex] = item;
				
				count++;
			}
			
			// since rearanging some items causes other items to move
			// we'll sort the order based on the direction we're going 
			if (moveTo == MOVE_DOWN || moveTo == MOVE_BOTTOM)
			{
				newIndicesArr.sort( Array.DESCENDING | Array.NUMERIC );
			}
			
			// then we'll move the items to their new spots
			for (var x:uint = 0; x < newIndicesArr.length; x++)
			{
				newIndex = Number(newIndicesArr[x]);
				item = newIndicesDict[newIndex];
				currentIndex = arrayCollection.getItemIndex( item );
				
				arrayCollection.removeItemAt( currentIndex );
				arrayCollection.addItemAt( item, newIndex );
			}					
		}
		
		private static function getNewIndex( arrayCollection:ArrayCollection, item:Object, moveTo:String ):uint
		{
			var index:uint = arrayCollection.getItemIndex( item );
			
			if (moveTo == MOVE_UP)
			{
				if (index == 0)
				{
					return 0;
				}
				else
				{
					return --index;
				}
			}
			else
			{
				if (index == arrayCollection.length - 1)
				{
					return arrayCollection.length - 1;
				}
				else
				{
					return ++index;
				}
			}
		}		
		
		public static function areTheSame( ac1:ArrayCollection, ac2:ArrayCollection ):Boolean
		{
			if (ac1 == null || ac2 == null)
			{
				return false;
			}
			
			if (ac1.length != ac2.length)
			{
				return false;
			}
			
			for (var index:uint = 0; index < ac1.length; index++)
			{
				var obj1:Object = ac1.getItemAt( index );
				var obj2:Object = ac2.getItemAt( index );
				
				if (obj1.id != obj2.id)
				{
					return false;
				}
			}
			
			return true;
		}
		
		public static function inArray( needle:*, haystack:Array ):Boolean
		{ 
			var itemIndex:int = haystack.indexOf( needle ); 
			return ( itemIndex < 0 ) ? false : true;
		}
	}
}

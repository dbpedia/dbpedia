package utils 
{
	import mx.collections.ArrayCollection;
	import mx.collections.Sort;
	import mx.collections.SortField;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class SimilaritySort 
	{
		private static var mWeightThreshold:Number = 0.7;
		private static var mNumChars:int = 4;
		
		public static function sort(data:ArrayCollection, comparator:String):void {
			
			if (data.length > 1) {
				
				var helperCollection:ArrayCollection = new ArrayCollection();
				
				for each(var ob:Object in data) {
					var newOb:Object = new Object();
					newOb.similarity = similarity(ob.label.toString().toLowerCase(), comparator.toLowerCase());
					newOb.object = ob;
					helperCollection.addItem(newOb);
				}
				var dataSortField:SortField = new SortField();
				dataSortField.name = "similarity";
				dataSortField.numeric = true;
				dataSortField.descending = true;
				
				var numericDataSort:Sort = new Sort();
				numericDataSort.fields = [dataSortField];
				
				helperCollection.sort = numericDataSort;
				helperCollection.refresh();
				
				data.removeAll();
				for each(var ob2:Object in helperCollection) {
					data.addItem(ob2.object);
				}
			}
		}
		
		/**
		 * Calculating similarity with Jaro-Winkler-Distance
		 * 
		 * @param	str1 - 1st String
		 * @param	str2 - 2nd String
		 * @return	Similarity
		 */
		public static function similarity(str1:String, str2:String):Number {
			
			var sim:Number;
			
			var len1:int = str1.length;
			var len2:int = str2.length;
			
			if (len1 == 0) {
				if (len2 == 0) {
					return 1.0;
				}else {
					return 0.0;
				}
			}
			
			var searchRange:int = Math.max(0, Math.max(len1, len2) / 2 - 1);
			
			var matched1:Array = new Array(len1);
			for (var m1:int = 0; m1 < len1; m1++) {
				matched1[m1] = false;
			}
			
			var matched2:Array = new Array(len2);
			for (var m2:int = 0; m2 < len2; m2++) {
				matched2[m2] = false;
			}
			
			var numCommon:int = 0;
			var i:int;
			var j:int;
			for (i = 0; i < len1; ++i) {
				var start:int = Math.max(0, i - searchRange);
				var end:int = Math.min(i + searchRange + 1, len2);
				
				for (j = start; j < end; ++j) {
					if ((matched2[j] as Boolean) == true) {
						continue;
					}
					if (str1.charAt(i) != str2.charAt(j)) {
						continue;
					}
					matched1[i] = true;
					matched2[j] = true;
					++numCommon;
					break;
				}
			}
			
			if (numCommon == 0) {
				return 0.0;
			}
			
			var numHalfTransposed:int = 0;
			j = 0;
			
			for (i = 0; i < len1; ++i) {
				if ((matched1[i] as Boolean) == false) {
					continue;
				}
				while ((matched2[j] as Boolean) == false) {
					++j;
				}
				if (str1.charAt(i) != str2.charAt(j)) {
					++numHalfTransposed;
				}
				++j;
			}
			
			var numTransposed:int = numHalfTransposed / 2;
			
			var numCommonD:Number = new Number(numCommon);
			var weight:Number = (numCommonD / len1 + numCommonD / len2 + (numCommon - numTransposed) / numCommonD) / 3.0;
			
			if (weight <= mWeightThreshold) {
				return weight;
			}
			
			var max:int = Math.min(mNumChars, Math.min(len1, len2));
			var pos:int = 0;
			while (pos < max && str1.charAt(pos) == str2.charAt(pos)) {
				++pos;
			}
			
			if (pos == 0) {
				return weight;
			}
			
			return weight + 0.1 * pos * (1.0 - weight);
			
		}
		
	}
	
}
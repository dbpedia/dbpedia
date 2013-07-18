/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package connection 
{
	import mx.rpc.http.HTTPService;
	
	public class SPARQLQuery extends HTTPService
	{
		public var query:String;
		public var defaultGraphURI:String;
		public var phpSessionId:String;
		public var obj:Object = null;
		
		public function SPARQLQuery(_host:String) 
		{
			super(_host);
			super.url = _host;
		}
		
		public function execute():void{
			var params:Object = new Object();
			params.query = this.query;
			params.output = super.resultFormat;
			if (this.defaultGraphURI != "") {
				params["default-graph-uri"] = this.defaultGraphURI;
			}
			
			
			//super.addEventListener(ResultEvent.RESULT, responseListener);
			super.cancel();
			super.send(params);
		}
	}
	
}
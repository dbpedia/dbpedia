////////////////////////////////////////////////////////////////////////////////
//
// Copyright (C) 2003-2006 Adobe Macromedia Software LLC and its licensors.
// All Rights Reserved.
// The following is Sample Code and is subject to all restrictions on such code
// as contained in the End User License Agreement accompanying this product.
// If you have received this file from a source other than Adobe,
// then your use, modification, or distribution of it requires
// the prior written permission of Adobe.
//
////////////////////////////////////////////////////////////////////////////////

import mx.utils.ArrayUtil;
import mx.events.CloseEvent;

		[Bindable]
		public var TeamInfoDP:Array = [
		{firstname:"Ajit",lastname:"Gosavi",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Ram",lastname:"Krishnaiyer",designation:"Engineering Manager",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Jyoti",lastname:"Kishnani",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Lauren",lastname:"Park",designation:"Quality Manager",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Nisheet",lastname:"Jain",designation:"MTS",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Nihit",lastname:"Saxena",designation:"Computer Scientist",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Sreenivas",lastname:"Ramaswamy",designation:"Computer Scientist",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Manish",lastname:"Jethani",designation:"Computer Scientist, Sw Engrg 3",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Rishikesh",lastname:"Shetty",designation:"Computer Scientist",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"PR",lastname:"Muruganandh",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Sam",lastname:"Reuben",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Kishan",lastname:"Venkataramana",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Anjali",lastname:"Bhardwaj",designation:"Computer Scientist",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Harish",lastname:"Sivaramakrishnan",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Raghu",lastname:"Rao T.S.",designation:"Quality Engineer",role:"QA",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Deeptika",lastname:"Gottipati",designation:"MTS",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Anant",lastname:"Gilra",designation:"Computer Scientist",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		{firstname:"Sameer",lastname:"Bhat",designation:"MTS",role:"Dev",birthdate:"20June",phone:"4444",imid:"imid@domain.com"},
		];
		
		[Bindable]
		public var SearchFieldDP:Array = [
		{label:"First Name", data:"firstname"},
		{label:"Last Name", data:"lastname"},
		{label:"Designation", data:"designation"},
		];
		
		[Bindable]
		public var notFound:Array = [
		{firstname:"Not Found",lastname:"Not Found",designation:"Not Found",role:"Not Found",birthdate:"Not Found",phone:"Not Found",imid:"Not Found"},
		];
		
		[Bindable]
		public var SearchTypeDP:Array = [
		"Begins","Contains"
		];
		          
        private function ch1(event:Event):void
        {
        	callLater(ChangeHandler1,[event]);
        }
        private var ac:ArrayCollection;
        private function ChangeHandler1(event:Event):void
        {
        	  	ac = SearchString.dataProvider as ArrayCollection;
        	  	if(ac.length == 0)
        	  	{
        	  		dg.dataProvider = notFound;
        	  	}
        	  	else
        	  	{
        	  		dg.dataProvider = SearchString.dataProvider as ArrayCollection;
        	  	}
        }
        private function AddChangeHandler():void
        {
			SearchString.addEventListener("typedTextChange",ch1, false);            	
        }
        private function UpdateSearchField():void
        {
        	SearchString.labelField = SearchField.selectedItem.data;
        	SearchString.text = "";
        	SearchString.typedText = "";
        	SearchString.selectedIndex = -1;
        }
        
        private function UpdateSearchType():void
        {
        	if(SearchType.selectedLabel=="Contains")
        	{
        	SearchString.filterFunction=MyFilterFunction;
        	SearchString.typedText = "";
        	SearchString.text = "";
        	SearchString.selectedIndex = -1;
        	}
        	else
        	{
        	SearchString.filterFunction=null;
        	SearchString.typedText = "";
        	SearchString.text = "";
        	SearchString.selectedIndex = -1;
        	}
        }
        private function MyFilterFunction(element:*, typedText:String):Boolean
		{
				var r:RegExp = new RegExp(SearchString.typedText, "i");
				var label:String = SearchString.itemToLabel(element);
				return r.test(label);
		}
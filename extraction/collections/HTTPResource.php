<?php

abstract class HTTPResource  extends AbstractResource {
	
	
	
		public function httpget($url){
				$s = curl_init();
				Timer::start(get_class($this)."");
				curl_setopt($s, CURLOPT_URL,$url);
				curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($s, CURLOPT_BINARYTRANSFER, true);
				$result = curl_exec($s);
				if($result===false){
					Logger::warn($url.' returned faulty result '.curl_error());
				}
				Timer::stop(get_class($this)."");
				//echo $result;
				return $result;
			
			}
			
		public function httppost($url){
				$s = curl_init();
/*
				$post_data = array("FLD1"=>"VAL1","FLD2"=>"VAL2");
*/
				$t = "{{Infobox+Person%0A|+name++++++++%3D+Job+Charnock%0A|+image+++++++%3D%0A|+birth_date++%3D+c.+1630%0A|+birth_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[London]]%2C+[[England]]%0A|+death_date+%3D+{{death-date|10+January+1693|[[10+January]]+[[1693]]}}+%0A|+death_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[Calcutta]]%2C+[[British+India|India]]+%0A|+other_names+%3D+%0A|+known_for+++%3D+Founding+[[Calcutta]]%0A|+occupation++%3D+Colonial+Administrator}}%0A%0A'''Job+Charnock'''+(c+1630-1693)+was+a+servant+and+administrator+of+the+[[British+East+India+Company|English+East+India+Company]]%2C+traditionally+regarded+as+the+founder+of+the+city+of+[[Calcutta]].<ref>Thankappan+Nair%2C+Job+Charnock%3A+The+Founder+of+Calcutta%2C+Calcutta%3A+Engineering+Press%2C+1977<%2Fref><ref>[http%3A%2F%2Fbanglapedia.org%2Fht%2FC_0140.HTM+Banglapedia+Article+on+Job+Charnock]<%2Fref><ref>[http%3A%2F%2Fwww.britannica.com%2Feb%2Farticle-9022634%2FJob-Charnock+Encyclopedia+Britannica+article+on+Charnock]<%2Fref>+%0A%0A%3D%3DEarly+life+and+career%3D%3D%0ACharnock+came+from+a+[[Lancashire]]+family+and+was+the+second+son+of+Richard+Charnock+(''d.''+c.+1665)%2C+of+[[London]].+The+[[Puritans|Puritan]]+[[preacher]]+[[Stephen+Charnock]]+(1628–1680)+was+probably+his+elder+brother.++He+went+out+to+[[British+India|India]]+on+a+private+trading+enterprise+in+the+employ+of+the+merchant+Maurice+Thomson%2C+some+time+between+1650+and+1653%2C+but+in+January+1658+he+joined+the+East+India+Company's+service+in+[[Bengal]]%2C+where+he+was+stationed+by+turn+at+[[Cossimbazar]]%2C+[[Hooghly+River|Hooghly]]+(a+[[Portugal|Portuguese]]+trading+settlement+on+the+river+of+the+same+name)%2C+and+[[Balasore]].+He+learnt+the+local+languages%2C+cut+his+hair+and+dressed+in+‘Moores+fashion’%2C+and+lived+very+much+as+an+{{Infobox+Person%0A|+name++++++++%3D+Job+Charnock%0A|+image+++++++%3D%0A|+birth_date++%3D+c.+1630%0A|+birth_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[London]]%2C+[[England]]%0A|+death_date+%3D+{{death-date|10+January+1693|[[10+January]]+[[1693]]}}+%0A|+death_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[Calcutta]]%2C+[[British+India|India]]+%0A|+other_names+%3D+%0A|+known_for+++%3D+Founding+[[Calcutta]]%0A|+occupation++%3D+Colonial+Administrator}}%0A%0A'''Job+Charnock'''+(c+1630-1693)+was+a+servant+and+administrator+of+the+[[British+East+India+Company|English+East+India+Company]]%2C+traditionally+regarded+as+the+founder+of+the+city+of+[[Calcutta]].<ref>Thankappan+Nair%2C+Job+Charnock%3A+The+Founder+of+Calcutta%2C+Calcutta%3A+Engineering+Press%2C+1977<%2Fref><ref>[http%3A%2F%2Fbanglapedia.org%2Fht%2FC_0140.HTM+Banglapedia+Article+on+Job+Charnock]<%2Fref><ref>[http%3A%2F%2Fwww.britannica.com%2Feb%2Farticle-9022634%2FJob-Charnock+Encyclopedia+Britannica+article+on+Charnock]<%2Fref>+%0A%0A%3D%3DEarly+life+and+career%3D%3D%0ACharnock+came+from+a+[[Lancashire]]+family+and+was+the+second+son+of+Richard+Charnock+(''d.''+c.+1665)%2C+of+[[London]].+The+[[Puritans|Puritan]]+[[preacher]]+[[Stephen+Charnock]]+(1628–1680)+was+probably+his+elder+brother.++He+went+out+to+[[British+India|India]]+on+a+private+trading+enterprise+in+the+employ+of+the+merchant+Maurice+Thomson%2C+some+time+between+1650+and+1653%2C+but+in+January+1658+he+joined+the+East+India+Company's+service+in+[[Bengal]]%2C+where+he+was+stationed+by+turn+at+[[Cossimbazar]]%2C+[[Hooghly+River|Hooghly]]+(a+[[Portugal|Portuguese]]+trading+settlement+on+the+river+of+the+same+name)%2C+and+[[Balasore]].+He+learnt+the+local+languages%2C+cut+his+hair+and+dressed+in+‘Moores+fashion’%2C+and+lived+very+much+as+an+{{Infobox+Person%0A|+name++++++++%3D+Job+Charnock%0A|+image+++++++%3D%0A|+birth_date++%3D+c.+1630%0A|+birth_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[London]]%2C+[[England]]%0A|+death_date+%3D+{{death-date|10+January+1693|[[10+January]]+[[1693]]}}+%0A|+death_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[Calcutta]]%2C+[[British+India|India]]+%0A|+other_names+%3D+%0A|+known_for+++%3D+Founding+[[Calcutta]]%0A|+occupation++%3D+Colonial+Administrator}}%0A%0A'''Job+Charnock'''+(c+1630-1693)+was+a+servant+and+administrator+of+the+[[British+East+India+Company|English+East+India+Company]]%2C+traditionally+regarded+as+the+founder+of+the+city+of+[[Calcutta]].<ref>Thankappan+Nair%2C+Job+Charnock%3A+The+Founder+of+Calcutta%2C+Calcutta%3A+Engineering+Press%2C+1977<%2Fref><ref>[http%3A%2F%2Fbanglapedia.org%2Fht%2FC_0140.HTM+Banglapedia+Article+on+Job+Charnock]<%2Fref><ref>[http%3A%2F%2Fwww.britannica.com%2Feb%2Farticle-9022634%2FJob-Charnock+Encyclopedia+Britannica+article+on+Charnock]<%2Fref>+%0A%0A%3D%3DEarly+life+and+career%3D%3D%0ACharnock+came+from+a+[[Lancashire]]+family+and+was+the+second+son+of+Richard+Charnock+(''d.''+c.+1665)%2C+of+[[London]].+The+[[Puritans|Puritan]]+[[preacher]]+[[Stephen+Charnock]]+(1628–1680)+was+probably+his+elder+brother.++He+went+out+to+[[British+India|India]]+on+a+private+trading+enterprise+in+the+employ+of+the+merchant+Maurice+Thomson%2C+some+time+between+1650+and+1653%2C+but+in+January+1658+he+joined+the+East+India+Company's+service+in+[[Bengal]]%2C+where+he+was+stationed+by+turn+at+[[Cossimbazar]]%2C+[[Hooghly+River|Hooghly]]+(a+[[Portugal|Portuguese]]+trading+settlement+on+the+river+of+the+same+name)%2C+and+[[Balasore]].+He+learnt+the+local+languages%2C+cut+his+hair+and+dressed+in+‘Moores+fashion’%2C+and+lived+very+much+as+an+{{Infobox+Person%0A|+name++++++++%3D+Job+Charnock%0A|+image+++++++%3D%0A|+birth_date++%3D+c.+1630%0A|+birth_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[London]]%2C+[[England]]%0A|+death_date+%3D+{{death-date|10+January+1693|[[10+January]]+[[1693]]}}+%0A|+death_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[Calcutta]]%2C+[[British+India|India]]+%0A|+other_names+%3D+%0A|+known_for+++%3D+Founding+[[Calcutta]]%0A|+occupation++%3D+Colonial+Administrator}}%0A%0A'''Job+Charnock'''+(c+1630-1693)+was+a+servant+and+administrator+of+the+[[British+East+India+Company|English+East+India+Company]]%2C+traditionally+regarded+as+the+founder+of+the+city+of+[[Calcutta]].<ref>Thankappan+Nair%2C+Job+Charnock%3A+The+Founder+of+Calcutta%2C+Calcutta%3A+Engineering+Press%2C+1977<%2Fref><ref>[http%3A%2F%2Fbanglapedia.org%2Fht%2FC_0140.HTM+Banglapedia+Article+on+Job+Charnock]<%2Fref><ref>[http%3A%2F%2Fwww.britannica.com%2Feb%2Farticle-9022634%2FJob-Charnock+Encyclopedia+Britannica+article+on+Charnock]<%2Fref>+%0A%0A%3D%3DEarly+life+and+career%3D%3D%0ACharnock+came+from+a+[[Lancashire]]+family+and+was+the+second+son+of+Richard+Charnock+(''d.''+c.+1665)%2C+of+[[London]].+The+[[Puritans|Puritan]]+[[preacher]]+[[Stephen+Charnock]]+(1628–1680)+was+probably+his+elder+brother.++He+went+out+to+[[British+India|India]]+on+a+private+trading+enterprise+in+the+employ+of+the+merchant+Maurice+Thomson%2C+some+time+between+1650+and+1653%2C+but+in+January+1658+he+joined+the+East+India+Company's+service+in+[[Bengal]]%2C+where+he+was+stationed+by+turn+at+[[Cossimbazar]]%2C+[[Hooghly+River|Hooghly]]+(a+[[Portugal|Portuguese]]+trading+settlement+on+the+river+of+the+same+name)%2C+and+[[Balasore]].+He+learnt+the+local+languages%2C+cut+his+hair+and+dressed+in+‘Moores+fashion’%2C+and+lived+very+much+as+an+{{Infobox+Person%0A|+name++++++++%3D+Job+Charnock%0A|+image+++++++%3D%0A|+birth_date++%3D+c.+1630%0A|+birth_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[London]]%2C+[[England]]%0A|+death_date+%3D+{{death-date|10+January+1693|[[10+January]]+[[1693]]}}+%0A|+death_place+%3D+[[Image%3AFlag+of+England.svg|25px]]+[[Calcutta]]%2C+[[British+India|India]]+%0A|+other_names+%3D+%0A|+known_for+++%3D+Founding+[[Calcutta]]%0A|+occupation++%3D+Colonial+Administrator}}%0A%0A'''Job+Charnock'''+(c+1630-1693)+was+a+servant+and+administrator+of+the+[[British+East+India+Company|English+East+India+Company]]%2C+traditionally+regarded+as+the+founder+of+the+city+of+[[Calcutta]].<ref>Thankappan+Nair%2C+Job+Charnock%3A+The+Founder+of+Calcutta%2C+Calcutta%3A+Engineering+Press%2C+1977<%2Fref><ref>[http%3A%2F%2Fbanglapedia.org%2Fht%2FC_0140.HTM+Banglapedia+Article+on+Job+Charnock]<%2Fref><ref>[http%3A%2F%2Fwww.britannica.com%2Feb%2Farticle-9022634%2FJob-Charnock+Encyclopedia+Britannica+article+on+Charnock]<%2Fref>+%0A%0A%3D%3DEarly+life+and+career%3D%3D%0ACharnock+came+from+a+[[Lancashire]]+family+and+was+the+second+son+of+Richard+Charnock+(''d.''+c.+1665)%2C+of+[[London]].+The+[[Puritans|Puritan]]+[[preacher]]+[[Stephen+Charnock]]+(1628–1680)+was+probably+his+elder+brother.++He+went+out+to+[[British+India|India]]+on+a+private+trading+enterprise+in+the+employ+of+the+merchant+Maurice+Thomson%2C+some+time+between+1650+and+1653%2C+but+in+January+1658+he+joined+the+East+India+Company's+service+in+[[Bengal]]%2C+where+he+was+stationed+by+turn+at+[[Cossimbazar]]%2C+[[Hooghly+River|Hooghly]]+(a+[[Portugal|Portuguese]]+trading+settlement+on+the+river+of+the+same+name)%2C+and+[[Balasore]].+He+learnt+the+local+languages%2C+cut+his+hair+and+dressed+in+‘Moores+fashion’%2C+and+lived+very+much+as+an+";
				//$url.="?action=parse&format=text&text=".$t;
				$post_data = array('action' => 'parse');
				$post_data = array('format' => 'txt');
				$post_data = array('text' => $t );
				$post_date = "action=edit";
				//echo $url;
				//$post_data['text'] = 
				curl_setopt($s,CURLOPT_URL,$url);
   				//curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
				//curl_setopt($s,CURLOPT_BINARYTRANSFER, true);
				curl_setopt($s,CURLOPT_POST,true);
				curl_setopt($s,CURLOPT_POSTFIELDS,$post_data); 
/*
				die ("post not implemented");
				curl_setopt($this->socket, CURLOPT_URL,$url);
				curl_setopt($this->socket, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($this->socket, CURLOPT_BINARYTRANSFER, true);
*/
				$content =  curl_exec($s);
				if($content == false ){
					echo curl_error();
				}else{
					echo $content;
				}
				//$array = unserialize($content);
				
				/*
				 curl_setopt($s,CURLOPT_FOLLOWLOCATION,$this->_followlocation);
				 curl_setopt($s,CURLOPT_COOKIEJAR,$this->_cookieFileLocation);
				 curl_setopt($s,CURLOPT_COOKIEFILE,$this->_cookieFileLocation); 
*/

			
			/*
				 curl_setopt($s,CURLOPT_HTTPHEADER,array('Except:'));
				 curl_setopt($s,CURLOPT_TIMEOUT,$this->_timeout);
				 curl_setopt($s,CURLOPT_MAXREDIRS,$this->_maxRedirects);
*/
			
			}
	
	}

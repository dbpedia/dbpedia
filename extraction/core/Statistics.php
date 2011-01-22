<?php

class Statistics{
	static public $countArr = array();	
	static public $extractorMeta = array();
	private static $articleQueue = 0;
	
	public static function setArticleQueue($count){
			self::$articleQueue = $count;
		}
	
	public static function addExtractorMetaArray($meta){
			$extractorID = $meta[EXTRACTORID];
			foreach ($meta as $key=>$value){
				@self::$extractorMeta[$extractorID][$key]=$value;
			}
	}
	
	public static function addExtractorMeta($extractorID, $key, $value){
			@self::$extractorMeta[$extractorID][$key]=$value;
		}
	
	
	static public function increaseCount($id, $what, $by=1){
			//the dirty way
			@self::$countArr[$id][$what]+=$by;
			@self::$countArr[$id]['hits']+=1;
		}
		
	static public function serializeToFile($array, $filename, $statisticdir){
			@mkdir($statisticdir);
			if(is_writable($statisticdir)){
				$fp = fopen($statisticdir.'/'.$filename , 'w');
				$ser = serialize($array);
				fwrite($fp, $ser);
				fclose($fp);
			}else{
				Logger::warn('Statistics.php:  dir not writable: '. $statisticdir.'/'.$filename);
			}
		
		}
		
	static public function statisticsToFile($statisticdir){
			self::serializeToFile(self::$countArr,'triples.ser',$statisticdir);
			self::serializeToFile(self::$extractorMeta,'extractorMeta.ser',$statisticdir);
		}	
		
	static public function printStats(){
			ksort(self::$countArr);
			$msg = "\n";
			foreach (self::$countArr as $key=>$value){
					$msg.=$key."\n";
					foreach ($value as $keyinner=>$valueinner){
						$msg.="\t".$keyinner.": ".$valueinner."\n";	
						}
				}
			Logger::info($msg);
		}

        public static function plot()
        {
            $gnuscript = Options::getOption("harvester_gnu_script");
           	if(Options::getOption("useGnuplot")){
		    	system("gnuplot $gnuscript");
			}
        }

	public static function getTotalTriples(){
		
			return self::$countArr[STAT_TOTAL][CREATEDTRIPLES];
		}
	public static function getTotalArticles(){
		
			return @self::$countArr[STAT_TOTAL][ARTICLE];
		}
	public static function getTotalCategories(){
		
			return @self::$countArr[STAT_TOTAL][CATEGORY];
		}
	public static function getTotalRedirects(){
		
			return @self::$countArr[STAT_TOTAL][REDIRECT];
		}
		
	public static function generateStatisticHTML($linkeddataresourceprefix, $language, $data){
                        self::plot();

                        $wikipedia_ns = 'http://'.$language.'.wikipedia.org/wiki/';
			//deserialize
			//comes from extractor.php
			$extractorMeta = $data["extractorMeta"];
			//comes from timer.php
			$time = $data["time"];
			//comes from timer.php
			$timeOverall =$data["timeOverall"];
			//comes from timer.php
			$processingTime =$data["processingTime"];
			//comes from statistics.php
			$triples = $data["triples"];
			//comes from extract_live.php
			$lastarticles = $data["lastarticles"];
			//time as String 
			$timeString = $data["timeString"];
			//$memory = file_get_contents($base."memory.txt");
			$generalString = $data["general"];

			$dateformat = 'l jS \of F Y h:i:s A';

			$out =  '<h3>DBpedia Live Extraction</h3>'."\n";
			//$out .=  '<b>This is a preliminary statistics page, nicer layout and better charts will follow soon</b>'."<br>\n";
			$s = $timeOverall['startingtime'];
			$l = $timeOverall['lasttime'];
			$overallSeconds = ($l-$s);
			$processingSeconds = $processingTime;
			

			$running_hours = round(($overallSeconds)/(60*60 ),2);

			$out .=  '<table border = "0">'."\n";
			$out .=   '<tr><td>';
			$out .=    'Started at: ';
			$out .=   '</td><td>';
			$out .=    date($dateformat, $s); 
			$out .=    '</td></tr><tr><td>';
			$out .=    'Still running at: ';
			$out .=   '</td><td>';
			$out .=    date($dateformat,($l));
			$out .=   '</td></tr><tr><td>';  
			$out .=    'Running for: ';
			$out .=   '</td><td>';
			$out .=    $running_hours.' hours'; 
			$out .=   '</td></tr><tr><td>';
			$out .=   'Articles in queues: ';
			$out .=   '</td><td>';
			$out .=   Statistics::$articleQueue;
			$out .=   '</td></tr></table><br>'."\n";

			$total = $triples['Total'];
			if(empty($total['article'])){$total['article'] = 0;	}
			if(empty($total['category'])){$total['category'] = 0;	}
			if(empty($total['redirect'])){$total['redirect'] = 0;	}
			if(empty($total['createdAnnotations'])){$total['createdAnnotations'] = 0;	}
			$totalPages = $total['article']+$total['category']+$total['redirect'];
			$ratio = round($total['createdAnnotations']/$total['created_Triples'],1);
			$out .=   'Processed '.$total['article'].' article pages, '.$total['category'].' Category pages and '.$total['redirect'].' redirects.<br>';
			$out .=   'Estimated total for all 6 processes : '.($totalPages * 6) . ' pages<br>';
			$out .=   'Extracted '.$total['created_Triples'].' triples and created '.$total['createdAnnotations'].' annotation triples (Ratio 1:'.$ratio.')<br>';
			$out .=   '<br>'."\n";

			
			$out .=   'Throughput under load (no idle time):<br>';
			$out .=   ''.round(($total['created_Triples']/$processingSeconds),2).' triples per second<br>';
			$out .=   ''.round(($totalPages/$processingSeconds),2).' pages per second  <br>';
			$out .=   ''.round(($processingSeconds/$totalPages),3).' seconds per page<br>'."\n";

			
			
            //$out .= '<img src="harvester_throughput.png" border=1 /> <br />'."\n";

			$out .=   '<h3>Last '.count($lastarticles).' Articles</h3>'."\n";
			$out .= '<table border="1">';
			foreach ($lastarticles as $one){
				$name = str_replace(DB_RESOURCE_NS,'',$one);
				$db = $linkeddataresourceprefix.$name;
				$wiki = $wikipedia_ns.$name;
				$out .=   '<tr><td><small>';
				$out .=   str_replace('_',' ',urldecode($name));
				$out .=   '</small></td><td><small>';
				$out .=   "<a href='$db' target='_blank'>DBpedia</a>";
				$out .=   '</small></td><td><small>';
				$out .=   "<a href='$wiki' target='_blank'>Wikipedia</a>";
				$out .=   '</small></td></tr>'."\n";
				}
			$out .=   "</table>";
			$out .=   '<h3>Details, mainly for debugging:</h3>'."\n";
			$out .=   'Throughput with idle time:<br>';
			$out .=   ''.round(($total['created_Triples']/$overallSeconds),2).' triples per second<br>';
			$out .=   ''.round(($totalPages/$overallSeconds),2).' pages per second<br>';
			$out .=   ''.round(($overallSeconds/$totalPages),3).' seconds per page<br><br>'."\n";
			
			$out .=   '<table border="1" id="time"  class="tablesorter" border="0" cellpadding="0" cellspacing="1">'."\n".'<thead><tr>'.
						self::th('Total').
						self::th('Percent').
						self::th('Hits').
						self::th('Average (msec) ').
						self::th('Label').
						'</tr></thead><tbody> '."\n".
						$timeString.'</tbody></table><br>';
			$out .=   '<textarea name="time" cols="200" rows="13" readonly>'.$generalString.'</textarea>';
			//$out .=   '<textarea name="time" cols="200" rows="4" readonly>'.$memory.'</textarea>';
			
			$head = '<head>
			<title>DBpedia Live Extraction</title>
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
			<link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
			<script type="text/javascript" src="jquery-1.3.2.min.js"></script> 
			<script type="text/javascript" src="jquery.tablesorter.min.js"></script> 
			<script type="text/javascript">
			$(function() {		
				$("#time").tablesorter({sortList:[[1,1]], widgets: [\'zebra\']});
				'.//$("#time").tablesorter({sortList:[[0,0],[2,1]], widgets: [\'zebra\']});
			'});	
			</script>
			</head>';
			$out = '<html>'.$head.'<body>'.$out.'</body></html>';
			
			return $out;
		}
		
	static function th($s){
			return '<th>'.$s.'</th>'."\n";
		}

}

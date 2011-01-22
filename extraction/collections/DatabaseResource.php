<?

abstract class DatabaseResource extends AbstractResource{
	
	
		protected $connection;
		
		public static function getDatabase($requestingClass, $type){
				if($type == 'mysql'){
					$this->log('debug', $requestingClass. ' requested mysql database ');
					return new MySQLDatabaseResource($requestingClass.'::'.$type);
				}
			
			}
		
		
		public abstract function query($query);
		
		
	
	}

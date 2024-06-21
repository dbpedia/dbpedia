<?php

class DatabaseWikipediaCollection implements PageCollection {
    
    private $mysqli;
    private $language;
    
    public function __construct($language) {
        require("databaseconfig.php"); // Assuming this file defines $dbconfig
        $this->mysqli = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['password'], $dbconfig['database']);
        $this->language = $language;
    }
    
    public function getLanguage() {
        return $this->language;
    }
    
    public function getSource($pageID) {
        $PageNamespace = 0;
        $PageTitle = $pageID;
        
        if (preg_match("/Category:(.*)/", $pageID, $match)) {
            $PageNamespace = 14;
            $PageTitle = str_replace("Category:", "", $PageTitle);
        }
        
        if (strpos($pageID, "Template:") === 0) {
            $PageNamespace = 10;
            $PageTitle = substr($pageID, 9);
        }
        
        $query = "SELECT old_text FROM text t INNER JOIN page p ON (p.page_latest = t.old_id) WHERE p.page_title = ? AND page_namespace = ?";
        
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("si", $PageTitle, $PageNamespace);
        $stmt->execute();
        $stmt->bind_result($old_text);
        
        $returnString = '';
        if ($stmt->fetch()) {
            $returnString = $old_text;
        }
        
        $stmt->close();
        
        return $returnString;
    }
}
?>

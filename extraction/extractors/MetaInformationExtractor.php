<?php

/**
 * Extracts the Pagelabel from a Wikipedia page, by using the last part of its URL
 * 
 */

class MetaInformationExtractor extends Extractor 
{

    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
        
        $meta = $this->additionalInfo;
		
		//generate dc modified
        $date = date('c');
        $datatype = XS_DATETIME;
        $o = RDFtriple::Literal($date, $datatype, "");
        $this->log('trace',  $o->toString());
		$result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(DC_MODIFIED, false),
				$o
                );
		
		
		//OAIIDENTIFIER
		$this->log(DEBUG,$meta['oaiId']);
		$result->addTriple(
                $this->getPageURI(),
                RDFtriple::URI(DBM_OAIIDENTIFIER,false),
				//turning off validation
				new RDFLiteral($meta['oaiId'], XS_INTEGER)
			//	new URI($meta['oaiidentifier'], false)
                );
		
		//REVISION
		$revisionURI = 'http://'.$meta['language'].'.wikipedia.org/w/index.php?title=';
		$revisionURI .= urlencode($pageID).'&oldid='.$meta['revision'];
		//http://en.wikipedia.org/w/index.php?title=Robotics&oldid=293678514
		$this->log(DEBUG, $revisionURI);
		$result->addTriple(
                $this->getPageURI(),
                RDFtriple::URI(DBM_REVISION,false),
				RDFtriple::URI($revisionURI)
                );
		
		$editLink = 'http://'.$meta['language'].'.wikipedia.org/w/index.php?title=';
		$editLink .= urlencode($pageID).'&action=edit';
		$this->log(DEBUG, $editLink);
		$result->addTriple(
                $this->getPageURI(),
                RDFtriple::URI(DBM_EDITLINK,false),
				RDFtriple::URI($editLink)
                );
        return $result;
    }
	
    
    
}



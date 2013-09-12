/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 *   KarshaAnnotate- Annotation tool for financial documents
 *  
 * 
 *      Date             Author          Changes 
 *      Sep 10, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.wikiquery;


import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class WikiQuery {
    
    public static void main(String[] args ) throws UnsupportedEncodingException {
        
        int pageId=83430;
        
        String urlParameters = "fName=" + URLEncoder.encode("???", "UTF-8") + "&lName=" + URLEncoder.encode("???", "UTF-8");
        //timestamp June 4th, 2013 00:00:00 UTC=20130604000000
       // String url="http://en.wikipedia.org/w/api.php?action=query&format=xml&prop=revisions&titles=Mother&rvlimit=max&rvstart=20130604000000";
   String url="http://en.wikipedia.org/w/api.php?action=query&format=xml&prop=revisions&pageids="+pageId+"&rvlimit=max&rvstart=20130604000000";
 
        //pageid
        // RevisionHistory.excutePost( url, urlParameters );
      //  ReadXMLFile.ReadFile( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\WikipediaCategoryProcessor\\api.xml");
    int totalRevisions= ReadXMLFile.ReadFile(RevisionHistory.excutePost( url, urlParameters ),urlParameters,url);
    System.out.println("totalRevisions "+ totalRevisions);
    }

}

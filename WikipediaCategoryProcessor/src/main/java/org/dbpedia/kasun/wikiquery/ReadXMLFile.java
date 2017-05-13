/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Sep 10, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.wikiquery;


/**
 * TODO- describe the purpose of the class
 *
 */
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.DocumentBuilder;
import org.w3c.dom.Document;
import org.w3c.dom.NodeList;
import org.w3c.dom.Node;
import org.w3c.dom.Element;
import java.io.File;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

public class ReadXMLFile
{

    public static void ReadFile( String filename )
    {
        //public static void ReadFile(File fXmlFile) {
        try
        {

            File fXmlFile = new File( filename );
            DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
            DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
            Document doc = dBuilder.parse( fXmlFile );

            //optional, but recommended
            //read this - http://stackoverflow.com/questions/13786607/normalization-in-dom-parsing-with-java-how-does-it-work
            doc.getDocumentElement().normalize();

            System.out.println( "Root element :" + doc.getDocumentElement().getNodeName() );

            NodeList nList = doc.getElementsByTagName( "rev" );

            System.out.println( "----------------------------" );

            for ( int temp = 0; temp < nList.getLength(); temp++ )
            {

                Node nNode = nList.item( temp );

                System.out.println( "Current Element :" + nNode.getNodeName() );

                if ( nNode.getNodeType() == Node.ELEMENT_NODE )
                {


                    Element eElement = (Element) nNode;

                    System.out.println( "Revision22222 id : " + eElement.getAttribute( "revid" ) );
//			System.out.println("First Name : " + eElement.getElementsByTagName("firstname").item(0).getTextContent());
//			System.out.println("Last Name : " + eElement.getElementsByTagName("lastname").item(0).getTextContent());
//			System.out.println("Nick Name : " + eElement.getElementsByTagName("nickname").item(0).getTextContent());
//			System.out.println("Salary : " + eElement.getElementsByTagName("salary").item(0).getTextContent());

                }
            }
        } catch ( Exception e )
        {
            e.printStackTrace();
        }
    }

    public static int ReadFile( Document doc ,String urlParameters, String url) throws UnsupportedEncodingException
    {

          int numberOfRevisions=0;  
        //public static void ReadFile(File fXmlFile) {
        try
        {
            doc.getDocumentElement().normalize();

          //  System.out.println( "Root element :" + doc.getDocumentElement().getNodeName() );

            NodeList continueNodeList = doc.getElementsByTagName( "revisions" );
            if ( continueNodeList.getLength() > 0 )
            {
                Node continueNode = continueNodeList.item( 0 );

                Element continueElement = (Element) continueNode;
              //  String urlParameters = "fName=" + URLEncoder.encode( "???", "UTF-8" ) + "&lName=" + URLEncoder.encode( "???", "UTF-8" );
       // String url = "http://en.wikipedia.org/w/api.php?action=query&format=xml&prop=revisions&titles=Mother&rvlimit=max&rvstart=20130604000000&rvcontinue="+continueElement.getAttribute( "rvcontinue" );
      
                //  System.out.println("Calling recursive function using rivision Id "+ continueElement.getAttribute( "rvcontinue" ));
                numberOfRevisions=ReadFile(RevisionHistory.excutePost( url+ "&rvcontinue="+continueElement.getAttribute( "rvcontinue" ), urlParameters ),urlParameters, url );
              
              //  System.out.println( "Continue revision Id : " + continueElement.getAttribute( "rvcontinue" ) );
            }

            NodeList nList = doc.getElementsByTagName( "rev" );

          //  System.out.println( "number of nodes" + nList.getLength());
/*
            for ( int temp = 0; temp < nList.getLength(); temp++ )
            {

                Node nNode = nList.item( temp );

             //   System.out.println( "\nCurrent Element :" + nNode.getNodeName() + " count: " + temp );

                if ( nNode.getNodeType() == Node.ELEMENT_NODE )
                {

                    Element eElement = (Element) nNode;

                    System.out.println( "Revision id : " + eElement.getAttribute( "revid" ) );

                }
            }
            */
            
            return numberOfRevisions+ nList.getLength();
        } catch ( Exception e )
        {
            e.printStackTrace();
            return 0;
            
        }
    }
}

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


import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import org.w3c.dom.Document;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

/**
 * TODO- describe the purpose of the class
 *
 */
public class RevisionHistory
{

   // public static String excutePost( String targetURL, String urlParameters )
         public static Document excutePost( String targetURL, String urlParameters )
    {
        URL url;
        HttpURLConnection connection = null;
        try
        {
            //Create connection
            url = new URL( targetURL );
            connection = (HttpURLConnection) url.openConnection();
            connection.setRequestMethod( "GET" );
            connection.setRequestProperty( "Accept", "application/xml" );

            //connection.setRequestProperty( "Content-Length", ""+ Integer.toString( urlParameters.getBytes().length ) );
           // connection.setRequestProperty( "Content-Language", "en-US" );

            connection.setUseCaches( false );
            connection.setDoInput( true );
            connection.setDoOutput( true );

            //Send request
            DataOutputStream wr = new DataOutputStream(
                connection.getOutputStream() );
            wr.writeBytes( urlParameters );
            wr.flush();
            wr.close();

            //Get Response	
            InputStream is = connection.getInputStream();
            
            DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
DocumentBuilder db = dbf.newDocumentBuilder();
Document doc = (Document) db.parse(is);


/*
            BufferedReader rd = new BufferedReader( new InputStreamReader( is ) );
            String line;

           
                // Create temp file.
                File temp = File.createTempFile( "pattern", ".xml" );

                // Delete temp file when program exits.
                temp.deleteOnExit();

                // Write to temp file
                BufferedWriter out = new BufferedWriter( new FileWriter( temp ) );
               
            

            StringBuffer response = new StringBuffer();
            while ( ( line = rd.readLine() ) != null )
            {
                 out.write( line + "\n" );
               
                System.out.println( line + "\n" );
                response.append( line + "\n" );
                //  response.append( '\r' );
            }
            rd.close();
             out.close();
             
             
             */
              return doc;
          //  return response.toString();

        } catch ( Exception e )
        {

            e.printStackTrace();
            return null;

        } finally
        {

            if ( connection != null )
            {
                connection.disconnect();
            }
        }
    }
}

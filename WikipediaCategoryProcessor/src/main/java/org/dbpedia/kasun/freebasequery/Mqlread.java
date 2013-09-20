/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 *      Date             Author          Changes 
 *      Sep 16, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.freebasequery;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
import com.google.api.client.http.GenericUrl;
import com.google.api.client.http.HttpRequest;
import com.google.api.client.http.HttpRequestFactory;
import com.google.api.client.http.HttpResponse;
import com.google.api.client.http.HttpTransport;
import com.google.api.client.http.javanet.NetHttpTransport;
import com.jayway.jsonpath.JsonPath;
import java.io.FileInputStream;
import java.util.Properties;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;

public class Mqlread {
  public static Properties properties = new Properties();
  public static void main(String[] args) {
   String curcer=curcerQuery("");
   while(curcer!="FALSE"){
       curcer=curcerQuery(curcer);
   }
  }
  
  private static String  curcerQuery(String curcer){
     
      String newCurcer = null;
       try {
     // properties.load(new FileInputStream("freebase.properties"));
      HttpTransport httpTransport = new NetHttpTransport();
      HttpRequestFactory requestFactory = httpTransport.createRequestFactory();
      JSONParser parser = new JSONParser();
      String query = "[{\"id\":null,\"name\":null,\"type\":\"/people/person\",\"limit\":100}]";
      GenericUrl url = new GenericUrl("https://www.googleapis.com/freebase/v1/mqlread");
      url.put("query", query);
     // url.put("key", properties.get("API_KEY"));
      url.put("key","AIzaSyDcHfGTZlVm0KE4KKK9JAM61KBDaXtPiJc");
      url.put("cursor", curcer);
      HttpRequest request = requestFactory.buildGetRequest(url);
      HttpResponse httpResponse = request.execute();
      JSONObject response = (JSONObject)parser.parse(httpResponse.parseAsString());
      JSONArray results = (JSONArray)response.get("result");
      newCurcer=(String)response.get("cursor");
      
      for (Object result : results) {
        System.out.println(JsonPath.read(result,"$.name").toString());
       // System.out.println( newCurcer);
      }
    } catch (Exception ex) {
      ex.printStackTrace();
    }
      
      return newCurcer;
  }
}

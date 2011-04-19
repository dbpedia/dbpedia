/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF;

import java.util.Calendar;
import java.util.Locale;

/**
 *
 * @author Paul
 */
public class Config
{

    private static final String passw = "gary.72.wbsg";
    private static final String user = "root";
    private static final String localDatabase = "jdbc:mysql://160.45.137.72/dbpedia_en";
    private static final String urlToLocalWikipedia = "http://localhost/wikipedia_local/";

    private static final int dumpYear = 2011;
    private static final int dumpMonth = 01;
    private static final int dumpDay = 17;
    private static final String projectPath = "C:/Users/Paul/Documents/Projects/Diplomarbeit/DBpediaQAF";
    private static final String dbpediaReleasePath = projectPath + "/DBpedia_release/mappingbased_properties_en.nt";
    private static final String dataPath = projectPath + "/articles/data/";
    private static final String mappingPath = projectPath + "/articles/mappings_10_03_15/";
    //private static final String mappingPath = projectPath + "/articles/mappings_11_01_17/";
    private static final String articleListPath = projectPath + "/articles/articles.xml";
    private static final String articleBufferPath = projectPath + "/articleBuffer.n3";
    private static final String relevantDBpediaTriplesPath = projectPath + "/relevant351Triples.n3";
    //private static final String relevantDBpediaTriplesPath = projectPath + "/relevant36Triples.n3";
    private static final Calendar cal = Calendar.getInstance();
    private static final String date = cal.get(1) + "-" + (cal.get(2) + 1) + "-" + cal.get(5) + "_" + cal.get(11) + "-" + cal.get(12);
    private static final String csvOutputPath = projectPath + "/results/result_" + date + ".csv";
    private static final String excelFileOutputPath = projectPath + "/results/evaluationResult.xls";
    private static final Boolean presentTriplesAreMarkedAsMapped = true;
    private static final Boolean printDeviations = false;
    private static final Boolean exportToExcel = false;
    private static final Boolean printResult = true;

    public static String getLocalDatabase()
    {
        return localDatabase;
    }

    public static String getPassw()
    {
        return passw;
    }

    public static String getUrlToLocalWikipedia()
    {
        return urlToLocalWikipedia;
    }

    public static String getUser()
    {
        return user;
    }


    public static Boolean isExportToExcel()
    {
        return exportToExcel;
    }

    public static Boolean isPrintResult()
    {
        return printResult;
    }

    public static Boolean isPrintDeviations()
    {
        return printDeviations;
    }

    public static Boolean isPresentTriplesAreMarkedAsMapped()
    {
        return presentTriplesAreMarkedAsMapped;
    }

    public static String getExcelFileOutputPath()
    {
        return excelFileOutputPath;
    }

    public static String getCsvOutputPath()
    {
        return csvOutputPath;
    }

    public static String getRelevantDBpediaTriplesPath()
    {
        return relevantDBpediaTriplesPath;
    }

    public static String getArticleBufferPath()
    {
        return articleBufferPath;
    }

    public static String getArticleListPath()
    {
        return articleListPath;
    }

    public static String getDataPath()
    {
        return dataPath;
    }

    public static String getDbpediaReleasePath()
    {
        return dbpediaReleasePath;
    }

    public static String getProjectPath()
    {
        return projectPath;
    }

    public static String getMappingPath()
    {
        return mappingPath;
    }

    public static int getDumpDay()
    {
        return dumpDay;
    }

    public static int getDumpMonth()
    {
        return dumpMonth;
    }

    public static int getDumpYear()
    {
        return dumpYear;
    }
}

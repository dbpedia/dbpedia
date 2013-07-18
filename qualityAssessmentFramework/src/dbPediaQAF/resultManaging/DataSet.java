/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.*;
import com.hp.hpl.jena.util.FileManager;
import dbPediaQAF.Config;
import dbPediaQAF.Evaluator;
import dbPediaQAF.xmlQuery.*;
import java.io.*;
import java.net.URLEncoder;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 *
 * @author Paul
 */
public class DataSet
{

    private Model goldModel;
    private HashMap mapStatementToPatternCategory;
    private HashMap mapStatementToMarkupSnippet;
    private HashMap mapStatementToInfobox;
    private HashMap mapSnippetToSnippetsGoldModel;
    private HashMap mapStatementHasMapping;
    private List<Resource> listOfIntermediateNodeSubjects;
    private Map<String, List<String>> mapInfoboxToListOfItsMappedProperties;

    public boolean hasMapping(Statement stmt)
    {
        try
        {
            if (mapStatementHasMapping.containsKey(stmt.hashCode()))
            {
                if (mapStatementHasMapping.get(stmt.hashCode()).equals(true))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                throw new IndexOutOfBoundsException("Statement is not in map.");
            }
        }
        catch (IndexOutOfBoundsException ex)
        {
            System.out.println("Error Msg: " + ex.getMessage());
            Evaluator.printStatement(stmt, null);
            System.exit(1);
            return false;
        }
    }

    public boolean isIntermediate(Statement stmt)
    {
        Resource subject = stmt.getSubject();
        if (listOfIntermediateNodeSubjects.contains(subject))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public List<Resource> getListOfIntermediateNodeSubjects()
    {
        return listOfIntermediateNodeSubjects;
    }

    public Map<String, List<String>> getMapInfoboxToListOfItsMappedProperties()
    {
        return mapInfoboxToListOfItsMappedProperties;
    }

    public HashMap getMapSnippetToGoldModel()
    {
        return mapSnippetToSnippetsGoldModel;
    }

    public HashMap getMapStatementHasMapping()
    {
        return mapStatementHasMapping;
    }

    public HashMap getMapStatementToInfobox()
    {
        return mapStatementToInfobox;
    }

    public HashMap getMapStatementToMarkupSnippet()
    {
        return mapStatementToMarkupSnippet;
    }

    public HashMap getMapStatementToPatternCategory()
    {
        return mapStatementToPatternCategory;
    }

    public Model getGoldModel()
    {
        return goldModel;
    }

    public DataSet()
    {
        goldModel = ModelFactory.createDefaultModel();
        mapStatementToPatternCategory = new HashMap();
        mapStatementToMarkupSnippet = new HashMap();
        mapStatementToInfobox = new HashMap();
        mapStatementHasMapping = new HashMap();
        mapSnippetToSnippetsGoldModel = new HashMap();
        mapInfoboxToListOfItsMappedProperties = new HashMap<String, List<String>>();
        listOfIntermediateNodeSubjects = new LinkedList<Resource>();

    }

    public void loadTestdata()
    {

        File articleListfile = new File(Config.getArticleListPath());
        ArticleItemCol root = null;
        try
        {
            root = ArticleItemCol.load(articleListfile);
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
        List<ArticleItem> articleItemList = root.getArticleItem();
        for (ArticleItem articleItem : articleItemList)
        {
            if (articleItem.isDone())
            {
                String template = articleItem.getTemplate();
                File file = null;
                try
                {
                    file = new File(Config.getDataPath() + URLEncoder.encode(articleItem.getUri(), "UTF-8") + ".xml");
                }
                catch (UnsupportedEncodingException ex)
                {
                    System.out.println("Msg: " + ex.getMessage());
                }
                Article article = null;
                try
                {
                    article = Article.load(file);
                }
                catch (IOException ex)
                {
                    System.out.println("Msg: " + ex.getMessage());
                }
                BufferedWriter buffer;

                try
                {
                    List<Snippet> snippetList = article.getSnippet();
                    for (Snippet snippet : snippetList)
                    {
                        Model modelSnippet = ModelFactory.createDefaultModel();
                        buffer = new BufferedWriter(new FileWriter(Config.getArticleBufferPath()));
                        String triples = snippet.getTriple();
                        if (triples != null && triples.length() != 0)
                        {
                            buffer.write(triples);
                        }
                        else
                        {
//                    System.out.println("null triples in " + article.getName());
//                    System.out.println("null triples in " + snippet.getSource());
//                    System.exit(1);
                        }
                        buffer.close();

                        InputStream fileInputStreamArticle = FileManager.get().open(Config.getArticleBufferPath());
                        if (fileInputStreamArticle == null)
                        {
                            throw new IllegalArgumentException("File: " + fileInputStreamArticle + " not found");
                        }
                        //System.out.println("Try to create model from snippet in article: " + article.getName());
                        modelSnippet.read(fileInputStreamArticle, null, "N-TRIPLE");
                        goldModel.add(modelSnippet);
                        mapSnippetToSnippetsGoldModel.put(snippet, modelSnippet);

                        for (StmtIterator snippetStmtItr = modelSnippet.listStatements(); snippetStmtItr.hasNext();)
                        {
                            Statement snippetStmt = snippetStmtItr.nextStatement();
                            //System.out.println("snippetStmt: "+ snippetStmt.toString());
                            //System.out.println("pattern category: " + snippet.getPatternClass());
                            mapStatementToPatternCategory.put(snippetStmt.hashCode(), snippet.getPatternClass());
//                            if (snippet.getPatternClass().equals("Coordinates")) {
//                                printStatement(snippetStmt, "COORD:");
//                            }
                            mapStatementToMarkupSnippet.put(snippetStmt.hashCode(), snippet.getSource());
                            mapStatementToInfobox.put(snippetStmt.hashCode(), template.toLowerCase());

                            // create list of intermediateNodes
                            RDFNode object = snippetStmt.getObject();
                            if (object.isResource())
                            {
                                // TODO: DONE! intermediate node check is to simple. See Astro_Boy and Astro_Boy__Anime. Also check for predicate!
                                String dbPediaNameSpace = "http://dbpedia.org/resource/";
                                String subjectName = snippetStmt.getSubject().getURI().replace(dbPediaNameSpace, "");
                                String predicateName = snippetStmt.getPredicate().getURI().replace("http://dbpedia.org/ontology/", "");
                                String objectName = object.asResource().getURI().replace(dbPediaNameSpace, "");
                                // get intermediate node resources
                                if (objectName.contains(subjectName + "__" + predicateName))
                                {
                                    listOfIntermediateNodeSubjects.add(object.asResource());
                                    //printStatement(snippetStmt, "INTERM. NODE:");
                                }
                            }
                        }
                        fileInputStreamArticle.close();
                    }
                }
                catch (Exception ex)
                {
                    System.out.println("Msg: " + ex.getMessage());
                    System.out.println("Article: " + article.getName());
                    ex.printStackTrace();
                    System.exit(1);
                }
                // TODO load mappings
                //System.out.println("LOAD MAPPINGS");
                // TODO use multi map instead
                List<String> mappings = new LinkedList<String>();

//                if (template.equalsIgnoreCase("Infobox_company"))
//                {
//                    System.out.println("");
//                }
                //String mappingSourceCode = getmapInfoboxToListOfItsMappedPropertiesourceCode(template, 0);


                // HERE ADD GET XML FILE STUFF
                String mappingSourceCode = null;
                File mappingFile = new File(Config.getMappingPath() + template.toLowerCase() + ".xml");
                try
                {
                    BufferedReader in = new BufferedReader(new FileReader(mappingFile));
                    String line;
                    while ((line = in.readLine()) != null)
                    {
                        mappingSourceCode = mappingSourceCode + line;
                    }
                }
                catch (Exception exReader)
                {
                    mappingSourceCode = null;
                    System.out.println("Msg: " + exReader.getMessage());
                    //exReader.printStackTrace();
                    //System.exit(1);
                }
                if (mappingSourceCode != null)
                {
                    String regex = "\\|\\s*(templateProperty|templateProperty1|templateProperty2|"
                            + "templateProperty3|templateProperty4|templateProperty5|templateProperty6|"
                            + "templateProperty7|templateProperty8|latitudeDirection|latitudeDegrees|"
                            + "latitudeMinutes|latitudeSeconds|longitudeDirection|longitudeDegrees|"
                            + "longitudeMinutes|longitudeSeconds|coordinates|latitude|longitude|"
                            + "startDateOntologyProperty|endDateOntologyProperty)\\s*=\\s*"
                            + "([^\\||\\}]+)";
                    Pattern p = Pattern.compile(regex);
                    Matcher m = p.matcher(mappingSourceCode);
                    while (m.find())
                    {
                        if (!mappings.contains(m.group(2).trim()))
                        {
                            mappings.add(m.group(2).trim().toLowerCase());
                        }
                        //System.out.println(m.group(2));
                    }

                    if (!mapInfoboxToListOfItsMappedProperties.containsKey(template.toLowerCase()))
                    {
                        mapInfoboxToListOfItsMappedProperties.put(template.toLowerCase(), mappings);
                    }
                    else
                    {
                        mapInfoboxToListOfItsMappedProperties.get(template.toLowerCase()).addAll(mappings);
                    }
                }
            }

        }

        for (StmtIterator iterator = goldModel.listStatements(); iterator.hasNext();)
        {
            Statement statement = iterator.nextStatement();
            try
            {
                if (insertHasMapping(statement))
                {
                    mapStatementHasMapping.put(statement.hashCode(), true);
                }
                else
                {
                    mapStatementHasMapping.put(statement.hashCode(), false);
                }
            }
            catch (Exception ex)
            {
                System.out.println("Msg: " + ex.getMessage());
            }
        }
    }

    public boolean insertHasMapping(Statement stmt) throws Exception
    {
        boolean propertyMappingMissing = false;

        if (mapStatementToMarkupSnippet.containsKey(stmt.hashCode()))
        {
            if (mapStatementToInfobox.containsKey(stmt.hashCode()))
            {
                String infobox = mapStatementToInfobox.get(stmt.hashCode()).toString();
                String wikiCodeSnippet = mapStatementToMarkupSnippet.get(stmt.hashCode()).toString();
//                if (wikiCode.equals("|name=Chinese"))
//                {
//                    System.out.print("");
//                }
//                if (infobox.equalsIgnoreCase("infobox_company"))
//                {
//                    System.out.println("");
//                }
                /**
                 * Delete all templates and comments used in the infobox property values,
                 * because they could contain '='-signs.
                 */
                String regex1 = "\\{\\{.*\\}\\}";
                String regex2 = "<[^/]*/[^>]*>";
                Pattern p1 = Pattern.compile(regex1, Pattern.CASE_INSENSITIVE | Pattern.DOTALL | Pattern.MULTILINE);
                Pattern p2 = Pattern.compile(regex2, Pattern.CASE_INSENSITIVE | Pattern.DOTALL | Pattern.MULTILINE);
                Matcher m1 = p1.matcher(wikiCodeSnippet);
                String wikiCode2 = m1.replaceAll("");
                Matcher m2 = p2.matcher(wikiCode2);
                String cleanWikiCode = m2.replaceAll("");

                // loop all infobox properties in this snippet
                for (String line : cleanWikiCode.split("\n"))
                {
                    if (!line.trim().isEmpty())
                    {
                        // Only the property keys are relevant for finding a mapping
                        Pattern p = Pattern.compile("[^=]+=");
                        Matcher m = p.matcher(line);
                        if (m.find())
                        {
                            //clean up
                            String match = line.substring(m.start(), m.end() - 1);
                            String prop = match.trim();
                            if (prop.startsWith("|"))
                            {
                                prop = prop.replace("|", "").trim().toLowerCase();
                                // mapping exist?
                                if (mapInfoboxToListOfItsMappedProperties.containsKey(infobox))
                                {
//                                    if (infobox.equalsIgnoreCase("infobox_company"))
//                                    {
//                                        System.out.println(mapInfoboxToListOfItsMappedProperties.get(infobox).toString());
//                                    }
                                    if (!mapInfoboxToListOfItsMappedProperties.get(infobox).contains(prop))
                                    {
                                        propertyMappingMissing = true;
                                        //System.out.print("mapping for infobox property (" + prop + ") is missing.");
                                    }
                                }
                                else
                                {
                                    return false;
                                }
                            }
                        }
                    }
                }
                if (!propertyMappingMissing)
                {
                    //printStatement(stmt, "FOUND: " + infobox);
                    //mapStatementHasMapping.put(stmt.hashCode(), true);
                    return true;
                }
                else
                {
                    //printStatement(stmt, "MISSNIG: " + infobox);
                    //mapStatementHasMapping.put(stmt.hashCode(), false);
                    return false;
                }
            }
        }
        else
        {
            throw new Exception("Could not find Statement in the hashmap: " + mapStatementToMarkupSnippet.getClass().getName());
        }
        return false;
    }
}

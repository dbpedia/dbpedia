/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF;

import dbPediaQAF.xmlQuery.*;
import com.hp.hpl.jena.rdf.model.*;
import com.hp.hpl.jena.util.FileManager;
import java.io.*;
import java.net.URL;
import java.net.URLEncoder;
import java.util.*;
import org.w3c.dom.*;
import org.w3c.tidy.Tidy;
import org.glowacki.CalendarParser;

/**
 *
 * @author Paul
 */
public class Preprocessor
{

    private static ArticleItemCol root;
    private static Calendar date;

    /**
     * Creates xml files with mapping source code for all infoboxes used in the sample.
     * Is needed only once after changing the date of the requierd mappings.
     */
    public static void createMappingFiles()
    {
        date = Calendar.getInstance();
        date.set(Config.getDumpYear(), Config.getDumpMonth(), Config.getDumpDay());
        File articleListfile = new File(Config.getArticleListPath());
        try
        {
            root = ArticleItemCol.load(articleListfile);
            List<ArticleItem> articleItemList = root.getArticleItem();
            for (ArticleItem articleItem : articleItemList)
            {
                String template = articleItem.getTemplate();
                if (template.equalsIgnoreCase("Infobox_language"))
                {
                    System.out.println();
                }
                String versionLink = getMappingVersionLink(template, date);
                if (versionLink != null)
                {
                    String wikiCode = "<?xml version=\"1.0\"?>" + getInfoboxMappingSourceCode(versionLink, template, 0);
                    String mappingFile = Config.getMappingPath() + template.toLowerCase() + ".xml";
                    try
                    {
                        BufferedWriter out = new BufferedWriter(new FileWriter(mappingFile));
                        out.write(wikiCode);
                        out.close();
                    }
                    catch (IOException exWriter)
                    {
                        System.out.println("Msg: " + exWriter.getMessage());
                    }
                }
            }
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
    }

    /**
     * Gets the source code of an infobox mapping.
     * @param mappingUrl
     * @param infobox
     * @param counter internal config for recursive procedure.
     * @return
     */
    private static String getInfoboxMappingSourceCode(String mappingUrl, String infobox, int counter)
    {
        String sourceCode = "";
        if (counter < 3)
        {
            try
            {
                URL url = new URL(mappingUrl.toString() + "&action=edit");
                BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream()));
                String inputLine;
                Boolean isRel = false;
                Boolean textareaFound = false;
                while ((inputLine = in.readLine()) != null)
                {
                    if (inputLine.contains("<textarea"))
                    {
                        isRel = true;
                        inputLine = inputLine.substring(inputLine.indexOf("<textarea"));
                    }
                    if (isRel)
                    {
                        textareaFound = true;
                        if (inputLine.contains("</textarea>"))
                        {
                            int startIndex = inputLine.indexOf("</textarea>");
                            int endIndex = inputLine.indexOf("</textarea>") + 11;
                            inputLine = inputLine.substring(startIndex, endIndex);
                            sourceCode = sourceCode + inputLine + "\n";
                            isRel = false;
                        }
                        else
                        {
                            sourceCode = sourceCode + inputLine + "\n";
                        }
                    }
                }
                in.close();
                if (!textareaFound)
                {
                    switch (counter)
                    {
                        // make first char upper: Infobox_language
                        case 0:
                            infobox.toLowerCase();
                            String firstLetter = infobox.substring(0, 1).toUpperCase();
                            String rest = infobox.substring(1);
                            infobox = firstLetter + rest;
                            sourceCode = getInfoboxMappingSourceCode(mappingUrl, infobox, 1);
                            break;
                        case 1:
                            // make first char upper and first infobox name char upper: Infobox_Language
                            if (infobox.length() > 9)
                            {
                                String firstPart = infobox.substring(0, 8);
                                String firstLetterOfInfoboxName = infobox.substring(8, 9).toUpperCase();
                                String rest2 = infobox.substring(9);
                                infobox = firstPart + firstLetterOfInfoboxName + rest2;
                                sourceCode = getInfoboxMappingSourceCode(mappingUrl, infobox, 2);
                                break;
                            }
                        default:
                            break;
                    }
                }
            }
            catch (IOException ex)
            {
                System.out.println("Msg: " + ex.getMessage());
                ex.printStackTrace();
                System.exit(1);
            }
        }
        return sourceCode;
    }

    /**
     * Gets the link to a infobox mapping version from the mappings.dbpedia.org wiki
     * depending on the date param.
     *
     * @param infoboxName
     * @param date
     * @return link to mapping
     */
    private static String getMappingVersionLink(String infoboxName, Calendar date)
    {
        String requestUrl = "http://mappings.dbpedia.org/index.php?title=Mapping:" + infoboxName + "&action=history";
        //String requestUrl = "http://en.wikipedia.org/w/index.php?title=" + infoboxName + "&action=history";
        String sourceCode = "";
        String link = null;
        Calendar versionDate = Calendar.getInstance();
        //System.out.println(requestUrl);
        try
        {
            URL url = new URL(requestUrl.toString());
            InputStream is = url.openStream();

            BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream()));
            String inputLine;

            while ((inputLine = in.readLine()) != null)
            {
                sourceCode = sourceCode + inputLine + "\n";
            }
            //System.out.println(sourceCode);

            // Tidy
            Tidy tidy = new Tidy();
            Document doc = tidy.parseDOM(is, null);
            NodeList bodyNodeList = doc.getElementsByTagName("ul");
            for (int i = 0; i < bodyNodeList.getLength(); i++)
            {
                Node childNode = bodyNodeList.item(i);
                if (childNode.hasAttributes())
                {
                    if (childNode.getAttributes().item(0).getNodeValue().equals("pagehistory"))
                    {
//                        System.out.println("<" + childNode.getNodeName()
//                                + " " + childNode.getAttributes().item(0).getNodeName()
//                                + "=" + childNode.getAttributes().item(0).getNodeValue() + ">");
                        NodeList childNodeList = childNode.getChildNodes();
                        cnl:
                        for (int j = 0; j < childNodeList.getLength(); j++)
                        {
                            Node childChildNode = childNodeList.item(j);
                            NodeList childChildNodeList = childChildNode.getChildNodes();

                            ccnl:
                            for (int k = 0; k < childChildNodeList.getLength(); k++)
                            {
                                Node childChildChildNode = childChildNodeList.item(k);
                                if (childChildChildNode.getNodeName().equals("a"))
                                {
//                                    System.out.println("<" + childChildChildNode.getNodeName()
//                                            + " " + childChildChildNode.getAttributes().item(0).getNodeName()
//                                            + "=" + childChildChildNode.getAttributes().item(0).getNodeValue()
//                                            + " " + childChildChildNode.getAttributes().item(1).getNodeName()
//                                            + "=" + childChildChildNode.getAttributes().item(1).getNodeValue()
//                                            + ">" + childChildChildNode.getFirstChild().getNodeValue()
//                                            + "</" + childChildChildNode.getNodeName() + ">");
                                    String[] dateAr = childChildChildNode.getFirstChild().getNodeValue().split(" ");
                                    //System.out.println("0:" + dateAr[0]);
                                    //System.out.println("1:" + dateAr[1]);
                                    //System.out.println("2:" + dateAr[2]);
                                    //System.out.println("3:" + dateAr[3]);
                                    if (!dateAr[0].equals("prev") && !dateAr[0].equals("cur"))
                                    {
                                        int year = Integer.parseInt(dateAr[3]);
                                        int month = CalendarParser.monthNameToNumber(dateAr[2]);
                                        int day = Integer.parseInt(dateAr[1]);

                                        versionDate.set(year, month, day);
                                        if (versionDate.before(date))
                                        {
                                            link = "http://mappings.dbpedia.org" + childChildChildNode.getAttributes().item(1).getNodeValue();
                                            break cnl;
                                        }
                                    }
                                }
                            }
                            //System.out.println();
                        }
                    }
                }
            }
            //System.out.println("LINK: " + link);
            //System.out.println("versionDate: " + versionDate.get(1) + "-" + versionDate.get(2) + "-" + versionDate.get(5));
            //System.out.println("Date: " + date.get(1) + "-" + date.get(2) + "-" + date.get(5));
        }
        catch (Exception ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            ex.printStackTrace();
            System.exit(1);
        }
        return link;
    }

    /**
     * Deletes all triples from the extraction result file (Config.getDbpediaReleasePath), which do not arise
     * from a sample Wikipedia article and creates a new file with the relevantTriples (Config.getRelevantDBpediaTriplesPath).
     * Only needed if one want to compare the whole DBpedia ontology-based extraction result with the gold standard.
     * I needed it for the DBpedia 3.5.1 extraction result.
     */
    public static void createRelevantTriplesFileFromDBpediaRelease()
    {
        String articleUri = null;
        BufferedWriter buffer;

        // get the list of all articles in the testdataset (articleItemList)
        File articleListfile = new File(Config.getArticleListPath());
        try
        {
            root = ArticleItemCol.load(articleListfile);
            buffer = new BufferedWriter(new FileWriter(Config.getRelevantDBpediaTriplesPath()));
            List<ArticleItem> articleItemList = root.getArticleItem();
            // check for a relevat URI in every triple in the DBpedia release

            FileInputStream fstream;
            String inputLine;
            int x = 0;
            // 11135755
            for (ArticleItem articleItem : articleItemList)
            {
                x++;
                boolean finder = false;
                fstream = new FileInputStream(Config.getDbpediaReleasePath());
                BufferedReader in = new BufferedReader(new InputStreamReader(fstream));
                // replace %3A to :
                articleUri = URLEncoder.encode(articleItem.getUri(), "UTF-8").replace("%3A", ":");
                if (articleUri == null)
                {
                    throw new NullPointerException("articleUri is null");
                }
                System.out.println("#" + x + ": " + articleUri);
                System.out.println("searching ... ");
                while ((inputLine = in.readLine()) != null)
                {
                    //if (inputLine.contains("<http://dbpedia.org/resource/" + articleUri + ">")) {
                    // search only for the uri, otherwise blank/intermediate nodes are removed
                    if (inputLine.contains(articleUri))
                    {
                        buffer.write(inputLine);
                        buffer.newLine();
                        finder = true;
                    }
                }
                if (!finder)
                {
                    System.out.println("not found!");
                }
                else
                {
                    System.out.println("done");
                }
                System.out.println();
            }
            System.out.println(x);
            buffer.close();

        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            System.exit(1);
        }
        cleanUpRelevantTriples();
    }

    /**
     * Delete additional created triples from the relevantTriplesFile.
     * For example the additional gYear triple during the birthDate extraction.
     */
    private static void cleanUpRelevantTriples()
    {
        Model actualDBpediaModel = ModelFactory.createDefaultModel();
        Model statementsToDeleteModel = ModelFactory.createDefaultModel();

        // read the file stream into a model
        InputStream fileInputStreamModel = FileManager.get().open(Config.getRelevantDBpediaTriplesPath());
        if (fileInputStreamModel == null)
        {
            throw new IllegalArgumentException("File: " + fileInputStreamModel + " not found");
        }
        actualDBpediaModel.read(fileInputStreamModel, null, "N-TRIPLE");
        try
        {
            fileInputStreamModel.close();
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            System.exit(1);
        }

        // find and mark label props to delete
        Property label = actualDBpediaModel.getProperty("http://www.w3.org/2000/01/rdf-schema#label");
        Selector labelSelector = new SimpleSelector(null, label, (Object) null);
        for (StmtIterator labelIterator = actualDBpediaModel.listStatements(labelSelector); labelIterator.hasNext();)
        {
            Statement labelStatement = labelIterator.nextStatement();
            Evaluator.printStatement(labelStatement, null);
            statementsToDeleteModel.add(labelStatement);
        }

        // find and mark type props to delete
        Property type = actualDBpediaModel.getProperty("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
        Selector typeSelector = new SimpleSelector(null, type, (Object) null);
        for (StmtIterator typeIterator = actualDBpediaModel.listStatements(typeSelector); typeIterator.hasNext();)
        {
            Statement typeStatement = typeIterator.nextStatement();
            Evaluator.printStatement(typeStatement, null);
            statementsToDeleteModel.add(typeStatement);
        }

        // find and mark additional gYears to delete
        Property deathDate = actualDBpediaModel.getProperty("http://dbpedia.org/ontology/deathDate");
        Property deathYear = actualDBpediaModel.getProperty("http://dbpedia.org/ontology/deathYear");
        Selector selector = new SimpleSelector(null, deathYear, (Object) null);
        for (StmtIterator deathYearIterator = actualDBpediaModel.listStatements(selector); deathYearIterator.hasNext();)
        {
            Statement deathYearStatement = deathYearIterator.nextStatement();
            if (actualDBpediaModel.contains(deathYearStatement.getSubject(), deathDate))
            {
                Evaluator.printStatement(actualDBpediaModel.getProperty(deathYearStatement.getSubject(), deathDate), null);
                Evaluator.printStatement(deathYearStatement, null);
                statementsToDeleteModel.add(deathYearStatement);
            }
        }
        Property birthDate = actualDBpediaModel.getProperty("http://dbpedia.org/ontology/birthDate");
        Property birthYear = actualDBpediaModel.getProperty("http://dbpedia.org/ontology/birthYear");
        Selector birthYearSelector = new SimpleSelector(null, birthYear, (Object) null);
        for (StmtIterator birthYearIterator = actualDBpediaModel.listStatements(birthYearSelector); birthYearIterator.hasNext();)
        {
            Statement birthYearStatement = birthYearIterator.nextStatement();
            if (actualDBpediaModel.contains(birthYearStatement.getSubject(), birthDate))
            {
                Evaluator.printStatement(actualDBpediaModel.getProperty(birthYearStatement.getSubject(), birthDate), null);
                Evaluator.printStatement(birthYearStatement, null);
                statementsToDeleteModel.add(birthYearStatement);
            }
        }

        //remove marked statements from the model and save it to file
        actualDBpediaModel.remove(statementsToDeleteModel);
        if (actualDBpediaModel.containsAny(statementsToDeleteModel))
        {
            System.out.println("There are still statements to delete in the model.");
        }
        BufferedWriter writeBuffer;
        try
        {
            writeBuffer = new BufferedWriter(new FileWriter(Config.getRelevantDBpediaTriplesPath()));
            actualDBpediaModel.write(writeBuffer, "N-TRIPLE");
            writeBuffer.close();
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            System.exit(1);
        }
    }

    /**
     *
     * @param article
     * @return Jena Model
     */
    private static Model articleToModel(Article article)
    {
        BufferedWriter buffer;
        Model modelArticle = ModelFactory.createDefaultModel();
        try
        {
            buffer = new BufferedWriter(new FileWriter(Config.getArticleBufferPath()));
            List<Snippet> snippetList = article.getSnippet();
            for (Snippet snippet : snippetList)
            {
                String triples = snippet.getTriple();
                if (triples != null && triples.length() != 0)
                {
                    buffer.write(triples);
                    buffer.newLine();
                }
                else
                {
//                    System.out.println("null triples in " + article.getName());
//                    System.out.println("null triples in " + snippet.getSource());
//                    System.exit(1);
                }
            }
            buffer.close();
            InputStream fileInputStreamArticle = FileManager.get().open(Config.getArticleBufferPath());
            if (fileInputStreamArticle == null)
            {
                throw new IllegalArgumentException("File: " + fileInputStreamArticle + " not found");
            }
            //System.out.println("Try to create model from triples: " + article.getName());
            modelArticle.read(fileInputStreamArticle, null, "N-TRIPLE");
            fileInputStreamArticle.close();
        }
        catch (Exception ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            System.out.println("Article: " + article.getName());
            System.exit(1);
        }
        return modelArticle;
    }

    /**
     * Extracts a N-Triples file from a local wikipedia instance for all articles in the sample.
     * This method takes the local DBpedia framework for the extraction, therefore it is possible to extract with different
     * extraction framework verions.
     * I needed it for comparing the 3.5.1 result with the 3.6 result.
     */
    public static void createRelevantTriplesFileFromLocalWikiInstance()
    {
        System.out.println("Extraction started");
        File articleListfile = new File(Config.getArticleListPath());
        //BufferedWriter buffer = new BufferedWriter(new FileWriter(Config.getProjectPath() + "/newExtraction.n3"));
        root = null;
        BufferedWriter out = null;
        try
        {
            root = ArticleItemCol.load(articleListfile);
            out = new BufferedWriter(new FileWriter(Config.getProjectPath() + "/relevant36Triples.n3"));
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
        String extractionResult = "";

        List<ArticleItem> articleItemList = root.getArticleItem();
        for (ArticleItem articleItem : articleItemList)
        {
            System.out.println(articleItem.getUri());
            if (articleItem.isDone())
            {
                extractionResult = extractFromLocal(articleItem.getUri());
                //buffer.write(extractionResult);
                try
                {
                    System.out.println(extractionResult);
                    System.out.println();
                    out.write(extractionResult);
                }
                catch (IOException exWriter)
                {
                    System.out.println("Msg: " + exWriter.getMessage());
                }
            }
        }
        try
        {
            out.close();
            //buffer.close();
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
    }

    /**
     * Returns n-triples from http://localhost:9999/extraction/en/extract?title=" + article + "&format=N-Triples
     * @param article
     * @return
     */
    private static String extractFromLocal(String article)
    {
        //"http://mappings.dbpedia.org/server/extraction/en/extract?title=" + article + "&format=N-Triples"
        //"http://mappings.dbpedia.org/server/extraction/extract?title=" + article + "&format=N-Triples"
        // TODO Done: get extraction from local mediawiki.
        String requestUrl = "http://localhost:9999/extraction/en/extract?title=" + article + "&format=N-Triples";
        String buffer = "";
        try
        {
            URL url = new URL(requestUrl.toString());
            BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream()));
            String inputLine;
            while ((inputLine = in.readLine()) != null)
            {
                buffer = buffer + inputLine + "\n";
            }
            in.close();
        }
        catch (Exception e)
        {
            System.out.println("Msg: " + e.getMessage());
        }
        return buffer;
    }
}

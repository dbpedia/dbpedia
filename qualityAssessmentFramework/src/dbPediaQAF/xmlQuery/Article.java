/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package dbPediaQAF.xmlQuery;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.LinkedList;
import java.util.List;
import javax.xml.bind.JAXBContext;
import javax.xml.bind.JAXBException;
import javax.xml.bind.Marshaller;
import javax.xml.bind.Unmarshaller;
import javax.xml.bind.annotation.XmlRootElement;

/**
 *
 * @author Paul
 */
@XmlRootElement
public class Article {

    private String name;
    private List<Snippet> snippet = new LinkedList<Snippet>();

    /**
     * Get the value of name
     *
     * @return the value of name
     */
    public String getName() {
        return name;
    }

    /**
     * Set the value of name
     *
     * @param name new value of name
     */
    public void setName(String name) {
        this.name = name;
    }

    /**
     * Get the value of snippets
     *
     * @return the value of snippets
     */
    public List<Snippet> getSnippet() {
        return snippet;
    }
    

    /**
     * Set the value of snippets
     *
     * @param snippet new value of snippets
     */
    public void setSnippet(List<Snippet> snippet) {
        this.snippet = snippet;
    }

    /**
     * Get the value of snippets at specified index
     *
     * @param index
     * @return the value of snippets at specified index
     */
    public Snippet getSnippet(int index) {
        return this.snippet.get(index);
    }

    /**
     * Set the value of snippets at specified index.
     *
     * @param index
     * @param newSnippet new value of snippets at specified index
     */
    public void setSnippet(int index, Snippet newSnippet) {
        this.snippet.set(index, newSnippet);
    }

    public void addSnippet(Snippet snippet) {
        this.snippet.add(snippet);
    }


    public void save(File file) throws IOException {
        FileOutputStream outputStream = null;

        try {
            outputStream = new FileOutputStream(file);

            Marshaller m = JAXBContext.newInstance(Article.class, Snippet.class).createMarshaller();

            m.marshal(this, outputStream);
        }
        catch(JAXBException ex) {
            throw new IOException("Serialization error", ex);
        }
        finally {
            if(outputStream != null)
                outputStream.close();
        }

    }

    public static Article load(File file) throws IOException {
        FileInputStream inputStream = null;

        try {
            inputStream = new FileInputStream(file);

            Unmarshaller m = JAXBContext.newInstance(Article.class, Snippet.class).createUnmarshaller();

            return (Article) m.unmarshal(inputStream);
        }
        catch(JAXBException ex) {
            throw new IOException("Serialization error", ex);
        }
        finally {
            if(inputStream != null)
                inputStream.close();
        }
    }
}

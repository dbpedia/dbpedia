package dbPediaQAF.xmlQuery;

import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;
import java.util.logging.Level;
import java.util.logging.Logger;

public class ArticleItem {

    protected String template;

    /**
     * Get the value of template
     *
     * @return the value of template
     */
    public String getTemplate() {
        return template;
    }

    /**
     * Set the value of template
     *
     * @param template new value of template
     */
    public void setTemplate(String template) {
        this.template = template;
    }
    protected int id;

    /**
     * Get the value of id
     *
     * @return the value of id
     */
    public int getId() {
        return id;
    }

    /**
     * Set the value of id
     *
     * @param id new value of id
     */
    public void setId(int id) {
        this.id = id;
    }
    protected boolean done;
    private String uri;

    /**
     * Get the value of done
     *
     * @return the value of done
     */
    public boolean isDone() {
        return done;
    }

    /**
     * Set the value of done
     *
     * @param done new value of done
     */
    public void setDone(boolean done) {
        this.done = done;
    }

    /**
     * Get the value of uri
     *
     * @return the value of uri
     */
    public String getUri() {
        if (uri != null) {
            try {
                return URLDecoder.decode(uri, "UTF-8");
            } catch (UnsupportedEncodingException ex) {
                Logger.getLogger(ArticleItem.class.getName()).log(Level.SEVERE, null, ex);
                return null;
            }
        }
        return null;
    }

    /**
     * Set the value of uri
     *
     * @param uri new value of uri
     */
    public void setUri(String uri) {
        this.uri = uri;
    }

}

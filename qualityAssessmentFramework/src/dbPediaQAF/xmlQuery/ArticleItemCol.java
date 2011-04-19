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

@XmlRootElement
public class ArticleItemCol {

    private List<ArticleItem> articleItem = new LinkedList<ArticleItem>();

    public boolean isSet() {
        if (articleItem.isEmpty()) {
            return false;
        }
        else {
            return true;
        }
    }

    public void setArticleItem(List<ArticleItem> articleItem) {
        this.articleItem = articleItem;
    }

    public List<ArticleItem> getArticleItem() {
        return articleItem;
    }

    public List<ArticleItem> getDoneArticleItems() {
        List<ArticleItem> doneArticleItems = new LinkedList<ArticleItem>();
        for (ArticleItem item : this.articleItem) {
            if (item.isDone()) {
                doneArticleItems.add(item);
            }
        }
        return doneArticleItems;
    }

    public ArticleItem getArticleItem(int index) {
        return this.articleItem.get(index);
    }

    public ArticleItem getArticleItem(String uri) {
        for (ArticleItem item : this.articleItem) {
            if (item.getUri().equals(uri)) {
                return item;
            }
        }
        return new ArticleItem();
    }

    public ArticleItem getLastUndoneArticleItem() {
        for (ArticleItem item : this.articleItem) {
            if (!item.isDone()) {
                return item;
            }
        }
        return new ArticleItem();
    }

    public void setArticleItem(int index, ArticleItem newArticle) {
        this.articleItem.set(index, newArticle);
    }

    public void addArticleItem(ArticleItem articleItem) {
        this.articleItem.add(articleItem);
    }

    public void save(File file) throws IOException {
        FileOutputStream outputStream = null;

        try {
            outputStream = new FileOutputStream(file);

            Marshaller m = JAXBContext.newInstance(ArticleItemCol.class, ArticleItem.class).createMarshaller();

            m.marshal(this, outputStream);
        } catch (JAXBException ex) {
            throw new IOException("Serialization error", ex);
        } finally {
            if (outputStream != null) {
                outputStream.close();
            }
        }

    }

    public static ArticleItemCol load(File file) throws IOException {
        FileInputStream inputStream = null;

        try {
            inputStream = new FileInputStream(file);

            Unmarshaller m = JAXBContext.newInstance(ArticleItemCol.class, ArticleItem.class).createUnmarshaller();

            return (ArticleItemCol) m.unmarshal(inputStream);
        } catch (JAXBException ex) {
            ex.printStackTrace();
            throw new IOException("Serialization error", ex);
        } finally {
            if (inputStream != null) {
                inputStream.close();
            }
        }
    }
}

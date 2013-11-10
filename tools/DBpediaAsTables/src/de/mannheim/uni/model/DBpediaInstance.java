package de.mannheim.uni.model;

import java.io.Serializable;
import java.util.ArrayList;
import java.util.List;

public class DBpediaInstance implements Serializable {

	private String uri;

	private List<DBpediaInstanceProperty> properties;

	public String getUri() {
		return uri;
	}

	public void setUri(String uri) {
		this.uri = uri;
	}

	public List<DBpediaInstanceProperty> getProperties() {
		return properties;
	}

	public void setProperties(List<DBpediaInstanceProperty> properties) {
		this.properties = properties;
	}

	public DBpediaInstance() {
		properties = new ArrayList<DBpediaInstanceProperty>();
	}

	public DBpediaInstanceProperty gerPropertyByURI(String propUri) {
		for (DBpediaInstanceProperty prop : properties) {
			if (prop.getUri().equals(propUri))
				return prop;
		}
		DBpediaInstanceProperty prop = new DBpediaInstanceProperty();
		prop.setUri(propUri);
		properties.add(prop);

		return prop;
	}

	public boolean existProperty(String propUri) {
		for (DBpediaInstanceProperty prop : properties) {
			if (prop.getUri().equals(propUri))
				return true;
		}
		return false;
	}
}

package de.mannheim.uni.model;

import java.io.Serializable;
import java.util.ArrayList;
import java.util.LinkedList;
import java.util.List;

public class DBpediaInstanceProperty implements Serializable{

	private String uri;

	private List<String> types;

	private String finalRange;

	private List<String> values;

	private boolean isFinalRange;

	public String getUri() {
		return uri;
	}

	public void setUri(String uri) {
		this.uri = uri;
	}

	public List<String> getTypes() {
		return types;
	}

	public void setTypes(List<String> types) {
		this.types = types;
	}

	public String getFinalRange() {
		return finalRange;
	}

	public void setFinalRange(String finalRange) {
		this.finalRange = finalRange;
	}

	public List<String> getValues() {
		return values;
	}

	public void setValues(List<String> values) {
		this.values = values;
	}

	public DBpediaInstanceProperty() {
		types = new LinkedList<String>();
		values = new ArrayList<String>();
		isFinalRange = false;
	}

	public boolean isFinalRange() {
		return isFinalRange;
	}

	public void setFinalRange(boolean isFinalRange) {
		this.isFinalRange = isFinalRange;
	}
}

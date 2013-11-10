package de.mannheim.uni.model;

import java.io.Serializable;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import com.hp.hpl.jena.rdf.model.Literal;

public class DBpediaProperty implements Serializable {
	public static final String DOUBLE_SCHEMA_TYPE = "http://www.w3.org/2001/XMLSchema#double";
	public static final String DATE_SCHEMA_TYPE = "http://www.w3.org/2001/XMLSchema#date";
	public static final String STRING_SCHEMA_TYPE = "http://www.w3.org/2001/XMLSchema#string";
	public static final String UNKNOWN_URI_SCHEMA_TYPE = "http://www.w3.org/2002/07/owl#Thing";

	private String uri;

	private Map<String, Integer> range;

	private String finalRange;

	private String finalRangeLabel;

	private String label;

	private boolean isObjectProperty;

	public void setObjectProperty(boolean isObjectProperty) {
		this.isObjectProperty = isObjectProperty;
	}

	public boolean isObjectProperty() {
		return isObjectProperty;
	}

	public String getFinalRangeLabel() {
		return finalRangeLabel;
	}

	public void setFinalRangeLabel(String finalRangeLabel) {
		this.finalRangeLabel = finalRangeLabel;
	}

	public String getFinalRange() {
		return finalRange;
	}

	public void setFinalRange(String finalRange) {
		this.finalRange = finalRange;
	}

	public String getUri() {
		return uri;
	}

	public void setUri(String uri) {
		this.uri = uri;
	}

	public String getLabel() {
		return label;
	}

	public void setLabel(String label) {
		this.label = label;
	}

	public Map<String, Integer> getRange() {
		return range;
	}

	public void setRange(Map<String, Integer> range) {
		this.range = range;
	}

	public DBpediaProperty(String uri, Map<String, Integer> range, String label) {
		super();
		this.uri = uri;
		this.range = range;
		this.label = label;
	}

	public DBpediaProperty() {
		// TODO Auto-generated constructor stub
		range = new HashMap<String, Integer>();
		finalRange = "";
		isObjectProperty = false;
	}

	public static DBpediaProperty gerPropertyByURI(String propUri,
			List<DBpediaProperty> properties) {
		for (DBpediaProperty prop : properties) {
			if (prop.getUri().equals(propUri))
				return prop;
		}
		return null;
	}

	public static String guessAttributeType(Literal literal) {
		if (literal.getDatatypeURI() != null
				&& !literal.getDatatypeURI().equals(""))
			return literal.getDatatypeURI();

		// an even rougher guess
		try {
			Double.parseDouble(literal.getString());
			System.out.println("rough numeric");
			return DOUBLE_SCHEMA_TYPE;
		} catch (NumberFormatException e) {
		}
		try {
			SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd");
			sdf.parse(literal.toString());
			System.out.println("rough date");
			return DATE_SCHEMA_TYPE;
		} catch (ParseException e) {
		}
		return STRING_SCHEMA_TYPE;
	}

}

/*
 * DumperWindow.java
 *
 * Created on January 6, 2006, 2:57 PM
 *
 * Implementation on top of the NetBeans-generated code in DumperWindowForm.
 * I hate editing generated code!
 */

package org.mediawiki.dumper.gui;

import java.awt.Component;
import java.awt.FileDialog;
import java.io.File;
import java.io.IOException;
import java.sql.SQLException;
import javax.swing.JFileChooser;
import javax.swing.JComboBox;
import javax.swing.SwingUtilities;
import javax.swing.event.DocumentEvent;
import javax.swing.event.DocumentListener;
import org.mediawiki.importer.DumpWriter;

/**
 *
 * @author brion
 */
public class DumperWindow extends DumperWindowForm {
	protected DumperGui backend;
	private int dbtype = DumperGui.DBTYPE_MYSQL;
	
	/** Creates a new instance of DumperWindow */
	public DumperWindow(DumperGui aBackend) {
		super();
		backend = aBackend;
		
		// For some reason the Netbeans GUI editor doesn't offer these events
		dbnameText.getDocument().addDocumentListener(new DocumentListener() {
			public void changedUpdate(DocumentEvent e) {
				backend.setDbname(dbnameText.getText());
			}
			public void insertUpdate(DocumentEvent e) {
				backend.setDbname(dbnameText.getText());
			}
			public void removeUpdate(DocumentEvent e) {
				backend.setDbname(dbnameText.getText());
			}
		});
		
		prefixText.getDocument().addDocumentListener(new DocumentListener() {
			public void changedUpdate(DocumentEvent e) {
				backend.setPrefix(prefixText.getText());
			}
			public void insertUpdate(DocumentEvent e) {
				backend.setPrefix(prefixText.getText());
			}
			public void removeUpdate(DocumentEvent e) {
				backend.setPrefix(prefixText.getText());
			}
		});
		
		showFields();
	}
	
	public DumpWriter getProgressWriter(DumpWriter sink, int interval) {
		return new GraphicalProgressFilter(sink, interval, progressLabel);
	}
	
	/**
	 * Update all the fields' enabled flags and button names.
	 */
	public void showFields() {
		showBrowseFields();
		showDatabaseFields();
		showSchemaFields();
		showImportFields();
	}
	
	void showBrowseFields() {
		enableFields(new Component[] { fileText, browseButton },
				!backend.running);
	}
	
	void showDatabaseFields() {
		enableFields(new Component[] {
				serverLabel,
				serverText,
				portLabel,
				portText,
				userLabel,
				userText,
				passwordLabel,
				passwordText,
				dbTypeButton},
			!backend.running && !backend.connected);
		connectButton.setEnabled(!backend.running);
		connectButton.setText(backend.connected ? "Disconnect" : "Connect");
	}
	
	void showSchemaFields() {
		enableFields(new Component[] {
				schemaLabel,
				schema14Radio,
				schema15Radio,
				prefixLabel,
				prefixText,
				dbnameLabel,
				dbnameText},
			!backend.running && backend.connected);
	}
	
	void showImportFields() {
		startButton.setEnabled(backend.connected && backend.schemaReady);
		startButton.setText(backend.running ? "Cancel" : "Start import");
	}
	
	void enableFields(Component[] widgets, boolean val) {
		for (int i = 0; i < widgets.length; i++) {
			widgets[i].setEnabled(val);
		}
	}
	
	/**
	 * Set the progress bar text asynchronously, eg from a background thread
	 */
	public void setProgress(String text) {
		final String _text = text;
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				progressLabel.setText(_text);
			}
		});
	}
	
	public void setDatabaseStatus(String text) {
		dbStatusLabel.setText(text);
	}
	
	public void setSchemaStatus(String text) {
		schemaStatusLabel.setText(text);
	}
	
	/* -- event handlers -- */
	
	protected void onBrowseButtonActionPerformed(java.awt.event.ActionEvent evt) {
		File selection = chooseFile("Select dump file");
		if (selection != null) {
			try {
				fileText.setText(selection.getCanonicalPath());
			} catch (IOException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		}
	}
	protected void onConnectButtonActionPerformed(java.awt.event.ActionEvent evt) {
		if (backend.connected)
			backend.disconnect();
		else
			backend.connect(dbtype, serverText.getText(),
				portText.getText(),
				userText.getText(),
				passwordText.getText());
	}
	
	protected void onDbTypeButtonActionPerformed(java.awt.event.ActionEvent evt) {
		String dbt = (String)((JComboBox)evt.getSource()).getSelectedItem();
		if (dbt.equals("MySQL")) {
			portText.setText("3306");
			dbtype = DumperGui.DBTYPE_MYSQL;
		} else if (dbt.equals("PostgreSQL")) {
			portText.setText("5432");
			dbtype = DumperGui.DBTYPE_PGSQL;
		}
	}

	protected void onStartButtonActionPerformed(java.awt.event.ActionEvent evt) {
		if (backend.running) {
			backend.abort();
		} else {
			try {
				backend.startImport(fileText.getText());
			} catch (IOException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			} catch (SQLException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		}
	}

	protected void onQuitItemActionPerformed(java.awt.event.ActionEvent evt) {
		System.exit(0);
	}

	protected void onDbnameTextActionPerformed(java.awt.event.ActionEvent evt) {
		// This gets called if you hit enter in the field while the import
		// button is still disabled. Check the db again...
		backend.setDbname(dbnameText.getText());
	}
	
	protected void onSchema14RadioActionPerformed(java.awt.event.ActionEvent evt) {
		backend.setSchema("1.4");
	}

	protected void onSchema15RadioActionPerformed(java.awt.event.ActionEvent evt) {
		backend.setSchema("1.5");
	}
	
	/* ---- more random crap ---- */
	
	File chooseFile(String message) {
		String os = System.getProperty("os.name");
		boolean swingSucks = (os.equals("Mac OS X") || os.startsWith("Win"));
		if (swingSucks)
			return chooseFileAwt(message);
		else
			return chooseFileSwing(message);
	}
	
	/*
	 * Note: I'm using the AWT FileDialog for Mac OS X and Windows because
	 * JFileChooser is a piece of total crap that doesn't make any attempt
	 * to fit in with platform UI standards. On the Mac it doesn't even
	 * show mounted volumes properly!
	 */
	File chooseFileAwt(String message) {
		FileDialog chooser = new FileDialog(this, message);
		chooser.setVisible(true);
		String filename = chooser.getFile();
		if (filename == null) {
			return null;
		} else {
			return new File(chooser.getDirectory(), filename);
		}
	}
	
	/**
	 * Sadly, the AWT file chooser is some crappy Motif thing on Unix.
	 * The Swing file chooser actually is less hideous there.
	 */
	File chooseFileSwing(String message) {
		JFileChooser chooser = new JFileChooser();
		chooser.setDialogTitle(message);
		chooser.showOpenDialog(this);
		return chooser.getSelectedFile();
	}

}

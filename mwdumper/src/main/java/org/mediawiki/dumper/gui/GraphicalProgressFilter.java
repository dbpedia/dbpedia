package org.mediawiki.dumper.gui;

import javax.swing.JLabel;
import javax.swing.SwingUtilities;

import org.mediawiki.dumper.ProgressFilter;
import org.mediawiki.importer.DumpWriter;

public class GraphicalProgressFilter extends ProgressFilter {
	final JLabel target;
	
	public GraphicalProgressFilter(DumpWriter sink, int interval, JLabel target) {
		super(sink, interval);
		this.target = target;
	}
	
	protected void sendOutput(String text) {
		super.sendOutput(text);
		final String _text = text;
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				target.setText(_text);
			}
		});
	}

}

package org.hackyourlife.webpage;

import java.util.Vector;

public class Webpage {
	public static String DOCTYPE = "<!DOCTYPE html>";

	private Vector<String> sections;

	private String stylesheet = null;
	private String title = null;
	private String footer = null;

	public Webpage(String title) {
		this.title = title;
		this.sections = new Vector<String>();
	}

	public void setStylesheet(String stylesheet) {
		this.stylesheet = stylesheet;
	}

	public void setFooter(String footer) {
		this.footer = footer;
	}

	public void addSection(String section) {
		sections.addElement(section);
	}

	public String toString() {
		StringBuilder s = new StringBuilder();
		s.append(DOCTYPE);
		s.append("<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>");
		s.append("<title>");
		s.append(title);
		s.append("</title>");
		if(stylesheet != null) {
			s.append("<link type=\"text/css\" rel=\"stylesheet\" href=\"");
			s.append(stylesheet);
			s.append("\">");
		}
		s.append("</head><body>");
		for(String section : sections) {
			s.append(section);
		}
		if(footer != null) {
			s.append(footer);
		}
		s.append("</body></html>");
		return s.toString();
	}
}

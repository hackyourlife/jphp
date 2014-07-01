package org.hackyourlife.syntax;
import java.util.Vector;
import java.io.BufferedReader;
import java.io.Reader;
import java.io.Writer;
import java.io.IOException;
public abstract class Highlighter {
	private Vector<String> keywords;
	private String delimiters;

	public static String DEFAULT_CSS = "pre{font-family:Consolas,\"Courier New\",\"Lucida Console\",monospace;}"
		+ ".keyword{color:#00f;}.class{color:#f00;}.comment{color:#008080;}.string{color:#808080;}"
		+ ".number{color:#f00;}.operator{color:#ff8000;}";

	public Highlighter(String delimiters, String[] keywords) {
		this.delimiters = delimiters;
		this.keywords = new Vector<String>();
		for(int i = 0; i < keywords.length; i++) {
			this.keywords.addElement(keywords[i]);
		}
	}

	public boolean isDelimiter(char c) {
		if((c == '\r') || (c == '\n')) {
			return true;
		} else {
			return delimiters.indexOf(c) != -1;
		}
	}

	public static String htmlspecialchars(char c) {
		return htmlspecialchars(Character.toString(c));
	}

	public static String htmlspecialchars(String s) {
		return s
			.replace("&", "&amp;")
			.replace("<", "&lt;")
			.replace(">", "&gt;");
	}

	public boolean isKeyword(String keyword) {
		return keywords.contains(keyword);
	}

	public boolean isInsideString(String line, int position) {
		if(line.indexOf("\"") == -1) {
			return false;
		}
		boolean inString = false;
		for(int i = 0; i < position; i++) {
			char ch = line.charAt(i);
			if((i > 0) && (ch == '"') && (line.charAt(i - 1) == '\\')) {
				continue;
			} else if(ch == '"') {
				inString = !inString;
			}
		}
		return inString;
	}

	public static boolean isNumeric(char c) {
		if((c < '0') || (c > '9')) {
			return false;
		} else {
			return true;
		}
	}

	public static boolean isNumeric(String line) {
		int length = line.length();
		for(int i = 0; i < length; i++) {
			char c = line.charAt(i);
			if((c < '0') || (c > '9')) {
				return false;
			}
		}
		return true;
	}

	public abstract String formatLine(String line);

	public void format(Reader in, Writer out) throws IOException {
		BufferedReader linein = new BufferedReader(in);
		String line = null;
		while((line = linein.readLine()) != null) {
			out.write(formatLine(line));
		}
	}
}

package org.hackyourlife.syntax;
import java.util.Vector;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.BufferedReader;
import java.io.IOException;
public class JavaHighlighter extends Highlighter {
	private Vector<String> classes;
	private boolean inComment = false;

	public static String[] keywords = {
		"abstract", "boolean", "break", "byte", "case",
		"catch", "char", "class", "continue", "default",
		"do", "double", "else", "extends", "false",
		"final", "finally", "float", "for", "if",
		"implements", "import", "instanceof", "int",
		"interface", "length", "long", "native", "new",
		"null", "package", "private", "protected", "public",
		"return", "short", "static", "super", "switch",
		"synchronized", "this", "threadsafe", "throw", "throws",
		"transient", "true", "try", "void", "while" };

	public static String operators = "~!%^&*-+=|/:<>?";

	public JavaHighlighter() throws IOException {
		this(null);
	}

	public JavaHighlighter(String classfile) throws IOException {
		super("~!@%^&*()-+=|\\/{}[]:;\"\'<> ,	.?", keywords);
		classes = new Vector<String>();
		if(classfile != null) {
			BufferedReader in = new BufferedReader(new FileReader(classfile));
			String line;
			while((line = in.readLine()) != null) {
				String[] tokens = line.split(" ");
				for(int i = 0; i < tokens.length; i++) {
					classes.addElement(tokens[i]);
				}
			}
			in.close();
		}
	}

	public boolean isClass(String name) {
		return classes.contains(name);
	}

	public static boolean isOperator(char c) {
		return operators.indexOf(c) != -1;
	}

	public String formatLine(String line) {
		StringBuffer formatted = new StringBuffer();
		int i = 0;
		int startAt = 0;
		char ch;
		StringBuffer temp;
		String tmp;
		boolean inString = false;
		boolean inCharacter = false;

		int length = line.length();
		while(i < length) {
			temp = new StringBuffer();
			ch = line.charAt(i);
			startAt = i;
			while((i < length) && !isDelimiter(ch)) {
				temp.append(ch);
				i++;
				if(i < length) {
					ch = line.charAt(i);
				}
			}

			tmp = temp.toString();
			if(tmp.length() == 0) {
				// nothing
			} else if(isKeyword(tmp) && !inString && !inCharacter && !inComment) {
				formatted.append("<span class=\"keyword\">" + htmlspecialchars(tmp) + "</span>");
			} else if(isClass(tmp) && !inString && !inCharacter && !inComment) {
				formatted.append("<span class=\"class\">" + htmlspecialchars(tmp) + "</span>");
			} else if(isNumeric(tmp) && !inString && !inCharacter && !inComment) {
				formatted.append("<span class=\"number\">" + tmp + "</span>");
			} else {
				formatted.append(htmlspecialchars(tmp));
			}
			i++; // because the last character read in the while-loop is not part of tmp

			boolean do_append = true;

			if((i < length) && (ch == '/') && (line.charAt(i) == '/') && !inString && !inCharacter && !inComment) {
				formatted.append("<span class=\"comment\">" + ch + line.substring(i) + "</span>");
				break;
			} else if(!inComment && !inCharacter && (ch == '"')) {
				do_append = false;
				if(i > 1) {
					if(line.charAt(i - 2) == '\\') {
						if((i > 2) && (line.charAt(i - 3) == '\\')) {
							do_append = false;
						} else {
							do_append = true;
						}
					}
				}
				if(!do_append) {
					if(!inString) {
						formatted.append("<span class=\"string\">" + htmlspecialchars(ch));
					} else {
						formatted.append(htmlspecialchars(ch) + "</span>");
					}
					inString = !inString;
				}
			} else if(!inComment && !inString && (ch == '\'')) {
				do_append = false;
				if(i > 1) {
					if(line.charAt(i - 2) == '\\') {
						if((i > 2) && (line.charAt(i - 3) == '\\')) {
							do_append = false;
						} else {
							do_append = true;
						}
					}
				}
				if(!do_append) {
					if(!inCharacter) {
						formatted.append("<span class=\"string\">" + htmlspecialchars(ch));
					} else {
						formatted.append(htmlspecialchars(ch) + "</span>");
					}
					inCharacter = !inCharacter;
				}
			} else if(!inString && !inCharacter && (i < length) && (ch == '/') && (line.charAt(i) == '*')) {
				do_append = false;
				formatted.append("<span class=\"comment\">" + htmlspecialchars(ch));
				inComment = true;
			} else if(!inString && !inCharacter && (i < length) && (ch == '*') && (line.charAt(i) == '/')) {
				do_append = false;
				formatted.append(htmlspecialchars(Character.toString(ch) + Character.toString(line.charAt(i))) + "</span>");
				inComment = false;
				i++;
			}

			// append last character (not contained in tmp) if it was not
			// processed elsewhere
			if(do_append && ((startAt + tmp.length()) < length)) {
				if(isOperator(ch) && !inString && !inComment && !inCharacter) {
					formatted.append("<span class=\"operator\">" + htmlspecialchars(ch) + "</span>");
				} else {
					formatted.append(htmlspecialchars(ch));
				}
			}
		}

		formatted.append("\n");
		return formatted.toString();
	}
}

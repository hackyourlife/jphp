package org.hackyourlife.server;

import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.Servlet;
import javax.servlet.ServletRequest;
import javax.servlet.ServletResponse;
import javax.servlet.ServletException;
import javax.servlet.ServletOutputStream;

import java.io.IOException;
import java.io.FileInputStream;
import java.io.File;

import java.util.Hashtable;

public class Server {
	private static Hashtable<String,HttpServlet> servlets;
	private static Hashtable<String,String> servletMappings;
	private static Hashtable<String,String> mimetypes;
	private static String[] welcomepages;
	private static String version = "PHP-Java Server 1.0";

	static {
		servlets = new Hashtable<String,HttpServlet>();
		servletMappings = new Hashtable<String,String>();
		welcomepages = new String[0];

		mimetypes = new Hashtable<String,String>();
		mimetypes.put("java",	"text/x-java-source");
		mimetypes.put("c",	"text/x-c");
		mimetypes.put("h",	"text/x-h");
		mimetypes.put("php",	"text/plain");
		mimetypes.put("txt",	"text/plain");
		mimetypes.put("css",	"text/css");
		mimetypes.put("html",	"text/html");
		mimetypes.put("js",	"text/javascript");
		mimetypes.put("png",	"image/png");
	}

	public static void registerServlet(String name, HttpServlet servlet) {
		servlets.put(name, servlet);
	}

	public static void mapServlet(String url, String name) {
		servletMappings.put(url, name);
	}

	public static void setWelcomePages(String[] names) {
		welcomepages = names;
	}

	private static String getError(int status, String title, String description, String type, String exception, String note) {
		StringBuffer message = new StringBuffer();
		message.append("<html><head><title>" + version + " - Error report</title><style>"
			+ "<!--H1 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:22px;}"
			+ "H2 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:16px;}"
			+ "H3 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:14px;}"
			+ "BODY {font-family:Tahoma,Arial,sans-serif;color:black;background-color:white;}"
			+ "B {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;}"
			+ "P {font-family:Tahoma,Arial,sans-serif;background:white;color:black;font-size:12px;}"
			+ "A {color : black;}A.name {color : black;}HR {color : #525D76;}--></style></head><body>"
			+ "<h1>HTTP Status " + status + " - " + title + "</h1><HR size=\"1\" noshade=\"noshade\">");
		if(type != null) {
			message.append("<p><b>type</b> " + type + "</p>");
		}
		message.append("<p><b>message</b> <u>" + title + "</u></p><p>"
			+ "<b>description</b> <u>" + description + "</u></p>");
		if(exception != null) {
			message.append("<p><b>exception</b> <pre>" + exception + "</pre></p>");
		}
		if(note != null) {
			message.append("<p><b>note</b> <u>The full stack trace of the root cause is available in the " + version + " logs.</u></p>");
		}
		message.append("<HR size=\"1\" noshade=\"noshade\"><h3>" + version + "</h3></body></html>");
		return message.toString();
	}

	private static String getError500(String msg, String trace) {
		String description = "The server encountered an internal error that prevented it from fulfilling this request.";
		String note = "The full stack trace of the root cause is available in the " + version + " logs.";
		if(trace == null) {
			return getError(500, msg, description, "Exception report", null, null);
		} else {
			return getError(500, msg, description, "Exception report", trace, note);
		}
	}

	private static String getError404(String requestURI) {
		return getError(404, requestURI, "The requested resource is not available.", "Status report", null, null);
	}

	public static String getFileExtension(String path) {
		int i = path.lastIndexOf('.');
		if(i != -1) {
			return path.substring(i + 1);
		} else {
			return "";
		}
	}

	public static String join(String[] s, String delimiter) {
		return join(s, delimiter, s.length);
	}

	public static String join(String[] s, String delimiter, int length) {
		StringBuilder result = new StringBuilder();
		for(int i = 0; i < (length - 1); i++) {
			result.append(s[i]);
			result.append(delimiter);
		}
		result.append(s[length - 1]);
		return result.toString();
	}

	public static boolean inArray(String s, String[] array) {
		for(int i = 0; i < array.length; i++) {
			if(array[i].equals(s)) {
				return true;
			}
		}
		return false;
	}

	public static String getRealPath(String path) {
		String[] tokens = path.split("/");
		String[] newpath = new String[tokens.length + 1];
		int top = 0;
		for(int i = 0; i < tokens.length; i++) {
			if(tokens[i].equals(".")) {
				continue;
			} else if(tokens[i].equals("..")) {
				if(top > 0) {
					top--;
				}
			} else if(tokens[i].length() == 0) {
				continue;
			} else {
				newpath[top++] = tokens[i];
			}
		}
		if(path.charAt(path.length() - 1) == '/') { // trailing "/"
			newpath[top++] = "";
		}
		if(path.charAt(0) == '/') {
			return "/" + join(newpath, "/", top);
		} else {
			return join(newpath, "/", top);
		}
	}

	public static void service(String contextPath, String method, String pathInfo, String queryString, String requestURI, String requestURL, String serverName, int serverPort, String remoteAddr, int remotePort, String scheme, String protocol) throws IOException, ServletException {
		String[] parts = requestURL.split("/");
		StringBuffer url = new StringBuffer(requestURL.length());
		String name = servletMappings.get("/");
		if(name == null) {
			for(int i = 1; i < parts.length; i++) {
				url.append("/");
				url.append(parts[i]);
				name = servletMappings.get(url.toString());
				if(name != null) {
					break;
				}
			}
			if(url.length() == 0) { // requestURL = "/" results in zero length "parts"
				url.append("/");
			}
		} else {
			url.append("/");
		}

		if((name == null) && (url.charAt(url.length() - 1) == '/')) { // check default page
			for(String page : welcomepages) {
				name = servletMappings.get(url + page);
				if(name != null) {
					break;
				}
			}
		}

		String servletPath = url.toString();
		pathInfo = null;
		if(!servletPath.equals(requestURL)) {
			pathInfo = requestURL.substring(servletPath.length());
		}

		HttpServletRequest request = new HttpServletRequestImpl(contextPath, method, pathInfo, queryString, requestURI, requestURL, servletPath, protocol, remoteAddr, remotePort, scheme, serverName, serverPort);
		HttpServletResponse response = new HttpServletResponseImpl();

		response.setContentType("text/html");

		if(name == null) { // not mapped
			String path = getRealPath(requestURL);
			ServletOutputStream out = response.getOutputStream();
			boolean forbidden = path.startsWith("/lib/") || path.startsWith("/WEB-INF/");
			if(!forbidden && (path.charAt(path.length() - 1) == '/')) { // check welcome pages
				for(String file : welcomepages) {
					if(new File(path + file).exists()) {
						path += file;
						break;
					}
				}
			}
			File f = new File(path);
			if(!forbidden && f.exists() && f.isFile()) {
				String type = mimetypes.get(getFileExtension(path));
				if(type == null) {
					type = "application/octet-stream";
				}
				response.setContentLength((int)f.length());
				response.setContentType(type);
				FileInputStream in = new FileInputStream(path);
				byte[] buf = new byte[128];
				int nbytes;
				while((nbytes = in.read(buf, 0, buf.length)) != -1) {
					out.write(buf, 0, nbytes);
				}
				in.close();
			} else {
				response.setStatus(404);
				out.println(getError404(contextPath + requestURI));
			}
		} else {
			HttpServlet servlet = servlets.get(name);
			if(servlet == null) {
				String msg = getError500("A mapped servlet could not be found.", null);
				ServletOutputStream out = response.getOutputStream();
				response.setContentLength(msg.length());
				response.setStatus(500);
				out.println(msg);
			} else {
				servlet.service((HttpServletRequest)request, (HttpServletResponse)response);
			}
		}
	}
}

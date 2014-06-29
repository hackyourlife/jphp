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
	private static String version = "PHP-Java Server 1.0";

	static {
		servlets = new Hashtable<String,HttpServlet>();
		servletMappings = new Hashtable<String,String>();

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

	private static String getError404(String requestURI) {
		String message =
			"<html><head><title>" + version + " - Error report</title><style>" +
			"<!--H1 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:22px;}" +
			" H2 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:16px;}" +
			" H3 {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;font-size:14px;}" +
			" BODY {font-family:Tahoma,Arial,sans-serif;color:black;background-color:white;}" +
			" B {font-family:Tahoma,Arial,sans-serif;color:white;background-color:#525D76;}" +
			" P {font-family:Tahoma,Arial,sans-serif;background:white;color:black;font-size:12px;}" +
			" A {color : black;}A.name {color : black;}HR {color : #525D76;}--></style></head><body>" +
			"<h1>HTTP Status 404 - " + requestURI + "</h1><HR size=\"1\" noshade=\"noshade\"><p><b>type</b> Status report</p>" +
			"<p><b>message</b> <u>" + requestURI + "</u></p><p><b>description</b> <u>The requested resource is not available.</u></p>" +
			"<HR size=\"1\" noshade=\"noshade\"><h3>" + version + "</h3></body></html>";
		return message;
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

	public static String getRealPath(String path) {
		String[] tokens = path.split("/");
		String[] newpath = new String[tokens.length];
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
		if(path.charAt(0) == '/') {
			return "/" + join(newpath, "/", top);
		} else {
			return join(newpath, "/", top);
		}
	}

	public static void service(String contextPath, String method, String pathInfo, String queryString, String requestURI, String requestURL, String serverName, int serverPort, String remoteAddr, int remotePort, String scheme, String protocol) throws IOException, ServletException {
		String name = servletMappings.get(requestURL);
		String servletPath = requestURI;
		HttpServletRequest request = new HttpServletRequestImpl(contextPath, method, pathInfo, queryString, requestURI, requestURL, servletPath, protocol, remoteAddr, remotePort, scheme, serverName, serverPort);
		HttpServletResponse response = new HttpServletResponseImpl();

		response.setContentType("text/html");

		if(name == null) { // not mapped
			String path = getRealPath(requestURL);
			ServletOutputStream out = response.getOutputStream();
			boolean forbidden = path.startsWith("/lib/") || path.startsWith("/WEB-INF/");
			File f = new File(path);
			if(!forbidden && f.exists()) {
				String type = mimetypes.get(getFileExtension(path));
				if(type == null) {
					type = "application/octet-stream";
				}
				response.setContentLength((int)f.length());
				response.setContentType(type);
				FileInputStream in = new FileInputStream(requestURL);
				byte[] buf = new byte[128];
				int nbytes;
				while((nbytes = in.read(buf, 0, buf.length)) != -1) {
					out.write(buf, 0, nbytes);
				}
				in.close();
			} else {
				response.setStatus(404);
				out.println(getError404(requestURI));
			}
		} else {
			HttpServlet servlet = servlets.get(name);
			servlet.service((HttpServletRequest)request, (HttpServletResponse)response);
		}
	}
}

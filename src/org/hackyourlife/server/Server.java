package org.hackyourlife.server;

import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.Servlet;
import javax.servlet.ServletRequest;
import javax.servlet.ServletResponse;
import javax.servlet.ServletException;

import java.io.IOException;

import java.util.Hashtable;

public class Server {
	private static Hashtable<String, HttpServlet> servlets;
	private static String version = "PHP-Java Server 1.0";

	static {
		servlets = new Hashtable<String, HttpServlet>();
	}

	public static void registerServlet(String url, HttpServlet servlet) {
		servlets.put(url, servlet);
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
	public static void service(String contextPath, String method, String pathInfo, String queryString, String requestURI, String requestURL, String serverName, int serverPort, String remoteAddr, int remotePort, String scheme, String protocol) throws IOException, ServletException {
		HttpServlet servlet = servlets.get(requestURL);
		String servletPath = requestURI;
		HttpServletRequest request = new HttpServletRequestImpl(contextPath, method, pathInfo, queryString, requestURI, requestURL, servletPath, protocol, remoteAddr, remotePort, scheme, serverName, serverPort);
		HttpServletResponse response = new HttpServletResponseImpl();

		response.setContentType("text/html");

		if(servlet == null) { // 404
			response.setStatus(404);
			response.getOutputStream().println(getError404(requestURI));
		} else {
			servlet.service((HttpServletRequest)request, (HttpServletResponse)response);
		}
	}
}

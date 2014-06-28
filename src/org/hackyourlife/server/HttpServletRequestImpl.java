package org.hackyourlife.server;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.Cookie;
import javax.servlet.http.Part;
import javax.servlet.http.HttpSession;
import javax.servlet.AsyncContext;
import javax.servlet.DispatcherType;
import javax.servlet.ServletInputStream;
import javax.servlet.RequestDispatcher;
import javax.servlet.ServletRequest;
import javax.servlet.ServletResponse;
import javax.servlet.ServletContext;
//import java.security.Principal;

//import java.io.BufferedReader;
//import java.io.InputStreamReader;

import java.util.Hashtable;
import java.util.Enumeration;
//import java.util.Collection;
//import java.util.Locale;
//import java.util.Map;

public class HttpServletRequestImpl implements HttpServletRequest {
	private String contextPath;
	private Cookie[] cookies;
	private Hashtable<String,String> headers;
	private String method;
	private String pathInfo;
	private String queryString;
	private String remoteUser;
	private String requestURI;
	private String requestURL;
	private String servletPath;
	private Hashtable<String,Object> attributes;
	private int contentLength;
	private String contentType;
	private String localAddr;
	private String localName;
	private int localPort;
	private Hashtable<String,String> parameters;
	private String protocol;
	private ServletInputStream in;
	//private BufferedReader reader;
	private String remoteAddr;
	private int remotePort;
	private String scheme;
	private String serverName;
	private int serverPort;

	protected HttpServletRequestImpl(String contextPath, String method, String pathInfo, String queryString, String requestURI, String requestURL, String servletPath, String protocol, String remoteAddr, int remotePort, String scheme, String serverName, int serverPort) {
		cookies = new Cookie[0];
		attributes = new Hashtable<String,Object>();
		//in = new ServletInputStreamImpl();
		//reader = new BufferedReader(new InputStreamReader(in));
		this.contextPath = contextPath;
		this.method = method;
		this.pathInfo = pathInfo;
		this.queryString = queryString;
		this.requestURI = requestURI;
		this.requestURL = requestURL;
		this.servletPath = servletPath;
		this.protocol = protocol;
		this.remoteAddr = remoteAddr;
		this.remotePort = remotePort;
		this.scheme = scheme;
		this.serverName = serverName;
		this.serverPort = serverPort;
	}

	// HttpServletRequest
	public boolean authenticate(HttpServletResponse response) {
		return false;
	}

	public String getAuthType() {
		return null;
	}

	public String getContextPath() {
		return contextPath;
	}

	public Cookie[] getCookies() {
		return cookies;
	}

	public long getDateHeader(String name) {
		return 0;
	}

	public String getHeader(String name) {
		return headers.get(name);
	}

	public Enumeration<String> getHeaderNames() {
		return headers.keys();
	}

	public Enumeration<String> getHeaders(String name) {
		return null;
	}

	public int getIntHeader(String name) {
		return Integer.parseInt(getHeader(name));
	}

	public String getMethod() {
		return method;
	}

	public Part getPart(String name) {
		return null;
	}

	//public Collection<Part> getParts() {
	//	return null;
	//}

	public String getPathInfo() {
		return pathInfo;
	}

	public String getPathTranslated() {
		return pathInfo;
	}

	public String getQueryString() {
		return queryString;
	}

	public String getRemoteUser() {
		return remoteUser;
	}

	public String getRequestedSessionId() {
		return null;
	}

	public String getRequestURI() {
		return requestURI;
	}

	public StringBuffer getRequestURL() {
		return new StringBuffer(requestURL);
	}

	public String getServletPath() {
		return servletPath;
	}

	public HttpSession getSession() {
		return null;
	}

	public HttpSession getSession(boolean create) {
		return null;
	}

	//public Principal getUserPrincipal() {
	//	return null;
	//}

	public boolean isRequestedSessionIdFromCookie() {
		return false;
	}

	public boolean isRequestedSessionIdFromUrl() {
		return false;
	}

	public boolean isRequestedSessionIdFromURL() {
		return false;
	}

	public boolean isRequestedSessionIdValid() {
		return false;
	}

	public boolean isUserInRole(String role) {
		return false;
	}

	public void login(String username, String password) {
		return;
	}

	public void logout() {
		return;
	}

	// ServletRequest
	public AsyncContext getAsyncContext() {
		return null;
	}

	public Object getAttribute(String name) {
		return attributes.get(name);
	}

	public Enumeration<String> getAttributeNames() {
		return attributes.keys();
	}

	public String getCharacterEncoding() {
		return "UTF-8";
	}

	public int getContentLength() {
		return contentLength;
	}

	public String getContentType() {
		return contentType;
	}

	public DispatcherType getDispatcherType() {
		return null;
	}

	public ServletInputStream getInputStream() {
		return in;
	}

	public String getLocalAddr() {
		return localAddr;
	}

	//public Locale getLocale() {
	//	return null;
	//}

	//public Enumeration<Locale> getLocales() {
	//	return null;
	//}

	public String getLocalName() {
		return localName;
	}

	public int getLocalPort() {
		return localPort;
	}

	public String getParameter(String name) {
		return parameters.get(name);
	}

	//public Map<String,String[]> getParameterMap() {
	//	return null;
	//}

	public Enumeration<String> getParameterNames() {
		return parameters.keys();
	}

	public String[] getParameterValues(String name) {
		return null;
	}

	public String getProtocol() {
		return protocol;
	}

	//public BufferedReader getReader() {
	//	return reader;
	//}

	public String getRealPath(String path) {
		return null;
	}

	public String getRemoteAddr() {
		return remoteAddr;
	}

	public String getRemoteHost() {
		return remoteAddr;
	}

	public int getRemotePort() {
		return remotePort;
	}

	public RequestDispatcher getRequestDispatcher(String path) {
		return null;
	}

	public String getScheme() {
		return scheme;
	}

	public String getServerName() {
		return serverName;
	}

	public int getServerPort() {
		return serverPort;
	}

	public ServletContext getServletContext() {
		return null;
	}

	public boolean isAsyncStarted() {
		return false;
	}

	public boolean isAsyncSupported() {
		return false;
	}

	public boolean isSecure() {
		return false;
	}

	public void removeAttribute(String name) {
		attributes.remove(name);
	}

	public void setAttribute(String name, Object o) {
		attributes.put(name, o);
	}

	public void setCharacterEncoding(String env) {
	}

	public AsyncContext startAsync() {
		return null;
	}

	public AsyncContext startAsync(ServletRequest request, ServletResponse response) {
		return null;
	}
}

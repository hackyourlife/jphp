package javax.servlet;

import java.util.Enumeration;

public interface ServletRequest {
	public AsyncContext getAsyncContext();
	public Object getAttribute(String name);
	public Enumeration<String> getAttributeNames();
	public String getCharacterEncoding();
	public int getContentLength();
	public String getContentType();
	public DispatcherType getDispatcherType();
	public ServletInputStream getInputStream();
	public String getLocalAddr();
	public String getLocalName();
	public int getLocalPort();
	public String getParameter(String name);
	public Enumeration<String> getParameterNames();
	public String[] getParameterValues(String name);
	public String getProtocol();
	public String getRealPath(String path);
	public String getRemoteAddr();
	public String getRemoteHost();
	public int getRemotePort();
	public RequestDispatcher getRequestDispatcher(String path);
	public String getScheme();
	public String getServerName();
	public int getServerPort();
	public ServletContext getServletContext();
	public boolean isAsyncStarted();
	public boolean isAsyncSupported();
	public boolean isSecure();
	public void removeAttribute(String name);
	public void setAttribute(String name, Object o);
	public void setCharacterEncoding(String env);
	public AsyncContext startAsync();
	public AsyncContext startAsync(ServletRequest request, ServletResponse response);
}

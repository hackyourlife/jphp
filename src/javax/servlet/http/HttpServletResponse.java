package javax.servlet.http;

import javax.servlet.http.Cookie;
import javax.servlet.ServletOutputStream;
import javax.servlet.ServletResponse;

public interface HttpServletResponse extends ServletResponse {
	public void addCookie(Cookie cookie);
	public void addDateHeader(String name, long date);
	public void addHeader(String name, String value);
	public void addIntHeader(String name, int value);
	public boolean containsHeader(String name);
	public String encodeRedirectUrl(String url);
	public String encodeRedirectURL(String url);
	public String encodeUrl(String url);
	public String encodeURL(String url);
	public String getHeader(String name);
	public int getStatus();
	public void sendError(int sc);
	public void sendError(int sc, String msg);
	public void sendRedirect(String location);
	public void setDateHeader(String name, long date);
	public void setHeader(String name, String value);
	public void setIntHeader(String name, int value);
	public void setStatus(int sc);
	public void setStatus(int sc, String sm);
}

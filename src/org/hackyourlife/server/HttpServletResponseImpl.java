package org.hackyourlife.server;

import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.Cookie;
import javax.servlet.ServletOutputStream;

import java.io.PrintWriter;
import java.io.OutputStreamWriter;

import java.util.Hashtable;
import java.util.Collection;
import java.util.Locale;

public class HttpServletResponseImpl implements HttpServletResponse {
	private Hashtable<String,String> headers;
	private int status;
	private String contentType;
	private ServletOutputStream out;
	private PrintWriter writer;
	private int contentLength;

	protected HttpServletResponseImpl() {
		headers = new Hashtable<String,String>();
		status = 200;
		contentType = "text/html";
		out = new ServletOutputStreamImpl();
		writer = new PrintWriter(new OutputStreamWriter(out));
		contentLength = 0;
	}

	// HttpServletResponse
	public void addCookie(Cookie cookie) {
		return;
	}

	public void addDateHeader(String name, long date) {
		return;
	}

	public void addHeader(String name, String value) {
		headers.put(name, value);
	}

	public void addIntHeader(String name, int value) {
		headers.put(name, Integer.toString(value));
	}

	public boolean containsHeader(String name) {
		return headers.contains(name);
	}

	public String encodeRedirectUrl(String url) {
		return url;
	}

	public String encodeRedirectURL(String url) {
		return url;
	}

	public String encodeUrl(String url) {
		return url;
	}

	public String encodeURL(String url) {
		return url;
	}

	public String getHeader(String name) {
		return headers.get(name);
	}

	public Collection<String> getHeaderNames() {
		return headers.keySet();
	}

	public Collection<String> getHeaders(String name) {
		return null;
	}

	public int getStatus() {
		return status;
	}

	public void sendError(int sc) {
		sendError(sc, null);
	}

	public void sendError(int sc, String msg) {
		status0(sc, msg);
		finish0();
	}

	public void sendRedirect(String location) {
		header0("location: " + location);
		finish0();
	}

	public void setDateHeader(String name, long date) {
		return;
	}

	public void setHeader(String name, String value) {
		headers.put(name, value);
		header0(name + ":" + value);
	}

	public void setIntHeader(String name, int value) {
		headers.put(name, Integer.toString(value));
	}

	public void setStatus(int sc) {
		status = sc;
		status0(status, null);
	}

	public void setStatus(int sc, String sm) {
		status = sc;
		status0(status, sm);
	}

	// ServletResponse
	public void flushBuffer() {
		return;
	}

	public int getBufferSize() {
		return 0;
	}

	public String getCharacterEncoding() {
		return "UTF-8";
	}

	public String getContentType() {
		return contentType;
	}

	public Locale getLocale() {
		return null;
	}

	public ServletOutputStream getOutputStream() {
		return out;
	}

	public PrintWriter getWriter() {
		return writer;
	}

	public boolean isCommitted() {
		return true;
	}

	public void reset() {
		return;
	}

	public void resetBuffer() {
		return;
	}

	public void setBufferSize(int size) {
		return;
	}

	public void setCharacterEncoding(String charset) {
		return;
	}

	public void setContentLength(int len) {
		contentLength = len;
		header0("content-length: " + len);
	}

	public void setContentType(String type) {
		contentType = type;
		header0("content-type:" + type);
	}

	public void setLocale(Locale loc) {
		return;
	}

	// natives
	private native void header0(String header);
	private native void status0(int sc, String sm);
	private native void finish0();
}

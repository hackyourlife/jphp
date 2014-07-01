package javax.servlet;

import java.io.PrintWriter;

public interface ServletResponse {
	public void flushBuffer();
	public int getBufferSize();
	public String getCharacterEncoding();
	public String getContentType();
	public ServletOutputStream getOutputStream();
	public PrintWriter getWriter();
	public boolean isCommitted();
	public void reset();
	public void resetBuffer();
	public void setBufferSize(int size);
	public void setCharacterEncoding(String charset);
	public void setContentLength(int len);
	public void setContentType(String type);
}

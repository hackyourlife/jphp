package org.hackyourlife.webpage;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.ServletOutputStream;
import javax.servlet.ServletException;
import java.io.IOException;

public class Index extends HttpServlet {
	protected void doGet(HttpServletRequest request, HttpServletResponse response) throws IOException {
		ServletOutputStream out = response.getOutputStream();
		String msg = "Hello, World!\n"
			+ "I am running on Java using a subset of the servlet api.\n"
			+ "My Java version is " + System.getProperty("java.version")
			+ " running on " + System.getProperty("os.name")
			+ " (version " + System.getProperty("os.version") + ")";
		response.setContentType("text/plain");
		response.setContentLength(msg.length());
		out.print(msg);
		out.flush();
	}
}

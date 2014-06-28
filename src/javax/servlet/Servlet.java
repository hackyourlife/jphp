package javax.servlet;

import java.io.IOException;

public interface Servlet {
	void service(ServletRequest req, ServletResponse res) throws ServletException, IOException;
}

package org.hackyourlife.webpage;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.ServletOutputStream;
import javax.servlet.ServletException;

import java.io.IOException;

import java.util.Properties;
import java.util.Enumeration;

public class Index extends HttpServlet {
	protected void doGet(HttpServletRequest request, HttpServletResponse response) throws IOException {
		Webpage page = new Layout("Willkommen!");
		page.addSection("<h1>Java auf Lima-City</h1>");
		page.addSection("<h2>Diese Seite</h2>"
				+ "<p>Diese Webseite wird von einer Java-Anwendung ausgeliefert, welche auf"
				+ " einem kostenlosen Webspace ausgeführt wird, der ausschließlich PHP"
				+ " unterstützt.</p>");
		page.addSection("<h2>Die JVM</h2>"
				+ "<p>Die Java-VM ist vollständig in PHP implementiert und lässt sich auf"
				+ " jedem PHP-fähigen Webserver ausführen. Allerdings benötigt sie abhängig vom"
				+ " ausgeführten Programm mitunter sehr viel Speicher (>128M), sodass nicht"
				+ " alle Klassen der Standard-Klassenbibliothek gleichzeitig zur Verfügung"
				+ " stehen. Als Klassenbibliothek wird die offizielle von Oracle benutzt.</p>"
				+ "<p>Da beim Start der JVM sehr viele Objekte initialisiert werden müssen"
				+ " benötigt dies auch Zeit. Um nicht bei jedem Seitenaufruf lange warten"
				+ " zu müssen werden alle Klassen einmalig initialisiert und der Zustand der"
				+ " Java-VM dann gespeichert. Soll eine Webseite ausgeliefert werden, wird nur"
				+ " die Zustandsdatei geladen und die entsprechende Java-Methode zum Ausliefern"
				+ " der Webseite aufgerufen.</p>");

		String[] properties = {
			"java.version",
			"java.vendor",
			//"java.vendor.url",
			//"java.home",
			//"java.vm.specification.version",
			//"java.vm.specification.vendor",
			//"java.vm.specification.name",
			//"java.vm.version",
			//"java.vm.vendor",
			"java.vm.name",
			//"java.specification.version",
			//"java.specification.vendor",
			//"java.specification.name",
			"java.class.version",
			//"java.class.path",
			//"java.library.path",
			//"java.io.tmpdir",
			//"java.compiler",
			//"java.ext.dirs",
			"os.name",
			"os.arch",
			"os.version",
			//"file.separator",
			//"path.separator",
			//"line.separator"
		};
		StringBuffer rows = new StringBuffer();
		//Properties props = System.getProperties();
		//Enumeration<String> e = (Enumeration<String>)props.propertyNames();
		//while(e.hasMoreElements()) {
		for(int i = 0; i < properties.length; i++) {
			String name = properties[i];
			rows.append("<tr><td><code>");
			rows.append(name);
			rows.append("</code></td><td><code>");
			rows.append(System.getProperty(name));
			rows.append("</code></td></tr>");
		}

		page.addSection("<h2>Systemeigenschaften</h2>"
				+ "<div><table class=\"list\"><thead>"
				+ "<tr><th>Eigenschaft</th><th>Wert</th></tr>"
				+ "</thead><tbody>" + rows + "</tbody></table></div>");

		page.addSection("<h2>Quellcode</h2>"
				+ "<p>Der Quellcode des Servlet-Containers kann hier eingesehen werden:"
				+ " <a href=\"src/org/hackyourlife/server/Server.java\">Server.java</a></p>"
				+ "<p>Für besonders Interessierte gibt es hier den Quellcode der PHP-Hauptseite,"
				+ " welcher die Nutzung der JVM zeigt: <a href=\"index.php\">index.php</a></p>");

		String msg = page.toString();
		ServletOutputStream out = response.getOutputStream();
		byte[] bytes = msg.getBytes("UTF-8");
		response.setContentType("text/html; charset=utf-8");
		response.setContentLength(bytes.length);
		out.write(bytes);
		out.flush();
	}
}

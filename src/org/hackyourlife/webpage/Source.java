package org.hackyourlife.webpage;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.ServletOutputStream;
import javax.servlet.ServletException;

import java.io.IOException;
import java.io.FileNotFoundException;
import java.io.FileInputStream;
import java.io.File;

import java.util.Properties;
import java.util.Enumeration;

import org.hackyourlife.syntax.JavaHighlighter;

public class Source extends HttpServlet {
	protected void doGet(HttpServletRequest request, HttpServletResponse response) throws IOException {
		String pathInfo = request.getPathInfo();
		if(pathInfo == null) {
			throw new FileNotFoundException(pathInfo);
		}
		String path = "src" + pathInfo;
		File f = new File(path);
		if(!f.exists() || !f.isFile()) {
			throw new FileNotFoundException(pathInfo);
		}

		int size = (int)f.length();
		byte[] data = new byte[size];
		FileInputStream in = new FileInputStream(path);
		size = in.read(data, 0, size);

		String content = new String(data);
		String[] lines = content.split("\n");

		response.setContentType("text/html; charset=utf-8");
		ServletOutputStream out = response.getOutputStream();

		JavaHighlighter highlighter = new JavaHighlighter(null);
		for(int i = 0; i < lines.length; i++) {
			out.print(highlighter.formatLine(lines[i]));
			out.flush();
		}
		out.flush();
		/*
		out.print("<!DOCTYPE html><html><head><link type=\"text/css\" rel=\"stylesheet\" href=\"css/style.css\"/></head><body><pre>");
		while((line = in.readLine()) != null) {
			out.write(highlighter.formatLine(line));
			out.flush();
		}
		out.print("</pre></body></html>");
		in.close();
		out.flush();
		*/
	}
}

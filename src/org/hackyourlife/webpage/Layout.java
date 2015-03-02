package org.hackyourlife.webpage;

public class Layout extends Webpage {
	public Layout(String title) {
		super(title);
		setStylesheet("css/style.css");
		setFooter("<div id=\"footer\"><a href=\"https://www.lima-city.de/homepage/ref:250835\">lima-city.de</a>"
			+ " • <a href=\"http://hackyourlife.lima-city.de\">hackyourlife</a>"
			+ " • <a href=\"https://github.com/hackyourlife/jphp\">GitHub</a></div>");
	}
}

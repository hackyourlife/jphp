package javax.servlet;
import java.io.OutputStream;
import java.io.IOException;

public abstract class ServletOutputStream extends OutputStream {
	public void print(String s) throws IOException {
		if(s == null) {
			s = "null";
		}

		int len = s.length();
		for(int i = 0; i < len; i++) {
			char c = s.charAt(i);
			write(c);
		}
	}

	public void print(boolean b) throws IOException {
		print(b ? "true" : "false");
	}

	public void print(char c) throws IOException {
		print(String.valueOf(c));
	}

	public void print(int i) throws IOException {
		print(String.valueOf(i));
	}

	public void print(long l) throws IOException {
		print(String.valueOf(l));
	}

	public void print(float f) throws IOException {
		print(String.valueOf(f));
	}

	public void print(double d) throws IOException {
		print(String.valueOf(d));
	}

	public void println() throws IOException {
		print("\r\n");
	}

	public void println(String s) throws IOException {
		print(s);
		println();
	}

	public void println(boolean b) throws IOException {
		print(b ? "true" : "false");
		println();
	}

	public void println(char c) throws IOException {
		print(String.valueOf(c));
		println();
	}

	public void println(int i) throws IOException {
		print(String.valueOf(i));
		println();
	}

	public void println(long l) throws IOException {
		print(String.valueOf(l));
		println();
	}

	public void println(float f) throws IOException {
		print(String.valueOf(f));
		println();
	}

	public void println(double d) throws IOException {
		print(String.valueOf(d));
		println();
	}
}

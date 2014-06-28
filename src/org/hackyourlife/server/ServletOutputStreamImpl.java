package org.hackyourlife.server;
import javax.servlet.ServletOutputStream;
import java.io.IOException;

public class ServletOutputStreamImpl extends ServletOutputStream {
	public void write(int c) throws IOException {
		System.out.write(c);
	}

	public void write(byte[] c, int offset, int len) throws IOException {
		System.out.write(c, offset, len);
	}
}

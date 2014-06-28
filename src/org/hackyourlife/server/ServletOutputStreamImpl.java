package org.hackyourlife.server;
import javax.servlet.ServletOutputStream;
import java.io.IOException;

public class ServletOutputStreamImpl extends ServletOutputStream {
	public native void write(int c) throws IOException;

	public void write(byte[] c, int offset, int len) throws IOException {
		write0(c, offset, len);
	}

	private native void write0(byte[] c, int offset, int len) throws IOException;
}

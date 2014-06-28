package org.hackyourlife.server;
import javax.servlet.ServletInputStream;
import java.io.IOException;

public class ServletInputStreamImpl extends ServletInputStream {
	public native int read() throws IOException;
}

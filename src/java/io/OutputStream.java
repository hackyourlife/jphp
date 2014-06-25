package java.io;

abstract public class OutputStream {
	abstract public void write(int c);

	public void write(byte[] c) {
		for(int i = 0; i < c.length; i++)
			write(c);
	}
}

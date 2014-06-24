public class constructor {
	static {
		System.out.println("static initializer");
	}

	public constructor() {
		System.out.println("construct!");
	}

	public static Object test() {
		Object[] x = new Object[7];
		return null;
	}

	public double doubleTest() {
		double a = 1;
		double b = a + a;
		return a + b;
	}

	public static void test(int x) {
		int y = x;
	}

	public static void main(String[] args) {
		new constructor();
	}
}

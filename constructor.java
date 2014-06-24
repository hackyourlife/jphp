public class constructor {
	static int x;
	static {
		x = 42;
		System.out.println("static initializer");
	}

	String s;

	public constructor() {
		s = "construct!";
		System.out.println(s);
	}

	public static void main(String[] args) {
		constructor c = new constructor();
	}
}

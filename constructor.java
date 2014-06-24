public class constructor {
	static int x;
	static {
		x = 42;
		System.out.println("static initializer");
	}

	public constructor() {
		System.out.println("construct!");
	}

	public static void main(String[] args) {
		constructor c = new constructor();
	}
}

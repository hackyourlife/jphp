public class constructor {
	static {
		System.out.println("static initializer");
	}

	public constructor() {
		System.out.println("construct!");
	}

	public static void main(String[] args) {
		new constructor();
	}
}
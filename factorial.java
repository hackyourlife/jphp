public class factorial {
	public static long factorial(int n) {
		long value = 1;
		for(int i = 2; i <= n; i++) {
			value *= i;
		}
		return value;
	}

	public static void main(String[] args) {
		int x = 5;
		long f = factorial(x);
		System.out.printf("factorial(%d) = %d\n", x, f);
	}
}

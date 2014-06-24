public class factorial {
	public static long factorial(int n) {
		long value = 1;
		for(int i = 2; i < n; i++) {
			value *= i;
		}
		return value;
	}

	public static void main(String[] args) {
		factorial(5);
	}
}

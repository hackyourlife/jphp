<?php

abstract class InputStream {
	abstract public function read($count = 1);
	abstract public function close();
}

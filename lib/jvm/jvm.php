<?php

//define('PATH_SEPARATOR', ':');

class JVM {
	private	$classpath;
	private $classinstances;
	private $threads;
	private $references;

	public function __construct($params = array()) {
		$this->classpath = array('lib/classes', '.');
		$this->classinstances = array();
		$this->threads = array();
		foreach($params as $name => $value) {
			switch($name) {
			case 'classpath':
				$this->classpath = explode(PATH_SEPARATOR, $value);
				break;
			}
		}
	}

	private function locateClass($classname) {
		foreach($this->classpath as $path) {
			if($path[strlen($path) - 1] != '/')
				$path .= '/';
			$filepath = "{$path}{$classname}.class";
			if(file_exists($filepath))
				return $filepath;
		}
		return false;
	}

	public function load($classname) {
		if(isset($this->classes[$classname]))
			return;
		print("[JVM] loading '$classname'\n");
		$filename = $this->locateClass($classname);
		if($filename === false)
			throw new ClassNotFoundException($classname);
		$file = new FileInputStream($filename);
		$c = new JavaClass($file);
		$file->close();
		$this->classes[$classname] = new JavaClassStatic($this, $c);
		$this->classes[$classname]->initialize();
		//echo('constant_pool: ');
		//print_r($c->constant_pool);
		//echo('interfaces: ');
		//print_r($c->interfaces);
		//echo('fields: ');
		//print_r($c->fields);
		//echo('methods: ');
		//print_r($c->methods);
		//echo('attributes: ');
		//print_r($c->attributes);
	}

	public function getStatic($classname) {
		$this->load($classname);
		return $this->classes[$classname];
	}

	public function call($classname, $method, $signature, $args = NULL) {
		$this->load($classname);
		return $this->classes[$classname]->call($method, $signature, $args);
	}

	public function instantiate($classname) {
		$this->load($classname);
		return $this->classes[$classname]->instantiate();
	}
}

<?php

//define('PATH_SEPARATOR', ':');

class JVM {
	private	$classpath;
	private $classinstances;
	private $threads;
	private $native;
	public $references;

	public function __construct($params = array()) {
		$this->classpath = array('lib/classes', '.');
		$this->classinstances = array();
		$this->threads = array();
		$this->references = new References();
		$this->native = array();
		foreach($params as $name => $value) {
			switch($name) {
			case 'classpath':
				$this->classpath = explode(PATH_SEPARATOR, $value);
				break;
			}
		}
	}

	public function initialize() {
		//$this->load('java/lang/System');
		//print("completely instantiated\n");
		//var_dump($this->getStatic('java/lang/System')->getField('out'));
		//$string = $this->instantiate('java/lang/String');
		//$ref = $this->references->newref();
		//$this->references->set($ref, $string);
		//$string->callSpecial($ref, '<init>', '()V');
		//$string->dump();
		//$array = $this->references->get($string->getField('value'));
		//var_dump($array->toString());
		//$this->references->free($ref);
		$object = $this->instantiate('characters');
		$ref = $this->references->newref();
		$this->references->set($ref, $object);
		$object->callSpecial($ref, '<init>', '()V');
		$object->dump();
		$this->getStatic('characters')->dump();
		$this->references->free($ref);
		exit(0);
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
		$this->classes[$classname] = new JavaClassStatic($this, $classname, $c);
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

	public function callNative($class, $method, $signature, $args) {
		$classname = $class->getName();
		if(!isset($this->native[$classname])) {
			require_once("lib/native/$classname.php");
			$this->native[$classname] = true;
		}
		$path = str_replace('/', '_', $classname);
		$call = "Java_{$path}_$method";
		$call($this, $class, $args);
	}

	public function &getStatic($classname) {
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

	public static function defaultValue($descriptor) {
		switch($descriptor[0]) {
		case JAVA_FIELDTYPE_BYTE:
		case JAVA_FIELDTYPE_CHAR:
		case JAVA_FIELDTYPE_INTEGER:
		case JAVA_FIELDTYPE_LONG:
		case JAVA_FIELDTYPE_SHORT:
		case JAVA_FIELDTYPE_BOOLEAN:
			return 0;
		case JAVA_FIELDTYPE_DOUBLE:
			return (double)0.0;
		case JAVA_FIELDTYPE_FLOAT:
			return (float)0.0;
		case JAVA_FIELDTYPE_CLASS:
		case JAVA_FIELDTYPE_ARRAY:
			return NULL;
		}
	}

}

class References {
	private $references;
	private $nextref;

	public function __construct() {
		$this->references = array();
		$this->nextref = 1;
	}

	public function dump() {
		print_r($this->references);
	}

	public function get($ref) {
		if(!isset($this->references[$ref])) {
			throw new NoSuchReferenceException($ref);
		}
		return $this->references[$ref]->value;
	}

	public function set($ref, $value) {
		print("[GC] allocating object #$ref\n");
		if(isset($this->references[$ref])) {
			//$this->references[$ref]->refcount++;
			$this->references[$ref]->value = $value;
		} else {
			$this->references[$ref] = (object)array(
				'refcount' => 1,
				'value' => $value
			);
		}
	}

	public function useref($ref) {
		if(!isset($this->references[$ref])) {
			throw new NoSuchReferenceException($ref);
		}
		$this->references[$ref]->refcount++;
		return $this->references[$ref]->value;
	}

	public function free($ref) {
		if(!isset($this->references[$ref])) {
			throw new NoSuchReferenceException($ref);
		}
		$this->references[$ref]->refcount--;
		if($this->references[$ref]->refcount == 0) {
			print("[GC] releasing object #$ref\n");
			try {
				$this->references[$ref]->value->finalize();
			} catch(Exception $e) {
				printException($e);
			}
			unset($this->references[$ref]);
		}
	}

	public function newref() {
		return $this->nextref++;
	}

	public function getReference(&$object) {
		foreach($this->references as $reference => $value) {
			if($value->value === $object) {
				return $reference;
			}
		}
		return NULL;
	}
}

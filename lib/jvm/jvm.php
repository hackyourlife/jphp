<?php

//define('PATH_SEPARATOR', ':');

class JVM {
	private	$classpath;
	private $builtinlibpath;
	private $classinstances;
	private $primitiveclasses;
	private $threads;
	private $native;
	public $references;
	private $classes;
	private $current_thread;
	private $system_threadgroup;

	private static $debug_loading = true;

	public function __construct($params = array()) {
		$this->classpath = array('lib/classes', '.');
		$this->builtinlibpath = 'lib/libs';
		$this->classinstances = array();
		$this->primitiveclasses = array();
		$this->threads = array();
		$this->references = new References();
		$this->native = array();
		$this->classes = array();
		$this->current_thread = NULL;
		$this->system_threadgroup = NULL;
		foreach($params as $name => $value) {
			switch($name) {
			case 'classpath':
				$this->classpath = explode(PATH_SEPARATOR, $value);
				break;
			case 'builtinlibpath':
				$this->builtinlibpath = $value;
				break;
			}
		}
	}

	public function initialize() {
		$this->loadSystemClass('java/lang/Object');
		$this->loadSystemClass('java/lang/Class');
		$this->registerClass('java/lang/Class');
		$this->registerClass('java/lang/Object');
		$this->registerPrimitives();
		$this->load('java/lang/String');
		//$this->load('java/lang/Throwable');
		//$this->load('java/lang/Thread');
		$this->load('java/lang/ThreadGroup');
		//$this->load('java/lang/System');
		$this->load('sun/misc/Unsafe');

		$trace = new StackTrace();
		$trace->push('org/hackyourlife/jvm/JVM', 'initialize', 0, true);
		$this->initialize_thread($trace);

		//$this->load('reflection');

		$this->call('java/lang/System', 'initializeSystemClass', '()V', NULL, $trace);
		$trace->pop();
	}

	private function initialize_thread($trace = NULL) {
		if($trace === NULL) {
			$trace = new StackTrace();
		}

		$threadgroup = $this->instantiate('java/lang/ThreadGroup');
		$threadgroup_ref = $this->references->newref();
		$this->references->set($threadgroup_ref, $threadgroup);
		$threadgroup->setReference($threadgroup_ref);
		$trace->push('org/hackyourlife/jvm/JVM', 'initialize_thread', 0, true);
		$threadgroup->call('<init>', '()V', NULL, $trace);
		$trace->pop();
		$this->system_threadgroup = $threadgroup_ref;

		$current_thread = $this->instantiate('java/lang/Thread');
		$current_thread_ref = $this->references->newref();
		$this->references->set($current_thread_ref, $current_thread);
		$current_thread->setReference($current_thread_ref);
		$trace->push('org/hackyourlife/jvm/JVM', 'initialize_thread', 0, true);
		$thread = $this->getStatic('java/lang/Thread');
		$normal_priority = $thread->getField('NORM_PRIORITY');
		$current_thread->setField('daemon', 0);
		$current_thread->setField('stillborn', 0);
		$current_thread->setField('group', $threadgroup_ref);
		$current_thread->setField('priority', $normal_priority);
		$trace->pop();
		$this->current_thread = $current_thread_ref;

		$trace->push('org/hackyourlife/jvm/JVM', 'initialize_thread', 0, true);
		$threadgroup->call('add', '(Ljava/lang/Thread;)V', array($threadgroup_ref), $trace);
		$trace->pop();
	}

	public function mapLibraryName($name) {
		return "$name.php";
	}

	public function findBuiltinLib($name) {
		return "{$this->builtinlibpath}/$name";
	}

	public function loadLibrary($name) {
		require_once($name);
	}

	public function showClasses() {
		foreach($this->classes as $name => $class) {
			print("$name\n");
		}
	}

	public function currentThread() {
		return $this->current_thread;
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

	private function loadSystemClass($classname) {
		if(isset($this->classes[$classname]))
			return;
		if(self::$debug_loading) {
			print("[JVM] loading '$classname'\n");
		}
		$filename = $this->locateClass($classname);
		if($filename === false)
			throw new ClassNotFoundException($classname);
		$file = new FileInputStream($filename);
		$c = new JavaClass($file);
		$file->close();
		$this->classes[$classname] = new JavaClassStatic($this, $classname, $c);
		$this->classes[$classname]->initialize();
	}

	private function registerClass($classname, $delayed_load = false, $component_type = NULL) {
		if(isset($this->classinstances[$classname])) {
			$class = $this->references->get($this->classinstances[$classname]);
			$class->info->loaded = !$delayed_load;
			return;
		}
		$class = $this->instantiate('java/lang/Class');
		$ref = $this->references->newref();
		$this->references->set($ref, $class);
		$class->setReference($ref);
		$trace = new StackTrace();
		$trace->push('org/hackyourlife/jvm/JVM', 'load', 0, true);
		$class->callSpecial('<init>', '()V', NULL, NULL, $trace);
		$class->info = (object)array(
			'name' => $classname,
			'loaded' => !$delayed_load,
			'array' => $component_type !== NULL,
			'component_type' => $component_type
		);
		$this->classinstances[$classname] = $ref;
	}

	private function registerPrimitives() {
		$primitives = array(
			JAVA_FIELDTYPE_BOOLEAN => 'boolean',
			JAVA_FIELDTYPE_CHAR => 'char',
			JAVA_FIELDTYPE_FLOAT => 'float',
			JAVA_FIELDTYPE_DOUBLE => 'double',
			JAVA_FIELDTYPE_BYTE => 'byte',
			JAVA_FIELDTYPE_SHORT => 'short',
			JAVA_FIELDTYPE_INTEGER => 'int',
			JAVA_FIELDTYPE_LONG => 'long'
		);
		foreach($primitives as $primitive => $name) {
			$class = $this->instantiate('java/lang/Class');
			$ref = $this->references->newref();
			$this->references->set($ref, $class);
			$class->setReference($ref);
			$trace = new StackTrace();
			$trace->push('org/hackyourlife/jvm/JVM', 'load', 0, true);
			$class->callSpecial('<init>', '()V', NULL, NULL, $trace);
			$class->info = (object)array(
				'primitive' => $primitive,
				'name' => $name,
				'loaded' => true,
				'array' => false
			);
			$this->primitiveclasses[$primitive] = $ref;
		}
	}

	public function load($classname) {
		if(!is_string($classname)) {
			throw new Exception();
		}
		if(isset($this->classes[$classname]))
			return;
		if(self::$debug_loading) {
			print("[JVM] loading '$classname'\n");
		}
		if($classname == 'java/lang/Class') {
			$this->showClasses();
			throw new Exception();
		}
		$filename = $this->locateClass($classname);
		if($filename === false)
			throw new ClassNotFoundException($classname);
		$file = new FileInputStream($filename);
		$c = new JavaClass($file);
		$file->close();
		$this->classes[$classname] = new JavaClassStatic($this, $classname, $c);

		// register class
		$this->registerClass($classname);

		// initialize class
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

	public function reloadNatives() {
		foreach($this->native as $classname => $value) {
			$path = "lib/native/$classname.php";
			if(!file_exists($path)) {
				var_dump($path);
				print("$classname:$method$signature\n");
				throw new Exception();
			}
			require_once($path);
		}
	}

	public function callNative($class, $method, $signature, $args, $classname, $trace) {
		$name = str_replace('$', '_', $classname);
		if(!isset($this->native[$classname])) {
			$path = "lib/native/$classname.php";
			if(!file_exists($path)) {
				var_dump($path);
				print("$classname:$method$signature\n");
				throw new Exception();
			}
			require_once($path);
			$this->native[$classname] = true;
		}
		$path = str_replace('/', '_', $name);
		$call = "Java_{$path}_$method";
		return $call($this, $class, $args, $trace);
	}

	public function getPrimitiveClass($primitive) {
		if(!isset($this->primitiveclasses[$primitive])) {
			throw new Exception('primitive not fund');
		}
		return $this->primitiveclasses[$primitive];
	}

	public function getClass($classname) {
		if(!isset($this->classinstances[$classname])) {
			if($classname[0] == '[') { // FIXME: array types
				$type = new stdClass();
				if($classname[1] == 'L') {
					$ref = $this->getClass(substr($classname, 2, strlen($classname) - 3));
					$type->name = $this->references->get($ref)->info->name;
				} else {
					$ref = $this->getPrimitiveClass(substr($classname, 1));
					$type->primitive = $this->references->get($ref)->info->primitive;
				}
				$this->registerClass($classname, false, $type);
			} else {
				//$this->load($classname);
				// register class
				$this->registerClass($classname, true);
			}
		}
		return $this->classinstances[$classname];
	}

	public function &getStatic($classname) {
		if($classname[0] == '[') {
			if($classname[1] != 'L') {
				throw new Exception('not implemented');
			}
			$classname = substr($classname, 2, strlen($classname) - 3);
		}
		$this->load($classname);
		return $this->classes[$classname];
	}

	public function call($classname, $method, $signature, $args = NULL, $trace = NULL) {
		$this->load($classname);
		return $this->classes[$classname]->call($method, $signature, $args, $trace);
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
	private static $debug = false;

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
		if(self::$debug) {
			print("[GC] allocating object #$ref ({$value->getName()})\n");
		}
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
			if(self::$debug) {
				print("[GC] releasing object #$ref\n");
			}
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

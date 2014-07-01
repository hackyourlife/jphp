<?php

header('content-type: text/plain');

require_once('lib/io/File.php');
require_once('lib/io/binary.php');
require_once('lib/io/endianess.php');
require_once('lib/io/InputStream.php');
require_once('lib/io/FileInputStream.php');
require_once('lib/io/StringInputStream.php');
require_once('lib/io/DataInputStream.php');
require_once('lib/jvm/commonexceptions.php');
require_once('lib/jvm/classfile.php');
require_once('lib/jvm/javatypes.php');
require_once('lib/jvm/javaclass.php');
require_once('lib/jvm/javaclassstatic.php');
require_once('lib/jvm/opcodes.php');
require_once('lib/jvm/interpreter.php');
require_once('lib/jvm/jvm.php');

set_time_limit(60);

$classlist = array(
	//'java/io/PrintWriter',
	'java/io/IOException',
	//'java/io/OutputStreamWriter',
	//'java/io/BufferedReader',
	//'java/io/FileReader',
	//'java/io/InputStreamReader',
	'java/lang/Enum',
	'java/lang/Byte',
	'java/lang/StringBuffer',
	'java/lang/NullPointerException',
	'java/lang/ThreadLocal$ThreadLocalMap',
	'java/lang/ThreadLocal$ThreadLocalMap$Entry',
	'java/lang/StringCoding',
	'java/lang/StringCoding$StringEncoder',
	'java/util/ArrayList',
	'java/util/RandomAccess',
	'java/util/ArrayList$SubList',
	'java/util/ArrayList$SubList$1',
	'java/util/Vector',
	'java/util/Vector$Itr',
	'java/nio/CharBuffer',
	'java/nio/HeapCharBuffer',
	'java/nio/charset/CoderResult',
	'java/nio/charset/CoderResult$1',
	'java/nio/charset/CoderResult$Cache',
	'java/nio/charset/CoderResult$2',
	'java/nio/charset/CharsetDecoder',
	'java/io/File',
	'java/io/File$PathStatus',
	//'java/io/Reader',
	//'java/io/Writer',
	//'java/io/BufferedReader',
	//'java/io/StringReader',
	//'java/io/StringWriter',
	'java/io/FileInputStream',
	'java/io/FileInputStream$1',
	//'java/util/ResourceBundle',
	//'java/util/concurrent/ConcurrentHashMap',
	//'java/util/Collection'
	'sun/nio/cs/ArrayEncoder',
	'sun/nio/cs/ArrayDecoder',
	'sun/nio/cs/StreamDecoder',
	'sun/nio/cs/UTF_8$Decoder',
	// servlet api
	'javax/servlet/http/HttpServletRequest',
	'javax/servlet/http/HttpServletResponse',
	// application
	'org/hackyourlife/server/HttpServletRequestImpl',
	'org/hackyourlife/server/HttpServletResponseImpl',
	'org/hackyourlife/server/ServletOutputStreamImpl',
	'org/hackyourlife/webpage/Webpage',
	'org/hackyourlife/webpage/Layout',
	//'org/hackyourlife/syntax/JavaHighlighter'
);
echo('creating jvm...');
$jvm = new JVM(array('classpath' => 'lib/classes:WEB-INF/classes', 'fsroot' => dirname(__FILE__)));
$jvm->initialize();
echo(" done\n");

echo("loading classes\n");
foreach($classlist as $class) {
	$jvm->load($class);
}

$peak = memory_get_peak_usage(true);
$peak_mb = (int) ($peak / (1024 * 1024));
$usage = memory_get_usage(true);
$usage_mb = (int) ($usage / (1024 * 1024));
print("peak usage: $peak_mb MiB\n");
print("current usage: $usage_mb MiB\n");

echo("initializing application\n");

$trace = new StackTrace();
$server = $jvm->getStatic('org/hackyourlife/server/Server');

$servlet_names = array(
	'index'	=> 'org/hackyourlife/webpage/Index',
	//'source' => 'org/hackyourlife/webpage/Source'
);

$servlet_paths = array(
	'/'	=> 'index',
	//'/src'	=> 'source'
);

foreach($servlet_names as $name => $class) {
	$servlet = $jvm->instantiate($class);
	$servletref = $jvm->references->newref();
	$jvm->references->set($servletref, $servlet);
	$servlet->setReference($servletref);
	$servlet->callSpecial('<init>', '()V', NULL, NULL, $trace);
	$nameref = JavaString::newString($jvm, $name);
	$args = array($nameref, $servletref);
	$jvm->call('org/hackyourlife/server/Server', 'registerServlet', '(Ljava/lang/String;Ljavax/servlet/http/HttpServlet;)V', $args, $trace);
}

foreach($servlet_paths as $path => $name) {
	$pathref = JavaString::newString($jvm, $path);
	$nameref = JavaString::newString($jvm, $name);
	$args = array($pathref, $nameref);
	$jvm->call('org/hackyourlife/server/Server', 'mapServlet', '(Ljava/lang/String;Ljava/lang/String;)V', $args, $trace);
}


$peak = memory_get_peak_usage(true);
$peak_mb = (int) ($peak / (1024 * 1024));
$usage = memory_get_usage(true);
$usage_mb = (int) ($usage / (1024 * 1024));
print("peak usage: $peak_mb MiB\n");
print("current usage: $usage_mb MiB\n");

$jvm->setFSRoot('/');
file_put_contents('state.jvm', serialize($jvm));
//file_put_contents('jvm.state', $jvm->saveState());

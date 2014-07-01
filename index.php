<?php

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

$jvm = unserialize(file_get_contents('state.jvm'));
$jvm->reloadNatives();
$jvm->setFSRoot(dirname(__FILE__));

header('content-type: text/plain');
$query_string = isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : $_SERVER['QUERY_STRING'];
$url = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME'];

$contextPath = dirname($_SERVER['PHP_SELF']);
$offset = strlen($contextPath);
$pathInfo = '/';
$uri = substr($_SERVER['REQUEST_URI'], $offset);
$queryString = '';
if(strpos($uri, '?') !== false) {
	$queryString = substr($uri, strpos($uri, '?') + 1);
}
$method = $_SERVER['REQUEST_METHOD'];
$requestURI = substr($url, $offset);
$requestURL = $uri;
$serverName = $_SERVER['SERVER_NAME'];
$serverPort = $_SERVER['SERVER_PORT'];
$remoteAddr = $_SERVER['REMOTE_ADDR'];
$remotePort = $_SERVER['REMOTE_PORT'];
$protocol = $_SERVER['SERVER_PROTOCOL'];
$scheme = 'http';

$trace = new StackTrace();
$server = $jvm->getStatic('org/hackyourlife/server/Server');

$args = array(
	JavaString::newString($jvm, $contextPath),
	JavaString::newString($jvm, $method),
	JavaString::newString($jvm, $pathInfo),
	JavaString::newString($jvm, $queryString),
	JavaString::newString($jvm, $requestURI),
	JavaString::newString($jvm, $requestURL),
	JavaString::newString($jvm, $serverName),
	$serverPort,
	JavaString::newString($jvm, $remoteAddr),
	$remotePort,
	JavaString::newString($jvm, $scheme),
	JavaString::newString($jvm, $protocol)
);

$server->call('service', '(Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;ILjava/lang/String;ILjava/lang/String;Ljava/lang/String;)V', $args, $trace);

//$peak = memory_get_peak_usage(true);
//$peak_mb = (int) ($peak / (1024 * 1024));
//$usage = memory_get_usage(true);
//$usage_mb = (int) ($usage / (1024 * 1024));
//print("peak usage: $peak_mb MiB\n");
//print("current usage: $usage_mb MiB\n");

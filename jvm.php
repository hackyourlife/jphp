<?php

header('content-type: text/plain');

require_once('lib/io/File.php');
require_once('lib/io/binary.php');
require_once('lib/io/endianess.php');
require_once('lib/io/InputStream.php');
require_once('lib/io/FileInputStream.php');
require_once('lib/io/StringInputStream.php');
require_once('lib/io/DataInputStream.php');
require_once('lib/jvm/classfile.php');
require_once('lib/jvm/javaclass.php');
require_once('lib/jvm/javaclassstatic.php');
require_once('lib/jvm/javatypes.php');
require_once('lib/jvm/interpreter.php');
require_once('lib/jvm/jvm.php');

echo('creating jvm...');
$jvm = new JVM();
echo(" done\n");
echo('creating instance of "test"...');
$class = $jvm->instantiate('constructor');
$class->call('main', '([Ljava/lang/String;)V');
echo(" done\n");

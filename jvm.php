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
require_once('lib/jvm/stdio.php');
require_once('lib/jvm/jvm.php');

echo('creating jvm...');
$jvm = new JVM();
$jvm->initialize();
file_put_contents('state.jvm', serialize($jvm));
echo(" done\n");

$peak = memory_get_peak_usage();
$peak_mb = (int) ($peak / (1024 * 1024));
print("peak usage: $peak_mb MiB\n");

//$result = $jvm->call('factorial', 'factorial', '(I)J', array(6));
//echo('result: '); var_dump($result);

echo("running helloworld\n");

$arg0 = JavaString::newString($jvm, "PHP Java VM");
$args = new JavaArray($jvm, 1, 'java/lang/String');
$args->set(0, $arg0);
$argsref = $jvm->references->newref();
$jvm->references->set($argsref, $args);
$args->setReference($argsref);

$trace = new StackTrace();
$helloworld = $jvm->getStatic('helloworld');
$helloworld->call('main', '([Ljava/lang/String;)V', array($argsref), $trace);

$peak = memory_get_peak_usage();
$peak_mb = (int) ($peak / (1024 * 1024));
print("peak usage: $peak_mb MiB\n");

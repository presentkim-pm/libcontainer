<?php
declare(strict_types=1);

$out = __DIR__ . '/LibContainerSpike.phar';
if(is_file($out)){
	unlink($out);
}
$phar = new Phar($out);
$phar->startBuffering();
$phar->addFromString('plugin.yml', file_get_contents(__DIR__ . '/plugin.yml'));
$files = glob(__DIR__ . '/src/*.php');
foreach($files as $f){
	$phar->addFromString('src/' . basename($f), file_get_contents($f));
}
$phar->setStub('<?php __HALT_COMPILER();');
$phar->stopBuffering();
echo "built $out\n";

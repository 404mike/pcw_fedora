<?php
require_once realpath(__DIR__.'./')."vendor/autoload.php";
$foaf = new \EasyRdf\Graph("http://njh.me/foaf.rdf");
$foaf->load();
$me = $foaf->primaryTopic();
echo "My name is: ".$me->get('foaf:name')."\n";
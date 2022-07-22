<?php
define( 'DATA_PATH','/Volumes/DATOS MAC/Users/job/Sites/factura/facturacion/Backend/data');
require_once 'Validador.php';
$xml= file_get_contents('cfdi.xml');
$validador= new Validador($xml);
$validador->validarEstructura();
// $valido = $validador->validarEstructura();
// var_dump($valido);
var_dump($validador->validarSello());

<?php
/**
 *  @package PHPonTrax
 *  @uses Dispatcher::dispatch()
 */

/**
 *  Load the Trax environment
 */
require_once("environment.php");

$dispatcher = new Dispatcher();
$dispatcher->dispatch();

?>
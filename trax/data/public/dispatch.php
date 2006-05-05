<?php
/**
 *  @package PHPonTrax
 *  @uses Dispatcher::dispatch()
 *  $Id$
 */

/**
 *  Load the Trax environment
 */
require_once("environment.php");

$dispatcher = new Dispatcher();
$dispatcher->dispatch();

?>
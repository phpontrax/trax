<?php
/**
 *  Test routes table
 *
 *  @package PHPonTraxTest
 *  @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *  @copyright (c) Walter O. Haas 2006
 *  @version $Id$
 *  @author Walt Haas <haas@xmission.com>
 */
$router->connect( "products/:id",
                   array(":controller" => "catalog",
                         ":action"     => "view") ); 
$router->connect( ":controller/:action/:id" );

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
<?                       
# Add your own custom routes here.
# The priority is based upon order of creation: first created -> highest priority.

# Here's a sample route:
# $router->connect( "products/:id", array(":controller" => "catalog", ":action" => "view") );

# You can have the root of your site routed by hooking up "".
# Just remember to delete public_html/index.html.
# $router->connect( "", array(":controller" => "welcome") );

# Install the default route as the lowest priority.
$router->connect( ":controller/:action/:id" );

?>
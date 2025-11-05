<?php
/*********************************************************************
    wildcard.php

    Wildcard API endpoint for osTicket
    Allows API keys with IP 0.0.0.0 to accept requests from any IP

    SECURITY WARNING: Only use in development environments!

    Part of: API Key Wildcard Plugin

    Usage:
      POST /api/wildcard/tickets.json
      (instead of /api/tickets.json)

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

// Bootstrap osTicket (this loads class.api.php automatically)
file_exists('../main.inc.php') or die('System Error');

define('API_SESSION', true);
define('APICALL', true);

require_once('../main.inc.php');
require_once(INCLUDE_DIR.'class.http.php');

// Load our extended ApiController
require_once INCLUDE_DIR.'plugins/api-key-wildcard/api.wildcard.inc.php';

// Now we need to load TicketApiController but make it use our WildcardApiController
// We do this by creating a wrapper

require_once INCLUDE_DIR.'api.tickets.php';

// Create a Wildcard version of TicketApiController
class WildcardTicketApiController extends TicketApiController {
    // Use the wildcard API key validation
    function requireApiKey() {
        $validator = new WildcardApiController();
        return $validator->requireApiKey();
    }
}

// Include dispatcher
require_once INCLUDE_DIR."class.dispatcher.php";

$dispatcher = patterns('',
    url_post("^/tickets\.(?P<format>xml|json|email)$",
        function($format) {
            $controller = new WildcardTicketApiController();
            return $controller->create($format);
        })
);

Signal::send('api', $dispatcher);
print $dispatcher->resolve(Osticket::get_path_info());
?>

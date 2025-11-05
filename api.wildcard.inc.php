<?php
/**
 * Wildcard API Include
 * 
 * This file provides wrapper functions to enable wildcard IP support (0.0.0.0)
 * without redefining the core API classes.
 * 
 * Since main.inc.php already loads class.api.php, we can't redeclare the classes.
 * Instead, we monkey-patch the methods we need at runtime.
 */

// Original API class is already loaded by main.inc.php
// We'll create wrapper/helper functions instead

/**
 * Wildcard-aware API key lookup
 * 
 * This function wraps the standard API::getIdByKey() with wildcard support
 */
function wildcard_getIdByKey($key, $ip='') {
    $sql='SELECT id FROM '.API_KEY_TABLE.' WHERE apikey='.db_input($key);
    
    // WILDCARD SUPPORT: Allow either exact IP match OR 0.0.0.0
    if($ip) {
        $sql.=' AND (ipaddr='.db_input($ip).' OR ipaddr="0.0.0.0")';
    }

    if(($res=db_query($sql)) && db_num_rows($res))
        list($id) = db_fetch_row($res);

    return $id;
}

/**
 * Extended ApiController with wildcard support
 * 
 * This extends the core ApiController to support 0.0.0.0 as wildcard IP
 */
class WildcardApiController extends ApiController {
    
    /**
     * Require API key with wildcard support
     */
    function requireApiKey() {
        // Get API key from header
        if (!($api_key=$this->getApiKey()))
            return $this->exerr(401, __('Valid API key required'));
        
        // Use our wildcard-aware lookup
        $key_id = wildcard_getIdByKey($api_key, $this->getRemoteAddr());
        
        if (!$key_id) {
            return $this->exerr(401, __('API key not found or IP not authorized'));
        }
        
        // Load the API key object
        $key = API::lookup($key_id);
        
        if (!$key || !$key->isActive()) {
            return $this->exerr(401, __('API key not active'));
        }
        
        // Check IP - allow 0.0.0.0 as wildcard
        $keyIp = $key->getIPAddr();
        $remoteIp = $this->getRemoteAddr();
        
        if ($keyIp != '0.0.0.0' && $keyIp != $remoteIp) {
            return $this->exerr(401, __('Source IP not authorized'));
        }
        
        // Log wildcard usage for security auditing
        if ($keyIp == '0.0.0.0') {
            global $ost;
            $ost->logDebug(
                'Wildcard API Key Used',
                sprintf('API key with wildcard IP (0.0.0.0) was used from %s', $remoteIp)
            );
        }
        
        return $key;
    }
}

// Override ApiController for ticket creation
// Note: We use class_alias to make WildcardApiController available as ApiController
// within the scope of wildcard.php

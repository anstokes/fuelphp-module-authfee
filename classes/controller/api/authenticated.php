<?php

namespace AuthFee\Controller\Api;

use Anstech\Rest\Controller\Cors;

class Authenticated extends Cors
{
    // Authentication method
    protected $auth = 'auth';

    // Allowed headers, origin
    protected static $headers = 'Authorization, Content-Type';
    protected static $origin = 'http://localhost:3000';

    // Any required claims
    protected static $required_claims = null;


    /**
     * Check the claims provided by the token against the claims required by the API
     *
     * @param type $claims
     * @return boolean
     */
    protected function requiredClaims($claims)
    {
        // Check if claims are required
        if ($required_claims = static::$required_claims) {
            // Loop through requirements
            foreach ($required_claims as $claim_key => $claim_value) {
                // Check for claim
                if ($claims->get($claim_key) != $claim_value) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Check the JWT token, and relevant claims
     *
     * @return boolean
     */
    protected function auth()
    {
        // Read JWT
        $token = Model_Jwt::read_authorisation_header();
        $claims = Model_Jwt::validate($token);

        if ($token && $claims && static::required_claims($claims)) {
            return true;
        }

        // Not authenticated by default
        return false;
    }
}

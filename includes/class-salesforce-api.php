<?php
/**
 * Salesforce API - Interface avec Salesforce REST API
 *
 * @package iSonic_Analytics
 */

class Isonic_Salesforce_API {
    
    private $instance_url;
    private $access_token;
    private $consumer_key;
    private $consumer_secret;
    private $username;
    private $password;
    private $security_token;
    private $org_type; // 'primary' or 'secondary'
    
    /**
     * Constructor
     * 
     * @param string $org_type 'primary' (default) or 'secondary'
     */
    public function __construct( $org_type = 'primary' ) {
        $this->org_type = $org_type;
        
        // Charger les credentials selon l'org type
        if ( $org_type === 'secondary' ) {
            $this->consumer_key = get_option( 'isonic_sf_secondary_consumer_key', '' );
            $this->consumer_secret = get_option( 'isonic_sf_secondary_consumer_secret', '' );
            $this->username = get_option( 'isonic_sf_secondary_username', '' );
            $this->password = get_option( 'isonic_sf_secondary_password', '' );
            $this->security_token = get_option( 'isonic_sf_secondary_security_token', '' );
            $this->instance_url = get_option( 'isonic_sf_secondary_instance_url', 'https://isonic.lightning.force.com' );
        } else {
            // Primary org (par défaut)
            $this->consumer_key = get_option( 'isonic_sf_primary_consumer_key', '' ) ?: get_option( 'isonic_sf_consumer_key', '' );
            $this->consumer_secret = get_option( 'isonic_sf_primary_consumer_secret', '' ) ?: get_option( 'isonic_sf_consumer_secret', '' );
            $this->username = get_option( 'isonic_sf_primary_username', '' ) ?: get_option( 'isonic_sf_username', '' );
            $this->password = get_option( 'isonic_sf_primary_password', '' ) ?: get_option( 'isonic_sf_password', '' );
            $this->security_token = get_option( 'isonic_sf_primary_security_token', '' ) ?: get_option( 'isonic_sf_security_token', '' );
            $this->instance_url = get_option( 'isonic_sf_primary_instance_url', '' ) ?: get_option( 'isonic_sf_instance_url', 'https://isonic-ai.my.salesforce.com' );
        }
        
        $this->authenticate();
    }
    
    /**
     * Authentification OAuth2 (Username-Password Flow)
     */
    private function authenticate() {
        // Vérifier si on a un token valide en cache (séparé par org)
        $cache_key = 'isonic_sf_access_token_' . $this->org_type;
        $cached_token = get_transient( $cache_key );
        if ( $cached_token ) {
            $this->access_token = $cached_token;
            Isonic_Logger::log_info( sprintf( '[%s] Using cached Salesforce access token', strtoupper( $this->org_type ) ) );
            return true;
        }
        
        // Sinon, obtenir un nouveau token
        return $this->get_new_access_token();
    }
    
    /**
     * Obtenir un nouveau Access Token
     */
    private function get_new_access_token() {
        if ( empty( $this->consumer_key ) || empty( $this->consumer_secret ) || 
             empty( $this->username ) || empty( $this->password ) ) {
            Isonic_Logger::log_error( sprintf( '[%s] Salesforce credentials missing', strtoupper( $this->org_type ) ) );
            return false;
        }
        
        $login_url = 'https://login.salesforce.com/services/oauth2/token';
        
        $params = [
            'grant_type' => 'password',
            'client_id' => $this->consumer_key,
            'client_secret' => $this->consumer_secret,
            'username' => $this->username,
            'password' => $this->password . $this->security_token,
        ];
        
        $response = wp_remote_post( $login_url, [
            'body' => $params,
            'timeout' => 30,
        ]);
        
        if ( is_wp_error( $response ) ) {
            Isonic_Logger::log_error( sprintf( '[%s] Salesforce OAuth error: %s', strtoupper( $this->org_type ), $response->get_error_message() ) );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
            
            // Mettre en cache pour 1 heure (cache séparé par org)
            $cache_key = 'isonic_sf_access_token_' . $this->org_type;
            set_transient( $cache_key, $this->access_token, HOUR_IN_SECONDS );
            
            Isonic_Logger::log_info( sprintf( '[%s] Salesforce authentication successful', strtoupper( $this->org_type ) ) );
            return true;
        }
        
        Isonic_Logger::log_error( sprintf( '[%s] Salesforce OAuth failed: %s', strtoupper( $this->org_type ), print_r( $body, true ) ) );
        return false;
    }
    
    /**
     * Test de connexion Salesforce
     */
    public function test_connection() {
        // Forcer un nouveau token (cache séparé par org)
        $cache_key = 'isonic_sf_access_token_' . $this->org_type;
        delete_transient( $cache_key );
        
        if ( ! $this->get_new_access_token() ) {
            return [
                'success' => false,
                'message' => sprintf( '[%s] Authentication failed. Check credentials.', strtoupper( $this->org_type ) ),
            ];
        }
        
        // Tester un appel API simple
        $url = $this->instance_url . '/services/data/v62.0/sobjects';
        
        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'timeout' => 15,
        ]);
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => sprintf( '[%s] API call failed: %s', strtoupper( $this->org_type ), $response->get_error_message() ),
            ];
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        
        if ( $code === 200 ) {
            return [
                'success' => true,
                'message' => sprintf( '[%s] Connection successful! Salesforce API is reachable. (%s)', strtoupper( $this->org_type ), $this->instance_url ),
            ];
        }
        
        return [
            'success' => false,
            'message' => sprintf( '[%s] API returned status code: %d', strtoupper( $this->org_type ), $code ),
        ];
    }
    
    /**
     * Crée un Lead dans Salesforce
     *
     * @param array $lead_data Données du lead
     * @return string|false Lead ID ou false si échec
     */
    public function create_lead( $lead_data ) {
        $url = $this->instance_url . '/services/data/v62.0/sobjects/Lead';
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $lead_data ),
            'timeout' => 30,
        ]);
        
        if ( is_wp_error( $response ) ) {
            Isonic_Logger::log_error( sprintf( '[%s] Salesforce API error: %s', strtoupper( $this->org_type ), $response->get_error_message() ) );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['success'] ) && $body['success'] ) {
            Isonic_Logger::log_info( sprintf( '[%s] Lead created: %s', strtoupper( $this->org_type ), $body['id'] ) );
            return $body['id'];
        }
        
        Isonic_Logger::log_error( sprintf( '[%s] Salesforce Lead creation failed: %s', strtoupper( $this->org_type ), print_r( $body, true ) ) );
        return false;
    }
    
    /**
     * Crée un CampaignMember
     *
     * @param string $campaign_id Campaign ID
     * @param string $lead_id Lead ID
     * @return bool Success
     */
    public function create_campaign_member( $campaign_id, $lead_id ) {
        $url = $this->instance_url . '/services/data/v62.0/sobjects/CampaignMember';
        
        $member_data = [
            'CampaignId' => $campaign_id,
            'LeadId' => $lead_id,
            'Status' => 'Sent',
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $member_data ),
            'timeout' => 30,
        ]);
        
        if ( is_wp_error( $response ) ) {
            Isonic_Logger::log_error( sprintf( '[%s] CampaignMember creation error: %s', strtoupper( $this->org_type ), $response->get_error_message() ) );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['success'] ) && $body['success'] ) {
            Isonic_Logger::log_info( sprintf( '[%s] CampaignMember created for Lead %s', strtoupper( $this->org_type ), $lead_id ) );
            return true;
        }
        
        return false;
    }
}

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
    
    public function __construct() {
        $this->consumer_key = get_option( 'isonic_sf_consumer_key', '' );
        $this->consumer_secret = get_option( 'isonic_sf_consumer_secret', '' );
        $this->username = get_option( 'isonic_sf_username', '' );
        $this->password = get_option( 'isonic_sf_password', '' );
        $this->security_token = get_option( 'isonic_sf_security_token', '' );
        $this->instance_url = get_option( 'isonic_sf_instance_url', 'https://isonic-ai.my.salesforce.com' );
        
        $this->authenticate();
    }
    
    /**
     * Authentification OAuth2 (Username-Password Flow)
     */
    private function authenticate() {
        // Vérifier si on a un token valide en cache
        $cached_token = get_transient( 'isonic_sf_access_token' );
        if ( $cached_token ) {
            $this->access_token = $cached_token;
            Isonic_Logger::log_info( 'Using cached Salesforce access token' );
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
            Isonic_Logger::log_error( 'Salesforce credentials missing' );
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
            Isonic_Logger::log_error( 'Salesforce OAuth error: ' . $response->get_error_message() );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
            
            // Mettre en cache pour 1 heure
            set_transient( 'isonic_sf_access_token', $this->access_token, HOUR_IN_SECONDS );
            
            Isonic_Logger::log_info( 'Salesforce authentication successful' );
            return true;
        }
        
        Isonic_Logger::log_error( 'Salesforce OAuth failed: ' . print_r( $body, true ) );
        return false;
    }
    
    /**
     * Test de connexion Salesforce
     */
    public function test_connection() {
        // Forcer un nouveau token
        delete_transient( 'isonic_sf_access_token' );
        
        if ( ! $this->get_new_access_token() ) {
            return [
                'success' => false,
                'message' => 'Authentication failed. Check credentials.',
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
                'message' => 'API call failed: ' . $response->get_error_message(),
            ];
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        
        if ( $code === 200 ) {
            return [
                'success' => true,
                'message' => 'Connection successful! Salesforce API is reachable.',
            ];
        }
        
        return [
            'success' => false,
            'message' => 'API returned status code: ' . $code,
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
            Isonic_Logger::log_error( 'Salesforce API error: ' . $response->get_error_message() );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['success'] ) && $body['success'] ) {
            Isonic_Logger::log_info( 'Lead created: ' . $body['id'] );
            return $body['id'];
        }
        
        Isonic_Logger::log_error( 'Salesforce Lead creation failed: ' . print_r( $body, true ) );
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
            Isonic_Logger::log_error( 'CampaignMember creation error: ' . $response->get_error_message() );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['success'] ) && $body['success'] ) {
            Isonic_Logger::log_info( 'CampaignMember created for Lead ' . $lead_id );
            return true;
        }
        
        return false;
    }
}

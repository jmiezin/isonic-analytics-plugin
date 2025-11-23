<?php
/**
 * Salesforce API - Interface avec Salesforce REST API
 *
 * @package iSonic_Analytics
 */

class Isonic_Salesforce_API {
    
    private $instance_url;
    private $access_token;
    
    public function __construct() {
        $this->authenticate();
    }
    
    /**
     * Authentification OAuth2
     */
    private function authenticate() {
        // TODO: Implémenter OAuth2 flow
        // Pour l'instant, stocker token dans options
        $this->access_token = get_option( 'isonic_sf_access_token', '' );
        $this->instance_url = get_option( 'isonic_sf_instance_url', '' );
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

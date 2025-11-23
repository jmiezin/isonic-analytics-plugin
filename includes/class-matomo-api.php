<?php
/**
 * Matomo API - Interface avec l'API Matomo
 *
 * @package iSonic_Analytics
 */

class Isonic_Matomo_API {
    
    private $matomo_url;
    private $site_id;
    private $auth_token;
    
    public function __construct() {
        $this->matomo_url = get_option( 'isonic_matomo_url', '' );
        $this->site_id = get_option( 'isonic_matomo_site_id', 1 );
        $this->auth_token = get_option( 'isonic_matomo_auth_token', '' );
    }
    
    /**
     * Récupère les données visiteur depuis Matomo
     *
     * @return array|false Données visiteur ou false si échec
     */
    public function get_visitor_history() {
        // Récupérer Visitor ID depuis cookie Matomo
        $visitor_id = $this->get_visitor_id_from_cookie();
        
        if ( ! $visitor_id ) {
            Isonic_Logger::log_warning( 'No Matomo visitor ID found in cookies' );
            return false;
        }
        
        // Appeler API Matomo
        $api_url = add_query_arg( [
            'module' => 'API',
            'method' => 'Live.getLastVisitsDetails',
            'idSite' => $this->site_id,
            'visitorId' => $visitor_id,
            'format' => 'JSON',
            'token_auth' => $this->auth_token,
        ], $this->matomo_url . '/index.php' );
        
        $response = wp_remote_get( $api_url, [
            'timeout' => 10,
        ]);
        
        if ( is_wp_error( $response ) ) {
            Isonic_Logger::log_error( 'Matomo API error: ' . $response->get_error_message() );
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( empty( $data ) || ! isset( $data[0] ) ) {
            Isonic_Logger::log_warning( 'No visit data from Matomo for visitor ' . $visitor_id );
            return false;
        }
        
        // Parser les données
        return $this->parse_matomo_data( $data );
    }
    
    /**
     * Récupère le Visitor ID depuis le cookie Matomo
     */
    private function get_visitor_id_from_cookie() {
        // Cookie Matomo format: _pk_id.{SITE_ID}.{HASH}
        foreach ( $_COOKIE as $name => $value ) {
            if ( strpos( $name, '_pk_id.' . $this->site_id ) === 0 ) {
                $parts = explode( '.', $value );
                return $parts[0] ?? null;
            }
        }
        return null;
    }
    
    /**
     * Parse les données Matomo
     */
    private function parse_matomo_data( $visits ) {
        // TODO: Implémenter parsing complet
        // Pour l'instant, retourner structure de base
        
        $total_time = 0;
        $total_pages = 0;
        $first_visit = null;
        $journey = [];
        
        foreach ( $visits as $visit ) {
            $total_time += $visit['visitDuration'] ?? 0;
            $total_pages += $visit['actions'] ?? 0;
            
            if ( ! $first_visit || $visit['firstActionTimestamp'] < $first_visit ) {
                $first_visit = $visit['firstActionTimestamp'];
            }
            
            // Ajouter les pages au parcours
            if ( isset( $visit['actionDetails'] ) ) {
                foreach ( $visit['actionDetails'] as $action ) {
                    $journey[] = $action['url'] ?? '';
                }
            }
        }
        
        return [
            'time_spent' => $this->format_duration( $total_time ),
            'visit_count' => count( $visits ),
            'pages_viewed' => $total_pages,
            'first_visit_date' => date( 'Y-m-d', $first_visit ),
            'entry_page' => $visits[0]['actionDetails'][0]['url'] ?? '',
            'journey' => implode( "\n", $journey ),
            'source' => $visits[0]['referrerName'] ?? 'Direct',
            'medium' => $visits[0]['referrerType'] ?? 'direct',
            'keyword' => $visits[0]['referrerKeyword'] ?? '',
        ];
    }
    
    /**
     * Formate une durée en secondes
     */
    private function format_duration( $seconds ) {
        $hours = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;
        
        if ( $hours > 0 ) {
            return sprintf( '%dh %02dmin %02ds', $hours, $minutes, $secs );
        } elseif ( $minutes > 0 ) {
            return sprintf( '%d min %02d sec', $minutes, $secs );
        } else {
            return sprintf( '%d sec', $secs );
        }
    }
}

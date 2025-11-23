<?php
/**
 * Matomo API - Interface avec l'API Matomo
 * Supporte : Matomo WordPress Plugin (natif) et Matomo externe (HTTP API)
 *
 * @package iSonic_Analytics
 */

class Isonic_Matomo_API {
    
    private $matomo_url;
    private $site_id;
    private $auth_token;
    private $use_wordpress_plugin = false;
    
    public function __construct() {
        $this->matomo_url = get_option( 'isonic_matomo_url', '' );
        $this->site_id = get_option( 'isonic_matomo_site_id', 1 );
        $this->auth_token = get_option( 'isonic_matomo_auth_token', '' );
        
        // Détecter si Matomo WordPress Plugin est installé
        if ( class_exists( '\WpMatomo\Tracker' ) || class_exists( 'WpMatomo' ) || defined( 'MATOMO_ANALYTICS_FILE' ) ) {
            $this->use_wordpress_plugin = true;
            Isonic_Logger::log_info( 'Matomo WordPress Plugin detected - using native integration' );
        }
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
        
        // Si Matomo WordPress Plugin est installé, utiliser l'API native
        if ( $this->use_wordpress_plugin ) {
            return $this->get_visitor_history_wordpress( $visitor_id );
        }
        
        // Sinon, utiliser l'API HTTP classique
        return $this->get_visitor_history_http( $visitor_id );
    }
    
    /**
     * Récupère les données visiteur via Matomo WordPress Plugin (natif)
     *
     * @param string $visitor_id Matomo Visitor ID
     * @return array|false Données visiteur ou false si échec
     */
    private function get_visitor_history_wordpress( $visitor_id ) {
        try {
            // Utiliser l'API Matomo WordPress directement (pas besoin de token)
            if ( ! class_exists( '\Piwik\API\Request' ) && file_exists( WP_CONTENT_DIR . '/plugins/matomo/app/core/API/Request.php' ) ) {
                require_once WP_CONTENT_DIR . '/plugins/matomo/app/core/API/Request.php';
            }
            
            // Appeler l'API Matomo via PHP (pas HTTP)
            $params = [
                'method' => 'Live.getLastVisitsDetails',
                'idSite' => $this->site_id,
                'visitorId' => $visitor_id,
                'format' => 'json',
                'filter_limit' => 10,
            ];
            
            // Utiliser la fonction Matomo WordPress si disponible
            if ( function_exists( 'matomo_get_api_data' ) ) {
                $data = matomo_get_api_data( $params );
            } elseif ( class_exists( '\Piwik\API\Request' ) ) {
                $data = \Piwik\API\Request::processRequest( 'Live.getLastVisitsDetails', $params );
                $data = json_decode( $data, true );
            } else {
                Isonic_Logger::log_warning( 'Matomo WordPress Plugin API not accessible, falling back to HTTP' );
                return $this->get_visitor_history_http( $visitor_id );
            }
            
            if ( empty( $data ) || ! is_array( $data ) ) {
                Isonic_Logger::log_warning( 'No visit data from Matomo WordPress Plugin for visitor ' . $visitor_id );
                return false;
            }
            
            Isonic_Logger::log_info( 'Successfully retrieved visitor data from Matomo WordPress Plugin' );
            return $this->parse_matomo_data( $data );
            
        } catch ( Exception $e ) {
            Isonic_Logger::log_error( 'Matomo WordPress Plugin error: ' . $e->getMessage() );
            // Fallback to HTTP API
            return $this->get_visitor_history_http( $visitor_id );
        }
    }
    
    /**
     * Récupère les données visiteur via HTTP API (méthode classique)
     *
     * @param string $visitor_id Matomo Visitor ID
     * @return array|false Données visiteur ou false si échec
     */
    private function get_visitor_history_http( $visitor_id ) {
        if ( empty( $this->matomo_url ) || empty( $this->auth_token ) ) {
            Isonic_Logger::log_error( 'Matomo HTTP API: URL or Auth Token missing' );
            return false;
        }
        
        // Appeler API Matomo via HTTP
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
            Isonic_Logger::log_warning( 'No visit data from Matomo HTTP API for visitor ' . $visitor_id );
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
    
    /**
     * Test de connexion Matomo
     */
    public function test_connection() {
        // Si Matomo WordPress Plugin est installé
        if ( $this->use_wordpress_plugin ) {
            return $this->test_connection_wordpress();
        }
        
        // Sinon, tester via HTTP API
        return $this->test_connection_http();
    }
    
    /**
     * Test de connexion Matomo WordPress Plugin
     */
    private function test_connection_wordpress() {
        try {
            // Vérifier que le plugin Matomo est bien actif
            if ( ! class_exists( '\WpMatomo\Tracker' ) && ! class_exists( 'WpMatomo' ) && ! defined( 'MATOMO_ANALYTICS_FILE' ) ) {
                return [
                    'success' => false,
                    'message' => 'Matomo WordPress Plugin not detected',
                ];
            }
            
            // Tester l'accès au Site ID
            $site_name = 'Site ' . $this->site_id;
            
            // Si on peut accéder aux fonctions Matomo
            if ( function_exists( 'matomo_get_site_name' ) ) {
                $site_name = matomo_get_site_name( $this->site_id );
            }
            
            return [
                'success' => true,
                'message' => 'Matomo WordPress Plugin detected and ready! Site ID: ' . $this->site_id . ' (' . $site_name . '). No Auth Token needed.',
            ];
            
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => 'Matomo WordPress Plugin error: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Test de connexion Matomo HTTP API
     */
    private function test_connection_http() {
        if ( empty( $this->matomo_url ) || empty( $this->auth_token ) ) {
            return [
                'success' => false,
                'message' => 'Matomo URL or Auth Token is missing (required for external Matomo).',
            ];
        }
        
        // Tester un appel API simple
        $api_url = add_query_arg( [
            'module' => 'API',
            'method' => 'SitesManager.getSiteFromId',
            'idSite' => $this->site_id,
            'format' => 'JSON',
            'token_auth' => $this->auth_token,
        ], $this->matomo_url . '/index.php' );
        
        $response = wp_remote_get( $api_url, [
            'timeout' => 10,
        ]);
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message(),
            ];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        // Vérifier s'il y a une erreur d'authentification
        if ( isset( $data['result'] ) && $data['result'] === 'error' ) {
            return [
                'success' => false,
                'message' => 'Authentication failed: ' . ( $data['message'] ?? 'Invalid token' ),
            ];
        }
        
        // Vérifier si on a bien les données du site
        if ( isset( $data['idsite'] ) && $data['idsite'] == $this->site_id ) {
            return [
                'success' => true,
                'message' => 'Connection successful! Site: ' . ( $data['name'] ?? 'Unknown' ) . ' (External Matomo via HTTP API)',
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Unexpected response from Matomo API',
        ];
    }
}

<?php
/**
 * Logger - Gestion des logs
 *
 * @package iSonic_Analytics
 */

class Isonic_Logger {
    
    /**
     * Log an info message
     */
    public static function log_info( $message ) {
        if ( WP_DEBUG && WP_DEBUG_LOG ) {
            error_log( '[iSonic Analytics INFO] ' . $message );
        }
    }
    
    /**
     * Log an error message
     */
    public static function log_error( $message ) {
        error_log( '[iSonic Analytics ERROR] ' . $message );
    }
    
    /**
     * Log a warning message
     */
    public static function log_warning( $message ) {
        if ( WP_DEBUG && WP_DEBUG_LOG ) {
            error_log( '[iSonic Analytics WARNING] ' . $message );
        }
    }
    
    /**
     * Log form submission data
     */
    public static function log_submission( $form_id, $form_title, $lead_id, $campaign_id ) {
        self::log_info( sprintf(
            'Form "%s" (ID: %d) → Lead %s → Campaign %s',
            $form_title,
            $form_id,
            $lead_id,
            $campaign_id
        ));
    }
}


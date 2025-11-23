<?php
/**
 * Plugin Name: iSonic Analytics Enrichment
 * Plugin URI: https://isonic.fr
 * Description: Enrichit automatiquement les soumissions Gravity Forms avec les données Matomo avant envoi à Salesforce
 * Version: 1.0.0
 * Author: iSonic
 * Author URI: https://isonic.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: isonic-analytics
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin version
 */
define( 'ISONIC_ANALYTICS_VERSION', '1.0.0' );
define( 'ISONIC_ANALYTICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ISONIC_ANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Campaign IDs Salesforce - NOUVELLE ORG (Primary - Production)
 * URL: https://isonic-ai.my.salesforce.com
 */
define( 'ISONIC_PRIMARY_CAMPAIGN_SITE_WEB', '701Jv00000oEi1EIAS' );
define( 'ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE', '701Jv00000oEgv7IAC' );

/**
 * Campaign IDs Salesforce - ANCIENNE ORG (Secondary - Migration)
 * URL: https://isonic.lightning.force.com/
 */
define( 'ISONIC_SECONDARY_CAMPAIGN_SITE_WEB', '7013X000001msrWQAQ' );
define( 'ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE', '701IV00000xTZBhYAO' );

/**
 * Backward compatibility (utilise Primary par défaut)
 */
define( 'ISONIC_CAMPAIGN_SITE_WEB', ISONIC_PRIMARY_CAMPAIGN_SITE_WEB );
define( 'ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE', ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE );

/**
 * Gravity Forms ID pour "Inscription Isonic"
 */
define( 'ISONIC_FORM_ID_INSCRIPTION', 1 );

/**
 * Autoload classes
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'Isonic_';
    $base_dir = ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/';
    
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    $relative_class = substr( $class, $len );
    $file = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
    
    if ( file_exists( $file ) ) {
        require $file;
    }
});

/**
 * Load core files
 */
require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/class-logger.php';
require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/class-matomo-api.php';
require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/class-salesforce-api.php';
require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/class-campaign-mapper.php';
require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'includes/class-form-enricher.php';

/**
 * Load admin files
 */
if ( is_admin() ) {
    require_once ISONIC_ANALYTICS_PLUGIN_DIR . 'admin/settings-page.php';
}

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'isonic_analytics_activate' );

function isonic_analytics_activate() {
    // Set default options
    add_option( 'isonic_analytics_version', ISONIC_ANALYTICS_VERSION );
    add_option( 'isonic_matomo_enabled', false );
    add_option( 'isonic_salesforce_enabled', false );
    
    Isonic_Logger::log_info( 'Plugin activated - Version ' . ISONIC_ANALYTICS_VERSION );
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, 'isonic_analytics_deactivate' );

function isonic_analytics_deactivate() {
    Isonic_Logger::log_info( 'Plugin deactivated' );
}

/**
 * Initialize the plugin
 */
add_action( 'plugins_loaded', 'isonic_analytics_init' );

function isonic_analytics_init() {
    // Check dependencies
    if ( ! class_exists( 'GFForms' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo '<strong>iSonic Analytics:</strong> Gravity Forms is required.';
            echo '</p></div>';
        });
        return;
    }
    
    // Hook Gravity Forms submissions
    if ( get_option( 'isonic_salesforce_enabled' ) ) {
        Isonic_Form_Enricher::init();
    }
    
    Isonic_Logger::log_info( 'Plugin initialized' );
}


<?php
/**
 * Settings Page - Page de configuration admin
 *
 * @package iSonic_Analytics
 */

add_action( 'admin_menu', 'isonic_analytics_add_settings_page' );

function isonic_analytics_add_settings_page() {
    add_options_page(
        'iSonic Analytics Settings',
        'iSonic Analytics',
        'manage_options',
        'isonic-analytics',
        'isonic_analytics_render_settings_page'
    );
}

function isonic_analytics_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>iSonic Analytics - Configuration</h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields( 'isonic_analytics_settings' );
            do_settings_sections( 'isonic-analytics' );
            submit_button();
            ?>
        </form>
        
        <hr>
        
        <h2>Campaign IDs Salesforce</h2>
        <table class="widefat">
            <tr>
                <th>Campaign</th>
                <th>ID</th>
            </tr>
            <tr>
                <td>Site web isonic.fr</td>
                <td><code><?php echo ISONIC_CAMPAIGN_SITE_WEB; ?></code></td>
            </tr>
            <tr>
                <td>Contenu pédagogique</td>
                <td><code><?php echo ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE; ?></code></td>
            </tr>
        </table>
        
        <hr>
        
        <h2>Status</h2>
        <p>
            <strong>Matomo:</strong> 
            <?php echo get_option( 'isonic_matomo_enabled' ) ? '✅ Activé' : '❌ Désactivé'; ?>
        </p>
        <p>
            <strong>Salesforce:</strong> 
            <?php echo get_option( 'isonic_salesforce_enabled' ) ? '✅ Activé' : '❌ Désactivé'; ?>
        </p>
    </div>
    <?php
}

// Register settings
add_action( 'admin_init', 'isonic_analytics_register_settings' );

function isonic_analytics_register_settings() {
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_site_id' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_auth_token' );
    
    register_setting( 'isonic_analytics_settings', 'isonic_salesforce_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_instance_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_access_token' );
    
    add_settings_section(
        'isonic_matomo_section',
        'Configuration Matomo',
        null,
        'isonic-analytics'
    );
    
    add_settings_field(
        'isonic_matomo_enabled',
        'Activer Matomo',
        'isonic_analytics_checkbox_field',
        'isonic-analytics',
        'isonic_matomo_section',
        [ 'option_name' => 'isonic_matomo_enabled' ]
    );
    
    add_settings_field(
        'isonic_matomo_url',
        'URL Matomo',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_matomo_section',
        [ 'option_name' => 'isonic_matomo_url', 'placeholder' => 'https://matomo.isonic.fr' ]
    );
    
    add_settings_section(
        'isonic_sf_section',
        'Configuration Salesforce',
        null,
        'isonic-analytics'
    );
    
    add_settings_field(
        'isonic_salesforce_enabled',
        'Activer Salesforce',
        'isonic_analytics_checkbox_field',
        'isonic-analytics',
        'isonic_sf_section',
        [ 'option_name' => 'isonic_salesforce_enabled' ]
    );
}

function isonic_analytics_text_field( $args ) {
    $value = get_option( $args['option_name'], '' );
    printf(
        '<input type="text" name="%s" value="%s" placeholder="%s" class="regular-text">',
        esc_attr( $args['option_name'] ),
        esc_attr( $value ),
        esc_attr( $args['placeholder'] ?? '' )
    );
}

function isonic_analytics_checkbox_field( $args ) {
    $value = get_option( $args['option_name'], false );
    printf(
        '<input type="checkbox" name="%s" value="1" %s>',
        esc_attr( $args['option_name'] ),
        checked( $value, true, false )
    );
}

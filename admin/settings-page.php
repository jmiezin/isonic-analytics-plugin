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
    // Handle AJAX test connections
    if ( isset( $_POST['test_matomo'] ) ) {
        check_admin_referer( 'isonic_test_matomo' );
        $matomo_api = new Isonic_Matomo_API();
        $result = $matomo_api->test_connection();
        echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . ' is-dismissible">';
        echo '<p><strong>Matomo Test:</strong> ' . esc_html( $result['message'] ) . '</p>';
        echo '</div>';
    }
    
    if ( isset( $_POST['test_salesforce_primary'] ) ) {
        check_admin_referer( 'isonic_test_salesforce_primary' );
        $sf_api = new Isonic_Salesforce_API( 'primary' );
        $result = $sf_api->test_connection();
        echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . ' is-dismissible">';
        echo '<p><strong>Salesforce PRIMARY Test:</strong> ' . esc_html( $result['message'] ) . '</p>';
        echo '</div>';
    }
    
    if ( isset( $_POST['test_salesforce_secondary'] ) ) {
        check_admin_referer( 'isonic_test_salesforce_secondary' );
        $sf_api = new Isonic_Salesforce_API( 'secondary' );
        $result = $sf_api->test_connection();
        echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . ' is-dismissible">';
        echo '<p><strong>Salesforce SECONDARY Test:</strong> ' . esc_html( $result['message'] ) . '</p>';
        echo '</div>';
    }
    
    ?>
    <div class="wrap">
        <h1>⚙️ iSonic Analytics - Configuration</h1>
        <p>Configurez l'intégration Matomo → Salesforce pour enrichir vos Leads automatiquement.</p>
        
        <form method="post" action="options.php">
            <?php
            settings_fields( 'isonic_analytics_settings' );
            do_settings_sections( 'isonic-analytics' );
            submit_button( 'Enregistrer la configuration', 'primary', 'submit', true );
            ?>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>🧪 Test de Connexion</h2>
        <p>Testez vos configurations avant activation.</p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Matomo API</th>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field( 'isonic_test_matomo' ); ?>
                        <button type="submit" name="test_matomo" class="button button-secondary">
                            🔍 Tester Matomo
                        </button>
                    </form>
                </td>
            </tr>
            <tr>
                <th scope="row">Salesforce PRIMARY API</th>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field( 'isonic_test_salesforce_primary' ); ?>
                        <button type="submit" name="test_salesforce_primary" class="button button-secondary">
                            🔍 Tester Primary Org
                        </button>
                    </form>
                </td>
            </tr>
            <tr>
                <th scope="row">Salesforce SECONDARY API</th>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field( 'isonic_test_salesforce_secondary' ); ?>
                        <button type="submit" name="test_salesforce_secondary" class="button button-secondary">
                            🔍 Tester Secondary Org
                        </button>
                    </form>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h2>📊 Campaigns Salesforce</h2>
        <p>Mapping automatique : Formulaires → Campaigns (2 orgs)</p>
        
        <h3>🟢 PRIMARY ORG (Nouvelle - Production)</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Formulaire</th>
                    <th>Campaign</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Inscription Isonic</strong> (Form ID: 1)</td>
                    <td>Contenu pédagogique</td>
                    <td><code><?php echo ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE; ?></code></td>
                </tr>
                <tr>
                    <td><strong>Tous les autres formulaires</strong></td>
                    <td>Site web isonic.fr</td>
                    <td><code><?php echo ISONIC_PRIMARY_CAMPAIGN_SITE_WEB; ?></code></td>
                </tr>
            </tbody>
        </table>
        
        <h3 style="margin-top: 20px;">🔄 SECONDARY ORG (Ancienne - Migration)</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Formulaire</th>
                    <th>Campaign</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Inscription Isonic</strong> (Form ID: 1)</td>
                    <td>Contenu pédagogique</td>
                    <td><code><?php echo ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE; ?></code></td>
                </tr>
                <tr>
                    <td><strong>Tous les autres formulaires</strong></td>
                    <td>Site web isonic.fr</td>
                    <td><code><?php echo ISONIC_SECONDARY_CAMPAIGN_SITE_WEB; ?></code></td>
                </tr>
            </tbody>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h2>📈 Status</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Plugin Version</th>
                <td><code><?php echo ISONIC_ANALYTICS_VERSION; ?></code></td>
            </tr>
            <tr>
                <th scope="row">Matomo Integration</th>
                <td>
                    <?php 
                    if ( get_option( 'isonic_matomo_enabled' ) ) {
                        echo '<span style="color: green;">✅ Activé</span>';
                    } else {
                        echo '<span style="color: red;">❌ Désactivé</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Salesforce PRIMARY (Production)</th>
                <td>
                    <?php 
                    if ( get_option( 'isonic_sf_primary_enabled' ) ) {
                        echo '<span style="color: green;">✅ Activé</span>';
                    } else {
                        echo '<span style="color: red;">❌ Désactivé</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Salesforce SECONDARY (Migration)</th>
                <td>
                    <?php 
                    if ( get_option( 'isonic_sf_secondary_enabled' ) ) {
                        echo '<span style="color: orange;">🔄 Activé (migration en cours)</span>';
                    } else {
                        echo '<span style="color: gray;">⚪ Désactivé (migration terminée)</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Gravity Forms</th>
                <td>
                    <?php 
                    if ( class_exists( 'GFForms' ) ) {
                        echo '<span style="color: green;">✅ Détecté</span>';
                    } else {
                        echo '<span style="color: red;">❌ Non installé</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <style>
    .wrap h2 {
        margin-top: 20px;
    }
    .widefat thead th {
        background: #f0f0f1;
    }
    </style>
    <?php
}

// Register settings
add_action( 'admin_init', 'isonic_analytics_register_settings' );

function isonic_analytics_register_settings() {
    // Matomo settings
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_site_id' );
    register_setting( 'isonic_analytics_settings', 'isonic_matomo_auth_token' );
    
    // Salesforce PRIMARY org settings
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_instance_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_consumer_key' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_consumer_secret' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_username' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_password' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_primary_security_token' );
    
    // Salesforce SECONDARY org settings (ancienne org - migration)
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_instance_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_consumer_key' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_consumer_secret' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_username' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_password' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_secondary_security_token' );
    
    // Legacy settings (backward compatibility)
    register_setting( 'isonic_analytics_settings', 'isonic_salesforce_enabled' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_instance_url' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_consumer_key' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_consumer_secret' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_username' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_password' );
    register_setting( 'isonic_analytics_settings', 'isonic_sf_security_token' );
    
    // MATOMO SECTION
    add_settings_section(
        'isonic_matomo_section',
        '📊 Configuration Matomo',
        'isonic_analytics_matomo_section_callback',
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
        [ 
            'option_name' => 'isonic_matomo_url', 
            'placeholder' => 'https://matomo.isonic.fr',
            'description' => 'URL de votre instance Matomo (sans le /index.php)',
        ]
    );
    
    add_settings_field(
        'isonic_matomo_site_id',
        'Site ID',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_matomo_section',
        [ 
            'option_name' => 'isonic_matomo_site_id', 
            'placeholder' => '1',
            'description' => 'ID du site dans Matomo (généralement 1)',
        ]
    );
    
    add_settings_field(
        'isonic_matomo_auth_token',
        'Auth Token',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_matomo_section',
        [ 
            'option_name' => 'isonic_matomo_auth_token',
            'description' => 'Token d\'authentification (Matomo → Personal → Security → Auth Tokens)',
        ]
    );
    
    // SALESFORCE PRIMARY ORG SECTION (Nouvelle org - Production)
    add_settings_section(
        'isonic_sf_primary_section',
        '☁️ Salesforce PRIMARY (Nouvelle Org - Production)',
        'isonic_analytics_salesforce_primary_section_callback',
        'isonic-analytics'
    );
    
    add_settings_field(
        'isonic_sf_primary_enabled',
        'Activer Primary Org',
        'isonic_analytics_checkbox_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 'option_name' => 'isonic_sf_primary_enabled' ]
    );
    
    add_settings_field(
        'isonic_sf_primary_instance_url',
        'Instance URL',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_instance_url', 
            'placeholder' => 'https://isonic-ai.my.salesforce.com',
            'description' => 'URL de la nouvelle org Salesforce',
        ]
    );
    
    add_settings_field(
        'isonic_sf_primary_consumer_key',
        'Consumer Key',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_consumer_key',
            'description' => 'Consumer Key de la Connected App',
        ]
    );
    
    add_settings_field(
        'isonic_sf_primary_consumer_secret',
        'Consumer Secret',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_consumer_secret',
            'description' => 'Consumer Secret de la Connected App',
        ]
    );
    
    add_settings_field(
        'isonic_sf_primary_username',
        'Username',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_username',
            'placeholder' => 'j.miezin@isonic.fr',
            'description' => 'Email de l\'utilisateur Salesforce',
        ]
    );
    
    add_settings_field(
        'isonic_sf_primary_password',
        'Password',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_password',
            'description' => 'Mot de passe Salesforce (sans le Security Token)',
        ]
    );
    
    add_settings_field(
        'isonic_sf_primary_security_token',
        'Security Token',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_primary_section',
        [ 
            'option_name' => 'isonic_sf_primary_security_token',
            'description' => 'Security Token (reçu par email)',
        ]
    );
    
    // SALESFORCE SECONDARY ORG SECTION (Ancienne org - Migration)
    add_settings_section(
        'isonic_sf_secondary_section',
        '🔄 Salesforce SECONDARY (Ancienne Org - Migration)',
        'isonic_analytics_salesforce_secondary_section_callback',
        'isonic-analytics'
    );
    
    add_settings_field(
        'isonic_sf_secondary_enabled',
        'Activer Secondary Org',
        'isonic_analytics_checkbox_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_enabled',
            'description' => 'Décocher quand la migration est terminée',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_instance_url',
        'Instance URL',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_instance_url', 
            'placeholder' => 'https://isonic.lightning.force.com',
            'description' => 'URL de l\'ancienne org Salesforce',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_consumer_key',
        'Consumer Key',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_consumer_key',
            'description' => 'Consumer Key de la Connected App',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_consumer_secret',
        'Consumer Secret',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_consumer_secret',
            'description' => 'Consumer Secret de la Connected App',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_username',
        'Username',
        'isonic_analytics_text_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_username',
            'placeholder' => 'j.miezin@isonic.fr',
            'description' => 'Email de l\'utilisateur Salesforce',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_password',
        'Password',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_password',
            'description' => 'Mot de passe Salesforce (sans le Security Token)',
        ]
    );
    
    add_settings_field(
        'isonic_sf_secondary_security_token',
        'Security Token',
        'isonic_analytics_password_field',
        'isonic-analytics',
        'isonic_sf_secondary_section',
        [ 
            'option_name' => 'isonic_sf_secondary_security_token',
            'description' => 'Security Token (reçu par email)',
        ]
    );
}

function isonic_analytics_matomo_section_callback() {
    echo '<p>Configurez l\'accès à l\'API Matomo pour récupérer les données analytics des visiteurs.</p>';
}

function isonic_analytics_salesforce_primary_section_callback() {
    echo '<p>📍 Org de <strong>production</strong> (isonic-ai) - Campaigns : Site web (701Jv...) et Contenu pédagogique (701Jv...)</p>';
}

function isonic_analytics_salesforce_secondary_section_callback() {
    echo '<p>⚠️ Ancienne org <strong>en migration</strong> (isonic) - Campaigns : Site web (7013X...) et Contenu pédagogique (701IV...)</p>';
    echo '<p><em>Décochez "Activer Secondary Org" une fois la migration terminée.</em></p>';
}

function isonic_analytics_text_field( $args ) {
    $value = get_option( $args['option_name'], '' );
    printf(
        '<input type="text" name="%s" value="%s" placeholder="%s" class="regular-text">',
        esc_attr( $args['option_name'] ),
        esc_attr( $value ),
        esc_attr( $args['placeholder'] ?? '' )
    );
    if ( isset( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

function isonic_analytics_password_field( $args ) {
    $value = get_option( $args['option_name'], '' );
    printf(
        '<input type="password" name="%s" value="%s" class="regular-text">',
        esc_attr( $args['option_name'] ),
        esc_attr( $value )
    );
    if ( isset( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

function isonic_analytics_checkbox_field( $args ) {
    $value = get_option( $args['option_name'], false );
    printf(
        '<input type="checkbox" name="%s" value="1" %s>',
        esc_attr( $args['option_name'] ),
        checked( $value, true, false )
    );
    if ( isset( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

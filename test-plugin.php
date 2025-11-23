<?php
/**
 * Test Script - iSonic Analytics Plugin
 * 
 * Ce script teste la configuration du plugin et les connexions API
 * 
 * UTILISATION :
 * 1. Activer le plugin dans WordPress
 * 2. Configurer les credentials dans Settings → iSonic Analytics
 * 3. Accéder à : https://isonic.fr/wp-content/plugins/isonic-analytics-plugin/test-plugin.php
 * 
 * @package iSonic_Analytics
 */

// Load WordPress
require_once '../../../wp-load.php';

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Accès refusé. Vous devez être administrateur.' );
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test iSonic Analytics Plugin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .success {
            background: #d7f8e8;
            border-color: #00a32a;
            color: #00551d;
        }
        .error {
            background: #fcf0f1;
            border-color: #d63638;
            color: #8a0000;
        }
        .warning {
            background: #fcf9e8;
            border-color: #dba617;
            color: #664d03;
        }
        .info {
            background: #e7f5fe;
            border-color: #2271b1;
            color: #003c5a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f1;
            font-weight: 600;
        }
        code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-ok { background: #00a32a; color: white; }
        .status-error { background: #d63638; color: white; }
        .status-warning { background: #dba617; color: white; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test iSonic Analytics Plugin</h1>
        <p><strong>Version:</strong> <?php echo ISONIC_ANALYTICS_VERSION; ?></p>
        
        <?php
        // TEST 1: Vérifier que les classes existent
        echo '<h2>1. Classes du Plugin</h2>';
        
        $classes = [
            'Isonic_Logger' => 'Logger',
            'Isonic_Matomo_API' => 'Matomo API',
            'Isonic_Salesforce_API' => 'Salesforce API',
            'Isonic_Campaign_Mapper' => 'Campaign Mapper',
            'Isonic_Form_Enricher' => 'Form Enricher',
        ];
        
        echo '<table>';
        echo '<tr><th>Classe</th><th>Description</th><th>Status</th></tr>';
        
        $all_classes_ok = true;
        foreach ( $classes as $class => $desc ) {
            $exists = class_exists( $class );
            if ( ! $exists ) $all_classes_ok = false;
            
            echo '<tr>';
            echo '<td><code>' . $class . '</code></td>';
            echo '<td>' . $desc . '</td>';
            echo '<td>';
            if ( $exists ) {
                echo '<span class="status-badge status-ok">✓ OK</span>';
            } else {
                echo '<span class="status-badge status-error">✗ Manquant</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // TEST 2: Vérifier Gravity Forms
        echo '<h2>2. Dépendances</h2>';
        
        if ( class_exists( 'GFForms' ) ) {
            echo '<div class="test-result success">';
            echo '<strong>✓ Gravity Forms</strong> est installé et activé.';
            echo '</div>';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>✗ Gravity Forms</strong> n\'est pas détecté. Le plugin ne fonctionnera pas.';
            echo '</div>';
        }
        
        // TEST 3: Configuration Matomo
        echo '<h2>3. Configuration Matomo</h2>';
        
        $matomo_enabled = get_option( 'isonic_matomo_enabled', false );
        $matomo_url = get_option( 'isonic_matomo_url', '' );
        $matomo_site_id = get_option( 'isonic_matomo_site_id', '' );
        $matomo_token = get_option( 'isonic_matomo_auth_token', '' );
        
        echo '<table>';
        echo '<tr><th>Paramètre</th><th>Valeur</th><th>Status</th></tr>';
        echo '<tr>';
        echo '<td>Activé</td>';
        echo '<td>' . ( $matomo_enabled ? 'Oui' : 'Non' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $matomo_enabled ? 'status-ok' : 'status-warning' ) . '">';
        echo $matomo_enabled ? '✓' : '⚠';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>URL</td>';
        echo '<td>' . ( $matomo_url ? '<code>' . esc_html( $matomo_url ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $matomo_url ? 'status-ok' : 'status-error' ) . '">';
        echo $matomo_url ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Site ID</td>';
        echo '<td>' . ( $matomo_site_id ? '<code>' . esc_html( $matomo_site_id ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $matomo_site_id ? 'status-ok' : 'status-error' ) . '">';
        echo $matomo_site_id ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Auth Token</td>';
        echo '<td>' . ( $matomo_token ? '<code>***' . substr( $matomo_token, -4 ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $matomo_token ? 'status-ok' : 'status-error' ) . '">';
        echo $matomo_token ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '</table>';
        
        // Test connexion Matomo
        if ( $matomo_url && $matomo_token ) {
            $matomo_api = new Isonic_Matomo_API();
            $matomo_result = $matomo_api->test_connection();
            
            echo '<div class="test-result ' . ( $matomo_result['success'] ? 'success' : 'error' ) . '">';
            echo '<strong>Test de connexion:</strong> ' . esc_html( $matomo_result['message'] );
            echo '</div>';
        }
        
        // TEST 4: Configuration Salesforce
        echo '<h2>4. Configuration Salesforce</h2>';
        
        $sf_enabled = get_option( 'isonic_salesforce_enabled', false );
        $sf_instance_url = get_option( 'isonic_sf_instance_url', '' );
        $sf_consumer_key = get_option( 'isonic_sf_consumer_key', '' );
        $sf_consumer_secret = get_option( 'isonic_sf_consumer_secret', '' );
        $sf_username = get_option( 'isonic_sf_username', '' );
        $sf_password = get_option( 'isonic_sf_password', '' );
        $sf_token = get_option( 'isonic_sf_security_token', '' );
        
        echo '<table>';
        echo '<tr><th>Paramètre</th><th>Valeur</th><th>Status</th></tr>';
        echo '<tr>';
        echo '<td>Activé</td>';
        echo '<td>' . ( $sf_enabled ? 'Oui' : 'Non' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_enabled ? 'status-ok' : 'status-warning' ) . '">';
        echo $sf_enabled ? '✓' : '⚠';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Instance URL</td>';
        echo '<td>' . ( $sf_instance_url ? '<code>' . esc_html( $sf_instance_url ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_instance_url ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_instance_url ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Consumer Key</td>';
        echo '<td>' . ( $sf_consumer_key ? '<code>***' . substr( $sf_consumer_key, -8 ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_consumer_key ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_consumer_key ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Consumer Secret</td>';
        echo '<td>' . ( $sf_consumer_secret ? '<code>***</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_consumer_secret ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_consumer_secret ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Username</td>';
        echo '<td>' . ( $sf_username ? '<code>' . esc_html( $sf_username ) . '</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_username ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_username ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Password</td>';
        echo '<td>' . ( $sf_password ? '<code>***</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_password ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_password ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Security Token</td>';
        echo '<td>' . ( $sf_token ? '<code>***</code>' : '<em>Non configuré</em>' ) . '</td>';
        echo '<td><span class="status-badge ' . ( $sf_token ? 'status-ok' : 'status-error' ) . '">';
        echo $sf_token ? '✓' : '✗';
        echo '</span></td>';
        echo '</tr>';
        echo '</table>';
        
        // Test connexion Salesforce
        if ( $sf_consumer_key && $sf_consumer_secret && $sf_username && $sf_password && $sf_token ) {
            $sf_api = new Isonic_Salesforce_API();
            $sf_result = $sf_api->test_connection();
            
            echo '<div class="test-result ' . ( $sf_result['success'] ? 'success' : 'error' ) . '">';
            echo '<strong>Test de connexion:</strong> ' . esc_html( $sf_result['message'] );
            echo '</div>';
        }
        
        // TEST 5: Campaigns
        echo '<h2>5. Campaigns Salesforce</h2>';
        echo '<table>';
        echo '<tr><th>Constante</th><th>Campaign</th><th>ID</th></tr>';
        echo '<tr>';
        echo '<td><code>ISONIC_CAMPAIGN_SITE_WEB</code></td>';
        echo '<td>Site web isonic.fr</td>';
        echo '<td><code>' . ISONIC_CAMPAIGN_SITE_WEB . '</code></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td><code>ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE</code></td>';
        echo '<td>Contenu pédagogique</td>';
        echo '<td><code>' . ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE . '</code></td>';
        echo '</tr>';
        echo '</table>';
        
        // Résumé final
        echo '<h2>📊 Résumé</h2>';
        
        $ready = $matomo_url && $matomo_token && $sf_consumer_key && $sf_consumer_secret && 
                 $sf_username && $sf_password && $sf_token && class_exists( 'GFForms' );
        
        if ( $ready && $matomo_enabled && $sf_enabled ) {
            echo '<div class="test-result success">';
            echo '<strong>✓ Plugin prêt !</strong> Toutes les configurations sont en place. Les soumissions Gravity Forms seront automatiquement enrichies.';
            echo '</div>';
        } elseif ( $ready ) {
            echo '<div class="test-result warning">';
            echo '<strong>⚠ Configuration complète mais désactivée.</strong> Activez Matomo et/ou Salesforce dans les settings pour démarrer l\'enrichissement.';
            echo '</div>';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>✗ Configuration incomplète.</strong> Veuillez configurer tous les paramètres dans <a href="/wp-admin/options-general.php?page=isonic-analytics">Settings → iSonic Analytics</a>.';
            echo '</div>';
        }
        ?>
        
        <p style="margin-top: 30px; text-align: center; color: #757575;">
            <a href="/wp-admin/options-general.php?page=isonic-analytics" class="button button-primary">← Retour aux Settings</a>
        </p>
    </div>
</body>
</html>


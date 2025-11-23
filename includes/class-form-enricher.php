<?php
/**
 * Form Enricher - Enrichit les soumissions Gravity Forms
 *
 * @package iSonic_Analytics
 */

class Isonic_Form_Enricher {
    
    /**
     * Initialise les hooks Gravity Forms
     */
    public static function init() {
        add_action( 'gform_after_submission', [ __CLASS__, 'enrich_and_send' ], 10, 2 );
    }
    
    /**
     * Enrichit et envoie les données à Salesforce (Primary + Secondary orgs)
     */
    public static function enrich_and_send( $entry, $form ) {
        
        Isonic_Logger::log_info( sprintf(
            'Processing form submission: "%s" (ID: %d)',
            $form['title'],
            $form['id']
        ));
        
        // 1. Récupérer données Matomo
        $matomo_api = new Isonic_Matomo_API();
        $matomo_data = $matomo_api->get_visitor_history();
        
        // 2. Construire payload Salesforce (commun aux 2 orgs)
        $lead_data = [
            // Champs standard (à adapter selon vos formulaires)
            'FirstName' => rgar( $entry, '1' ) ?? '',
            'LastName' => rgar( $entry, '2' ) ?? '',
            'Email' => rgar( $entry, '3' ) ?? '',
            'Company' => rgar( $entry, '4' ) ?? '',
            
            // Lead Source
            'LeadSource' => 'Site web',
            
            // Champs analytics
            'Web_Time_Spent__c' => $matomo_data['time_spent'] ?? '',
            'Web_Entry_Page__c' => $matomo_data['entry_page'] ?? '',
            'Web_Journey__c' => $matomo_data['journey'] ?? '',
            'Web_Source__c' => $matomo_data['source'] ?? '',
            'Web_Medium__c' => $matomo_data['medium'] ?? '',
            'Web_Keyword__c' => $matomo_data['keyword'] ?? '',
            'Web_First_Visit__c' => $matomo_data['first_visit_date'] ?? '',
            'Web_Visit_Count__c' => $matomo_data['visit_count'] ?? 1,
            'Web_Pages_Viewed__c' => $matomo_data['pages_viewed'] ?? 1,
            
            // Champs formulaire
            'Form_Page__c' => $entry['source_url'] ?? '',
            'Form_Name__c' => $form['title'] ?? '',
            'Form_Type__c' => self::detect_form_type( $form['id'], $form['title'] ),
        ];
        
        // 3. Envoyer à PRIMARY ORG (Nouvelle org - Production)
        if ( get_option( 'isonic_sf_primary_enabled', false ) ) {
            self::send_to_org( 'primary', $form, $lead_data );
        } else {
            Isonic_Logger::log_warning( '[PRIMARY] Org disabled - skipping' );
        }
        
        // 4. Envoyer à SECONDARY ORG (Ancienne org - Migration)
        if ( get_option( 'isonic_sf_secondary_enabled', false ) ) {
            self::send_to_org( 'secondary', $form, $lead_data );
        } else {
            Isonic_Logger::log_info( '[SECONDARY] Org disabled - skipping (normal si migration terminée)' );
        }
    }
    
    /**
     * Envoie le Lead à une org Salesforce spécifique
     *
     * @param string $org_type 'primary' or 'secondary'
     * @param array $form Gravity Forms form data
     * @param array $lead_data Lead data to send
     */
    private static function send_to_org( $org_type, $form, $lead_data ) {
        // Déterminer Campaign selon org
        $campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 
            $form['id'], 
            $form['title'],
            $org_type
        );
        
        $campaign_name = Isonic_Campaign_Mapper::get_campaign_name( $campaign_id );
        
        // Créer instance Salesforce API pour cette org
        $sf_api = new Isonic_Salesforce_API( $org_type );
        $lead_id = $sf_api->create_lead( $lead_data );
        
        // Créer CampaignMember
        if ( $lead_id && $campaign_id ) {
            $sf_api->create_campaign_member( $campaign_id, $lead_id );
            
            Isonic_Logger::log_submission( 
                $form['id'], 
                $form['title'], 
                $lead_id, 
                $campaign_name 
            );
        } else {
            Isonic_Logger::log_error( sprintf(
                '[%s] Failed to create Lead or CampaignMember for form "%s" (ID: %d)',
                strtoupper( $org_type ),
                $form['title'],
                $form['id']
            ));
        }
    }
    
    /**
     * Détecte le type de formulaire
     */
    private static function detect_form_type( $form_id, $form_title ) {
        if ( $form_id === ISONIC_FORM_ID_INSCRIPTION ) {
            return 'Formation';
        }
        
        if ( stripos( $form_title, 'demo' ) !== false ) {
            return 'Demo';
        }
        
        if ( stripos( $form_title, 'support' ) !== false ) {
            return 'Support';
        }
        
        return 'Contact General';
    }
}

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
     * Enrichit et envoie les données à Salesforce
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
        
        // 2. Déterminer Campaign Salesforce
        $campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 
            $form['id'], 
            $form['title'] 
        );
        
        $campaign_name = Isonic_Campaign_Mapper::get_campaign_name( $campaign_id );
        
        // 3. Construire payload Salesforce
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
        
        // 4. Envoyer à Salesforce
        $sf_api = new Isonic_Salesforce_API();
        $lead_id = $sf_api->create_lead( $lead_data );
        
        // 5. Créer CampaignMember
        if ( $lead_id && $campaign_id ) {
            $sf_api->create_campaign_member( $campaign_id, $lead_id );
            
            Isonic_Logger::log_submission( 
                $form['id'], 
                $form['title'], 
                $lead_id, 
                $campaign_name 
            );
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

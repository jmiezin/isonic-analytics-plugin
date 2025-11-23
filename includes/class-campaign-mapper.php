<?php
/**
 * Campaign Mapper - Gère le mapping Formulaire → Campaign Salesforce
 *
 * @package iSonic_Analytics
 */

class Isonic_Campaign_Mapper {
    
    /**
     * Détermine la Campaign Salesforce selon le formulaire
     *
     * @param int $form_id Gravity Forms ID
     * @param string $form_title Titre du formulaire
     * @return string Campaign ID Salesforce
     */
    public static function get_campaign_id( $form_id, $form_title ) {
        
        // Règle A : Formulaire "Inscription Isonic" → Contenu pédagogique
        if ( $form_id === ISONIC_FORM_ID_INSCRIPTION ) {
            return ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Détection par titre (fallback si Form ID change)
        if ( stripos( $form_title, 'Inscription Isonic' ) !== false ||
             stripos( $form_title, 'Inscription iSonic' ) !== false ) {
            return ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Règle B : Tous les autres formulaires → Site web isonic.fr
        return ISONIC_CAMPAIGN_SITE_WEB;
    }
    
    /**
     * Récupère le nom de la Campaign pour logging
     *
     * @param string $campaign_id Salesforce Campaign ID
     * @return string Nom de la campagne
     */
    public static function get_campaign_name( $campaign_id ) {
        switch ( $campaign_id ) {
            case ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE:
                return 'Contenu pédagogique';
            
            case ISONIC_CAMPAIGN_SITE_WEB:
                return 'Site web isonic.fr';
            
            default:
                return 'Unknown Campaign';
        }
    }
    
    /**
     * Valide qu'une Campaign ID est valide
     *
     * @param string $campaign_id
     * @return bool
     */
    public static function is_valid_campaign_id( $campaign_id ) {
        return in_array( $campaign_id, [
            ISONIC_CAMPAIGN_SITE_WEB,
            ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE
        ], true );
    }
}


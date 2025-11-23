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
     * @param string $org_type 'primary' or 'secondary'
     * @return string Campaign ID Salesforce
     */
    public static function get_campaign_id( $form_id, $form_title, $org_type = 'primary' ) {
        
        // Règle A : Formulaire "Inscription Isonic" → Contenu pédagogique
        if ( $form_id === ISONIC_FORM_ID_INSCRIPTION ) {
            return $org_type === 'secondary' ? ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE : ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Détection par titre (fallback si Form ID change)
        if ( stripos( $form_title, 'Inscription Isonic' ) !== false ||
             stripos( $form_title, 'Inscription iSonic' ) !== false ) {
            return $org_type === 'secondary' ? ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE : ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Règle B : Tous les autres formulaires → Site web isonic.fr
        return $org_type === 'secondary' ? ISONIC_SECONDARY_CAMPAIGN_SITE_WEB : ISONIC_PRIMARY_CAMPAIGN_SITE_WEB;
    }
    
    /**
     * Récupère le nom de la Campaign pour logging
     *
     * @param string $campaign_id Salesforce Campaign ID
     * @return string Nom de la campagne
     */
    public static function get_campaign_name( $campaign_id ) {
        // Primary org campaigns
        if ( $campaign_id === ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE ) {
            return 'Contenu pédagogique (Primary)';
        }
        if ( $campaign_id === ISONIC_PRIMARY_CAMPAIGN_SITE_WEB ) {
            return 'Site web isonic.fr (Primary)';
        }
        
        // Secondary org campaigns
        if ( $campaign_id === ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE ) {
            return 'Contenu pédagogique (Secondary)';
        }
        if ( $campaign_id === ISONIC_SECONDARY_CAMPAIGN_SITE_WEB ) {
            return 'Site web isonic.fr (Secondary)';
        }
        
        // Legacy
        if ( $campaign_id === ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE ) {
            return 'Contenu pédagogique';
        }
        if ( $campaign_id === ISONIC_CAMPAIGN_SITE_WEB ) {
            return 'Site web isonic.fr';
        }
        
        return 'Unknown Campaign';
    }
    
    /**
     * Valide qu'une Campaign ID est valide
     *
     * @param string $campaign_id
     * @return bool
     */
    public static function is_valid_campaign_id( $campaign_id ) {
        return in_array( $campaign_id, [
            ISONIC_PRIMARY_CAMPAIGN_SITE_WEB,
            ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE,
            ISONIC_SECONDARY_CAMPAIGN_SITE_WEB,
            ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE,
            ISONIC_CAMPAIGN_SITE_WEB, // Legacy
            ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE // Legacy
        ], true );
    }
}


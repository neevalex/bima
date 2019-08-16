<?php

defined( 'ABSPATH' ) || exit;

use underDEV\Utils\Settings\CoreFields;
/**
 * Import/Export settings
 *
 * @since      2.03
 * @author     Maxim K <woo.order.review@gmail.com>
 */
class LRM_Import_Export_Manager {

    public static function init() {
        add_action('wp_ajax_lrm_import', array(__CLASS__, 'AJAX_process_import'));
    }

    /**
     * Register settings
     * @param \underDEV\Utils\Settings $settings_class
     * @throws Exception
     */
    public static function register_settings( $settings_class ) {

        $SECTION = $settings_class->add_section( __( 'Import/Export', 'ajax-login-and-registration-modal-popup' ), 'import/export' );

        $SECTION->add_group( __( 'Import', 'ajax-login-and-registration-modal-popup' ), 'import' )

            ->add_field( array(
                'slug'        => 'import',
                'name'        => __('Login page', 'ajax-login-and-registration-modal-popup'),
                'default'     => true,
                'render'      => array( LRM_Settings::get(), '_render__text_section' ),
                'sanitize'    => '__return_false',
                'addons' => array('section_file'=>'import'),
            ) )
        ->description( __( 'Here you could override default WP pages (login, registration, restore password) to your custom pages.', 'ajax-login-and-registration-modal-popup' ) );

        $SECTION->add_group( __( 'Export', 'ajax-login-and-registration-modal-popup' ), 'export' )
            ->add_field( array(
                'slug'        => 'export',
                'name'        => __('Free version are compatible with:', 'ajax-login-and-registration-modal-popup' ),
                'default'     => true,
                'render'      => array( LRM_Settings::get(), '_render__text_section' ),
                'sanitize'    => '__return_false',
                'addons' => array('section_file'=>'export'),
            ) );

    }

    /**
     * @param bool $cached
     * @return array
     */
    public static function _get_pages_arr( $cached = true ) {

        if ( $cached && $pages_list = wp_cache_get( 'lrm_pages_list', 'lrm' ) ) {
            return $pages_list;
        }

        $pages_list = array();
        $post_title = '';


        $args = array(
            'post_type' => 'page',
            'suppress_filters' => false,
            'post_status' => 'publish',
            'perm' => 'readable',
            //'fields' => 'ids',
        );

        $query = new WP_Query($args);

        foreach ($query->posts as $page) {
            $post_title = $page->post_title;
            if ( 'publish' != $page->post_status ) {
                $post_title .= ' [' . $page->post_status . ']';
            }
            $pages_list[(string)$page->ID] = $post_title . ' [#' . $page->ID . ']';
        }


        if ( $cached ) {
            wp_cache_add( 'lrm_pages_list', $pages_list, 'lrm' );
        }

        return $pages_list;
    }

}

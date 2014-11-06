<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

require('asc_abstracts_cpost.php');
require( 'asc_abstracts_admin.php' );

class ASC_Abstracts {

    var $version = '1.0.1';

    protected static $settings = array();

    function __construct() {
        self::get_settings();
        self::register_custom_post_types();
        self::register_shortcodes();
        self::register_callbacks();
        self::register_scripts_stylesheets();
        self::includes();
    }

    //__________________________________________________________________________________________________________________

    function get_settings() {
        self::$settings['admin'] = ASC_Abstracts_Admin::get_instance()->settings;
    }

    function register_shortcodes() {
        // remove auto paragraph inside shortcodes
        remove_filter( 'the_content', 'wpautop' );
        add_filter( 'the_content', 'wpautop' , 99);
        add_filter( 'the_content', 'shortcode_unautop', 100 );
        // program shortcodes
        add_shortcode( 'asc-abstracts', array( $this, 'api_shortcode' ));
        add_shortcode( 'repeat-data', array( $this, 'repeat_data_shortcode' ) );
        // gravity form integration
        add_action( 'gform_after_submission', array( $this, 'add_author' ), 10, 2 );
    }

    function register_callbacks() {
        //add_action( 'save_post', array( $this, 'update_custom_post_slug' ) );
        //add_filter( 'the_content', array( $this, 'the_custom_content' ));
        add_action( 'wp', array( $this, 'check_confirmation' ));
    }

    function register_scripts_stylesheets() {
        add_action( 'admin_enqueue_scripts', array( $this, 'custom_scripts' ));
        add_action( 'wp_enqueue_scripts', array( $this, 'custom_scripts' ));
        add_action( 'wp_enqueue_scripts', array( $this, 'custom_stylesheets' ));
        add_action( 'wp_print_styles', array( $this, 'custom_print_styles'), 100 );
    }

    function custom_scripts() {
        //wp_enqueue_script( 'tablesort', self::$settings['program']['dir_url'] . 'includes/jquery-tablesorter/jquery.tablesorter.min.js' );
        //wp_enqueue_script( 'tablesort-widget', self::$settings['program']['dir_url'] . 'includes/jquery-tablesorter/jquery.tablesorter.widgets.min.js' );
        wp_enqueue_script( 'bootstrap-script', self::$settings['program']['dir_url'] . 'includes/bootstrap/js/bootstrap.min.js', array('jquery' ));
        wp_enqueue_script( 'bootstrapValidator-script', self::$settings['program']['dir_url'] . 'includes/bootstrapValidator/js/bootstrapValidator.min.js' );
        wp_enqueue_script( 'asc-abstracts-script', self::$settings['program']['dir_url'] . 'script.js' );
    }

    function custom_stylesheets() {
        wp_enqueue_style( 'bootstrap-style', self::$settings['program']['dir_url'] . 'includes/bootstrap/css/bootstrap.min.css' );
        wp_enqueue_style( 'bootstrapValidator-style', self::$settings['program']['dir_url'] . 'includes/bootstrapValidator/css/bootstrapValidator.min.css' );
        wp_enqueue_style( 'asc-abstracts-style', self::$settings['program']['dir_url'] . 'style.css' );
    }

    function custom_print_styles() {
        wp_deregister_style( 'wp-admin' );
    }

    function includes() {
        //include( self::$settings['program']['dir_path'] . 'includes/database.php' );
    }

    //__________________________________________________________________________________________________________________
    // Register custom post types and templates
    function register_custom_post_types() {
        self::$settings['custom_post_types'] = self::$settings['admin']['enable_custom_post_types'] ? ASC_Abstracts_CPost::get_instance()->custom_post_types : null;
    }

    //__________________________________________________________________________________________________________________
    // Custom apis
    var $data = array();

    function api_shortcode( $atts, $content = null ) {

        global $current_user;

        // list of apis
        $apis = array(
            'abstract' => 'abstract_api',
            'session' => 'abstract_api',
            'presenter' => 'presenter_api',
            'confirmation' => 'confirmation_api',
            'authors' => 'authors_api',
            'new-author' => 'authors_api',
            'new-disclosure' => 'authors_api'
        );

        $args = shortcode_atts( array(
            'user' => $current_user->ID,
            'action' => null,
            'status' => null,
            'webkey' => $_REQUEST['webkey'],
            'template' => $content ? $content : ''
        ), $atts );

        $action = $args['action'];
        $webkey = $args['webkey'];

        if ( $webkey ) {
            // get specified abstract info
            $this->load_abstract_data( $webkey );
            $args['template'] = $this->do_template( $args['template'], $_REQUEST );

            // set api mode
            $args['new_author_mode'] = $_REQUEST['new-author'] ? true : false;
            $args['new_disclosure_mode'] = $_REQUEST['new-disclosure'] == 'Yes' ? true : false;
            $args['confirmation_mode'] = $_REQUEST['confirmation'] ? true : false;
            $args['presenter_mode'] = ($args['new_author_mode'] || $args['new_disclosure_mode'] || $args['confirmation_mode'] ) ? false : true;
        }

        // call action if exist
        return method_exists( $this, $apis[ $action ] ) && count( $this->data['abstract'] ) ? call_user_func( array( $this, $apis[ $action ] ), $args ) : '';
    }

    function load_abstract_data( $webkey ) {
        $this->data['abstract'] = $this->get_abstract( $webkey );
        $this->data['authors'] = $this->get_authors( $webkey );
    }

    function repeat_data_shortcode( $atts, $content = null ) {

        $args = shortcode_atts( array(
            'source' => null
        ), $atts );

        if ( empty( $args['source'] ) ) return;   // no source data to process

        // process data
        $output = '';
        $data = (array) $this->data[ $args['source'] ];
        foreach ( $data as $values ) {
            $output .= $this->do_template( $content, (array) $values );
        };
        return $output;
    }

    function do_template( $template, array $values )
    {
        $keys = array_map( function( $key ){ return '{{' . $key . '}}'; }, array_keys( $values ));
        return str_replace( $keys, $values, $template );
    }

    function map_object_name( $object, array $values ) {
        foreach ( $values as $key => $value ) {
            $new_key = "{$object}.{$key}";
            $values[ $new_key ] = $value;
            unset( $values[ $key ] );
        }
        return $values;
    }

    function abstract_api( $args ) {

        extract( $args );
        // ( $action, $template );

        $abstract = $this->data['abstract'];
        $authors = $this->data['authors'];

        if ( empty( $abstract) || ($action == 'session' && empty( $abstract['session_number'] )) ) return; // no abstract or session info

        // set default values
        $contact = array(
            'first_name' => $abstract['owner.first_name'],
            'last_name' => $abstract['owner.last_name'],
            'email_address' => $abstract['owner.email_address']
        );
        $presenter = $this->get_confirmed_presenter( $abstract['session_author'], $authors );
        $presenter ? $contact = $presenter : $presenter = array();

        $values = array_merge( $abstract, $this->map_object_name( 'presenter', $this->author_atts( $presenter ) ),
            $this->map_object_name( 'contact', $this->author_atts( $contact ) ));

        return $this->do_template( do_shortcode( $template ), $values );
    }

    function presenter_api( $args ) {

        extract( $args );
        // ( $presenter_mode, $template );

        $abstract = $this->data['abstract'];
        $authors = $this->data['authors'];

        if ( !$presenter_mode || $abstract['confirmation'] ) return;

        if ( $_REQUEST['new_author_id'] )
            $presenter = $this->get_presenter_by_entryid( $_REQUEST['new_author_id'] );

        if ( empty( $presenter ) )
            $presenter = $authors ? $this->get_presenter( $_REQUEST['presenter'] ? $_REQUEST['presenter'] : $abstract['session_author'], $authors ) : array();

        // set default values
        $presenter = $this->author_atts( $presenter );
        $values = array_merge( $abstract, $this->map_object_name( 'presenter', $presenter ) );
        return $this->do_template( do_shortcode( $template ), $values );
    }

    function confirmation_api( $args ) {

        extract( $args );
        // ( $status, $confirmation_mode, $template );

        $abstract = $this->data['abstract'];
        $authors = $this->data['authors'];

        // update confirmation
        if ( $confirmation_mode && !$abstract['confirmation']) {
            $presenter_id = $_REQUEST['author_id'];
            $phone_number = $_REQUEST['phone_number'];
            $email_address = $_REQUEST['email_address'];
            $confirmation = $_REQUEST['accept'];

            $contact = $abstract['session_author'] ? $this->get_presenter( $abstract['session_author'], $authors ) : array(
                'first_name' => $abstract['owner.first_name'],
                'last_name' => $abstract['owner.last_name'],
                'email_address' => $abstract['owner.email_address']
            );
            $presenter = $this->get_confirmed_presenter( $presenter_id, $authors );

            if ( empty( $presenter )) return;

            $update = array(
                'confirmation' => $confirmation,
                'session_author' => $presenter_id
            );
            $this->update_abstract( $abstract['webkey'], $update);
            $abstract = array_merge( $abstract, $update );

            $update = array(
                'phone_number' => $phone_number,
                'email_address' => $email_address
            );
            $this->update_author( $presenter_id, $update );
            $presenter = array_merge( $presenter, $update );

            $this->send_confirmation( $abstract, $presenter, $contact );
            wp_safe_redirect( $_SERVER['REQUEST_URI'] );
            exit;
        }
        // show confirmation
        if ( $abstract['confirmation'] && $abstract['confirmation'] == $status ) {
            $presenter = $this->get_confirmed_presenter( $abstract['session_author'], $authors );
            $values = array_merge( $abstract, $this->map_object_name( 'presenter', $this->author_atts( $presenter ) ));
            return $this->do_template( do_shortcode( $template ), $values );
        }
    }

    function check_confirmation() {
        do_shortcode( '[asc-abstracts action=confirmation]' );
    }

    function send_confirmation( $abstract, $presenter, $contact ) {

        if ( !in_array( $abstract['confirmation'], array('accepted','declined') )) return;

        add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ));

        $values = array_merge( $abstract, $this->map_object_name( 'presenter', $this->author_atts( $presenter ) ),
            $this->map_object_name( 'contact', $this->author_atts( $contact ) ));

        if ( $contact['email_address'] && strcasecmp( $presenter['email_address'], $contact['email_address'] ) ) {
            // send presenter changed message to owner
            $to = $abstract['owner.email_address'];
            $subject = $this->do_template( self::$settings['admin']['presenter_changed']['subject'], $values );
            $message = $this->do_template( self::$settings['admin']['presenter_changed']['message'], $values );
            wp_mail( $to, $subject, $message );
        }

        $confirmation = ((preg_match( "/{$abstract['owner.email_address']}/", $presenter['email_address'])) ?  "owner" :" presenter") . "_{$abstract['confirmation']}";

        // send confirmation message to presenter
        $to = $presenter['email_address'];
        $subject = $this->do_template( self::$settings['admin'][ $confirmation ]['subject'], $values );
        $message = $this->do_template( self::$settings['admin'][ $confirmation ]['message'], $values );
        wp_mail( $to, $subject, $message );
    }

    function set_html_content_type() {
        return 'text/html';
    }

    function authors_api( $args ) {

        extract( $args );
        // ( $action, $new_author_mode, $new_disclosure_mode, $template );

        $abstract = $this->data['abstract'];
        $authors = $this->data['authors'];


        switch ( $action ) {
            case 'new-author':
                if ( !$new_author_mode ) return;
                $presenter = array();
                break;

            case 'new-disclosure':
                if ( !$new_disclosure_mode ) return;

            default:
                if ( empty( $authors )) return;  //no authors

                $presenter = $this->get_presenter( $_REQUEST['presenter'] ? $_REQUEST['presenter'] : $abstract['session_author'], $authors );
        }
        $presenter = $this->author_atts( $presenter );
        $values = array_merge( $abstract, $this->map_object_name( 'presenter', $presenter ));

        return $this->do_template( do_shortcode( $template ), $values );
    }

    function author_atts( $atts ) {
        $abstract = $this->data['abstract'];
        return shortcode_atts( array(
            'control_number' => $abstract['control_number'],
            'pkID' => null,
            'first_name' => null,
            'last_name' => null,
            'degrees' => null,
            'institution_name' => null,
            'institution_city' => null,
            'institution_state' => null,
            'institution_country' => null,
            'institution_department' => null,
            'email_address' => null,
            'phone_number' => null,
            'mailing_address' => null,
            'mailing_city' => null,
            'mailing_state' => null,
            'mailing_country' => null,
            'author_type' => null,
            // gravity form reference
            'gf_form_id' => null,
            'gf_entry_id' => null
        ), $atts );
    }

    function get_abstract( $webkey ) {
        global $wpdb;

        return (array) $wpdb->get_row( "SELECT * FROM abstracts WHERE webkey = '$webkey'" );
    }

    function update_abstract( $webkey, $abstract ) {
        global $wpdb;
        $fields = $this->set_columns( $abstract );
        $sql = "UPDATE abstracts_temp_export SET $fields WHERE webkey = '$webkey'";
        $wpdb->query( $sql );
    }

    function add_author( $entry, $form ) {

        global $wpdb;

        if ( !preg_match('/New Author/i', $form['title'] )) return;

        $webkey = $_REQUEST['webkey'];

        if ( empty( $webkey )) return;  //no webkey

        $abstract = $this->get_abstract( $webkey );

        $input = $this->get_input_fields( $entry, $form );
        $input['control_number'] = $abstract['control_number'];
        $input['gf_form_id'] = $entry['form_id'];
        $input['gf_entry_id'] = $entry['id'];

        // set default values
        $author = $this->author_atts( $input );

        /*
        // check if author already exist for this abstract
        $sql = "SELECT count(pkID) FROM authors WHERE webkey = '{$webkey}' AND email_address like '{$author['email_address']}'";
        if ( $wpdb->get_var( $sql )) return; //author already exist
        */

        $sql  = "INSERT INTO auths_temp_export";
        $sql .= " (`" . implode( "`, `", array_keys( $author )) . "`)";
        $sql .= " VALUES ('". implode( "', '", $author ) . "') ";

        $wpdb->query( $sql );
    }

    // Gravity form input fields
    function get_input_fields ( $entry, $form ) {
        $input = array();
        foreach ( $form['fields'] as $field ) {
            if ( $field['inputName'] )
                $input[ $field['inputName'] ] = $entry[ "{$field['id']}" ];
            elseif ( is_array( $field['inputs'] ))
                foreach ( $field['inputs'] as $sub_field ) {
                    if ( $sub_field['name'] )
                        $input[ $sub_field['name'] ] = $entry[ "{$sub_field['id']}" ];
                }
        }
        return $input;
    }

    function update_author( $author_id, array $author ) {
        global $wpdb;
        $fields = $this->set_columns( $author );
        $sql = "UPDATE auths_temp_export SET $fields WHERE pkID = $author_id";
        $wpdb->query( $sql );
    }

    function get_authors( $webkey ) {
        global $wpdb;

        return $wpdb->get_results( "SELECT * FROM authors WHERE webkey = '$webkey'" );
    }

    function get_presenter( $id, array $authors ) {

        foreach( $authors as $author ) {
            if ( $id == $author->pkID ) return (array) $author;
        }
        // no assigned presenter yet, default to first author on the list
        return count( $authors ) ? (array) $authors[0] : array();
    }

    function get_confirmed_presenter( $id, array $authors ) {

        foreach( $authors as $author ) {
            if ( $id == $author->pkID ) return (array) $author;
        }
        return null;
    }

    function get_presenter_by_entryid( $entry_id ) {
        global $wpdb;

        return (array) $wpdb->get_row( "SELECT * FROM authors WHERE gf_entry_id = $entry_id" );
    }

    function set_columns( array $fields ) {

        foreach ( $fields as $key => $value ) {
            $a = sprintf("%s=%s", $key, $this->escape_quotes( $value ) );
            $columns .= isset( $columns ) ? ',' . $a : $a;
        }
        return $columns;
    }

    function escape_quotes( $values ) {

        if ( is_array( $values ) ) {
            foreach( $values as $key => $value )
                $values[ $key ] = "'".((!(preg_match("/(^|[^\\\\])'/", $value))) ? $value : addslashes($value))."'";
        } else {
            $values = "'".((!(preg_match("/(^|[^\\\\])'/", $values))) ? $values : addslashes($values))."'";
        }
        return $values;
    }

}

<?php

class BSC_Abstract_CPost {

    private static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    var $custom_post_types;

    function __construct() {
        // custom post types
        $this->custom_post_types = array(
            'abstract' => array(
                'labels' => array(
                    'name'               => __( 'Abstracts', 'bsc-abstract'),
                    'singular_name'      => __( 'Abstract', 'bsc-abstract' ),
                    'menu_name'          => __( 'Abstracts', 'bsc-abstract' ),
                    'name_admin_bar'     => __( 'Abstract', 'bsc-abstract' ),
                    'add_new'            => __( 'Add New', 'bsc-abstract' ),
                    'add_new_item'       => __( 'Add New Abstract', 'bsc-abstract' ),
                    'new_item'           => __( 'New Abstract', 'bsc-abstract' ),
                    'edit_item'          => __( 'Edit Abstract', 'bsc-abstract' ),
                    'view_item'          => __( 'View Abstract', 'bsc-abstract' ),
                    'all_items'          => __( 'All Abstracts', 'bsc-abstract' ),
                    'search_items'       => __( 'Search Abstracts', 'bsc-abstract' ),
                    'parent_item_colon'  => __( 'Parent Abstracts:', 'bsc-abstract' ),
                    'not_found'          => __( 'No abstracts found.', 'bsc-abstract' ),
                    'not_found_in_trash' => __( 'No abstracts found in Trash.', 'bsc-abstract' )
                ),
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'abstract' ),
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                //'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
                'supports'           => array( 'title', 'editor', 'author' )
            ),
            'author' => array(
                'labels' => array(
                    'name'               => __( 'Authors', 'bsc-abstract'),
                    'singular_name'      => __( 'Author', 'bsc-abstract' ),
                    'menu_name'          => __( 'Authors', 'bsc-abstract' ),
                    'name_admin_bar'     => __( 'Author', 'bsc-abstract' ),
                    'add_new'            => __( 'Add New', 'bsc-abstract' ),
                    'add_new_item'       => __( 'Add New Author', 'bsc-abstract' ),
                    'new_item'           => __( 'New Author', 'bsc-abstract' ),
                    'edit_item'          => __( 'Edit Author', 'bsc-abstract' ),
                    'view_item'          => __( 'View Author', 'bsc-abstract' ),
                    'all_items'          => __( 'All Authors', 'bsc-abstract' ),
                    'search_items'       => __( 'Search Authors', 'bsc-abstract' ),
                    'parent_item_colon'  => __( 'Parent Authors:', 'bsc-abstract' ),
                    'not_found'          => __( 'No authors found.', 'bsc-abstract' ),
                    'not_found_in_trash' => __( 'No authors found in Trash.', 'bsc-abstract' )
                ),
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'author' ),
                'capability_type'    => 'page',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                //'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
                'supports'           => array( 'title', 'permalink', 'editor', 'author' )
            )
        );

        // register custom post types
        foreach ( $this->custom_post_types as $post_type => $custom_settings) {
            !post_type_exists( $post_type ) and register_post_type( $post_type, $custom_settings );
        }

        $this->register_callbacks();
    }

    function register_callbacks() {
        add_action( 'save_post', array( $this, 'update_custom_post_slug' ) );
        add_filter( 'the_content', array( $this, 'the_custom_content' ));
    }

    // update custom post slug
    function update_custom_post_slug( $post_id ) {

        $post = get_post( $post_id );

        // process only our custom post types
        if ( !$this->custom_post_types[ $post->post_type ] ) return;

        // verify post is not a revision
        if ( ! wp_is_post_revision( $post_id ) ) {

            // unhook this function to prevent infinite looping
            remove_action( 'save_post', array( $this, 'update_custom_post_slug' ) );

            // update the post slug
            wp_update_post( array(
                'ID' => $post_id,
                'post_name' => sanitize_title( $post->post_title )
            ));

            // re-hook this function
            add_action( 'save_post', array( $this, 'update_custom_post_slug' ) );

        }
    }

    //__________________________________________________________________________________________________________________
    // custom content with custom fields
    function the_custom_content( $content ) {
        return $content . $this->get_custom_fields();
    }

    function get_custom_fields() {
        global $post;
        /*
        *  get all custom fields, loop through them and load the field object to create a label => value markup
        */
        $custom = '';

        $fields = function_exists( 'get_fields' ) ? get_fields( $post->ID ) : null; //var_dump( $fields );

        if ( $fields ) {
            foreach ( $fields as $field_name => $value )
            {
                // get_field_object( $field_name, $post_id, $options )
                // - $value has already been loaded for us, no point to load it again in the get_field_object function
                $field = get_field_object( $field_name, false, array( 'load_value' => false ));

                $custom .= '<div class="custom-field">';
                $custom .= '<p><strong>' . $field['label'] . '</strong></p>';
                if ( is_array( $value ) ) {
                    $custom .= '<ul>';
                    foreach ( $value as $item ) $custom .= '<li>' . $item->post_title . '</li>';
                    $custom .= '</ul>';
                } else {
                    $custom .= $value;
                }
                $custom .= '</div>';
            }
        }
        return $custom;
    }

}
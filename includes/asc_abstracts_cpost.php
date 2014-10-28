<?php

class ASC_Abstracts_CPost {

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
                    'name'               => __( 'Abstracts', 'asc-abstracts'),
                    'singular_name'      => __( 'Abstract', 'asc-abstracts' ),
                    'menu_name'          => __( 'Abstracts', 'asc-abstracts' ),
                    'name_admin_bar'     => __( 'Abstract', 'asc-abstracts' ),
                    'add_new'            => __( 'Add New', 'asc-abstracts' ),
                    'add_new_item'       => __( 'Add New Abstract', 'asc-abstracts' ),
                    'new_item'           => __( 'New Abstract', 'asc-abstracts' ),
                    'edit_item'          => __( 'Edit Abstract', 'asc-abstracts' ),
                    'view_item'          => __( 'View Abstract', 'asc-abstracts' ),
                    'all_items'          => __( 'All Abstracts', 'asc-abstracts' ),
                    'search_items'       => __( 'Search Abstracts', 'asc-abstracts' ),
                    'parent_item_colon'  => __( 'Parent Abstracts:', 'asc-abstracts' ),
                    'not_found'          => __( 'No abstracts found.', 'asc-abstracts' ),
                    'not_found_in_trash' => __( 'No abstracts found in Trash.', 'asc-abstracts' )
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
                    'name'               => __( 'Authors', 'asc-abstracts'),
                    'singular_name'      => __( 'Author', 'asc-abstracts' ),
                    'menu_name'          => __( 'Authors', 'asc-abstracts' ),
                    'name_admin_bar'     => __( 'Author', 'asc-abstracts' ),
                    'add_new'            => __( 'Add New', 'asc-abstracts' ),
                    'add_new_item'       => __( 'Add New Author', 'asc-abstracts' ),
                    'new_item'           => __( 'New Author', 'asc-abstracts' ),
                    'edit_item'          => __( 'Edit Author', 'asc-abstracts' ),
                    'view_item'          => __( 'View Author', 'asc-abstracts' ),
                    'all_items'          => __( 'All Authors', 'asc-abstracts' ),
                    'search_items'       => __( 'Search Authors', 'asc-abstracts' ),
                    'parent_item_colon'  => __( 'Parent Authors:', 'asc-abstracts' ),
                    'not_found'          => __( 'No authors found.', 'asc-abstracts' ),
                    'not_found_in_trash' => __( 'No authors found in Trash.', 'asc-abstracts' )
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
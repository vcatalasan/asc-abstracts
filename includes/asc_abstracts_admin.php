<?php

class ASC_Abstracts_Admin {

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

    var $settings;

    function __construct() {
        $this->load_settings();
        $this->register_callbacks();
    }

    function register_callbacks() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ));
        add_action( 'admin_init', array( $this, 'admin_form_actions' ));
    }
    
    function admin_menu() {
        $minimum_cap = 'manage_options';
        add_submenu_page( 'options-general.php', __( 'ASC Abstract Settings', 'asc-abstracts' ), __( 'ASC Abstract', 'asc-abstracts' ), $minimum_cap, 'asc_abstract_settings', array( $this, 'admin_settings' ), plugins_url( 'asc-abstracts/images/abstract.png' ) );
    }

    function load_settings() {
        $presenter_accepted = get_option( 'presenter_accepted' );
        $presenter_declined = get_option( 'presenter_declined' );
        $presenter_changed = get_option( 'presenter_changed' );
        $this->settings = array(
            // admin panel options
            'enable_custom_post_types' => get_option( 'enable_custom_post_types' ),
            'enable_custom_templates' => get_option( 'enable_custom_post_types' ),
            'presenter_accepted' => $presenter_accepted && is_serialized( $presenter_accepted ) ? unserialize( $presenter_accepted ) : array(),
            'presenter_declined' => $presenter_declined && is_serialized( $presenter_declined ) ? unserialize( $presenter_declined ) : array(),
            'presenter_changed' => $presenter_changed && is_serialized( $presenter_changed ) ? unserialize( $presenter_changed ) : array()
        );
    }

    /**
     * ASC-Abstract main settings page output.
     */
    function admin_settings() {
        // get application default settings
        $this->load_settings();
        //FORM
        ?>
        <div class="wrap">
            <form id="admin-settings" method="post">
                <?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('admin_options_check'); ?>
                <p><input type="checkbox" id="enable_custom_post_types" name="settings[enable_custom_post_types]" value="1" <?php checked( $this->settings['enable_custom_post_types'], true ); ?>/>&nbsp;<label for="enable_custom_post_types"><strong><?php _e( 'Enable custom post types', 'asc-abstracts' ); ?></strong> (experimental)</label></p>
                <h3>Abstract Confirmation Messages</h3>
                <table>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <strong><?php _e( 'Presenter Accepted', 'asc-abstracts' ); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="presenter_accepted_subject">Subject</label></td>
                        <td><input id="presenter_accepted_subject" name="settings[presenter_accepted][subject]" value="<?php echo htmlentities( $this->settings['presenter_accepted']['subject'] ) ?>" /></td>
                    </tr>
                    <tr>
                        <td><label for="presenter_accepted_message">Message</label></td>
                        <td><textarea id="presenter_accepted_message" name="settings[presenter_accepted][message]" style="width:500px;height:100px;"><?php echo htmlentities($this->settings['presenter_accepted']['message']);?></textarea></td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <strong><?php _e( 'Presenter Declined', 'asc-abstracts' ); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="presenter_declined_subject">Subject</label></td>
                        <td><input id="presenter_declined_subject" name="settings[presenter_declined][subject]" value="<?php echo htmlentities( $this->settings['presenter_declined']['subject'] ) ?>" /></td>
                    </tr>
                    <tr>
                        <td><label for="presenter_declined_message">Message</label></td>
                        <td><textarea id="presenter_declined_message" name="settings[presenter_declined][message]" style="width:500px;height:100px;"><?php echo htmlentities($this->settings['presenter_declined']['message']);?></textarea></td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <strong><?php _e( 'Presenter Changed', 'asc-abstracts' ); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="presenter_changed_subject">Subject</label></td>
                        <td><input id="presenter_changed_subject" name="settings[presenter_changed][subject]" value="<?php echo htmlentities( $this->settings['presenter_changed']['subject'] ) ?>" /></td>
                    </tr>
                    <tr>
	                    <td><label for="presenter_changed_message">Message</label></td>
                        <td><textarea id="presenter_changed_message" name="settings[presenter_changed][message]" style="width:500px;height:100px;"><?php echo htmlentities($this->settings['presenter_changed']['message']);?></textarea></td>
                    </tr>
                    <tr>
	                    <td>&nbsp;</td>
	                    <td>
		                    <input type="submit" class="button button-primary" name="Save" value="<?php esc_attr_e( 'Save Options', 'asc-abstracts' ); ?>" />
	                    </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * form submissions
     */
    function admin_form_actions() {
        if ( !( is_admin() && isset( $_POST['Save'] ) )) return;

        check_admin_referer( 'admin_options_check' ); //nonce WP security check

        // set default values
        $settings = shortcode_atts( array(
            'enable_custom_post_types' => 0,
            'presenter_accepted' => array(),
            'presenter_declined' => array(),
            'presenter_changed' => array()
        ), $_POST['settings'] );

	    $safe = function( $value ) use ( &$safe ) {
			if ( is_numeric( $value ) )
				return $value;
		    if ( is_string( $value ) )
			    return stripslashes( $value );
		    if ( is_array( $value ) ) {
			    foreach ( $value as $key => $sub_value )
				    $value[ $key ] = $safe( $sub_value );
			    $value = serialize( $value );
		    }
			return $value;
	    };

        //save settings
        foreach ( $settings as $key => $value )
	        update_option( $key, $safe( $value  ));
    }
}
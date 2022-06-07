<?php

/*
Plugin Name: WPML Dhivehi Extension
Plugin URI: http://wordpress.org/extend/plugins/wpml-dhivehi-extension/
Description: Adds Dhivehi language to WMPL and Thaana editing support to WordPress backend
Version: 0.1
Author: Arushad Ahmed
Author URI: http://arushad.org
Text Domain: wpml-dv
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('WPMLDV_PATH', plugin_dir_path( __FILE__ ));
define( 'WPMLDV_META_PREFIX', '_wpmldv_' );

//load CMB2
if ( file_exists( WPMLDV_PATH . '/cmb2/init.php' ) ) {
    require_once WPMLDV_PATH . '/cmb2/init.php';
} elseif ( file_exists( WPMLDV_PATH . '/CMB2/init.php' ) ) {
    require_once WPMLDV_PATH . '/CMB2/init.php';
}

class WPML_Dhivehi_Extension {

    const CURRENT_VER = '0.1';

    /**
     * Setup the environment for the plugin
     */
    public function bootstrap() {
        register_activation_hook( __FILE__, array($this, 'activate' ) );

        add_action( 'plugins_loaded', array($this, 'maybe_self_deactivate') );
        add_action( 'plugins_loaded', array($this, 'check_language_files_installed'));
        add_action( 'init', array($this, 'register_scripts') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
        add_action( 'admin_init', array($this, 'editor_styles') );
        add_filter( 'tiny_mce_before_init', array($this, 'add_tinymce_jtk'), 1000, 1 );
        add_filter( 'wpml_rtl_languages_codes', array($this, 'add_wpml_rtl_dhivehi'), 10, 1 );
        add_action( 'wp_enqueue_scripts', array($this, 'embed_font_styles') );
        add_filter( 'body_class', array($this, 'add_body_class') );
        add_action( 'admin_print_styles', array($this, 'add_custom_admin_css') );
        add_action( 'admin_print_footer_scripts', array($this, 'add_custom_admin_js') );
        add_action( 'wp_print_footer_scripts', array($this, 'add_front_end_jtk') );
        add_action( 'cmb2_admin_init', array($this, 'register_thaana_metabox') );
        add_filter( 'wpmldv_latin_title' , array($this, 'get_latin_title') );
        add_shortcode( 'latin_title', array($this, 'latin_title_shortcode') );
        add_filter( 'get_user_metadata', array($this, 'override_user_language'), 10, 3);
        add_action( 'admin_footer-profile.php', array($this, 'hide_user_language') );
    }

    /**
     * Runs activation functions
     */
    public function activate() {
        $this->check_dependencies();
        $this->init_options();
        $this->add_dhivehi_language();
        $this->add_dhivehi_language_translation();
        $this->add_dhivehi_flag();
        $this->install_language_files();
        $this->clear_language_cache();
    }

    /**
     * Make sure WPML is installed and activated
     */
    public function check_dependencies() {
        if ( !defined( 'ICL_SITEPRESS_VERSION' ) || ICL_PLUGIN_INACTIVE ) {
            trigger_error( __('This plugin requires WPML. Please install and activate WPML before activating this plugin.', 'wpml-dv'), E_USER_ERROR );
        }
    }

    /**
     * If dependency requirements are not satisfied, self-deactivate
     */
    public function maybe_self_deactivate() {
        if ( !defined( 'ICL_SITEPRESS_VERSION' ) || ICL_PLUGIN_INACTIVE ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            add_action( 'admin_notices', array($this, 'self_deactivate_notice' ) );
        }
    }

    /**
     * Display an error message when the plugin deactivates itself.
     */
    public function self_deactivate_notice() {
        echo '<div class="error"><p>'.__('WPML Dhivehi Extension Plugin has deactivated itself because WPML is no longer active.', 'wpml-dv').'</p></div>';
    }

    /**
     * Initialise options
     */
    public function init_options() {
        update_option( 'wpmldv_ver', self::CURRENT_VER );
    }

    /**
     * Adds Dhivehi language to WMPL
     */
    public function add_dhivehi_language() {
        global $wpdb;

        $values = array(
            'code' => 'dv',
            'english_name' => __('Dhivehi', 'wpml-dv'),
            'default_locale' => 'dv_MV',
            'major' => 0,
            'active' => 1,
            'encode_url' => 1,
            'tag' => 'dv',
        );

        $sql =  "INSERT INTO {$wpdb->prefix}icl_languages ".
                "(code, english_name, default_locale, major, active, encode_url, tag) ".
                "VALUES (%s, %s, %s, %d, %d, %d, %s) ".
                "ON DUPLICATE KEY UPDATE ".
                "default_locale = %s, major = %d, active = %d, encode_url = %d, tag = %s";

        $sql =  $wpdb->prepare($sql, $values['code'], $values['english_name'], $values['default_locale'],
                $values['major'], $values['active'], $values['encode_url'], $values['tag'], $values['default_locale'],
                $values['major'], $values['active'], $values['encode_url'], $values['tag']);

        $wpdb->query($sql);
    }

    /**
     * Adds Dhivehi language translation to WPML
     */
    public function add_dhivehi_language_translation() {
        global $wpdb;

        $translations = array(
            array(
                'language_code' => 'dv',
                'display_language_code' => 'dv',
                'name' => __('ދިވެހި', 'wpml-dv'),
            ),
            array(
                'language_code' => 'dv',
                'display_language_code' => 'en',
                'name' => __('Dhivehi', 'wpml-dv'),
            ),
        );

        foreach ($translations as $translation) {
            $this->add_language_translation($translation);
        }
    }

    /**
     * Adds language translations to WPML
     */
    public function add_language_translation($values) {
        global $wpdb;

        $sql =  "INSERT INTO {$wpdb->prefix}icl_languages_translations ".
            "(language_code, display_language_code, name) ".
            "VALUES (%s, %s, %s) ".
            "ON DUPLICATE KEY UPDATE ".
            "name = %s";

        $sql =  $wpdb->prepare($sql, $values['language_code'], $values['display_language_code'], $values['name'],
            $values['name']);

        $wpdb->query($sql);
    }

    /**
     * Adds Maldivian flag to WMPL
     */
    public function add_dhivehi_flag() {
        global $wpdb;

        $values = array(
            'lang_code' => 'dv',
            'flag' => 'mv.png',
            'from_template' => 0,
        );

        $sql =  "INSERT INTO {$wpdb->prefix}icl_flags ".
            "(lang_code, flag, from_template) ".
            "VALUES (%s, %s, %d) ".
            "ON DUPLICATE KEY UPDATE ".
            "flag = %s, from_template = %d";

        $sql =  $wpdb->prepare($sql, $values['lang_code'], $values['flag'],
            $values['from_template'], $values['flag'], $values['from_template']);

        $wpdb->query($sql);
    }

    /**
     * Clear WPML language cache
     */
    public function clear_language_cache() {
        global $sitepress;

        // Refresh cache.
        $sitepress->get_language_name_cache()->clear();
        $sitepress->clear_flags_cache();
        delete_option('_icl_cache');
    }

    /**
     * Install the Dhivehi language files
     */
    public function install_language_files() {
        //check if language files already installed
        if ( file_exists(WP_LANG_DIR . '/dv_MV.mo')) {
            return;
        }
        $access_type = get_filesystem_method();
        if($access_type === 'direct') {
            // you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());

            // initialize the API
            if ( ! WP_Filesystem($creds) ) {
                // any problems and we exit
                return false;
            }

            global $wp_filesystem;
            // do our file manipulations below

            //check language dir exists
            if( !$wp_filesystem->is_dir($wp_filesystem->wp_lang_dir()) ) {
                //create language dir
                $wp_filesystem->mkdir($wp_filesystem->wp_lang_dir());
            }

            //copy the files
            $plugin_path = str_replace(ABSPATH, $wp_filesystem->abspath(), WPMLDV_PATH);
            $wp_filesystem->copy($plugin_path . '/res/languages/dv_MV.mo', $wp_filesystem->wp_lang_dir() . '/dv_MV.mo');
        } else {
            // don't have direct write access. Prompt user with our notice
            add_action('admin_notices', array($this, 'language_install_error_notice' ));
        }
    }

    /**
     * Check language files installed
     */
    public function check_language_files_installed() {
        if ( !file_exists(WP_LANG_DIR . '/dv_MV.mo') ) {
            add_action('admin_notices', array($this, 'language_install_error_notice' ));
        }
    }

    /**
     * Display an error message when unable to copy language files
     */
    public function language_install_error_notice() {
        echo '<div class="error"><p>'.wp_kses(__('<strong>WPML Dhivehi Extension:</strong> Dhivehi language files not installed. Please manually copy all the files inside this plugin\'s <strong>res/languages</strong> directory to <strong>wp-content/lanuages</strong>', 'wpml-dv'), array('strong' => array()) ).'</p></div>';
    }

    /**
     * Registers scripts
     */
    public function register_scripts() {
        wp_register_style('wpmldv_admin_css', plugins_url('/res/css/admin.css', __FILE__), false, '1.0.0');
        wp_register_script('jtk', plugins_url('/res/js/jtk-4.2.1.pack.js', __FILE__), array(), '4.2.1', true);
        wp_register_script('jtk-admin', plugins_url('/res/js/jtk-admin.js', __FILE__), array('jtk', 'jquery'), '1.0.0', true);
    }

    /**
     * Add JTK scripts and css to admin
     */
    public function admin_scripts() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {
            wp_enqueue_style('wpmldv_admin_css');

            if ( apply_filters('wpmldv_enable_admin_jtk', true) ) {
                wp_enqueue_script('jtk'); // Enqueue it!
                wp_enqueue_script('jtk-admin'); // Enqueue it!
            }
        }
    }

    /**
     * Add CSS to TinyMCE
     */
    public function editor_styles() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {
            add_editor_style(plugins_url('/res/css/editor-style.css', __FILE__));
        }
    }

    /**
     * Add JTK to TinyMCE
     *
     * Credits to @reallynattu https://wordpress.org/plugins/thaana-wp
     * and Jaa https://github.com/jawish/jtk
     */
    public function add_tinymce_jtk($init_array) {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {
            $init_array['directionality'] = 'RTL';

            if ( apply_filters('wpmldv_enable_admin_jtk', true) ) {
                //overriding WPML setup function
                //https://github.com/wp-premium/sitepress-multilingual-cms/blob/master/classes/class-wpml-translate-independently.php
                $init_array['setup'] = 'function(ed) {
                    ed.on(\'change\', function() {
                        edit_form_change();
                    });
                    ed.on(\'keypress\', function (e) {
                        thaanaKeyboard.value = \'\';
                        thaanaKeyboard.handleKey(e);
                        ed.insertContent(thaanaKeyboard.value);
                    });
                }';
            }
        }

        return $init_array;
    }

    /**
     * Add Dhivehi as a RTL language
     */
    public function add_wpml_rtl_dhivehi($langs) {
        $langs[] = 'dv';
        return $langs;
    }

    /**
     * Embed Thaana fonts on the front end
     */
    public function embed_font_styles() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {
            if ( apply_filters('wpmldv_enable_styles', true) ) {
                wp_register_style('wpml-dv', plugins_url('/res/css/style.css', __FILE__), array(), '0.1', 'all');
                wp_enqueue_style('wpml-dv');
            }

            if ( apply_filters('wpmldv_enable_jtk', true) ) {
                wp_enqueue_script('jtk'); // Enqueue it!
            }

            //add custom css under this hook
            do_action( 'wpmldv_enqueue_scripts' );
        }
    }

    /**
     * Add body class to front end
     */
    public function add_body_class( $classes ) {
        $classes[] = ICL_LANGUAGE_CODE;
        return $classes;
    }

    /**
     * Allows user to define custom fields for JTK
     */
    public function add_custom_admin_css() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {

            $ids = apply_filters('wpmldv_custom_metabox_ids', array());

            //print css if any ids specified
            if ( !empty($ids) && is_array($ids) ) {
                $ids_string = implode(',', $ids); ?>
                <style type="text/css">
                    <?php echo $ids_string; ?> {
                        font:300 14px "MV Faseyha", "MV Waheed", Faruma, "mv iyyu nala", "mv elaaf normal", "MV Waheed", "MV Boli";
                        direction: rtl;
                        line-height: 26px;
                        unicode-bidi: embed;
                    }
                </style>
            <?php }

        }
    }

    /**
     * Allows users to define custom fields for JTK
     */
    public function add_custom_admin_js() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {

            $ids = apply_filters('wpmldv_custom_metabox_ids', array());

            //print js if any ids specified
            if ( !empty($ids) && is_array($ids) ) {
                $ids_string = implode(',', $ids); ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        jQuery('<?php echo $ids_string; ?>').addClass('thaanaKeyboardInput');
                    });
                </script>
            <?php }

        }
    }

    /**
     * Allows users to define front end fields for JTK
     */
    public function add_front_end_jtk() {
        if ( ICL_LANGUAGE_CODE == 'dv' ) {

            $ids = apply_filters('wpmldv_front_end_ids', array());

            //print js if any ids specified
            if ( apply_filters('wpmldv_enable_jtk', true) && !empty($ids) && is_array($ids) ) {
                $ids_string = implode(',', $ids); ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        jQuery('<?php echo $ids_string; ?>').addClass('thaanaKeyboardInput');
                    });
                </script>
            <?php }

        }
    }

    /**
     * Returns a custom meta value
     */
    public function get_meta($meta_id,  $single=true, $post_id=null, $echo=false) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        $meta_value = get_post_meta($post_id, WPMLDV_META_PREFIX.$meta_id, $single);
        if ($echo && $single)
            echo $meta_value;
        return $meta_value;
    }

    /**
     * Add latin title metabox
     */
    public function register_thaana_metabox() {
        $prefix = WPMLDV_META_PREFIX;

        $meta_box = new_cmb2_box(array(
                'id' => 'wpmldv_thaana_metabox',
                'title' => __('Thaana Options', 'wpml-dv'),
                'object_types' => get_post_types( array('public' => true), 'names' ), // Post type
                'context' => 'normal',
                'priority' => 'high',
                'show_names' => true, // Show field names on the left
            )
        );

        $meta_box->add_field(array(
            'name' => __('Latin Title', 'wpml-dv'),
            'description' => __('Post title in latin', 'wpml-dv'),
            'id' => $prefix . 'latin_title',
            'type' => 'text',
            'attributes' => array(
                'style' => 'width:90%;',
            ),
        ));
    }

    /**
     * Returns the latin title if exists, otherwise return normal title
     */
    public function get_latin_title($latin_title = '', $post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }

        //first get latin title
        $latin_title = $this->get_meta('latin_title', true, $post_id);
        if ( trim($latin_title) ) {
            return $latin_title;
        } else {
            return get_the_title($post_id);
        }
    }

    /**
     * Displays latin title shortcode
     */
    public function latin_title_shortcode($atts = array(), $content = null) {
        global $post;
        $atts = shortcode_atts(array(
            'id' => $post->ID,
        ), $atts);

        return apply_filters('wpmldv_latin_title', '', $atts['id']);
    }

    /**
     * Override the user language
     */
    public function override_user_language( $value, $user_id, $meta_key ) {
        if ( $meta_key == 'locale' && apply_filters('wpmldv_disable_user_language', true) ) {
            return apply_filters('wpmldv_admin_language', 'en_US');
        } else {
            return $value;
        }
    }

    /**
     * Hide user language selector
     */
    public function hide_user_language() {
        if ( apply_filters('wpmldv_disable_user_language', true) ) : ?>
            <script>
                document.addEventListener( 'DOMContentLoaded', () => {
                    document.querySelector( 'tr.user-language-wrap' ).remove();
                } );
            </script>
    <?php endif; }
}

//initialize the plugin
global $wpml_dv;
$wpml_dv = new WPML_Dhivehi_Extension();
$wpml_dv->bootstrap();
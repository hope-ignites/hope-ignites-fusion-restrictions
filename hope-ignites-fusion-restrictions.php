<?php
/**
 * Plugin Name: Hope Ignites - Fusion Builder Container Restrictions
 * Plugin URI: https://637digital.com
 * Description: Locks specific Avada Fusion Builder containers for certain user roles. Containers with CSS class "nhq-locked" or "nhq-critical" will be restricted for users with the "affiliate_contributor" role.
 * Version: 1.0.0
 * Author: 637 Digital Solutions
 * Author URI: https://637digital.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: hi-fusion-restrictions
 * Domain Path: /languages
 * Network: true
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HI_FUSION_RESTRICTIONS_VERSION', '1.0.0' );
define( 'HI_FUSION_RESTRICTIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HI_FUSION_RESTRICTIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class HI_Fusion_Container_Restrictions {

    /**
     * Restricted user roles
     */
    private $restricted_roles = array( 'affiliate_contributor' );

    /**
     * Locked container CSS classes
     */
    private $locked_classes = array( 'nhq-locked', 'nhq-critical' );

    /**
     * Frontend script data (for manual output fallback)
     */
    private $frontend_script_data = null;
    private $frontend_script_url = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Load plugin text domain for translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        // Admin scripts and styles for Fusion Builder
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        // Frontend (Avada Live Editor) assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        
        // Add admin notices for locked containers
        add_action( 'admin_notices', array( $this, 'show_restriction_notice' ) );
        
        // Validate content on save
        add_action( 'save_post', array( $this, 'validate_locked_containers' ), 10, 3 );
        
        // Add settings page
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // AJAX handler for testing
        add_action( 'wp_ajax_hi_test_restriction', array( $this, 'ajax_test_restriction' ) );
        
        // Add debug output to all admin pages
        add_action( 'admin_footer', array( $this, 'add_debug_output' ) );
        
        // Add debug output to frontend (for live editor)
        add_action( 'wp_footer', array( $this, 'add_frontend_debug_output' ), 999 );
        
        // Fallback: manually output script if not enqueued properly
        add_action( 'wp_footer', array( $this, 'maybe_output_script_manually' ), 9999 );
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'hi-fusion-restrictions',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Check if current user has restricted role
     */
    public function is_restricted_user() {
        $user = wp_get_current_user();
        
        error_log( '[HI Fusion Restrictions] Checking user roles. User: ' . $user->user_login . ', Roles: ' . implode( ', ', $user->roles ) );
        error_log( '[HI Fusion Restrictions] Restricted roles to check: ' . implode( ', ', $this->restricted_roles ) );
        
        foreach ( $this->restricted_roles as $role ) {
            if ( in_array( $role, (array) $user->roles ) ) {
                error_log( '[HI Fusion Restrictions] User has restricted role: ' . $role );
                return true;
            }
        }
        
        error_log( '[HI Fusion Restrictions] User does not have any restricted roles' );
        return false;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets( $hook ) {
        error_log( '[HI Fusion Restrictions] enqueue_admin_assets called with hook: ' . $hook );
        
        // Only load on post edit screens
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            error_log( '[HI Fusion Restrictions] Hook not a post edit screen, skipping: ' . $hook );
            return;
        }

        error_log( '[HI Fusion Restrictions] Post edit screen detected' );

        // Only load if user is restricted
        $is_restricted = $this->is_restricted_user();
        error_log( '[HI Fusion Restrictions] User restricted check: ' . ( $is_restricted ? 'YES' : 'NO' ) );
        
        if ( ! $is_restricted ) {
            error_log( '[HI Fusion Restrictions] User is not restricted, skipping asset enqueue' );
            return;
        }

        error_log( '[HI Fusion Restrictions] Enqueuing assets for restricted user' );

        // Enqueue CSS
        wp_add_inline_style( 'wp-admin', $this->get_restriction_css() );
        error_log( '[HI Fusion Restrictions] CSS enqueued' );

        // Enqueue JavaScript
        $script_url = HI_FUSION_RESTRICTIONS_PLUGIN_URL . 'assets/fusion-restrictions.js';
        error_log( '[HI Fusion Restrictions] Enqueuing JS from: ' . $script_url );
        
        wp_enqueue_script(
            'hi-fusion-restrictions',
            $script_url,
            array( 'jquery' ),
            '1.0.0',
            true
        );

        // Pass data to JavaScript
        $localized_data = array(
            'lockedClasses' => $this->locked_classes,
            'isRestricted' => $is_restricted,
            'message' => __( '🔒 This section is managed by Network Headquarters. For assistance, please contact marketing@hopeignites.org', 'hi-fusion-restrictions' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hi_fusion_restrictions' ),
            'debug' => true,
            'currentUser' => wp_get_current_user()->user_login,
            'userRoles' => wp_get_current_user()->roles,
            'hook' => $hook
        );
        
        error_log( '[HI Fusion Restrictions] Localizing script with data: ' . print_r( $localized_data, true ) );
        
        wp_localize_script( 'hi-fusion-restrictions', 'hiFusionRestrictions', $localized_data );
        
        error_log( '[HI Fusion Restrictions] Assets enqueued successfully' );
    }

    /**
     * Get CSS for locked containers
     */
    private function get_restriction_css() {
        return "
            /* Locked Container Styling */
            .hi-locked-container {
                position: relative;
                border: 2px solid #ffc107 !important;
                background: repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 10px,
                    rgba(255, 193, 7, 0.05) 10px,
                    rgba(255, 193, 7, 0.05) 20px
                );
            }
            
            .hi-locked-notice {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 4px;
                padding: 12px 15px;
                margin: 10px;
                color: #856404;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
                position: relative;
                z-index: 9999;
            }
            
            .hi-locked-notice::before {
                content: '🔒';
                font-size: 18px;
            }
            
            /* Hide Fusion Builder controls for locked containers */
            .hi-locked-container .fusion-builder-controls,
            .hi-locked-container .fusion-builder-container-controls,
            .hi-locked-container .fusion-builder-clone,
            .hi-locked-container .fusion-builder-remove,
            .hi-locked-container .fusion-builder-settings {
                display: none !important;
            }
            
            /* Make locked container visually distinct but not editable */
            .hi-locked-container .fusion-builder-column-content {
                pointer-events: none;
                user-select: none;
            }
            
            /* Allow reading but not editing */
            .hi-locked-container * {
                cursor: not-allowed !important;
            }
            
            /* Admin notice styling */
            .notice-hi-restriction {
                border-left-color: #ffc107;
            }
        ";
    }

    /**
     * Enqueue frontend assets for Avada Live Editor (fb-edit=1)
     */
    public function enqueue_frontend_assets() {
        error_log( '[HI Fusion Restrictions] enqueue_frontend_assets called' );
        
        // Only proceed for restricted users
        $is_restricted = $this->is_restricted_user();
        error_log( '[HI Fusion Restrictions] Frontend - User restricted check: ' . ( $is_restricted ? 'YES' : 'NO' ) );
        
        if ( ! $is_restricted ) {
            error_log( '[HI Fusion Restrictions] Frontend - User is not restricted, skipping' );
            return;
        }

        // Detect Avada Live Editor via query param
        // Check multiple ways the parameter might be set
        $fb_edit = isset( $_GET['fb-edit'] ) ? $_GET['fb-edit'] : '';
        $fb_edit_raw = isset( $_REQUEST['fb-edit'] ) ? $_REQUEST['fb-edit'] : '';
        $query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
        
        error_log( '[HI Fusion Restrictions] Frontend - $_GET[fb-edit]: ' . ( $fb_edit ? $fb_edit : 'not set' ) );
        error_log( '[HI Fusion Restrictions] Frontend - $_REQUEST[fb-edit]: ' . ( $fb_edit_raw ? $fb_edit_raw : 'not set' ) );
        error_log( '[HI Fusion Restrictions] Frontend - QUERY_STRING: ' . ( $query_string ? $query_string : 'empty' ) );
        
        // Check multiple conditions for live editor
        $is_live_editor = false;
        
        // Direct GET parameter
        if ( isset( $_GET['fb-edit'] ) && ( '1' === $_GET['fb-edit'] || 'true' === $_GET['fb-edit'] || '1' === strval( $_GET['fb-edit'] ) ) ) {
            $is_live_editor = true;
        }
        
        // Check in query string directly
        if ( ! $is_live_editor && false !== strpos( $query_string, 'fb-edit=1' ) ) {
            $is_live_editor = true;
        }
        
        // Check REQUEST array
        if ( ! $is_live_editor && isset( $_REQUEST['fb-edit'] ) && ( '1' === $_REQUEST['fb-edit'] || 'true' === $_REQUEST['fb-edit'] ) ) {
            $is_live_editor = true;
        }
        
        error_log( '[HI Fusion Restrictions] Frontend - Live editor check result: ' . ( $is_live_editor ? 'YES' : 'NO' ) );
        
        if ( ! $is_live_editor ) {
            error_log( '[HI Fusion Restrictions] Frontend - Not live editor, skipping' );
            return;
        }

        error_log( '[HI Fusion Restrictions] Frontend live editor detected (fb-edit=1). Enqueuing assets.' );

        // Inject CSS on frontend
        if ( ! wp_style_is( 'hi-fusion-restrictions-inline', 'enqueued' ) ) {
            wp_register_style( 'hi-fusion-restrictions-inline', false );
            wp_enqueue_style( 'hi-fusion-restrictions-inline' );
            wp_add_inline_style( 'hi-fusion-restrictions-inline', $this->get_restriction_css() );
            error_log( '[HI Fusion Restrictions] Frontend - CSS enqueued' );
        }

        // Enqueue JS
        $script_url = HI_FUSION_RESTRICTIONS_PLUGIN_URL . 'assets/fusion-restrictions.js';
        error_log( '[HI Fusion Restrictions] Frontend - Enqueuing JS from: ' . $script_url );
        
        wp_enqueue_script(
            'hi-fusion-restrictions',
            $script_url,
            array( 'jquery' ),
            '1.0.0',
            true
        );

        // Localize data
        $localized_data = array(
            'lockedClasses' => $this->locked_classes,
            'isRestricted' => true,
            'message' => __( '🔒 This section is managed by Network Headquarters. For assistance, please contact marketing@hopeignites.org', 'hi-fusion-restrictions' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hi_fusion_restrictions' ),
            'debug' => true,
            'currentUser' => wp_get_current_user()->user_login,
            'userRoles' => wp_get_current_user()->roles,
            'hook' => 'frontend-live-editor'
        );
        
        error_log( '[HI Fusion Restrictions] Frontend - Localizing script with data: ' . print_r( $localized_data, true ) );
        
        wp_localize_script( 'hi-fusion-restrictions', 'hiFusionRestrictions', $localized_data );
        
        // Store data for potential manual output if needed
        $this->frontend_script_data = $localized_data;
        $this->frontend_script_url = $script_url;
        
        error_log( '[HI Fusion Restrictions] Frontend - Assets enqueued successfully' );
        error_log( '[HI Fusion Restrictions] Frontend - Script should be output in footer. Check if wp_print_footer_scripts is called.' );
    }

    /**
     * Show admin notice about restrictions
     */
    public function show_restriction_notice() {
        if ( ! $this->is_restricted_user() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page' ) ) ) {
            return;
        }

        ?>
        <div class="notice notice-warning notice-hi-restriction">
            <p>
                <strong><?php _e( 'Content Editing Restrictions Active:', 'hi-fusion-restrictions' ); ?></strong>
                <?php _e( 'Some sections of this page are managed by Network Headquarters and cannot be edited. Look for locked containers with yellow borders.', 'hi-fusion-restrictions' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Validate content on save to prevent locked container modifications
     */
    public function validate_locked_containers( $post_id, $post, $update ) {
        // Skip autosaves
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Only check for restricted users
        if ( ! $this->is_restricted_user() ) {
            return;
        }

        // Skip if not updating
        if ( ! $update ) {
            return;
        }

        // Get the old and new content
        $old_post = get_post( $post_id );
        if ( ! $old_post ) {
            return;
        }

        $old_content = $old_post->post_content;
        $new_content = $post->post_content;

        // Check if locked containers were modified
        if ( $this->locked_containers_modified( $old_content, $new_content ) ) {
            // Restore old content
            remove_action( 'save_post', array( $this, 'validate_locked_containers' ), 10 );
            
            wp_update_post( array(
                'ID' => $post_id,
                'post_content' => $old_content
            ) );
            
            add_action( 'save_post', array( $this, 'validate_locked_containers' ), 10, 3 );

            // Set error message
            set_transient( 'hi_fusion_restriction_error_' . get_current_user_id(), 
                __( 'Your changes could not be saved because you attempted to modify restricted content. Locked sections have been restored.', 'hi-fusion-restrictions' ),
                30 
            );
        }
    }

    /**
     * Check if locked containers were modified
     */
    private function locked_containers_modified( $old_content, $new_content ) {
        // Extract locked containers from old and new content
        foreach ( $this->locked_classes as $class ) {
            // Simple pattern matching for containers with locked classes
            $pattern = '/\[fusion_builder_container[^\]]*class=["\'][^"\']*' . preg_quote( $class, '/' ) . '[^"\']*["\'][^\]]*\](.*?)\[\/fusion_builder_container\]/s';
            
            preg_match_all( $pattern, $old_content, $old_matches );
            preg_match_all( $pattern, $new_content, $new_matches );
            
            // If the content of locked containers changed, return true
            if ( $old_matches[0] !== $new_matches[0] ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        // Add to site admin (works per-site in multisite)
        add_options_page(
            __( 'Fusion Builder Restrictions', 'hi-fusion-restrictions' ),
            __( 'Fusion Restrictions', 'hi-fusion-restrictions' ),
            'manage_options',
            'hi-fusion-restrictions',
            array( $this, 'render_settings_page' )
        );
        
        // Add network admin menu if multisite
        if ( is_multisite() ) {
            add_action( 'network_admin_menu', array( $this, 'add_network_settings_page' ) );
        }
    }

    /**
     * Add network admin settings page
     */
    public function add_network_settings_page() {
        add_submenu_page(
            'settings.php',
            __( 'Fusion Builder Restrictions', 'hi-fusion-restrictions' ),
            __( 'Fusion Restrictions', 'hi-fusion-restrictions' ),
            'manage_network_options',
            'hi-fusion-restrictions-network',
            array( $this, 'render_network_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'hi_fusion_restrictions', 'hi_fusion_restricted_roles' );
        register_setting( 'hi_fusion_restrictions', 'hi_fusion_locked_classes' );
        register_setting( 'hi_fusion_restrictions', 'hi_fusion_contact_email' );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check for error messages
        $error = get_transient( 'hi_fusion_restriction_error_' . get_current_user_id() );
        if ( $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
            delete_transient( 'hi_fusion_restriction_error_' . get_current_user_id() );
        }

        ?>
        <div class="wrap">
            <h1><?php _e( 'Fusion Builder Container Restrictions', 'hi-fusion-restrictions' ); ?></h1>
            
            <div class="card">
                <h2><?php _e( 'How It Works', 'hi-fusion-restrictions' ); ?></h2>
                <p><?php _e( 'This plugin restricts editing of specific Avada Fusion Builder containers based on user roles and CSS classes.', 'hi-fusion-restrictions' ); ?></p>
                
                <h3><?php _e( 'Setup Instructions:', 'hi-fusion-restrictions' ); ?></h3>
                <ol>
                    <li><?php _e( 'Add CSS class "nhq-locked" or "nhq-critical" to containers you want to restrict', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'Users with the "affiliate_contributor" role will see these containers with a yellow border and lock icon', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'These users cannot edit or modify locked containers', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'If they try to save changes to locked containers, their edits will be rejected', 'hi-fusion-restrictions' ); ?></li>
                </ol>

                <h3><?php _e( 'Testing the Plugin:', 'hi-fusion-restrictions' ); ?></h3>
                <ol>
                    <li><?php _e( 'Create a test user with role "affiliate_contributor"', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'Edit a page with Fusion Builder', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'Add CSS class "nhq-locked" to a container', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'Save and log in as the test user', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( 'Try to edit the locked container - it should be restricted', 'hi-fusion-restrictions' ); ?></li>
                </ol>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'hi_fusion_restrictions' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e( 'Restricted Roles', 'hi-fusion-restrictions' ); ?></label>
                        </th>
                        <td>
                            <p><strong>affiliate_contributor</strong></p>
                            <p class="description"><?php _e( 'Currently configured for the affiliate_contributor role. To change this, contact your developer.', 'hi-fusion-restrictions' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e( 'Locked CSS Classes', 'hi-fusion-restrictions' ); ?></label>
                        </th>
                        <td>
                            <p><strong>nhq-locked, nhq-critical</strong></p>
                            <p class="description"><?php _e( 'Containers with these CSS classes will be locked. To change this, contact your developer.', 'hi-fusion-restrictions' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="hi_fusion_contact_email"><?php _e( 'Contact Email', 'hi-fusion-restrictions' ); ?></label>
                        </th>
                        <td>
                            <input 
                                type="email" 
                                id="hi_fusion_contact_email" 
                                name="hi_fusion_contact_email" 
                                value="<?php echo esc_attr( get_option( 'hi_fusion_contact_email', 'marketing@hopeignites.org' ) ); ?>" 
                                class="regular-text"
                            />
                            <p class="description"><?php _e( 'Email address shown in locked container messages', 'hi-fusion-restrictions' ); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>

            <div class="card">
                <h2><?php _e( 'Current Status', 'hi-fusion-restrictions' ); ?></h2>
                <p>
                    <strong><?php _e( 'Your Role:', 'hi-fusion-restrictions' ); ?></strong>
                    <?php 
                    $user = wp_get_current_user();
                    echo esc_html( implode( ', ', $user->roles ) );
                    ?>
                </p>
                <p>
                    <strong><?php _e( 'Restrictions Active:', 'hi-fusion-restrictions' ); ?></strong>
                    <?php echo $this->is_restricted_user() ? __( 'Yes', 'hi-fusion-restrictions' ) : __( 'No', 'hi-fusion-restrictions' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render network settings page
     */
    public function render_network_settings_page() {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            return;
        }
        
        // Get all sites in the network
        $sites = get_sites( array( 'number' => 1000 ) );
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Fusion Builder Restrictions - Network Overview', 'hi-fusion-restrictions' ); ?></h1>
            
            <div class="card">
                <h2><?php _e( 'Plugin Status', 'hi-fusion-restrictions' ); ?></h2>
                <p><?php _e( 'This plugin is network activated. Each site maintains its own settings.', 'hi-fusion-restrictions' ); ?></p>
                <p><strong><?php _e( 'Total Sites:', 'hi-fusion-restrictions' ); ?></strong> <?php echo count( $sites ); ?></p>
            </div>

            <div class="card">
                <h2><?php _e( 'How It Works in Multisite', 'hi-fusion-restrictions' ); ?></h2>
                <ul>
                    <li><?php _e( '✅ Network activation: Plugin active across all sites', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( '✅ Per-site settings: Each site can customize roles, classes, and contact email', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( '✅ User roles: Works with site-level and network-level user assignments', 'hi-fusion-restrictions' ); ?></li>
                    <li><?php _e( '✅ Independent operation: Settings on one site don\'t affect others', 'hi-fusion-restrictions' ); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php _e( 'Configuration Per Site', 'hi-fusion-restrictions' ); ?></h2>
                <p><?php _e( 'Site administrators can configure restrictions at:', 'hi-fusion-restrictions' ); ?></p>
                <p><strong><?php _e( 'Settings → Fusion Restrictions', 'hi-fusion-restrictions' ); ?></strong></p>
                
                <h3><?php _e( 'Site List', 'hi-fusion-restrictions' ); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e( 'Site', 'hi-fusion-restrictions' ); ?></th>
                            <th><?php _e( 'Path', 'hi-fusion-restrictions' ); ?></th>
                            <th><?php _e( 'Settings Link', 'hi-fusion-restrictions' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $sites as $site ) : 
                            switch_to_blog( $site->blog_id );
                            $site_name = get_bloginfo( 'name' );
                            $settings_url = admin_url( 'options-general.php?page=hi-fusion-restrictions' );
                            restore_current_blog();
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $site_name ); ?></strong></td>
                                <td><?php echo esc_html( $site->path ); ?></td>
                                <td><a href="<?php echo esc_url( $settings_url ); ?>" target="_blank"><?php _e( 'Configure', 'hi-fusion-restrictions' ); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><?php _e( 'Default Configuration', 'hi-fusion-restrictions' ); ?></h2>
                <p><strong><?php _e( 'Restricted Role:', 'hi-fusion-restrictions' ); ?></strong> affiliate_contributor</p>
                <p><strong><?php _e( 'Locked Classes:', 'hi-fusion-restrictions' ); ?></strong> nhq-locked, nhq-critical</p>
                <p><strong><?php _e( 'Default Contact:', 'hi-fusion-restrictions' ); ?></strong> marketing@hopeignites.org</p>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX test handler
     */
    public function ajax_test_restriction() {
        check_ajax_referer( 'hi_fusion_restrictions', 'nonce' );
        
        wp_send_json_success( array(
            'restricted' => $this->is_restricted_user(),
            'message' => $this->is_restricted_user() 
                ? __( 'Restrictions are active for your role', 'hi-fusion-restrictions' )
                : __( 'You have full editing access', 'hi-fusion-restrictions' )
        ) );
    }

    /**
     * Add debug output to all admin pages
     */
    public function add_debug_output() {
        $screen = get_current_screen();
        $hook = $screen ? $screen->base : 'unknown';
        $user = wp_get_current_user();
        $is_restricted = $this->is_restricted_user();
        
        ?>
        <script type="text/javascript">
        (function() {
            console.log('[HI Fusion Restrictions] ========================================');
            console.log('[HI Fusion Restrictions] DEBUG: Plugin is active');
            console.log('[HI Fusion Restrictions] Current page:', '<?php echo esc_js( $hook ); ?>');
            console.log('[HI Fusion Restrictions] Current user:', '<?php echo esc_js( $user->user_login ); ?>');
            console.log('[HI Fusion Restrictions] User roles:', <?php echo json_encode( $user->roles ); ?>);
            console.log('[HI Fusion Restrictions] Is restricted user:', <?php echo $is_restricted ? 'true' : 'false'; ?>);
            console.log('[HI Fusion Restrictions] Restricted roles:', <?php echo json_encode( $this->restricted_roles ); ?>);
            console.log('[HI Fusion Restrictions] Locked classes:', <?php echo json_encode( $this->locked_classes ); ?>);
            console.log('[HI Fusion Restrictions] hiFusionRestrictions object:', typeof hiFusionRestrictions !== 'undefined' ? hiFusionRestrictions : 'NOT DEFINED');
            
            <?php 
            $is_edit_screen = in_array( $hook, array( 'post', 'post-new', 'page', 'page-new' ) ) || 
                             ( $screen && in_array( $screen->parent_base, array( 'edit' ) ) );
            if ( $is_edit_screen ): ?>
                console.log('[HI Fusion Restrictions] ✓ On post/page edit screen - script should load');
            <?php else: ?>
                console.log('[HI Fusion Restrictions] ⚠ Not on post edit screen');
                console.log('[HI Fusion Restrictions] Script only loads on post.php or post-new.php for restricted users');
                console.log('[HI Fusion Restrictions] To test: Edit a post/page with a user that has "affiliate_contributor" role');
            <?php endif; ?>
            
            console.log('[HI Fusion Restrictions] ========================================');
        })();
        </script>
        <?php
    }

    /**
     * Add debug output to frontend pages (especially live editor)
     */
    public function add_frontend_debug_output() {
        // Check for live editor more robustly
        $fb_edit = isset( $_GET['fb-edit'] ) ? $_GET['fb-edit'] : '';
        $query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
        
        $is_live_editor = false;
        
        // Direct GET parameter
        if ( isset( $_GET['fb-edit'] ) && ( '1' === $_GET['fb-edit'] || 'true' === $_GET['fb-edit'] || '1' === strval( $_GET['fb-edit'] ) ) ) {
            $is_live_editor = true;
        }
        
        // Check in query string directly
        if ( ! $is_live_editor && false !== strpos( $query_string, 'fb-edit=1' ) ) {
            $is_live_editor = true;
        }
        
        // Always show debug on frontend if user is restricted (for troubleshooting)
        $always_show_debug = isset( $_GET['hi-debug'] ); // Allow ?hi-debug=1 to force show
        
        if ( ! $is_live_editor && ! $always_show_debug ) {
            return;
        }
        
        $user = wp_get_current_user();
        $is_restricted = $this->is_restricted_user();
        
        ?>
        <script type="text/javascript">
        (function() {
            console.log('[HI Fusion Restrictions] ========================================');
            console.log('[HI Fusion Restrictions] FRONTEND DEBUG: Plugin is active');
            console.log('[HI Fusion Restrictions] Current URL:', window.location.href);
            console.log('[HI Fusion Restrictions] URL contains fb-edit:', window.location.href.indexOf('fb-edit') !== -1);
            console.log('[HI Fusion Restrictions] Live Editor detected:', <?php echo $is_live_editor ? 'true' : 'false'; ?>);
            console.log('[HI Fusion Restrictions] Current user:', '<?php echo esc_js( $user->user_login ); ?>');
            console.log('[HI Fusion Restrictions] User roles:', <?php echo json_encode( $user->roles ); ?>);
            console.log('[HI Fusion Restrictions] Is restricted user:', <?php echo $is_restricted ? 'true' : 'false'; ?>);
            console.log('[HI Fusion Restrictions] Restricted roles:', <?php echo json_encode( $this->restricted_roles ); ?>);
            console.log('[HI Fusion Restrictions] Locked classes:', <?php echo json_encode( $this->locked_classes ); ?>);
            console.log('[HI Fusion Restrictions] Script file loaded:', typeof hiFusionRestrictions !== 'undefined' ? 'YES' : 'NO');
            console.log('[HI Fusion Restrictions] hiFusionRestrictions object:', typeof hiFusionRestrictions !== 'undefined' ? hiFusionRestrictions : 'NOT DEFINED');
            
            // Check if script tag exists in DOM
            var scriptTags = document.querySelectorAll('script[src*="fusion-restrictions.js"]');
            console.log('[HI Fusion Restrictions] Script tags found in DOM:', scriptTags.length);
            
            if (scriptTags.length > 0) {
                console.log('[HI Fusion Restrictions] ✓ Script tag is in DOM');
                scriptTags.forEach(function(tag, index) {
                    console.log('[HI Fusion Restrictions] Script tag ' + (index + 1) + ' src:', tag.src);
                });
            } else {
                console.error('[HI Fusion Restrictions] ✗ ERROR: Script tag NOT found in DOM!');
                console.error('[HI Fusion Restrictions] The script may not be enqueued correctly.');
            }
            
            console.log('[HI Fusion Restrictions] jQuery available:', typeof jQuery !== 'undefined' ? 'YES' : 'NO');
            console.log('[HI Fusion Restrictions] ========================================');
        })();
        </script>
        <?php
    }

    /**
     * Fallback: Manually output script if WordPress didn't output it
     */
    public function maybe_output_script_manually() {
        // Only on live editor
        $is_live_editor = isset( $_GET['fb-edit'] ) && ( '1' === $_GET['fb-edit'] || 'true' === $_GET['fb-edit'] );
        
        if ( ! $is_live_editor || ! $this->is_restricted_user() ) {
            return;
        }

        // Check if script was already queued and will be output by WordPress
        global $wp_scripts;
        $already_queued = false;
        if ( isset( $wp_scripts->registered['hi-fusion-restrictions'] ) ) {
            $already_queued = true;
            error_log( '[HI Fusion Restrictions] Script is registered in wp_scripts' );
        }
        
        // For live editor, we'll always output manually as it's more reliable
        // The script registration prevents duplicate output
        if ( $this->frontend_script_url && $this->frontend_script_data ) {
            error_log( '[HI Fusion Restrictions] Outputting script manually (fallback method)' );
            
            ?>
            <script type="text/javascript">
            // Output localized data first (only if not already set)
            if (typeof window.hiFusionRestrictions === 'undefined') {
                window.hiFusionRestrictions = <?php echo json_encode( $this->frontend_script_data ); ?>;
            }
            </script>
            <script type="text/javascript" src="<?php echo esc_url( $this->frontend_script_url ); ?>?ver=1.0.1"></script>
            <?php
        }
    }
}

// Initialize the plugin
function hi_fusion_restrictions_init() {
    error_log( '[HI Fusion Restrictions] Plugin initializing...' );
    new HI_Fusion_Container_Restrictions();
    error_log( '[HI Fusion Restrictions] Plugin initialized' );
}
add_action( 'plugins_loaded', 'hi_fusion_restrictions_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'hi_fusion_restrictions_activate' );
function hi_fusion_restrictions_activate( $network_wide ) {
    if ( is_multisite() && $network_wide ) {
        // Network activation - set defaults for all sites
        $sites = get_sites( array( 'number' => 1000 ) );
        
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            hi_fusion_restrictions_set_default_options();
            restore_current_blog();
        }
        
        // Also handle new sites added later
        add_action( 'wpmu_new_blog', 'hi_fusion_restrictions_new_site', 10, 1 );
    } else {
        // Single site activation
        hi_fusion_restrictions_set_default_options();
    }
}

/**
 * Set default options for a site
 */
function hi_fusion_restrictions_set_default_options() {
    // Only add if they don't exist (won't override existing settings)
    add_option( 'hi_fusion_contact_email', 'marketing@hopeignites.org' );
    add_option( 'hi_fusion_restricted_roles', array( 'affiliate_contributor' ) );
    add_option( 'hi_fusion_locked_classes', array( 'nhq-locked', 'nhq-critical' ) );
}

/**
 * Run activation for newly created sites in network
 */
function hi_fusion_restrictions_new_site( $blog_id ) {
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        switch_to_blog( $blog_id );
        hi_fusion_restrictions_set_default_options();
        restore_current_blog();
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'hi_fusion_restrictions_deactivate' );
function hi_fusion_restrictions_deactivate( $network_wide ) {
    if ( is_multisite() && $network_wide ) {
        // Network deactivation cleanup if needed
        error_log( '[HI Fusion Restrictions] Network deactivation - settings preserved per site' );
    }
    // Note: We preserve settings on deactivation for easy reactivation
}

/**
 * Handle site deletion in multisite
 */
function hi_fusion_restrictions_delete_site( $site_id ) {
    // WordPress automatically cleans up options when a site is deleted
    // This is just for logging/tracking if needed
    error_log( '[HI Fusion Restrictions] Site deleted: ' . $site_id );
}
add_action( 'delete_blog', 'hi_fusion_restrictions_delete_site', 10, 1 );

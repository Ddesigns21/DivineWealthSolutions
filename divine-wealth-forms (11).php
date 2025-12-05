<?php
/**
 * Plugin Name: Divine Wealth Forms
 * Description: Rewards Program signup, travel concierge request forms, business perks form, tier-based perks, and admin dashboards for Divine Wealth Solutions.
 * Author: Divine Wealth Solutions
 * Version: 1.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Global setting for how success alerts are displayed.
// Available values: 'pill', 'full', 'card', 'floating'.
if ( ! defined( 'DW_REWARDS_ALERT_STYLE' ) ) {
    define( 'DW_REWARDS_ALERT_STYLE', 'full' ); // branded banner default
}

class Divine_Wealth_Forms {

    const VERSION = '1.1.3';

    public function __construct() {

        // Frontend shortcodes
        add_shortcode( 'dw_rewards_join_form', array( $this, 'render_rewards_form' ) );
        add_shortcode( 'dw_concierge_request_form', array( $this, 'render_travel_form' ) );
        add_shortcode( 'dw_business_perks_form', array( $this, 'render_business_perks_form' ) );
        add_shortcode( 'dw_rewards_summary', array( $this, 'render_rewards_summary' ) );
        add_shortcode( 'dw_rewards_hub', array( $this, 'render_rewards_hub_shell' ) );
        add_shortcode( 'dw_tier_perks_table', array( $this, 'render_tier_perks_table' ) );



        // Handle form submissions
        add_action( 'init', array( $this, 'handle_form_submissions' ) );

        // ✅ Ensure DB columns exist after updates
        add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_tables' ) );

        // Assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        // Assets now loaded by theme (Option A).

        // AJAX tier lookup
        add_action( 'wp_ajax_dw_lookup_tier', array( $this, 'ajax_lookup_tier' ) );
        add_action( 'wp_ajax_nopriv_dw_lookup_tier', array( $this, 'ajax_lookup_tier' ) );

        // Dashboard widget snapshot
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );

        // ✅ Admin menu + submissions pages
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        
            // Settings registration (for logo, brand name, reply-to, toggles, etc.)
         add_action( 'admin_init', array( $this, 'register_settings' ) );

        
        // Admin POST actions (detail page tools)
        add_action( 'admin_post_dw_update_member_tier', array( $this, 'admin_update_member_tier' ) );
        add_action( 'admin_post_dw_update_member_code', array( $this, 'admin_update_member_code' ) );
        add_action( 'admin_post_dw_save_member_admin_notes', array( $this, 'admin_save_member_notes' ) );
        add_action( 'admin_post_dw_reply_member', array( $this, 'admin_reply_member' ) );
        // Admin POST actions (travel detail page tools)
        add_action( 'admin_post_dw_update_travel_tier', array( $this, 'admin_update_travel_tier' ) );
        add_action( 'admin_post_dw_update_travel_code', array( $this, 'admin_update_travel_code' ) );
        add_action( 'admin_post_dw_save_travel_admin_notes', array( $this, 'admin_save_travel_notes' ) );
        add_action( 'admin_post_dw_reply_travel_request', array( $this, 'admin_reply_travel_request' ) );
        add_action( 'admin_post_dw_update_travel_status', array( $this, 'admin_update_travel_status' ) );
        // Admin POST action (referral logs)
        add_action( 'admin_post_dw_add_referral_log', array( $this, 'admin_add_referral_log' ) );
        // Admin POST action (delete referral log)
        add_action( 'admin_post_dw_delete_referral_log', array( $this, 'admin_delete_referral_log' ) );

        // Admin POST actions (business detail page tools)
        add_action( 'admin_post_dw_save_business_admin_notes', array( $this, 'admin_save_business_notes' ) );
        add_action( 'admin_post_dw_reply_business_request', array( $this, 'admin_reply_business_request' ) );
        add_action( 'admin_post_dw_update_business_status', array( $this, 'admin_update_business_status' ) );
         // Export + delete for Business Perks
        add_action( 'admin_post_dw_export_business_perks', array( $this, 'admin_export_business_perks' ) );
        add_action( 'admin_post_dw_delete_business_request', array( $this, 'admin_delete_business_request' ) );
        // Admin POST delete actions
        add_action( 'admin_post_dw_delete_rewards_member', array( $this, 'admin_delete_rewards_member' ) );
        add_action( 'admin_post_dw_delete_travel_request', array( $this, 'admin_delete_travel_request' ) );

        // Admin POST: record closed referral
        add_action( 'admin_post_dw_add_member_referral', array( $this, 'admin_add_member_referral' ) );
        // Admin POST: save email branding settings
        add_action( 'admin_post_dw_save_email_settings', array( $this, 'admin_save_email_settings' ) );


    }

    /**
     * Activation: create tables
     */
   public static function activate() {
    self::create_tables();
    self::maybe_upgrade_tables();
}

/**
 * Add new admin-only columns safely if they don't exist.
 */
/**
 * Add new admin-only columns safely if they don't exist.
 */
public static function maybe_upgrade_tables() {
    global $wpdb;

    $members_table = $wpdb->prefix . 'dw_rewards_members';
    $travel_table  = $wpdb->prefix . 'dw_travel_requests';

    // ===== Rewards Members columns =====
    $member_cols = $wpdb->get_col( "DESC {$members_table}", 0 );

    if ( ! in_array( 'admin_notes', $member_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$members_table} ADD COLUMN admin_notes TEXT NULL" );
    }
    if ( ! in_array( 'admin_notes_updated_at', $member_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$members_table} ADD COLUMN admin_notes_updated_at DATETIME NULL" );
    }

    // ===== Travel Requests columns =====
    $travel_cols = $wpdb->get_col( "DESC {$travel_table}", 0 );

    if ( ! in_array( 'admin_notes', $travel_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$travel_table} ADD COLUMN admin_notes TEXT NULL" );
    }
    if ( ! in_array( 'admin_notes_updated_at', $travel_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$travel_table} ADD COLUMN admin_notes_updated_at DATETIME NULL" );
    }

    if ( ! in_array( 'status', $travel_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$travel_table} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new'" );
    }
    if ( ! in_array( 'status_updated_at', $travel_cols, true ) ) {
        $wpdb->query( "ALTER TABLE {$travel_table} ADD COLUMN status_updated_at DATETIME NULL" );
    }
        // ===== Business Perks Requests columns =====
    $business_table = $wpdb->prefix . 'dw_business_perks_requests';

    $business_exists = $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $business_table )
    );

    if ( $business_exists ) {

        $business_cols = $wpdb->get_col( "DESC {$business_table}", 0 );

        if ( ! in_array( 'referral_type', $business_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$business_table} ADD COLUMN referral_type VARCHAR(50) NULL" );
        }

        if ( ! in_array( 'admin_notes', $business_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$business_table} ADD COLUMN admin_notes TEXT NULL" );
        }
        if ( ! in_array( 'admin_notes_updated_at', $business_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$business_table} ADD COLUMN admin_notes_updated_at DATETIME NULL" );
        }

        if ( ! in_array( 'status', $business_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$business_table} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new'" );
        }
        if ( ! in_array( 'status_updated_at', $business_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$business_table} ADD COLUMN status_updated_at DATETIME NULL" );
        }
    }

}

private function maybe_create_payout_table() {
    global $wpdb;

    $table           = $wpdb->prefix . 'dw_payout_requests';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        member_id BIGINT(20) UNSIGNED NOT NULL,
        rewards_code VARCHAR(80) NOT NULL,
        amount_requested DECIMAL(10,2) NOT NULL DEFAULT 0,
        request_type VARCHAR(20) NOT NULL DEFAULT 'cash',
        payout_method VARCHAR(20) NOT NULL DEFAULT 'cashapp',
        payout_details TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        admin_notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY member_id (member_id),
        KEY rewards_code (rewards_code),
        KEY status (status)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

    /* =========================================================
     * ASSETS
     * ======================================================= */

    /**
     * Only load CSS/JS on pages that actually contain our shortcodes.
     * Divi sometimes stores content outside post_content, so we also
     * fall back to scanning the rendered post for our shortcode names.
     */
    private function page_has_dw_shortcode() {
        if ( is_admin() ) return false;

        $post_id = get_queried_object_id();
        if ( ! $post_id ) return false;

        $content = get_post_field( 'post_content', $post_id );
        if ( ! $content ) $content = '';

        if (
            has_shortcode( $content, 'dw_rewards_join_form' ) ||
            has_shortcode( $content, 'dw_concierge_request_form' ) ||
            has_shortcode( $content, 'dw_business_perks_form' )
        ) {
            return true;
        }

        // Also load assets if showing any DW success/error banners
        if (
            isset( $_GET['dw_rewards_success'] ) ||
            isset( $_GET['dw_travel_success'] ) ||
            isset( $_GET['dw_business_success'] ) ||
            isset( $_GET['dw_business_error'] )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue frontend CSS/JS
     */
    public function enqueue_assets() {

        // ✅ Only skip if we're 100% sure no DW forms/banners are present
        // if ( ! $this->page_has_dw_shortcode() ) {
        //     return;
        // }

        // CSS
        wp_enqueue_style(
            'dw-forms-front',
            plugin_dir_url( __FILE__ ) . 'assets/css/dw-forms.css',
            array(),
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/dw-forms.css' )
        );

        // JS
        wp_enqueue_script(
            'dw-rewards-front',
            plugin_dir_url( __FILE__ ) . 'assets/js/dw-rewards-front.js',
            array( 'jquery' ),
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/dw-rewards-front.js' ),
            true
        );

        // ✅ Localize AFTER enqueue
        wp_localize_script(
            'dw-rewards-front',
            'DWRewards',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'dw_lookup_tier' ),
            )
        );
    }

    /* =========================================================
     * DATABASE
     * ======================================================= */

    /**
     * Create DB tables.
     * Uses $wpdb->prefix so on your site they will be:
     *  wpdw_dw_rewards_members
     *  wpdw_dw_travel_requests
     *  wpdw_dw_business_perks_requests
     */
  public static function create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $members_table  = $wpdb->prefix . 'dw_rewards_members';
    $travel_table   = $wpdb->prefix . 'dw_travel_requests';
    $business_table = $wpdb->prefix . 'dw_business_perks_requests';
    $logs_table     = $wpdb->prefix . 'dw_referral_logs';

    $sql = "";

    // Rewards members
    $sql .= "CREATE TABLE {$members_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        full_name VARCHAR(190) NOT NULL,
        business_name VARCHAR(190) NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(50) NULL,
        referral_type VARCHAR(50) NULL,
        referral_code VARCHAR(50) NOT NULL,
        tier VARCHAR(20) NOT NULL DEFAULT 'concierge',
        city VARCHAR(190) NULL,
        state VARCHAR(50) NULL,
        notes TEXT NULL,
        total_referrals INT(11) UNSIGNED NOT NULL DEFAULT 0,
        total_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
        source VARCHAR(50) NULL,
        ip_address VARCHAR(100) NULL,
        user_agent TEXT NULL,
        admin_notes TEXT NULL,
        admin_notes_updated_at DATETIME NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY referral_code (referral_code),
        KEY email (email)
    ) {$charset_collate};";

    // Travel requests
    $sql .= "CREATE TABLE {$travel_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        full_name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(50) NULL,
        trip_purpose VARCHAR(20) NULL,
        departure_city VARCHAR(190) NULL,
        destination VARCHAR(255) NULL,
        depart_date DATE NULL,
        return_date DATE NULL,
        travelers INT(11) NULL,
        services TEXT NULL,
        budget VARCHAR(50) NULL,
        flexible_dates TINYINT(1) NOT NULL DEFAULT 0,
        flexible_airports TINYINT(1) NOT NULL DEFAULT 0,
        notes TEXT NULL,
        referral_code VARCHAR(50) NULL,
        member_id BIGINT(20) UNSIGNED NULL,
        member_tier_at_request VARCHAR(20) NULL,
        ip_address VARCHAR(100) NULL,
        user_agent TEXT NULL,
        admin_notes TEXT NULL,
        admin_notes_updated_at DATETIME NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        status_updated_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY referral_code (referral_code)
    ) {$charset_collate};";

    // Business perks requests
    $sql .= "CREATE TABLE {$business_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        full_name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(50) NULL,
        business_name VARCHAR(190) NULL,
        referral_code VARCHAR(50) NULL,
        referral_type VARCHAR(50) NULL,
        member_id BIGINT(20) UNSIGNED NULL,
        member_tier_at_request VARCHAR(20) NULL,
        perk_type VARCHAR(50) NULL,
        details TEXT NULL,
        notes TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        admin_notes TEXT NULL,
        admin_notes_updated_at DATETIME NULL,
        ip_address VARCHAR(100) NULL,
        user_agent TEXT NULL,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY referral_code (referral_code)
    ) {$charset_collate};";

    // Referral logs (each closed referral / commission)
    $sql .= "CREATE TABLE {$logs_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        member_id BIGINT(20) UNSIGNED NOT NULL,
        referral_code VARCHAR(50) NOT NULL,
        client_name VARCHAR(190) NULL,
        client_email VARCHAR(190) NULL,
        service_type VARCHAR(50) NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        notes TEXT NULL,
        source VARCHAR(50) NULL,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY referral_code (referral_code)
    ) {$charset_collate};";

    // New: referral payout requests table
    $table_payouts = $wpdb->prefix . 'dw_payout_requests';

    $sql_payouts = "CREATE TABLE {$table_payouts} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id BIGINT(20) UNSIGNED NOT NULL,
    rewards_code VARCHAR(50) NOT NULL,
    amount_requested DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    request_type VARCHAR(20) NOT NULL DEFAULT 'cash',      -- 'cash' or 'service'
    payout_method VARCHAR(20) NOT NULL DEFAULT 'cashapp',  -- cashapp/paypal/zelle/cash/check/other
    payout_details TEXT NULL,                              -- handle, email, etc.
    status VARCHAR(20) NOT NULL DEFAULT 'new',             -- new / pending / completed / cancelled
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    admin_notes TEXT NULL,
    PRIMARY KEY (id),
    KEY member_id (member_id),
    KEY rewards_code (rewards_code),
    KEY status (status)
    ) {$charset_collate};";

    dbDelta( $sql_payouts );


    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

    /* =========================================================
     * EMAIL HELPERS
     * ======================================================= */
    /**
     * Global plugin settings helper.
     * Stores everything in a single dw_rewards_settings option (array).
     */
    private function get_settings() {
        $defaults = array(
            'brand_name'              => 'Divine Wealth Solutions',
            'logo_url'                => 'https://divinewealthllc.com/wp-content/uploads/2025/03/Divine-Wealth-Solution-Logo-1.png',
            'banner_bg_color'         => '#f7f1dd',
            'email_reply_to'          => get_option( 'admin_email' ),
            'email_signature'         => "With gratitude,\nDivine Wealth Solutions",
            'email_footer_main'    => 'Thank you for choosing {{brand_name}}.',
            'email_footer_tagline' => 'Taxes • Business Growth • Wealth Planning',
            'base_referral_payout'    => '25',  // default $25 per referral
            'enable_employee_subcodes'=> 0,     // toggle OFF by default
            'license_key'             => '',
            
        );

        $saved = get_option( 'dw_rewards_settings', array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        return array_merge( $defaults, $saved );
    }

    /**
     * Get the logo URL used in outgoing emails.
     * Allows override via wp-admin option "dw_rewards_logo_url".
     */
     private function get_logo_url() {
        $settings = $this->get_settings();

        if ( ! empty( $settings['logo_url'] ) ) {
            return $settings['logo_url'];
        }

        // Fallback to the old hard-coded value if needed
        return 'https://divinewealthllc.com/wp-content/uploads/2025/03/Divine-Wealth-Solution-Logo-1.png';
    }

/**
 * Try to detect which payouts table actually exists in the DB,
 * and return that table name so both front-end and admin
 * use the same one.
 */
    private function get_payout_table_name() {
    global $wpdb;
    
    //return 'wpdw_dw_payout_requests';
    return $wpdb->prefix . 'dw_payout_requests';

    // Candidate table names we might have used
    $candidates = array(
        $wpdb->prefix . 'dw_payout_requests',
        $wpdb->prefix . 'dw_payout_request',
        $wpdb->prefix . 'dw_payouts',
    );

    foreach ( $candidates as $tbl ) {
        $found = $wpdb->get_var(
            // SHOW TABLES LIKE returns the table name if it exists
            $wpdb->prepare( "SHOW TABLES LIKE %s", $tbl )
        );

        if ( $found === $tbl ) {
            return $tbl;
        }
    }

    // Fallback: our current default
    return $candidates[0];
}


    /**
     * Small helper to get the brand / company name for emails.
     * Uses wp-admin option "dw_rewards_company_name" if present.
     */
    private function get_company_name_for_email() {
        $default = 'Divine Wealth Solutions';
        $saved   = get_option( 'dw_rewards_company_name', '' );

        if ( ! empty( $saved ) ) {
            return sanitize_text_field( $saved );
        }

        return $default;
    }

    /**
     * Build a branded HTML email wrapper around content.
     */
  private function build_email_html( $heading, $content_html ) {
    // Read everything from the Email Branding options
    $brand_name   = get_option( 'dw_rewards_company_name', 'Divine Wealth Solutions' );
    $logo_url     = esc_url( get_option( 'dw_rewards_logo_url', $this->get_logo_url() ) );
    $banner_color = get_option( 'dw_rewards_banner_color', '#f7f1dd' );

    $footer_main = get_option(
        'dw_rewards_footer_main',
        "Thank you for choosing {$brand_name}."
    );

    $footer_tagline = get_option(
        'dw_rewards_footer_tagline',
        'Taxes • Business Growth • Wealth Planning'
    );

    // Escape for output
    $heading_safe       = esc_html( $heading );
    $brand_name_safe    = esc_html( $brand_name );
    $footer_main_safe   = esc_html( $footer_main );
    $footer_tagline_safe= esc_html( $footer_tagline );
    $banner_color_safe  = esc_attr( $banner_color );

    ob_start();
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $heading_safe; ?></title>
    </head>
    <body style="margin:0; padding:0; background-color:#f4f4f7;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f7; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.07);">
                    <tr>
                        <td align="center"
                            style="padding:24px 24px 10px;
                                   background:linear-gradient(135deg,#ffffff,<?php echo $banner_color_safe; ?>);
                                   border-bottom:1px solid #ead9a1;">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo $logo_url; ?>"
                                     alt="<?php echo $brand_name_safe; ?>"
                                     style="max-width:200px; height:auto; display:block; margin:0 auto 10px;">
                            <?php else : ?>
                                <div style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
                                            font-size:22px; font-weight:700; color:#2d2a3e; letter-spacing:1px;">
                                    <?php echo $brand_name_safe; ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
                                        font-size:18px; font-weight:600; color:#2d2a3e; margin-top:8px;">
                                <?php echo $heading_safe; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 18px;
                                   font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
                                   font-size:14px; line-height:1.6; color:#333333;">
                            <?php echo $content_html; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 24px;
                                   background-color:<?php echo $banner_color_safe; ?>;
                                   border-top:1px solid #ead9a1;
                                   font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
                                   font-size:12px; line-height:1.6; color:#555555; text-align:center;">
                            <div style="font-weight:600; color:#2d2a3e; margin-bottom:3px;">
                                <?php echo $footer_main_safe; ?>
                            </div>
                            <div style="margin-bottom:6px;">
                                <?php echo $footer_tagline_safe; ?>
                            </div>
                            <div style="color:#777777;">
                                © <?php echo date( 'Y' ); ?> <?php echo $brand_name_safe; ?>. All rights reserved.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </body>
    </html>
    <?php

    return ob_get_clean();
}


    /**
     * Build email headers (HTML + optional Reply-To from settings).
     */
private function get_email_headers() {
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // 1) New email-branding setting (current screen)
    $reply_to = get_option( 'dw_rewards_reply_to', '' );

    // 2) Backwards-compat: old option name if it exists
    if ( empty( $reply_to ) ) {
        $reply_to = get_option( 'dw_rewards_reply_to_email', '' );
    }

    // 3) Final fallback to settings array (if you ever set it there)
    if ( empty( $reply_to ) ) {
        $settings = $this->get_settings();
        if ( ! empty( $settings['email_reply_to'] ) ) {
            $reply_to = $settings['email_reply_to'];
        }
    }

    if ( ! empty( $reply_to ) ) {
        $reply_to = sanitize_email( $reply_to );
        if ( is_email( $reply_to ) ) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
    }

    return $headers;
}



    /* =========================================================
     * FORM SUBMISSIONS
     * ======================================================= */

    public function handle_form_submissions() {

        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
        if ( empty( $_POST['dw_form_type'] ) ) return;

        $form_type = sanitize_text_field( wp_unslash( $_POST['dw_form_type'] ) );

        if ( 'rewards_signup' === $form_type ) {
            $this->handle_rewards_signup();
        } elseif ( 'travel_request' === $form_type ) {
            $this->handle_travel_request();
        } elseif ( 'business_perks_request' === $form_type ) {
            $this->handle_business_perks_request();
        }
    }

    private function handle_rewards_signup() {
        if (
            empty( $_POST['dw_rewards_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['dw_rewards_nonce'] ) ),
                'dw_rewards_signup'
            )
        ) return;

        if ( ! empty( $_POST['dw_hp'] ) ) return;

        $full_name     = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
        $business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
        $email         = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone         = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $referral_type = sanitize_text_field( wp_unslash( $_POST['referral_type'] ?? '' ) );
        $city          = sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) );
        $state         = sanitize_text_field( wp_unslash( $_POST['state'] ?? '' ) );
        $notes         = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

        if ( empty( $full_name ) || empty( $email ) ) return;

        global $wpdb;
        $members_table = $wpdb->prefix . 'dw_rewards_members';

        $base = $business_name ? $business_name : $full_name;
        $base = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $base ) );
        $base = substr( $base, 0, 12 );
        if ( '' === $base ) $base = 'DW';

        do {
            $suffix        = strtoupper( wp_generate_password( 4, false, false ) );
            $referral_code = $base . '-' . $suffix;
            $exists        = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$members_table} WHERE referral_code = %s",
                    $referral_code
                )
            );
        } while ( $exists );

        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        $ua = sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

        $wpdb->insert(
            $members_table,
            array(
                'created_at'    => current_time( 'mysql' ),
                'full_name'     => $full_name,
                'business_name' => $business_name,
                'email'         => $email,
                'phone'         => $phone,
                'referral_type' => $referral_type,
                'referral_code' => $referral_code,
                'tier'          => 'concierge',
                'city'          => $city,
                'state'         => $state,
                'notes'         => $notes,
                'source'        => 'form',
                'ip_address'    => $ip,
                'user_agent'    => $ua,
            )
        );

        $admin_email = get_option( 'admin_email' );
        $headers     = $this->get_email_headers();

        // Admin email
        $subject_admin = 'New Rewards Program Sign-Up: ' . $full_name;
        $admin_body  = '<p>A new <strong>Divine Wealth Rewards</strong> sign-up has been submitted.</p>';
        $admin_body .= '<p><strong>Name:</strong> ' . esc_html( $full_name ) . '<br>';
        if ( $business_name ) $admin_body .= '<strong>Business:</strong> ' . esc_html( $business_name ) . '<br>';
        $admin_body .= '<strong>Email:</strong> ' . esc_html( $email ) . '<br>';
        if ( $phone ) $admin_body .= '<strong>Phone:</strong> ' . esc_html( $phone ) . '<br>';
        if ( $referral_type ) $admin_body .= '<strong>Referral Type:</strong> ' . esc_html( ucfirst( str_replace( '_', ' ', $referral_type ) ) ) . '<br>';
        if ( $city || $state ) $admin_body .= '<strong>Location:</strong> ' . esc_html( trim( $city . ', ' . $state, ', ' ) ) . '<br>';
        $admin_body .= '<strong>Referral Code:</strong> ' . esc_html( $referral_code ) . '</p>';
        if ( $notes ) $admin_body .= '<p><strong>Notes / Goals:</strong><br>' . nl2br( esc_html( $notes ) ) . '</p>';

        wp_mail(
            $admin_email,
            $subject_admin,
            $this->build_email_html( 'New Rewards Member', $admin_body ),
            $headers
        );

        // User email
        $subject_user = 'Welcome to Divine Wealth Rewards';
        $user_body  = '<p>Hi ' . esc_html( $full_name ) . ',</p>';
        $user_body .= '<p>Thank you for joining the <strong>Divine Wealth Rewards Program</strong>!</p>';
        $user_body .= '<p>You can start earning <strong>$25 for every completed tax referral</strong> and unlock travel & business perks as your tier grows.</p>';
        $user_body .= '<div style="margin:18px 0; padding:14px 16px; border-radius:10px; border:1px solid #D4AF37; background-color:#fff7e0; text-align:center;">';
        $user_body .= '<div style="font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#7b672f; margin-bottom:4px;">Your Rewards Referral Code</div>';
        $user_body .= '<div style="font-size:22px; font-weight:700; color:#2d2a3e; letter-spacing:0.12em;">' . esc_html( $referral_code ) . '</div>';
        $user_body .= '</div>';
        $user_body .= '<p>Share this code with clients when they book or file. We’ll track your referrals automatically.</p>';
        $user_body .= '<p style="margin-top:18px;">With gratitude,<br>Divine Wealth Solutions</p>';

        wp_mail(
            $email,
            $subject_user,
            $this->build_email_html( 'Welcome to Divine Wealth Rewards', $user_body ),
            $headers
        );

        $redirect = ! empty( $_POST['dw_redirect_url'] )
            ? esc_url_raw( wp_unslash( $_POST['dw_redirect_url'] ) )
            : home_url( '/' );

        wp_safe_redirect( add_query_arg( 'dw_rewards_success', '1', $redirect ) );
        exit;
    }

    private function handle_travel_request() {
        if (
            empty( $_POST['dw_travel_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['dw_travel_nonce'] ) ),
                'dw_travel_request'
            )
        ) return;

        if ( ! empty( $_POST['dw_hp'] ) ) return;

        $full_name      = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
        $email          = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone          = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $trip_purpose   = sanitize_text_field( wp_unslash( $_POST['trip_purpose'] ?? '' ) );
        $departure_city = sanitize_text_field( wp_unslash( $_POST['departure_city'] ?? '' ) );
        $destination    = sanitize_text_field( wp_unslash( $_POST['destination'] ?? '' ) );
        $depart_date    = sanitize_text_field( wp_unslash( $_POST['depart_date'] ?? '' ) );
        $return_date    = sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) );
        $travelers      = intval( $_POST['travelers'] ?? 0 );
        $budget         = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );
        $flex_dates     = ! empty( $_POST['flexible_dates'] ) ? 1 : 0;
        $flex_airports  = ! empty( $_POST['flexible_airports'] ) ? 1 : 0;
        $notes          = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $ref_code       = sanitize_text_field( wp_unslash( $_POST['referral_code'] ?? '' ) );

        $services = array();
        if ( ! empty( $_POST['services'] ) && is_array( $_POST['services'] ) ) {
            foreach ( $_POST['services'] as $service ) {
                $services[] = sanitize_text_field( wp_unslash( $service ) );
            }
        }
        $services_str = implode( ', ', $services );

        global $wpdb;
        $travel_table = $wpdb->prefix . 'dw_travel_requests';

        $member_id = null;
        $tier      = null;

        if ( $ref_code ) {
            $member = $this->find_member_by_code( $ref_code );
            if ( $member ) {
                $member_id = (int) $member->id;
                $tier      = $member->tier;
            }
        }

        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        $ua = sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

        $wpdb->insert(
            $travel_table,
            array(
                'created_at'             => current_time( 'mysql' ),
                'full_name'              => $full_name,
                'email'                  => $email,
                'phone'                  => $phone,
                'trip_purpose'           => $trip_purpose,
                'departure_city'         => $departure_city,
                'destination'            => $destination,
                'depart_date'            => $depart_date ?: null,
                'return_date'            => $return_date ?: null,
                'travelers'              => $travelers,
                'services'               => $services_str,
                'budget'                 => $budget,
                'flexible_dates'         => $flex_dates,
                'flexible_airports'      => $flex_airports,
                'notes'                  => $notes,
                'referral_code'          => $ref_code,
                'member_id'              => $member_id,
                'member_tier_at_request' => $tier,
                'ip_address'             => $ip,
                'user_agent'             => $ua,
            )
        );

        $admin_email = get_option( 'admin_email' );
        $headers     = $this->get_email_headers();

        $subject    = 'New Travel Concierge Request from ' . $full_name;
        $admin_body = '<p>You’ve received a new <strong>Travel Concierge Request</strong>.</p>';
        $admin_body .= '<p><strong>Name:</strong> ' . esc_html( $full_name ) . '<br>';
        $admin_body .= '<strong>Email:</strong> ' . esc_html( $email ) . '<br>';
        if ( $phone ) $admin_body .= '<strong>Phone:</strong> ' . esc_html( $phone ) . '<br>';
        if ( $trip_purpose ) $admin_body .= '<strong>Traveling for:</strong> ' . esc_html( ucfirst( $trip_purpose ) ) . '<br>';
        if ( $departure_city ) $admin_body .= '<strong>Departure:</strong> ' . esc_html( $departure_city ) . '<br>';
        if ( $destination ) $admin_body .= '<strong>Destination(s):</strong> ' . esc_html( $destination ) . '<br>';
        if ( $depart_date || $return_date ) $admin_body .= '<strong>Dates:</strong> ' . esc_html( $depart_date ?: 'N/A' ) . ' → ' . esc_html( $return_date ?: 'N/A' ) . '<br>';
        if ( $travelers ) $admin_body .= '<strong>Travelers:</strong> ' . intval( $travelers ) . '<br>';
        if ( $services_str ) $admin_body .= '<strong>Services:</strong> ' . esc_html( $services_str ) . '<br>';
        if ( $budget ) $admin_body .= '<strong>Budget:</strong> ' . esc_html( $budget ) . '<br>';
        $admin_body .= '<strong>Flexible dates:</strong> ' . ( $flex_dates ? 'Yes' : 'No' ) . '<br>';
        $admin_body .= '<strong>Flexible airports:</strong> ' . ( $flex_airports ? 'Yes' : 'No' ) . '</p>';

        if ( $ref_code ) {
            $admin_body .= '<p><strong>Referral Code:</strong> ' . esc_html( $ref_code );
            if ( $tier ) $admin_body .= '<br><strong>Tier at Request:</strong> ' . esc_html( ucfirst( $tier ) );
            $admin_body .= '</p>';
        }

        if ( $notes ) $admin_body .= '<p><strong>Notes:</strong><br>' . nl2br( esc_html( $notes ) ) . '</p>';

        wp_mail(
            $admin_email,
            $subject,
            $this->build_email_html( 'New Travel Concierge Request', $admin_body ),
            $headers
        );

        // User confirmation email
        if ( $email ) {
            $subject_user = 'We Received Your Travel Concierge Request';
            $user_body  = '<p>Hi ' . esc_html( $full_name ) . ',</p>';
            $user_body .= '<p>Thank you for submitting a <strong>Travel Concierge Request</strong>.</p>';
            $user_body .= '<p>We will review your request and follow up with curated options soon.</p>';

            wp_mail(
                $email,
                $subject_user,
                $this->build_email_html( 'We Received Your Travel Request', $user_body ),
                $headers
            );
        }

        $redirect = ! empty( $_POST['dw_redirect_url'] )
            ? esc_url_raw( wp_unslash( $_POST['dw_redirect_url'] ) )
            : home_url( '/' );

        wp_safe_redirect( add_query_arg( 'dw_travel_success', '1', $redirect ) );
        exit;
    }

    private function handle_business_perks_request() {

        $redirect = ! empty( $_POST['dw_redirect_url'] )
            ? esc_url_raw( wp_unslash( $_POST['dw_redirect_url'] ) )
            : home_url( '/' );

        if (
            empty( $_POST['dw_business_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['dw_business_nonce'] ) ),
                'dw_business_request'
            )
        ) return;

        if ( ! empty( $_POST['dw_hp'] ) ) return;

        $full_name     = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
        $email         = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone         = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
        $perk_type     = sanitize_text_field( wp_unslash( $_POST['perk_type'] ?? '' ) );
        $details       = sanitize_textarea_field( wp_unslash( $_POST['details'] ?? '' ) );
        $notes         = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $ref_code      = sanitize_text_field( wp_unslash( $_POST['referral_code'] ?? '' ) );

        global $wpdb;
        $business_table = $wpdb->prefix . 'dw_business_perks_requests';

        $member_id = null;
        $tier      = null;
        $member    = null;

        if ( $ref_code ) {
            $member = $this->find_member_by_code( $ref_code );
            if ( $member ) {
                $member_id = (int) $member->id;
                $tier      = $member->tier;
            }
        }

        // ✅ Enforce BUSINESS eligibility
        if ( empty( $ref_code ) || ! $this->is_business_partner_code( $member ) ) {

            $redirect = ! empty( $_POST['dw_redirect_url'] )
                ? esc_url_raw( wp_unslash( $_POST['dw_redirect_url'] ) )
                : home_url( '/' );

            $redirect = add_query_arg( 'dw_business_error', 'not_eligible', $redirect );

            wp_safe_redirect( $redirect );
            exit;
        }

        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        $ua = sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

        $wpdb->insert(
            $business_table,
            array(
                'created_at'             => current_time( 'mysql' ),
                'full_name'              => $full_name,
                'email'                  => $email,
                'phone'                  => $phone,
                'business_name'          => $business_name,
                'referral_code'          => $ref_code,
                'referral_type'          => $member ? $member->referral_type : null,

                'member_id'              => $member_id,
                'member_tier_at_request' => $tier,
                'perk_type'              => $perk_type,
                'details'                => $details,
                'notes'                  => $notes,
                'ip_address'             => $ip,
                'user_agent'             => $ua,
            )
        );

        // ===== EMAILS: BUSINESS PERKS REQUEST =====
        $admin_email = get_option( 'admin_email' );
        $headers     = $this->get_email_headers();

        // Admin email
        $subject_admin = 'New Business Perks Request from ' . $full_name;

        $admin_body  = '<p>You received a new <strong>Business Perks Request</strong>.</p>';
        $admin_body .= '<p><strong>Name:</strong> ' . esc_html( $full_name ) . '<br>';
        $admin_body .= '<strong>Email:</strong> ' . esc_html( $email ) . '<br>';

        if ( $phone ) {
            $admin_body .= '<strong>Phone:</strong> ' . esc_html( $phone ) . '<br>';
        }
        if ( $business_name ) {
            $admin_body .= '<strong>Business:</strong> ' . esc_html( $business_name ) . '<br>';
        }
        if ( $perk_type ) {
            $admin_body .= '<strong>Perk Requested:</strong> ' . esc_html( $perk_type ) . '<br>';
        }
        if ( $ref_code ) {
            $admin_body .= '<strong>Referral Code:</strong> ' . esc_html( $ref_code ) . '<br>';
            if ( $tier ) {
                $admin_body .= '<strong>Tier at Request:</strong> ' . esc_html( ucfirst( $tier ) ) . '<br>';
            }
        }
        $admin_body .= '</p>';

        if ( $details ) {
            $admin_body .= '<p><strong>Details:</strong><br>' . nl2br( esc_html( $details ) ) . '</p>';
        }
        if ( $notes ) {
            $admin_body .= '<p><strong>Notes:</strong><br>' . nl2br( esc_html( $notes ) ) . '</p>';
        }

        wp_mail(
            $admin_email,
            $subject_admin,
            $this->build_email_html( 'New Business Perks Request', $admin_body ),
            $headers
        );

        // User confirmation email
        if ( $email ) {
            $subject_user = 'We Received Your Business Perks Request';

            $user_body  = '<p>Hi ' . esc_html( $full_name ) . ',</p>';
            $user_body .= '<p>Thank you for submitting your <strong>Business Perks Request</strong>.</p>';

            $user_body .= '<ul style="padding-left:18px; margin-top:6px;">';
            if ( $business_name ) $user_body .= '<li><strong>Business:</strong> ' . esc_html( $business_name ) . '</li>';
            if ( $perk_type ) $user_body .= '<li><strong>Perk Requested:</strong> ' . esc_html( $perk_type ) . '</li>';
            if ( $details ) $user_body .= '<li><strong>Details:</strong> ' . esc_html( $details ) . '</li>';
            $user_body .= '</ul>';

            if ( $ref_code ) {
                $user_body .= '<p style="margin-top:12px;">We linked this request to your Rewards code:</p>';
                $user_body .= '<div style="margin:10px 0 16px; padding:12px 14px; border-radius:10px; border:1px solid #D4AF37; background:#fff7e0; text-align:center;">';
                $user_body .= '<div style="font-size:11px; letter-spacing:0.08em; text-transform:uppercase; color:#7b672f; margin-bottom:4px;">Rewards Referral Code</div>';
                $user_body .= '<div style="font-size:20px; font-weight:700; color:#2d2a3e; letter-spacing:0.12em;">' . esc_html( $ref_code ) . '</div>';
                if ( $tier ) {
                    $user_body .= '<div style="font-size:12px; margin-top:4px; color:#4a3a1a;">Current tier: ' . esc_html( ucfirst( $tier ) ) . '</div>';
                }
                $user_body .= '</div>';
            }

            $user_body .= '<p>Our team will review your request and reach out with next steps.</p>';
            $user_body .= '<p style="margin-top:18px;">With gratitude,<br>Divine Wealth Solutions</p>';

            wp_mail(
                $email,
                $subject_user,
                $this->build_email_html( 'Business Perks Request Received', $user_body ),
                $headers
            );
        }

        wp_safe_redirect( add_query_arg( 'dw_business_success', '1', $redirect ) );
        exit;
    }

    private function find_member_by_code( $code ) {
        global $wpdb;
        $members_table = $wpdb->prefix . 'dw_rewards_members';
        if ( ! $code ) return null;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$members_table} WHERE referral_code = %s LIMIT 1",
                $code
            )
        );
    }

    /**
     * Check if a referral code belongs to a business-eligible Rewards member.
     * Allowed referral_type: business_partner or both
     */
    private function is_business_partner_code( $member ) {
        if ( ! $member ) return false;

        $type = strtolower( trim( (string) $member->referral_type ) );

        return in_array( $type, array( 'business_partner', 'both' ), true );
    }

    /* =========================================================
     * FRONTEND SHORTCODES
     * ======================================================= */

    public function render_rewards_form() {
        $template = locate_template( 'divine-wealth/dw-rewards-form.php' );
        if ( $template ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }

        $current_url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );
        $success     = isset( $_GET['dw_rewards_success'] ) && '1' === $_GET['dw_rewards_success'];

        ob_start();
        if ( $success ) : ?>
            <div class="dw-alert dw-alert--full dw-alert--success" data-dw-alert="rewards">
                <div class="dw-alert__inner">
                    <span class="dw-alert__icon">✓</span>
                    <span class="dw-alert__message">
                        Thank you for joining <strong>Divine Wealth Rewards</strong>!
                        Check your email for your referral code and next steps.
                    </span>
                    <button type="button" class="dw-alert__close" aria-label="Dismiss message">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="dw-form dw-form--rewards">
            <?php wp_nonce_field( 'dw_rewards_signup', 'dw_rewards_nonce' ); ?>
            <div style="display:none;"><input type="text" name="dw_hp" value="" /></div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_full_name">Full Name</label>
                    <input type="text" id="dw_full_name" name="full_name" required>
                </div>
                <div class="dw-field">
                    <label for="dw_email">Email</label>
                    <input type="email" id="dw_email" name="email" required>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_phone">Phone</label>
                    <input type="tel" id="dw_phone" name="phone">
                </div>
                <div class="dw-field">
                    <label for="dw_referral_type">Referral Type</label>
                    <select id="dw_referral_type" name="referral_type">
                        <option value="">Select one</option>
                        <option value="client">Client</option>
                        <option value="business_partner">Business Partner</option>
                        <option value="both">Both</option>
                    </select>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_business_name">Business Name (optional)</label>
                    <input type="text" id="dw_business_name" name="business_name">
                </div>
                <div class="dw-field">
                    <label for="dw_city">City</label>
                    <input type="text" id="dw_city" name="city">
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_state">State</label>
                    <input type="text" id="dw_state" name="state">
                </div>
                <div class="dw-field">
                    <label for="dw_notes">Notes or Goals (optional)</label>
                    <textarea id="dw_notes" name="notes" rows="3"></textarea>
                </div>
            </div>

            <input type="hidden" name="dw_form_type" value="rewards_signup" />
            <input type="hidden" name="dw_redirect_url" value="<?php echo esc_url( $current_url ); ?>" />

            <div class="submit-row">
                <button type="submit" class="dw-btn dw-btn--gold">Join &amp; Get My Referral Code</button>
            </div>
        </form>

        <?php
        return ob_get_clean();
    }

    public function render_travel_form() {

        $template = locate_template( 'divine-wealth/dw-travel-form.php' );
        if ( $template ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }

        $current_url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );
        $success     = isset( $_GET['dw_travel_success'] ) && $_GET['dw_travel_success'] === '1';

        ob_start();

        if ( $success ) : ?>
            <div class="dw-alert dw-alert--full dw-alert--success" data-dw-alert="travel">
                <div class="dw-alert__inner">
                    <span class="dw-alert__icon">✈</span>
                    <span class="dw-alert__message">
                        Thank you! Your <strong>Travel Concierge Request</strong> has been submitted.
                        We’ll follow up with curated options soon.
                    </span>
                    <button type="button" class="dw-alert__close" aria-label="Dismiss message">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="dw-form dw-form--travel">
            <?php wp_nonce_field( 'dw_travel_request', 'dw_travel_nonce' ); ?>

            <div style="display:none;">
                <input type="text" name="dw_hp" value="" />
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_travel_name">Full Name</label>
                    <input type="text" id="dw_travel_name" name="full_name" required>
                </div>
                <div class="dw-field">
                    <label for="dw_travel_email">Email</label>
                    <input type="email" id="dw_travel_email" name="email" required>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_travel_phone">Phone</label>
                    <input type="tel" id="dw_travel_phone" name="phone">
                </div>
                <div class="dw-field">
                    <label for="dw_trip_purpose">Are you traveling for:</label>
                    <select id="dw_trip_purpose" name="trip_purpose">
                        <option value="">Select one</option>
                        <option value="personal">Personal</option>
                        <option value="business">Business</option>
                        <option value="group">Group / Retreat</option>
                    </select>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_departure_city">Departure City / Airport</label>
                    <input type="text" id="dw_departure_city" name="departure_city">
                </div>
                <div class="dw-field">
                    <label for="dw_destination">Destination(s)</label>
                    <input type="text" id="dw_destination" name="destination">
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_depart_date">Departure Date</label>
                    <input type="date" id="dw_depart_date" name="depart_date">
                </div>
                <div class="dw-field">
                    <label for="dw_return_date">Return Date</label>
                    <input type="date" id="dw_return_date" name="return_date">
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_travelers">Number of Travelers</label>
                    <input type="number" id="dw_travelers" name="travelers" min="1" step="1">
                </div>
                <div class="dw-field">
                    <label for="dw_budget">Approximate Budget</label>
                    <select id="dw_budget" name="budget">
                        <option value="">Select</option>
                        <option value="under_1000">Under $1,000</option>
                        <option value="1000_3000">$1,000 - $3,000</option>
                        <option value="3000_5000">$3,000 - $5,000</option>
                        <option value="5000_plus">$5,000+</option>
                    </select>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label><input type="checkbox" name="flexible_dates" value="1"> Dates are flexible</label>
                </div>
                <div class="dw-field">
                    <label><input type="checkbox" name="flexible_airports" value="1"> Airports are flexible</label>
                </div>
            </div>

            <!-- Referral code FIRST -->
            <div class="dw-field">
                <label for="dw_referral_code">Rewards Referral Code</label>
                <input type="text" id="dw_referral_code" name="referral_code" autocomplete="off">
                <div id="dw_tier_badge" class="dw-tier-badge">Not linked to a Rewards code yet.</div>
                <div class="dw-body-note">Enter your Rewards referral code so perks apply correctly.</div>
            </div>

            <!-- Services -->
            <div class="dw-field">
                <label>What do you want us to help with?</label>

                <label class="dw-perk-option">
                    <input type="checkbox" name="services[]" value="flights" data-required-tier="concierge">
                    Flights
                </label>

                <label class="dw-perk-option">
                    <input type="checkbox" name="services[]" value="hotels" data-required-tier="concierge">
                    Hotels / Resorts
                </label>

                <label class="dw-perk-option">
                    <input type="checkbox" name="services[]" value="cruise" data-required-tier="elite">
                    Cruise Planning
                </label>

                <label class="dw-perk-option dw-perk-locked" data-dw-tooltip="Activities & excursions unlock at Platinum Partner level.">
                    <input type="checkbox" name="services[]" value="activities" data-required-tier="platinum">
                    Activities & Excursions <span class="dw-perk-lock-indicator">🔒</span>
                </label>
            </div>

            <div class="dw-field">
                <label for="dw_travel_notes">Special Notes or Preferences</label>
                <textarea id="dw_travel_notes" name="notes" rows="4"></textarea>
            </div>

            <input type="hidden" name="dw_form_type" value="travel_request" />
            <input type="hidden" name="dw_redirect_url" value="<?php echo esc_url( $current_url ); ?>" />

            <div class="submit-row">
                <button type="submit" class="dw-btn dw-btn--gold">Submit Travel Request</button>
            </div>
        </form>

        <?php
        return ob_get_clean();
    }

    public function render_business_perks_form() {

        $error = isset( $_GET['dw_business_error'] )
            ? sanitize_text_field( $_GET['dw_business_error'] )
            : '';

        $template = locate_template( 'divine-wealth/dw-business-perks-form.php' );
        if ( $template ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }

        $current_url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );
        $success     = isset( $_GET['dw_business_success'] ) && $_GET['dw_business_success'] === '1';

        ob_start();

        if ( $success ) : ?>
            <div class="dw-alert dw-alert--full dw-alert--success" data-dw-alert="business">
                <div class="dw-alert__inner">
                    <span class="dw-alert__icon">🏢</span>
                    <span class="dw-alert__message">
                        Thanks! Your <strong>Business Perks Request</strong> has been submitted.
                    </span>
                    <button type="button" class="dw-alert__close" aria-label="Dismiss message">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $error === 'not_eligible' ) : ?>
            <div class="dw-alert dw-alert--full dw-alert--error" data-dw-alert="business">
                <div class="dw-alert__inner">
                    <span class="dw-alert__icon">⚠</span>
                    <span class="dw-alert__message">
                        This reward is only available to <strong>Business Partners</strong>.
                        Please enter a Business Partner referral code to continue.
                    </span>
                    <button type="button" class="dw-alert__close" aria-label="Dismiss message">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="dw-form dw-form--business">
            <?php wp_nonce_field( 'dw_business_request', 'dw_business_nonce' ); ?>

            <div style="display:none;">
                <input type="text" name="dw_hp" value="" />
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_bus_full_name">Full Name</label>
                    <input type="text" id="dw_bus_full_name" name="full_name" required>
                </div>
                <div class="dw-field">
                    <label for="dw_bus_email">Email</label>
                    <input type="email" id="dw_bus_email" name="email" required>
                </div>
            </div>

            <div class="form-row form-row--2">
                <div class="dw-field">
                    <label for="dw_bus_phone">Phone</label>
                    <input type="tel" id="dw_bus_phone" name="phone">
                </div>
                <div class="dw-field">
                    <label for="dw_bus_business_name">Business Name</label>
                    <input type="text" id="dw_bus_business_name" name="business_name">
                </div>
            </div>

            <div class="dw-field">
                <label for="dw_bus_referral_code">Rewards Referral Code (optional)</label>
                <input type="text" id="dw_bus_referral_code" name="referral_code" autocomplete="off">
                <div id="dw_business_tier_badge" class="dw-tier-badge">
                    Enter a valid Business Partner referral code.
                </div>
            </div>

            <div class="dw-field">
                <label for="dw_bus_perk_type">What perk are you requesting?</label>
                <select id="dw_bus_perk_type" name="perk_type">
                    <option value="">Select one</option>
                    <option value="branding">Branding / Logo / Identity</option>
                    <option value="website_credit">Website Credit / Web Design</option>
                    <option value="apparel">Business Apparel / Merchandise</option>
                    <option value="marketing">Marketing / Promo Materials</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="dw-field">
                <label for="dw_bus_details">Details about what you need</label>
                <textarea id="dw_bus_details" name="details" rows="4"></textarea>
            </div>

            <div class="dw-field">
                <label for="dw_bus_notes">Notes / Timeline / Preferences</label>
                <textarea id="dw_bus_notes" name="notes" rows="3"></textarea>
            </div>

            <input type="hidden" name="dw_form_type" value="business_perks_request" />
            <input type="hidden" name="dw_redirect_url" value="<?php echo esc_url( $current_url ); ?>" />

            <div class="submit-row">
                <button type="submit" id="dw_bus_submit" class="dw-btn dw-btn--gold">Submit Business Perks Request</button>
            </div>
        </form>

        <?php
        return ob_get_clean();
    }

    /**
     * Frontend: Rewards Summary / Partner Dashboard
     *
     * Shortcode: [dw_rewards_summary referral_code="CODE-HERE"]
     * - If referral_code attribute is missing, it will also look for:
     *   ?dw_code=... or ?ref=... in the URL.
     */
   public function render_rewards_summary( $atts ) {
     global $wpdb;

    // Make sure payout table exists before trying to insert
    $this->maybe_create_payout_table();

    $atts = shortcode_atts(
        array(
            'referral_code' => '',
        ),
        $atts,
        'dw_rewards_summary'
    );

    // 1) Determine referral code: shortcode attr OR URL (?dw_code= / ?ref=)
    $code = $atts['referral_code'];

    if ( ! $code && isset( $_GET['dw_code'] ) ) {
        $code = sanitize_text_field( wp_unslash( $_GET['dw_code'] ) );
    } elseif ( ! $code && isset( $_GET['ref'] ) ) {
        $code = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
    }

    $code = trim( $code );

    // Initialize payout notice vars so they’re always defined
    $payout_notice      = '';
    $payout_notice_type = ''; // 'success' or 'error'

    ob_start();

    echo '<div class="dw-card dw-card--summary">';

    if ( ! $code ) {
        echo '<p>Please provide your Rewards code to view your current tier and perks.</p>';
        echo '<form method="get" class="dw-form dw-form--inline">';
        echo '<div class="dw-field">';
        echo '<label for="dw_summary_code">Rewards Code</label>';
        echo '<input type="text" id="dw_summary_code" name="dw_code" value="" placeholder="ENTER-CODE-1234" />';
        echo '</div>';
        echo '<button type="submit" class="dw-btn dw-btn--gold">View My Rewards</button>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }

    // 2) Look up member by code
    $member = $this->find_member_by_code( $code );

    if ( ! $member ) {
        echo '<div class="dw-alert dw-alert--full dw-alert--error">';
        echo '<div class="dw-alert__inner">';
        echo '<span class="dw-alert__icon">⚠</span>';
        echo '<span class="dw-alert__message">We couldn\'t find a Rewards member with that code. Please double-check and try again.</span>';
        echo '</div></div>';
        echo '</div>';
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Payout request handling (front-end form)
    // ------------------------------------------------------------------
    if (
        'POST' === $_SERVER['REQUEST_METHOD']
        && isset( $_POST['dw_payout_request'] )
        && isset( $_POST['dw_rewards_code'] )
    ) {
        $posted_code = sanitize_text_field( wp_unslash( $_POST['dw_rewards_code'] ) );

        // Ensure the posted code matches the member we’re showing
        if ( hash_equals( $member->referral_code, $posted_code ) ) {
            $nonce_ok = isset( $_POST['dw_payout_nonce'] )
                && wp_verify_nonce(
                    sanitize_text_field( wp_unslash( $_POST['dw_payout_nonce'] ) ),
                    'dw_payout_' . $member->id
                );

            if ( ! $nonce_ok ) {
                $payout_notice      = 'Security check failed. Please reload the page and try again.';
                $payout_notice_type = 'error';
            } else {
                $amount_raw = isset( $_POST['dw_payout_amount'] )
                    ? sanitize_text_field( wp_unslash( $_POST['dw_payout_amount'] ) )
                    : '';

                // Basic numeric clean-up
                $amount_requested = floatval( preg_replace( '/[^0-9.\-]/', '', $amount_raw ) );

                $request_type = isset( $_POST['dw_request_type'] )
                    ? sanitize_text_field( wp_unslash( $_POST['dw_request_type'] ) )
                    : 'cash';

                if ( ! in_array( $request_type, array( 'cash', 'service' ), true ) ) {
                    $request_type = 'cash';
                }

                $payout_method = isset( $_POST['dw_payout_method'] )
                    ? sanitize_text_field( wp_unslash( $_POST['dw_payout_method'] ) )
                    : 'cashapp';

                if ( ! in_array( $payout_method, array( 'cashapp', 'paypal', 'zelle', 'cash', 'check', 'other' ), true ) ) {
                    $payout_method = 'cashapp';
                }

                $details = isset( $_POST['dw_payout_details'] )
                    ? wp_kses_post( wp_unslash( $_POST['dw_payout_details'] ) )
                    : '';

                if ( $amount_requested <= 0 ) {
                    $payout_notice      = 'Please enter a valid payout amount.';
                    $payout_notice_type = 'error';
                } else {
                   $table_payouts = $this->get_payout_table_name();

/**
 * Detect existing columns so we don't reference ones
 * that aren't in the current DB schema.
 */
$columns_raw = $wpdb->get_results( "SHOW COLUMNS FROM {$table_payouts}", OBJECT_K );

$insert_data    = array();
$insert_formats = array();

/* Always insert these core fields (they should exist). */
$insert_data['member_id']        = (int) $member->id;
$insert_data['rewards_code']     = $member->referral_code;
$insert_data['amount_requested'] = $amount_requested;

$insert_formats[] = '%d';
$insert_formats[] = '%s';
$insert_formats[] = '%f';

/* Optional columns – only add if present in the table. */
if ( isset( $columns_raw['request_type'] ) ) {
    $insert_data['request_type'] = $request_type;
    $insert_formats[]            = '%s';
}

if ( isset( $columns_raw['payout_method'] ) ) {
    $insert_data['payout_method'] = $payout_method;
    $insert_formats[]             = '%s';
}

/* Details column might be named payout_details OR details. */
if ( isset( $columns_raw['payout_details'] ) ) {
    $insert_data['payout_details'] = $details;
    $insert_formats[]              = '%s';
} elseif ( isset( $columns_raw['details'] ) ) {
    $insert_data['details'] = $details;
    $insert_formats[]       = '%s';
}

if ( isset( $columns_raw['status'] ) ) {
    $insert_data['status'] = 'new';
    $insert_formats[]      = '%s';
}

$now = current_time( 'mysql' );
if ( isset( $columns_raw['created_at'] ) ) {
    $insert_data['created_at'] = $now;
    $insert_formats[]          = '%s';
}
if ( isset( $columns_raw['updated_at'] ) ) {
    $insert_data['updated_at'] = $now;
    $insert_formats[]          = '%s';
}

/** Finally perform the insert. */
$inserted = $wpdb->insert(
    $table_payouts,
    $insert_data,
    $insert_formats
);

if ( false === $inserted ) {
    // Build a safer debug string only for admins.
    $debug = '';

    if ( current_user_can( 'manage_options' ) ) {
        $col_names = array_keys( (array) $columns_raw );

        $debug  = ' DB error: ' . $wpdb->last_error;
        $debug .= ' | Columns: ' . implode( ', ', $col_names );
        $debug .= ' | Insert keys: ' . implode( ', ', array_keys( $insert_data ) );
    }

    $payout_notice      = 'Something went wrong while saving your request. Please try again or contact support.'
                        . ( $debug ? ' [' . esc_html( $debug ) . ']' : '' );
    $payout_notice_type = 'error';
} else {
    $payout_notice      = 'Your payout request has been submitted. We\'ll review it and update the status inside our system.';
    $payout_notice_type = 'success';
}



                }
            }
        }
    }

    // 3) Tier metadata (labels + perk descriptions)
    $tier_slug = strtolower( $member->tier ?: 'concierge' );

    $tiers_meta = array(
        'concierge' => array(
            'label' => 'Concierge Access',
            'blurb' => 'Perfect for clients and new partners starting to send referrals.',
            'perks' => array(
                '$25 for every completed tax referral',
                'Access to Travel Concierge for basic trip planning (flights & hotels)',
                'Invites to select Divine Wealth events & webinars',
            ),
        ),
        'elite' => array(
            'label' => 'Elite Concierge',
            'blurb' => 'You’re sending consistent referrals and unlocking enhanced perks.',
            'perks' => array(
                '$25+ per completed tax referral (based on custom agreements)',
                'Priority Travel Concierge responses on trip requests',
                'Occasional bonus perks (room upgrades, extra credits when available)',
                'Priority consideration for Business Perks requests',
            ),
        ),
        'premier' => array(
            'label' => 'Premier Partner',
            'blurb' => 'High–value referral partner with access to deeper perks.',
            'perks' => array(
                'Higher–tier referral payouts (per custom agreement)',
                'Access to select Business Perks (branding, website credits, apparel)',
                'Group & retreat travel planning perks when available',
                'Early access to select Divine Wealth programs & launches',
            ),
        ),
        'platinum' => array(
            'label' => 'Platinum Partner',
            'blurb' => 'Top–tier partner with VIP treatment across travel & business perks.',
            'perks' => array(
                'Custom referral payout structure',
                'VIP Travel Concierge support, including activities & excursions',
                'Priority Business Perks fulfillment (branding, web, apparel, marketing)',
                'First access to premium opportunities and collaborations',
            ),
        ),
    );

    $tier_meta = isset( $tiers_meta[ $tier_slug ] ) ? $tiers_meta[ $tier_slug ] : $tiers_meta['concierge'];

    // 4) Totals
    $total_referrals = (int) $member->total_referrals;
    $total_revenue   = (float) $member->total_revenue;

    // 5) Output summary card
    echo '<div class="dw-summary-header">';
    echo '<div class="dw-summary-heading">';

    echo '<div class="dw-summary-label">Divine Wealth Rewards</div>';
    echo '<h2 class="dw-summary-title">' . esc_html( $member->full_name ) . '</h2>';
    if ( $member->business_name ) {
        echo '<div class="dw-summary-subtitle">' . esc_html( $member->business_name ) . '</div>';
    }

    echo '</div>'; // .dw-summary-heading

    echo '<div class="dw-summary-tier">';
    echo '<div class="dw-summary-tier-label">Current Tier</div>';
    echo '<div class="dw-summary-tier-name">' . esc_html( $tier_meta['label'] ) . '</div>';
    echo '<div class="dw-summary-tier-code"><span>Rewards Code:</span> <code>' . esc_html( $member->referral_code ) . '</code></div>';
    echo '</div>'; // .dw-summary-tier

    echo '</div>'; // .dw-summary-header

    // Stats
    echo '<div class="dw-summary-stats">';
    echo '<div class="dw-summary-stat">';
    echo '<div class="dw-summary-stat-label">Total Referrals</div>';
    echo '<div class="dw-summary-stat-value">' . esc_html( $total_referrals ) . '</div>';
    echo '</div>';

    echo '<div class="dw-summary-stat">';
    echo '<div class="dw-summary-stat-label">Tracked Revenue</div>';
    echo '<div class="dw-summary-stat-value">$' . esc_html( number_format( $total_revenue, 2 ) ) . '</div>';
    echo '</div>';
    echo '</div>'; // .dw-summary-stats

    // Perks
    echo '<div class="dw-summary-perks">';
    echo '<h3>Your Current Perks</h3>';
    if ( ! empty( $tier_meta['blurb'] ) ) {
        echo '<p class="dw-summary-perks-blurb">' . esc_html( $tier_meta['blurb'] ) . '</p>';
    }

    if ( ! empty( $tier_meta['perks'] ) && is_array( $tier_meta['perks'] ) ) {
        echo '<ul class="dw-summary-perks-list">';
        foreach ( $tier_meta['perks'] as $perk ) {
            echo '<li>' . esc_html( $perk ) . '</li>';
        }
        echo '</ul>';
    }

    echo '</div>'; // .dw-summary-perks

    // ------------------------------------------------------------------
    // Payout notice + form
    // ------------------------------------------------------------------
    if ( ! empty( $payout_notice ) ) {
        if ( 'success' === $payout_notice_type ) {
            echo '<div class="dw-success" style="margin-top:1.25rem;">' . esc_html( $payout_notice ) . '</div>';
        } else {
            echo '<div class="dw-alert dw-alert--error" style="margin-top:1.25rem;"><div class="dw-alert__inner"><span class="dw-alert__message">'
               . esc_html( $payout_notice )
               . '</span></div></div>';
        }
    }

    echo '<hr style="margin:2rem 0; border:none; border-top:1px solid #e5e5e5;">';

    echo '<h3 style="font-size:1.15rem; margin-bottom:.75rem;">Request a Payout</h3>';
    echo '<p style="max-width:540px;">Use this form to request a cash payout or apply your rewards toward services. We’ll review your referrals and confirm the amount before payment is sent.</p>';

    echo '<form method="post" class="dw-form" style="max-width:520px; margin-top:1rem;">';
    echo wp_nonce_field( 'dw_payout_' . $member->id, 'dw_payout_nonce', true, false );
    echo '<input type="hidden" name="dw_rewards_code" value="' . esc_attr( $member->referral_code ) . '" />';

    // Amount
    echo '<div class="dw-field">';
    echo '<label for="dw_payout_amount">Amount you’d like to request (USD)</label>';
    echo '<input type="text" id="dw_payout_amount" name="dw_payout_amount" placeholder="e.g. 75.00" required />';
    echo '<p class="hint">You can request cash, or apply this amount toward services.</p>';
    echo '</div>';

    // Request type + method
    echo '<div class="dw-form form-row form-row--2">';

    echo '<div class="dw-field">';
    echo '<label for="dw_request_type">Request Type</label>';
    echo '<select id="dw_request_type" name="dw_request_type">';
    echo '<option value="cash">Cash payout</option>';
    echo '<option value="service">Apply toward services</option>';
    echo '</select>';
    echo '</div>';

    echo '<div class="dw-field">';
    echo '<label for="dw_payout_method">Preferred Method</label>';
    echo '<select id="dw_payout_method" name="dw_payout_method">';
    echo '<option value="cashapp">Cash App</option>';
    echo '<option value="paypal">PayPal</option>';
    echo '<option value="zelle">Zelle</option>';
    echo '<option value="cash">Cash</option>';
    echo '<option value="check">Check</option>';
    echo '<option value="other">Other</option>';
    echo '</select>';
    echo '</div>';

    echo '</div>'; // .form-row

    // Details
    echo '<div class="dw-field">';
    echo '<label for="dw_payout_details">Handle / Email / Extra details</label>';
    echo '<textarea id="dw_payout_details" name="dw_payout_details" rows="3" placeholder="Cash App $Cashtag, PayPal email, Zelle phone/email, or where to apply the credit."></textarea>';
    echo '</div>';

    // Submit
    echo '<div class="submit-row">';
    echo '<button type="submit" name="dw_payout_request" class="dw-btn dw-btn--gold">Submit Payout Request</button>';
    echo '</div>';

    echo '</form>';

    // Original footer text below summary
    echo '<div class="dw-summary-footer">';
    echo '<p>Want to unlock the next tier? Keep sharing your Rewards code with clients when they book tax prep, funding, or travel.</p>';
    echo '</div>';

    echo '</div>'; // .dw-card.dw-card--summary

    return ob_get_clean();
}

    /**
     * Shell layout for the Rewards Hub.
     * Wraps the core summary card in a branded section.
     *
     * Usage: [dw_rewards_hub dw_code="OPTIONAL-CODE"]
     * - If dw_code is omitted, it will use the ?dw_code=... query param
     *   and fall back to the same form your summary already uses.
     */
    public function render_rewards_hub_shell( $atts = array() ) {
        // Let shortcode override the code if provided
        $atts = shortcode_atts(
            array(
                'dw_code' => '',
            ),
            $atts,
            'dw_rewards_hub'
        );

        // Pass attributes straight through to the existing renderer
        // (it already knows how to deal with query-string dw_code as well).
        $summary_html = $this->render_rewards_summary( $atts );

        ob_start();
        ?>
        <section class="section section--light">
            <div class="dw-container">
                <div class="dw-rewards-summary-shell max-800 center">
                    <h2 class="dw-h2 mb-4">My Divine Wealth Rewards</h2>
                    <p class="dw-lead mb-6">
                        Enter your Rewards code to see your current tier, total
                        referrals, and the perks you’ve unlocked with
                        Divine Wealth Solutions.
                    </p>
                </div>

                <div class="dw-rewards-summary-card-wrap mt-4">
                    <?php
                    // This is the existing summary card.
                    echo $summary_html;
                    ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

public function render_rewards_hub() {

    ob_start();
    ?>
    <section class="dw-hub section section--light">
        <div class="dw-container">

            <header class="dw-hub__header center mb-6">
                <h2 class="dw-hub__title">My Divine Wealth Rewards</h2>
                <p class="dw-hub__intro">
                    Enter your Rewards code to see your current tier, total referrals, and the perks
                    you’ve unlocked with Divine Wealth Solutions.
                </p>
            </header>

            <div class="dw-hub__summary">
                <?php
                // Let the existing summary shortcode handle the form + results.
                echo do_shortcode( '[dw_rewards_summary]' );
                ?>
            </div>

        </div>
    </section>
    <?php

    return ob_get_clean();
}


public function render_tier_perks_table() {

    // If you ever want to override with a theme template:
    $template = locate_template( 'divine-wealth/dw-tier-perks-table.php' );
    if ( $template ) {
        ob_start();
        include $template;
        return ob_get_clean();
    }

    // Static config for now – you can tweak text anytime
    $tiers = array(
        'concierge' => array(
            'label'        => 'Concierge Access',
            'requirements' => 'Free sign-up; active client or referral',
            'business'     => 'Access to select partner discounts and basic business resources.',
            'travel'       => 'Trip research, flight & hotel suggestions, basic concierge support.',
            'commission'   => '$25 per completed tax referral',
        ),
        'elite' => array(
            'label'        => 'Elite Concierge',
            'requirements' => '5+ completed referrals or qualifying annual spend',
            'business'     => 'Website credit, priority design queue, select marketing perks.',
            'travel'       => 'Premium flight/hotel options, cruise planning, group trip support.',
            'commission'   => '$35 per completed tax referral',
        ),
        'premier' => array(
            'label'        => 'Premier Partner',
            'requirements' => '10+ completed referrals or partner agreement',
            'business'     => 'Business apparel / merch credits, branding upgrades, promo assets.',
            'travel'       => 'Activities & excursion planning, VIP itinerary curation.',
            'commission'   => '$50 per completed tax referral',
        ),
        'platinum' => array(
            'label'        => 'Platinum Partner',
            'requirements' => '25+ completed referrals or custom partner arrangement',
            'business'     => 'Full branding support, larger website / funnel credits, launch campaigns.',
            'travel'       => 'Full-service concierge, retreat planning, priority & white-glove support.',
            'commission'   => '$75 per completed tax referral',
        ),
    );

    ob_start();
    ?>
    <div class="dw-tier-table-wrap">
        <h2 class="dw-tier-table__heading">Divine Wealth Rewards Tiers</h2>
        <p class="dw-tier-table__intro">
            As your referrals close and your relationship grows with Divine Wealth Solutions,
            you unlock higher tiers of <strong>cash rewards, business perks, and travel concierge services.</strong>
        </p>

        <div class="dw-table-scroll">
            <table class="dw-table dw-table--tiers">
                <thead>
                    <tr>
                        <th>Tier</th>
                        <th>Requirements</th>
                        <th>Business Perks</th>
                        <th>Travel Perks</th>
                        <th>Referral Commission</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $tiers as $slug => $tier ) : ?>
                    <tr class="dw-tier-row dw-tier-row--<?php echo esc_attr( $slug ); ?>">
                        <td class="dw-tier-cell dw-tier-cell--label">
                            <strong><?php echo esc_html( $tier['label'] ); ?></strong>
                        </td>
                        <td><?php echo esc_html( $tier['requirements'] ); ?></td>
                        <td><?php echo esc_html( $tier['business'] ); ?></td>
                        <td><?php echo esc_html( $tier['travel'] ); ?></td>
                        <td><strong><?php echo esc_html( $tier['commission'] ); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="dw-tier-table__note">
            *Tiers and perks may be updated over time. Final eligibility is determined by Divine Wealth Solutions.
        </p>
    </div>
    <?php

    return ob_get_clean();
}

    /* =========================================================
     * AJAX
     * ======================================================= */

    public function ajax_lookup_tier() {
        check_ajax_referer( 'dw_lookup_tier', 'nonce' );

        $code = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
        if ( ! $code ) wp_send_json( array( 'success' => false ) );

        $member = $this->find_member_by_code( $code );
        if ( ! $member ) wp_send_json( array( 'success' => false ) );

        wp_send_json( array(
            'success'       => true,
            'tier'          => $member->tier,
            'referral_type' => $member->referral_type,
        ) );
    }

    /* =========================================================
     * DASHBOARD WIDGET SNAPSHOT
     * ======================================================= */

    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'dw_rewards_overview',
            'Divine Wealth Rewards Overview',
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
    global $wpdb;

    $members_table  = $wpdb->prefix . 'dw_rewards_members';
    $travel_table   = $wpdb->prefix . 'dw_travel_requests';
    $business_table = $wpdb->prefix . 'dw_business_perks_requests';

    // Base totals
    $total_members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$members_table}" );
    $total_travel  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$travel_table}" );

    $business_exists = $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $business_table )
    );
    $total_business = $business_exists
        ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$business_table}" )
        : 0;

    // Tier breakdown
    $tiers = array(
        'concierge' => 0,
        'elite'     => 0,
        'premier'   => 0,
        'platinum'  => 0,
    );

    $rows = $wpdb->get_results(
        "SELECT tier, COUNT(*) AS total FROM {$members_table} GROUP BY tier"
    );

    if ( $rows ) {
        foreach ( $rows as $row ) {
            $key = strtolower( $row->tier );
            if ( isset( $tiers[ $key ] ) ) {
                $tiers[ $key ] = (int) $row->total;
            }
        }
    }

    // How many travel / business requests are linked to a Rewards code
    $linked_travel = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$travel_table} WHERE referral_code IS NOT NULL AND referral_code <> ''"
    );

    $linked_business = $business_exists
        ? (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$business_table} WHERE referral_code IS NOT NULL AND referral_code <> ''"
        )
        : 0;

    /**
     * ===== NEW KPI METRICS =====
     */

    // Latest member date/time
    $latest_member_date = $wpdb->get_var(
        "SELECT created_at FROM {$members_table} ORDER BY created_at DESC LIMIT 1"
    );

    // New members & travel requests in the last 7 days
    $seven_days_ago = gmdate(
        'Y-m-d H:i:s',
        strtotime( current_time( 'mysql' ) . ' -7 days' )
    );

    $new_members_7_days = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$members_table} WHERE created_at >= %s",
            $seven_days_ago
        )
    );

    $new_travel_7_days = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$travel_table} WHERE created_at >= %s",
            $seven_days_ago
        )
    );

    // Referral log KPIs (adjust table name here if yours is different)
    $referral_log_table   = $wpdb->prefix . 'dw_referral_logs';
    $pending_referrals    = 0;
    $total_referrals      = 0;
    $closed_referrals     = 0;

    $referral_table_found = $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $referral_log_table )
    );

    if ( $referral_table_found ) {
        $total_referrals = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$referral_log_table}"
        );

        $pending_referrals = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$referral_log_table} WHERE status = 'pending'"
        );

        $closed_referrals = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$referral_log_table} WHERE status = 'closed'"
        );
    }
    ?>
    <div class="dw-dashboard-widget">
        <p><strong>Total Rewards Members:</strong> <?php echo esc_html( $total_members ); ?></p>

        <ul style="margin-left:1em;">
            <li>Concierge Access: <?php echo esc_html( $tiers['concierge'] ); ?></li>
            <li>Elite Concierge: <?php echo esc_html( $tiers['elite'] ); ?></li>
            <li>Premier Partner: <?php echo esc_html( $tiers['premier'] ); ?></li>
            <li>Platinum Partner: <?php echo esc_html( $tiers['platinum'] ); ?></li>
        </ul>

        <p><strong>New Rewards Members (last 7 days):</strong> <?php echo esc_html( $new_members_7_days ); ?></p>
        <?php if ( $latest_member_date ) : ?>
            <p><strong>Most Recent Member Joined:</strong> <?php echo esc_html( $latest_member_date ); ?></p>
        <?php endif; ?>

        <hr style="margin:10px 0;">

        <p><strong>Total Travel Requests:</strong> <?php echo esc_html( $total_travel ); ?></p>
        <p>New travel requests (last 7 days): <?php echo esc_html( $new_travel_7_days ); ?></p>
        <p>Linked to a Rewards code: <?php echo esc_html( $linked_travel ); ?></p>

        <hr style="margin:10px 0;">

        <p><strong>Total Business Perks Requests:</strong> <?php echo esc_html( $total_business ); ?></p>
        <p>Linked to a Rewards code: <?php echo esc_html( $linked_business ); ?></p>

        <?php if ( $referral_table_found ) : ?>
            <hr style="margin:10px 0;">

            <p><strong>Total Referrals Logged:</strong> <?php echo esc_html( $total_referrals ); ?></p>
            <p>Pending referrals: <?php echo esc_html( $pending_referrals ); ?></p>
            <p>Closed referrals: <?php echo esc_html( $closed_referrals ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

    /* =========================================================
     * ADMIN MENU + SUBMISSIONS PAGES
     * ======================================================= */

   public function register_admin_menu() {
    add_menu_page(
        'Divine Wealth Rewards',
        'Divine Wealth Rewards',
        'manage_options',
        'dw-rewards-admin',
        array( $this, 'render_admin_rewards_members' ),
        'dashicons-awards',
        56
    );

    add_submenu_page(
        'dw-rewards-admin',
        'Rewards Members',
        'Rewards Members',
        'manage_options',
        'dw-rewards-admin',
        array( $this, 'render_admin_rewards_members' )
    );

    add_submenu_page(
        'dw-rewards-admin',
        'Travel Requests',
        'Travel Requests',
        'manage_options',
        'dw-travel-requests',
        array( $this, 'render_admin_travel_requests' )
    );

    add_submenu_page(
        'dw-rewards-admin',
        'Business Perks Requests',
        'Business Perks Requests',
        'manage_options',
        'dw-business-requests',
        array( $this, 'render_admin_business_requests' )
    );

    add_submenu_page(
        'dw-rewards-admin',
        'Referral Logs',
        'Referral Logs',
        'manage_options',
        'dw-referral-logs',
        array( $this, 'render_admin_referral_logs' )
    );
    add_submenu_page(
        'dw-rewards-admin',
        'Rewards Email Settings',
        'Email Settings',
        'manage_options',
        'dw-rewards-settings',
        array( $this, 'render_admin_email_settings' )
    );
    
            add_submenu_page(
            'dw-rewards-admin',
            'Rewards Settings',
            'Settings',
            'manage_options',
            'dw-rewards-settings',
            array( $this, 'render_settings_page' )
        );

    add_submenu_page(
    'dw-rewards-admin',                      // parent slug (whatever you used for main Rewards menu)
    'Payout Requests',                       // page title
    'Payout Requests',                       // menu title
    'manage_options',                        // capability
    'dw-payout-requests',                    // menu slug
    array( $this, 'render_admin_payout_requests' )  // callback
    );

}

    /**
     * Register plugin settings (stored in dw_rewards_settings option).
     */
    public function register_settings() {

        register_setting(
            'dw_rewards_settings_group',   // settings group
            'dw_rewards_settings',         // option name
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(),
            )
        );

        add_settings_section(
            'dw_rewards_main_section',
            'Divine Wealth Rewards Settings',
            '__return_false',
            'dw_rewards_settings_page'
        );

        // Company / branding
        add_settings_field(
            'brand_name',
            'Business / Brand Name',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_brand_name',
                'key'       => 'brand_name',
                'description' => 'Used in email templates and summaries.',
            )
        );

        add_settings_field(
            'logo_url',
            'Logo URL',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_logo_url',
                'key'       => 'logo_url',
                'description' => 'Direct URL to your logo image used in emails.',
            )
        );

        add_settings_field(
            'banner_bg_color',
            'Banner Background Color',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_banner_bg_color',
                'key'       => 'banner_bg_color',
                'description' => 'Hex color used for email banner background (e.g. #f7f1dd).',
            )
        );

        // Email + signature
        add_settings_field(
            'email_reply_to',
            'Reply-To Email',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_email_reply_to',
                'key'       => 'email_reply_to',
                'description' => 'Address used as Reply-To for outgoing emails (optional).',
            )
        );

        add_settings_field(
            'email_signature',
            'Email Signature',
            array( $this, 'render_textarea_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_email_signature',
                'key'       => 'email_signature',
                'description' => 'Shown at the bottom of outgoing emails.',
            )
        );

        // Referral base payout
        add_settings_field(
            'base_referral_payout',
            'Base Referral Payout ($)',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_base_referral_payout',
                'key'       => 'base_referral_payout',
                'description' => 'Default payout per completed referral (for logic & displays).',
            )
        );

        // ✅ Toggle: employee sub-referral codes
        add_settings_field(
            'enable_employee_subcodes',
            'Enable Employee Sub-Referral Codes',
            array( $this, 'render_checkbox_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for'   => 'dw_enable_employee_subcodes',
                'key'         => 'enable_employee_subcodes',
                'description' => 'If checked, business partners can have sub-codes for employees.',
            )
        );

        // License key (phase 3 logic later)
        add_settings_field(
            'license_key',
            'License Key',
            array( $this, 'render_text_field' ),
            'dw_rewards_settings_page',
            'dw_rewards_main_section',
            array(
                'label_for' => 'dw_license_key',
                'key'       => 'license_key',
                'description' => 'Future use: validate updates / premium features.',
            )
        );
    }
    /**
     * Sanitize all settings before saving.
     */
    public function sanitize_settings( $input ) {
        $output = array();
        $defaults = $this->get_settings();

        $output['brand_name']           = isset( $input['brand_name'] ) ? sanitize_text_field( $input['brand_name'] ) : $defaults['brand_name'];
        $output['logo_url']             = isset( $input['logo_url'] ) ? esc_url_raw( $input['logo_url'] ) : $defaults['logo_url'];
        $output['banner_bg_color']      = isset( $input['banner_bg_color'] ) ? sanitize_hex_color( $input['banner_bg_color'] ) : $defaults['banner_bg_color'];
        $output['email_reply_to']       = isset( $input['email_reply_to'] ) ? sanitize_email( $input['email_reply_to'] ) : $defaults['email_reply_to'];
        $output['email_signature']      = isset( $input['email_signature'] ) ? sanitize_textarea_field( $input['email_signature'] ) : $defaults['email_signature'];
        $output['base_referral_payout'] = isset( $input['base_referral_payout'] ) ? sanitize_text_field( $input['base_referral_payout'] ) : $defaults['base_referral_payout'];

        // checkbox: 0/1
        $output['enable_employee_subcodes'] = ! empty( $input['enable_employee_subcodes'] ) ? 1 : 0;

        $output['license_key'] = isset( $input['license_key'] ) ? sanitize_text_field( $input['license_key'] ) : $defaults['license_key'];

        return $output;
    }

    public function render_text_field( $args ) {
        $settings = $this->get_settings();
        $key      = $args['key'];
        $id       = $args['label_for'];
        $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
        ?>
        <input type="text"
               id="<?php echo esc_attr( $id ); ?>"
               name="dw_rewards_settings[<?php echo esc_attr( $key ); ?>]"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text" />
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    public function render_textarea_field( $args ) {
        $settings = $this->get_settings();
        $key      = $args['key'];
        $id       = $args['label_for'];
        $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
        ?>
        <textarea id="<?php echo esc_attr( $id ); ?>"
                  name="dw_rewards_settings[<?php echo esc_attr( $key ); ?>]"
                  rows="4"
                  class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    public function render_checkbox_field( $args ) {
        $settings = $this->get_settings();
        $key      = $args['key'];
        $id       = $args['label_for'];
        $value    = ! empty( $settings[ $key ] ) ? 1 : 0;
        ?>
        <label for="<?php echo esc_attr( $id ); ?>">
            <input type="checkbox"
                   id="<?php echo esc_attr( $id ); ?>"
                   name="dw_rewards_settings[<?php echo esc_attr( $key ); ?>]"
                   value="1" <?php checked( 1, $value ); ?> />
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <span class="description"><?php echo esc_html( $args['description'] ); ?></span>
            <?php endif; ?>
        </label>
        <?php
    }

    /**
     * Render the Settings page UI.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Divine Wealth Rewards &amp; Referrals – Settings</h1>
            <p>Configure branding, email behavior, referral payout logic, and employee sub-referral options.</p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'dw_rewards_settings_group' );
                do_settings_sections( 'dw_rewards_settings_page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Admin page: Email / branding settings for rewards emails.
     */
 public function render_admin_email_settings() {
    $this->require_manage_options();

    // Current values
    $logo_url     = esc_url( get_option( 'dw_rewards_logo_url', $this->get_logo_url() ) );
    $company_name = esc_attr( get_option( 'dw_rewards_company_name', $this->get_company_name_for_email() ) );
    $reply_to     = esc_attr( get_option( 'dw_rewards_reply_to', '' ) );
    $signature    = get_option(
        'dw_rewards_email_signature',
        "With gratitude,\nDivine Wealth Solutions LLC"
    );
    $footer_main  = get_option(
        'dw_rewards_footer_main',
        'Thank you for choosing Divine Wealth Solutions.'
    );
    $footer_tag   = get_option(
        'dw_rewards_footer_tagline',
        'Taxes • Business Growth • Wealth Planning'
    );
    $banner_color = esc_attr( get_option( 'dw_rewards_banner_color', '#f7f1dd' ) );
    ?>
    <div class="wrap">
        <h1>Divine Wealth Rewards &mdash; Email Branding</h1>
        <?php if ( isset( $_GET['dw_saved'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved.</p>
            </div>
        <?php endif; ?>

        <form method="post"
              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              style="max-width:700px;">
            <?php wp_nonce_field( 'dw_save_email_settings' ); ?>
            <input type="hidden" name="action" value="dw_save_email_settings" />

            <table class="form-table" role="presentation">
                <!-- Brand name -->
                <tr>
                    <th scope="row">
                        <label for="dw_rewards_company_name">Business / Brand Name</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="dw_rewards_company_name"
                            name="dw_rewards_company_name"
                            class="regular-text"
                            value="<?php echo $company_name; ?>"
                        />
                        <p class="description">
                            This name appears in your email header, alt text, and footer
                            (e.g. "Thank you for choosing &hellip;").
                        </p>
                    </td>
                </tr>

                <!-- Logo URL -->
                <tr>
                    <th scope="row">
                        <label for="dw_rewards_logo_url">Email Logo URL</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="dw_rewards_logo_url"
                            name="dw_rewards_logo_url"
                            class="regular-text"
                            value="<?php echo $logo_url; ?>"
                        />
                        <p class="description">
                            Paste the URL of the logo you want in reward emails.
                            Leave blank to use the default Divine Wealth Solutions logo.
                        </p>
                    </td>
                </tr>

                <!-- Reply-To Email -->
                <tr>
                    <th scope="row"><label for="dw_rewards_reply_to">Reply-To Email</label></th>
                    <td>
                        <input type="email"
                               class="regular-text"
                               id="dw_rewards_reply_to"
                               name="dw_rewards_reply_to"
                               value="<?php echo $reply_to; ?>" />
                        <p class="description">
                            Optional. If set, emails will use this address as the Reply-To.
                        </p>
                    </td>
                </tr>

                <!-- Email Signature -->
                <tr>
                    <th scope="row"><label for="dw_rewards_email_signature">Email Signature</label></th>
                    <td>
                        <textarea id="dw_rewards_email_signature"
                                  name="dw_rewards_email_signature"
                                  rows="4"
                                  class="large-text"><?php
                            echo esc_textarea( $signature );
                        ?></textarea>
                        <p class="description">
                            Appears under your custom message in admin replies.
                        </p>
                    </td>
                </tr>

                <!-- Footer main line -->
                <tr>
                    <th scope="row"><label for="dw_rewards_footer_main">Footer &ndash; Main Line</label></th>
                    <td>
                        <textarea id="dw_rewards_footer_main"
                                  name="dw_rewards_footer_main"
                                  rows="2"
                                  class="large-text"><?php
                            echo esc_textarea( $footer_main );
                        ?></textarea>
                        <p class="description">
                            First line in the footer area of your emails.
                        </p>
                    </td>
                </tr>

                <!-- Footer tagline line -->
                <tr>
                    <th scope="row"><label for="dw_rewards_footer_tagline">Footer &ndash; Tagline Line</label></th>
                    <td>
                        <textarea id="dw_rewards_footer_tagline"
                                  name="dw_rewards_footer_tagline"
                                  rows="2"
                                  class="large-text"><?php
                            echo esc_textarea( $footer_tag );
                        ?></textarea>
                        <p class="description">
                            Second line in the footer (e.g. your services tagline).
                        </p>
                    </td>
                </tr>

                <!-- Banner background color -->
                <tr>
                    <th scope="row"><label for="dw_rewards_banner_color">Banner Background Color</label></th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               id="dw_rewards_banner_color"
                               name="dw_rewards_banner_color"
                               value="<?php echo $banner_color; ?>" />
                        <p class="description">
                            Hex color for the top banner gradient (e.g. <code>#f7f1dd</code>).
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save Email Settings' ); ?>
        </form>
    </div>
    <?php
}

    /**
     * Handle saving of email branding settings.
     */
public function admin_save_email_settings() {
    $this->require_manage_options();

    check_admin_referer( 'dw_save_email_settings' );

    // 1) Brand name & logo
    $company_name = isset( $_POST['dw_rewards_company_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['dw_rewards_company_name'] ) )
        : '';

    $logo_url = isset( $_POST['dw_rewards_logo_url'] )
        ? esc_url_raw( wp_unslash( $_POST['dw_rewards_logo_url'] ) )
        : '';

    // 2) Reply-To email
    $reply_to = isset( $_POST['dw_rewards_reply_to'] )
        ? sanitize_email( wp_unslash( $_POST['dw_rewards_reply_to'] ) )
        : '';

    // 3) Email signature
    $signature = isset( $_POST['dw_rewards_email_signature'] )
        ? wp_kses_post( wp_unslash( $_POST['dw_rewards_email_signature'] ) )
        : '';

    // 4) Footer main + tagline
    $footer_main = isset( $_POST['dw_rewards_footer_main'] )
        ? sanitize_text_field( wp_unslash( $_POST['dw_rewards_footer_main'] ) )
        : '';

    $footer_tagline = isset( $_POST['dw_rewards_footer_tagline'] )
        ? sanitize_text_field( wp_unslash( $_POST['dw_rewards_footer_tagline'] ) )
        : '';

    // 5) Banner color
    $banner_bg = isset( $_POST['dw_rewards_banner_color'] )
        ? sanitize_hex_color( wp_unslash( $_POST['dw_rewards_banner_color'] ) )
        : '';

    // Save options – NOTE: keys match render_admin_email_settings()
    update_option( 'dw_rewards_company_name',    $company_name );
    update_option( 'dw_rewards_logo_url',        $logo_url );
    update_option( 'dw_rewards_reply_to',        $reply_to );
    update_option( 'dw_rewards_email_signature', $signature );
    update_option( 'dw_rewards_footer_main',     $footer_main );
    update_option( 'dw_rewards_footer_tagline',  $footer_tagline );
    update_option( 'dw_rewards_banner_color',    $banner_bg );

    // Redirect back with success notice
    $redirect = add_query_arg(
        'dw_saved',
        '1',
        admin_url( 'admin.php?page=dw-rewards-settings' )
    );

    wp_safe_redirect( $redirect );
    exit;
}




    private function admin_get_paged() {
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        return max( 1, $paged );
    }

    private function admin_get_search() {
        return isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    }

    private function admin_pagination( $total_items, $per_page, $paged, $page_slug ) {
        $total_pages = (int) ceil( $total_items / $per_page );
        if ( $total_pages <= 1 ) return;

        $base_url = admin_url( 'admin.php?page=' . $page_slug );

        echo '<div class="tablenav"><div class="tablenav-pages" style="margin:10px 0;">';
        for ( $i = 1; $i <= $total_pages; $i++ ) {
            $url   = add_query_arg( array( 'paged' => $i, 's' => $this->admin_get_search() ), $base_url );
            $class = $i === $paged ? 'button button-primary' : 'button';
            echo '<a class="' . esc_attr( $class ) . '" style="margin-right:6px;" href="' . esc_url( $url ) . '">' . esc_html( $i ) . '</a>';
        }
        echo '</div></div>';
    }

    // ===== Admin View Helpers =====

    private function admin_get_action() {
        return isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
    }

    private function admin_get_id() {
        return isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    }

    /**
     * Reusable single-record detail view.
     */
    private function render_admin_detail_view( $table, $id, $back_page, $heading, $field_labels, $nonce_action ) {
        global $wpdb;

        if (
            isset( $_GET['_wpnonce'] ) &&
            ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), $nonce_action )
        ) {
            echo '<div class="wrap"><h1>Invalid request.</h1></div>';
            return;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id )
        );

        if ( ! $row ) {
            echo '<div class="wrap"><h1>' . esc_html( $heading ) . '</h1><p>Record not found.</p></div>';
            return;
        }

        $back_url = admin_url( 'admin.php?page=' . $back_page );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $heading ) . '</h1>';
        echo '<p><a class="button" href="' . esc_url( $back_url ) . '">&larr; Back to list</a></p>';

        echo '<table class="widefat striped" style="max-width:900px;">';
        echo '<tbody>';

        foreach ( $field_labels as $field => $label ) {
            $value = isset( $row->$field ) ? $row->$field : '';

            if ( $value === '' || $value === null ) {
                $value = '—';
            }

            if ( is_string( $value ) ) {
                $value = nl2br( esc_html( $value ) );
            }

            echo '<tr>';
            echo '<th style="width:220px;">' . esc_html( $label ) . '</th>';
            echo '<td>' . $value . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
private function render_admin_rewards_member_detail( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    // Security: verify nonce for viewing
    $nonce_action = 'dw_view_rewards_' . $id;
    if (
        isset( $_GET['_wpnonce'] ) &&
        ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), $nonce_action )
    ) {
        echo '<div class="wrap"><h1>Invalid request.</h1></div>';
        return;
    }

    $member = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id )
    );
        // ---- Email Status Notices (Success / Error) ----
    if ( isset( $_GET['dw_email_sent'] ) && (int) $_GET['dw_email_sent'] === 1 ) {
        echo '<div class="notice notice-success is-dismissible"><p>Email sent to this member successfully.</p></div>';
    }
    elseif ( isset( $_GET['dw_email_error'] ) && (int) $_GET['dw_email_error'] === 1 ) {
        echo '<div class="notice notice-error is-dismissible"><p>There was a problem sending the email. Please try again.</p></div>';
    }

    if ( ! $member ) {
        echo '<div class="wrap"><h1>Rewards Member Details</h1><p>Member not found.</p></div>';
        return;
    }

    $back_url = admin_url( 'admin.php?page=dw-rewards-admin' );

    // Prep action URLs + nonces
    $tier_action_url  = admin_url( 'admin-post.php' );
    $code_action_url  = admin_url( 'admin-post.php' );
    $notes_action_url = admin_url( 'admin-post.php' );
    $reply_action_url = admin_url( 'admin-post.php' );

    $tier_nonce  = wp_create_nonce( 'dw_update_member_tier_' . $id );
    $code_nonce  = wp_create_nonce( 'dw_update_member_code_' . $id );
    $notes_nonce = wp_create_nonce( 'dw_save_member_notes_' . $id );
    $reply_nonce = wp_create_nonce( 'dw_reply_member_' . $id );
    $referral_action_url = admin_url( 'admin-post.php' );
    $referral_nonce      = wp_create_nonce( 'dw_add_member_referral_' . $id );

    $tiers = array(
        'concierge' => 'Concierge Access',
        'elite'     => 'Elite Concierge',
        'premier'   => 'Premier Partner',
        'platinum'  => 'Platinum Partner',
    );

    echo '<div class="wrap">';
    echo '<h1>Rewards Member Details</h1>';
    echo '<p><a class="button" href="' . esc_url( $back_url ) . '">&larr; Back to list</a></p>';

    // A) Snapshot header
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin:0 0 6px;">' . esc_html( $member->full_name ) . '</h2>';
    echo '<p style="margin:0;">';
    echo '<strong>Email:</strong> ' . esc_html( $member->email ) . ' &nbsp; | &nbsp; ';
    echo '<strong>Phone:</strong> ' . esc_html( $member->phone ?: '—' ) . ' &nbsp; | &nbsp; ';
    echo '<strong>Code:</strong> <code>' . esc_html( $member->referral_code ) . '</code>';
    echo '</p>';
    echo '</div>';

    // B) Full read-only details (this replaces your array/table)
    echo '<table class="widefat striped" style="max-width:1100px; margin-bottom:14px;">';
    echo '<tbody>';
    $fields = array(
        'id'              => 'ID',
        'created_at'      => 'Submitted',
        'business_name'   => 'Business Name',
        'referral_type'   => 'Referral Type',
        'tier'            => 'Tier',
        'city'            => 'City',
        'state'           => 'State',
        'notes'           => 'Notes / Goals',
        'total_referrals' => 'Total Referrals',
        'total_revenue'   => 'Total Revenue',
        'source'          => 'Source',
        'ip_address'      => 'IP Address',
        'user_agent'      => 'User Agent',
        'admin_notes'     => 'Admin Notes',
        'admin_notes_updated_at' => 'Admin Notes Updated',
    );

    foreach ( $fields as $key => $label ) {
        $val = isset( $member->$key ) ? $member->$key : '';
        if ( $val === '' || $val === null ) $val = '—';
        if ( is_string( $val ) ) {
            $val = nl2br( esc_html( $val ) );
        }

        echo '<tr>';
        echo '<th style="width:220px;">' . esc_html( $label ) . '</th>';
        echo '<td>' . $val . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    // C) Update Tier tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Update Tier</h2>';
    echo '<form method="post" action="' . esc_url( $tier_action_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_member_tier">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $tier_nonce ) . '">';
    echo '<select name="tier">';
    foreach ( $tiers as $slug => $label ) {
        $selected = selected( $member->tier, $slug, false );
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select> ';
    echo '<button class="button button-primary">Save Tier</button>';
    echo '</form>';
    echo '</div>';

    // D) Update Referral Code tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Update Referral Code</h2>';
    if ( isset($_GET['dw_error']) && $_GET['dw_error'] === 'code_exists' ) {
        echo '<p style="color:#b32d2e;"><strong>Error:</strong> That code already exists. Try another.</p>';
    }
    echo '<form method="post" action="' . esc_url( $code_action_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_member_code">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $code_nonce ) . '">';
    echo '<input type="text" name="referral_code" value="' . esc_attr( $member->referral_code ) . '" style="width:260px; text-transform:uppercase;"> ';
    echo '<button class="button">Save Code</button>';
    echo '</form>';
    echo '</div>';

    // E) Admin notes tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Internal Admin Notes</h2>';
    echo '<form method="post" action="' . esc_url( $notes_action_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_save_member_admin_notes">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $notes_nonce ) . '">';
    echo '<textarea name="admin_notes" rows="5" style="width:100%;">' . esc_textarea( $member->admin_notes ?? '' ) . '</textarea>';
    echo '<p><button class="button button-primary">Save Notes</button></p>';
    echo '</form>';
    echo '</div>';

    // F) Reply tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Reply to Member</h2>';
    if ( isset($_GET['dw_email_sent']) ) {
        echo '<p style="color:green;"><strong>Sent!</strong> Your email was sent to the member.</p>';
    }
    echo '<form method="post" action="' . esc_url( $reply_action_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_reply_member">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $reply_nonce ) . '">';
    echo '<p><input type="text" name="subject" placeholder="Subject" style="width:100%; max-width:700px;"></p>';
    echo '<p><textarea name="message" rows="6" placeholder="Write your message..." style="width:100%;"></textarea></p>';
    echo '<p><button class="button button-primary">Send Email</button></p>';
    echo '</form>';
    echo '</div>';
    
        // G) Record Closed Referral
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Record Closed Referral</h2>';

    if ( isset( $_GET['dw_referral_recorded'] ) ) {
        echo '<p style="color:green;"><strong>Saved!</strong> Referral totals were updated.</p>';
    }

    echo '<form method="post" action="' . esc_url( $referral_action_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_add_member_referral">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $referral_nonce ) . '">';

    echo '<p>';
    echo '<label><strong>Referral Amount (optional)</strong><br>';
    echo '<input type="number" name="amount" step="0.01" min="0" style="width:200px;" placeholder="e.g. 250.00">';
    echo '<br><span style="color:#666; font-size:12px;">Leave blank to only increase the referral count.</span>';
    echo '</label>';
    echo '</p>';

    echo '<p>';
    echo '<label><strong>Referral Count Increment</strong><br>';
    echo '<input type="number" name="count" step="1" min="1" value="1" style="width:100px;">';
    echo '<br><span style="color:#666; font-size:12px;">Defaults to 1 closed referral.</span>';
    echo '</label>';
    echo '</p>';

    echo '<p><button class="button button-primary">Record Referral</button></p>';
    echo '</form>';
    echo '</div>';


    // H) Quick actions row (optional space for later)
    echo '<p style="max-width:1100px; color:#666;">More admin tools can be added here later (export, delete, referral adjustments, etc.).</p>';

    echo '</div>';
}

    /* =========================================================
     * ADMIN ACTION HANDLERS (Missing Functions)
     * ======================================================= */

  
    
 public function render_admin_rewards_members() {
    global $wpdb;

    $table    = $wpdb->prefix . 'dw_rewards_members';
    $per_page = 20;
    $paged    = $this->admin_get_paged();
    $offset   = ( $paged - 1 ) * $per_page;
    $search   = $this->admin_get_search();

    $where  = "1=1";
    $params = array();

    $action = $this->admin_get_action();
    $id     = $this->admin_get_id();

    // ======================
    //  DETAIL VIEW
    // ======================
    if ( $action === 'view' && $id ) {
        $this->render_admin_rewards_member_detail( $id );
        return;
    }

    // ======================
    //  SEARCH FILTER
    // ======================
    if ( $search ) {
        $where .= " AND (full_name LIKE %s OR business_name LIKE %s OR email LIKE %s OR referral_code LIKE %s)";
        $like  = '%' . $wpdb->esc_like( $search ) . '%';
        $params = array( $like, $like, $like, $like );
    }

    $total = (int) ( $params
        ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) )
        : $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" )
    );

    $query = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $rows  = $params
        ? $wpdb->get_results( $wpdb->prepare( $query, array_merge( $params, array( $per_page, $offset ) ) ) )
        : $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

    echo '<div class="wrap"><h1>Rewards Members</h1>';
        if ( isset( $_GET['dw_deleted'] ) && $_GET['dw_deleted'] === '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Rewards member deleted.</p></div>';
    }


    // ======================
    //  SEARCH FORM
    // ======================
    echo '<form method="get" style="margin:10px 0;">
            <input type="hidden" name="page" value="dw-rewards-admin" />
            <input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search name, business, email, code" />
            <button class="button">Search</button>
          </form>';

    echo '<table class="widefat striped">';
    echo '<thead><tr>
        <th>ID</th>
        <th>Date</th>
        <th>Name</th>
        <th>Business</th>
        <th>Email</th>
        <th>Referral Code</th>
        <th>Tier</th>
        <th>Total Referrals</th>
        <th>Total Revenue</th>
        <th>Actions</th>
    </tr></thead><tbody>';

    // ======================
    //  ROWS
    // ======================
    if ( $rows ) {
    foreach ( $rows as $r ) {

        $view_url = wp_nonce_url(
            admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $r->id ),
            'dw_view_rewards_' . $r->id
        );

        $delete_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=dw_delete_rewards_member&id=' . $r->id ),
            'dw_delete_rewards_member_' . $r->id
        );

        $mailto = 'mailto:' . sanitize_email( $r->email );

        echo '<tr>';

        // ID
        echo '<td><a href="' . esc_url( $view_url ) . '">' . esc_html( $r->id ) . '</a></td>';

        // Date
        echo '<td>' . esc_html( $r->created_at ) . '</td>';

        // Name + mini actions
        echo '<td>
                <strong><a href="' . esc_url( $view_url ) . '">' . esc_html( $r->full_name ) . '</a></strong>
                <div class="row-actions" style="margin-top:4px;">
                    <span class="view"><a href="' . esc_url( $view_url ) . '">View</a> | </span>
                    <span class="email"><a href="' . esc_url( $mailto ) . '">Email</a> | </span>
                    <span class="delete"><a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Are you sure you want to delete this rewards member? This cannot be undone.\');">Delete</a></span>
                </div>
              </td>';

        // Business
        echo '<td>' . esc_html( $r->business_name ) . '</td>';

        // Email
        echo '<td><a href="' . esc_url( $mailto ) . '">' . esc_html( $r->email ) . '</a></td>';

        // Code
        echo '<td><code>' . esc_html( $r->referral_code ) . '</code></td>';

        // Tier
        echo '<td>' . esc_html( ucfirst( $r->tier ) ) . '</td>';

        // Totals
        echo '<td>' . esc_html( $r->total_referrals ) . '</td>';
        echo '<td>$' . esc_html( number_format( (float) $r->total_revenue, 2 ) ) . '</td>';

        // Actions column (button style)
        echo '<td>
                <a class="button button-small" href="' . esc_url( $view_url ) . '">View</a>
                <a class="button button-small" href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Are you sure you want to delete this rewards member? This cannot be undone.\');">Delete</a>
              </td>';

        echo '</tr>';
    }
}
 else {
        echo '<tr><td colspan="10">No members found.</td></tr>';
    }

    echo '</tbody></table>';

    // Pagination
    $this->admin_pagination( $total, $per_page, $paged, 'dw-rewards-admin' );
    echo '</div>';
}

/* =========================================================
 * ADMIN POST HANDLERS (Step 4)
 * ======================================================= */

/**
 * Simple capability gate for admin actions.
 */
private function require_manage_options() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'divine-wealth-forms' ) );
    }
}

/**
 * Admin: update member tier.
 * Hook: admin_post_dw_update_member_tier
 */
public function admin_update_member_tier() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    $nonce_action = 'dw_update_member_tier_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $tier = isset($_POST['tier']) ? sanitize_key( wp_unslash($_POST['tier']) ) : '';
    $allowed = array( 'concierge', 'elite', 'premier', 'platinum' );
    if ( ! in_array( $tier, $allowed, true ) ) {
        wp_die( 'Invalid tier selected.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    $wpdb->update(
        $table,
        array( 'tier' => $tier ),
        array( 'id' => $id ),
        array( '%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $id ),
        'dw_view_rewards_' . $id
    );
    $view_url = add_query_arg( 'dw_updated', 'tier', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: update referral code.
 * Hook: admin_post_dw_update_member_code
 */
public function admin_update_member_code() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    $nonce_action = 'dw_update_member_code_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $new_code = isset($_POST['referral_code'])
        ? strtoupper( sanitize_text_field( wp_unslash($_POST['referral_code']) ) )
        : '';

    if ( ! $new_code ) {
        wp_die( 'Referral code cannot be empty.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    // Ensure code uniqueness (excluding current member)
    $exists = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE referral_code = %s AND id <> %d",
            $new_code,
            $id
        )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $id ),
        'dw_view_rewards_' . $id
    );

    if ( $exists ) {
        $view_url = add_query_arg( 'dw_error', 'code_exists', $view_url );
        wp_safe_redirect( $view_url );
        exit;
    }

    $wpdb->update(
        $table,
        array( 'referral_code' => $new_code ),
        array( 'id' => $id ),
        array( '%s' ),
        array( '%d' )
    );

    $view_url = add_query_arg( 'dw_updated', 'code', $view_url );
    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: save internal notes.
 * Hook: admin_post_dw_save_member_admin_notes
 */
public function admin_save_member_notes() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    $nonce_action = 'dw_save_member_notes_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $admin_notes = isset($_POST['admin_notes'])
        ? sanitize_textarea_field( wp_unslash($_POST['admin_notes']) )
        : '';

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    $wpdb->update(
        $table,
        array(
            'admin_notes'             => $admin_notes,
            'admin_notes_updated_at'  => current_time( 'mysql' ),
        ),
        array( 'id' => $id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $id ),
        'dw_view_rewards_' . $id
    );
    $view_url = add_query_arg( 'dw_updated', 'notes', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: reply to a member via email.
 * Hook: admin_post_dw_reply_member
 */
public function admin_reply_member() {
    // Only allow admins / users with manage_options
    $this->require_manage_options();

    // 1) Validate member ID
    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    // 2) Validate nonce
    $nonce_action = 'dw_reply_member_' . $id;
    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    // 3) Load the member safely from the DB
    $member = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d LIMIT 1",
            $id
        )
    );

    if ( ! $member ) {
        wp_die( 'Member not found in the database.' );
    }

    // 4) Build and validate email fields
    $to = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

    // Fallback – if the form email is empty, use the DB value
    if ( empty( $to ) && ! empty( $member->email ) ) {
        $to = sanitize_email( $member->email );
    }

    if ( ! is_email( $to ) ) {
        wp_die( 'Invalid recipient email address.' );
    }

    $subject = isset( $_POST['subject'] )
        ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )
        : '';

    $message = isset( $_POST['message'] )
        ? wp_kses_post( wp_unslash( $_POST['message'] ) )
        : '';

    if ( '' === $subject || '' === $message ) {
        wp_die( 'Please provide both a subject and a message.' );
    }

      // 5) Build the HTML email using Email Branding signature
    $brand_name = get_option( 'dw_rewards_company_name', 'Divine Wealth Solutions' );

    $signature = get_option(
    'dw_rewards_email_signature',
    "With gratitude,\n{$brand_name}"
    );

    $body  = '<p>Hi ' . esc_html( $member->full_name ) . ',</p>';
    $body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';
    $body .= '<p style="margin-top:18px; white-space:pre-line;">' .
         nl2br( esc_html( $signature ) ) . '</p>';

    $html_email = $this->build_email_html( $subject, $body );

    // 6) Send the email with global headers (HTML + Reply-To)
    $headers = $this->get_email_headers();

    $sent = wp_mail( $to, $subject, $html_email, $headers );

    if ( ! $sent ) {
        wp_die( 'The email could not be sent. Please check your mail configuration.' );
    }

    // 7) Redirect back to the member view with a "sent" flag
    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $id ),
        'dw_view_rewards_' . $id
    );
    $view_url = add_query_arg( 'dw_email_sent', '1', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: delete a rewards member.
 * Hook: admin_post_dw_delete_rewards_member
 */
public function admin_delete_rewards_member() {
    $this->require_manage_options();

    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    $nonce_action = 'dw_delete_rewards_member_' . $id;
    if (
        empty( $_GET['_wpnonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    // Note: this does NOT delete linked travel/business rows – just the member.
    $wpdb->delete(
        $table,
        array( 'id' => $id ),
        array( '%d' )
    );

    $redirect = add_query_arg(
        'dw_deleted',
        '1',
        admin_url( 'admin.php?page=dw-rewards-admin' )
    );

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Admin: record a closed referral for a member.
 * Increments total_referrals and adds to total_revenue.
 * Hook: admin_post_dw_add_member_referral
 */
public function admin_add_member_referral() {
    $this->require_manage_options();

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid member ID.' );
    }

    $nonce_action = 'dw_add_member_referral_' . $id;
    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    // Count (how many referrals)
    $count_raw = isset( $_POST['count'] ) ? wp_unslash( $_POST['count'] ) : 1;
    $count     = absint( $count_raw );
    if ( $count < 1 ) {
        $count = 1;
    }

    // Amount (optional revenue)
    $amount_raw = isset( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : '';
    $amount     = 0.0;
    if ( $amount_raw !== '' ) {
        $amount = floatval( $amount_raw );
        if ( $amount < 0 ) {
            $amount = 0.0;
        }
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_rewards_members';

    // Increment totals atomically
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table}
             SET total_referrals = total_referrals + %d,
                 total_revenue   = total_revenue + %f
             WHERE id = %d",
            $count,
            $amount,
            $id
        )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-rewards-admin&action=view&id=' . $id ),
        'dw_view_rewards_' . $id
    );
    $view_url = add_query_arg( 'dw_referral_recorded', '1', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}


/* =========================================================
 * TRAVEL ADMIN POST HANDLERS
 * ======================================================= */

/**
 * Admin: update travel tier (and optionally linked member tier)
 * Hook: admin_post_dw_update_travel_tier
 */
public function admin_update_travel_tier() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die('Invalid request ID.');

    $nonce_action = 'dw_update_travel_tier_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die('Security check failed.');
    }

    $tier = isset($_POST['tier']) ? sanitize_key(wp_unslash($_POST['tier'])) : '';
    $allowed = array('concierge','elite','premier','platinum');
    if ( ! in_array($tier, $allowed, true) ) wp_die('Invalid tier.');

    global $wpdb;
    $travel_table  = $wpdb->prefix . 'dw_travel_requests';
    $members_table = $wpdb->prefix . 'dw_rewards_members';

    // Update tier on travel request
    $wpdb->update(
        $travel_table,
        array( 'member_tier_at_request' => $tier ),
        array( 'id' => $id ),
        array( '%s' ),
        array( '%d' )
    );

    // Also update linked member tier if requested
    if ( ! empty($_POST['also_update_member']) ) {
        $req = $wpdb->get_row(
            $wpdb->prepare("SELECT member_id FROM {$travel_table} WHERE id=%d LIMIT 1", $id)
        );
        if ( $req && ! empty($req->member_id) ) {
            $wpdb->update(
                $members_table,
                array( 'tier' => $tier ),
                array( 'id' => (int)$req->member_id ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }

    $view_url = wp_nonce_url(
        admin_url('admin.php?page=dw-travel-requests&action=view&id=' . $id),
        'dw_view_travel_' . $id
    );
    $view_url = add_query_arg('dw_updated','tier',$view_url);

    wp_safe_redirect($view_url);
    exit;
}

/**
 * Admin: update travel referral code (and relink member)
 * Hook: admin_post_dw_update_travel_code
 */
public function admin_update_travel_code() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die('Invalid request ID.');

    $nonce_action = 'dw_update_travel_code_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action )
    ) {
        wp_die('Security check failed.');
    }

    $new_code = isset($_POST['referral_code'])
        ? strtoupper(sanitize_text_field(wp_unslash($_POST['referral_code'])))
        : '';

    global $wpdb;
    $travel_table = $wpdb->prefix . 'dw_travel_requests';

    $member_id = null;
    $tier      = null;

    if ( $new_code ) {
        $member = $this->find_member_by_code($new_code);
        if ( $member ) {
            $member_id = (int)$member->id;
            $tier      = $member->tier;
        }
    }

    $wpdb->update(
        $travel_table,
        array(
            'referral_code'          => $new_code,
            'member_id'              => $member_id,
            'member_tier_at_request' => $tier,
        ),
        array( 'id' => $id ),
        array( '%s','%d','%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url('admin.php?page=dw-travel-requests&action=view&id=' . $id),
        'dw_view_travel_' . $id
    );
    $view_url = add_query_arg('dw_updated','code',$view_url);

    wp_safe_redirect($view_url);
    exit;
}

/**
 * Admin: save internal travel notes
 * Hook: admin_post_dw_save_travel_admin_notes
 */
public function admin_save_travel_notes() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die('Invalid request ID.');

    $nonce_action = 'dw_save_travel_notes_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action )
    ) {
        wp_die('Security check failed.');
    }

    $admin_notes = isset($_POST['admin_notes'])
        ? sanitize_textarea_field(wp_unslash($_POST['admin_notes']))
        : '';

    global $wpdb;
    $travel_table = $wpdb->prefix . 'dw_travel_requests';

    $wpdb->update(
        $travel_table,
        array(
            'admin_notes'            => $admin_notes,
            'admin_notes_updated_at' => current_time('mysql'),
        ),
        array( 'id' => $id ),
        array( '%s','%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url('admin.php?page=dw-travel-requests&action=view&id=' . $id),
        'dw_view_travel_' . $id
    );
    $view_url = add_query_arg('dw_updated','notes',$view_url);

    wp_safe_redirect($view_url);
    exit;
}

/**
 * Admin: reply to traveler
 * Hook: admin_post_dw_reply_travel_request
 */
public function admin_reply_travel_request() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die('Invalid request ID.');

    $nonce_action = 'dw_reply_travel_request_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action )
    ) {
        wp_die('Security check failed.');
    }

    $subject = isset($_POST['subject'])
        ? sanitize_text_field(wp_unslash($_POST['subject']))
        : '';
    $message = isset($_POST['message'])
        ? sanitize_textarea_field(wp_unslash($_POST['message']))
        : '';

    if ( ! $subject || ! $message ) wp_die('Subject and message are required.');

    global $wpdb;
    $travel_table = $wpdb->prefix . 'dw_travel_requests';

    $req = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$travel_table} WHERE id=%d LIMIT 1", $id)
    );
    if ( ! $req || empty($req->email) ) wp_die('Request not found or missing email.');

        $settings = $this->get_settings();
    $headers  = $this->get_email_headers();

    $signature = ! empty( $settings['email_signature'] )
        ? $settings['email_signature']
        : "With gratitude,\n" . ( $settings['brand_name'] ?? 'Divine Wealth Solutions' );

    $body  = '<p>Hi ' . esc_html( $req->full_name ) . ',</p>';
    $body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';
    $body .= '<p style="margin-top:18px; white-space:pre-line;">' . nl2br( esc_html( $signature ) ) . '</p>';


    wp_mail(
        sanitize_email($req->email),
        $subject,
        $this->build_email_html($subject, $body),
        $headers
    );

    $view_url = wp_nonce_url(
        admin_url('admin.php?page=dw-travel-requests&action=view&id=' . $id),
        'dw_view_travel_' . $id
    );
    $view_url = add_query_arg('dw_email_sent','1',$view_url);

    wp_safe_redirect($view_url);
    exit;
}

/**
 * Admin: update travel request status
 * Hook: admin_post_dw_update_travel_status
 */
public function admin_update_travel_status() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die('Invalid request ID.');

    $nonce_action = 'dw_update_travel_status_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action )
    ) {
        wp_die('Security check failed.');
    }

    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'new';
    $allowed = array('new','in_progress','completed');
    if ( ! in_array($status,$allowed,true) ) wp_die('Invalid status.');

    global $wpdb;
    $travel_table = $wpdb->prefix . 'dw_travel_requests';

    $wpdb->update(
        $travel_table,
        array(
            'status'            => $status,
            'status_updated_at' => current_time('mysql'),
        ),
        array('id'=>$id),
        array('%s','%s'),
        array('%d')
    );

    $view_url = wp_nonce_url(
        admin_url('admin.php?page=dw-travel-requests&action=view&id=' . $id),
        'dw_view_travel_' . $id
    );
    $view_url = add_query_arg('dw_updated','status',$view_url);

    wp_safe_redirect($view_url);
    exit;
}

/**
 * Admin: delete a travel request.
 * Hook: admin_post_dw_delete_travel_request
 */
public function admin_delete_travel_request() {
    $this->require_manage_options();

    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid request ID.' );
    }

    $nonce_action = 'dw_delete_travel_request_' . $id;
    if (
        empty( $_GET['_wpnonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_travel_requests';

    $wpdb->delete(
        $table,
        array( 'id' => $id ),
        array( '%d' )
    );

    $redirect = add_query_arg(
        'dw_deleted',
        '1',
        admin_url( 'admin.php?page=dw-travel-requests' )
    );

    wp_safe_redirect( $redirect );
    exit;
}

private function render_admin_travel_request_detail( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'dw_travel_requests';

    // Security nonce
    $nonce_action = 'dw_view_travel_' . $id;
    if (
        isset( $_GET['_wpnonce'] ) &&
        ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), $nonce_action )
    ) {
        echo '<div class="wrap"><h1>Invalid request.</h1></div>';
        return;
    }

    $req = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id )
    );
    if ( ! $req ) {
        echo '<div class="wrap"><h1>Travel Request Details</h1><p>Request not found.</p></div>';
        return;
    }

    $back_url = admin_url( 'admin.php?page=dw-travel-requests' );

    // Action URLs + nonces
    $post_url = admin_url( 'admin-post.php' );

    $tier_nonce   = wp_create_nonce( 'dw_update_travel_tier_' . $id );
    $code_nonce   = wp_create_nonce( 'dw_update_travel_code_' . $id );
    $notes_nonce  = wp_create_nonce( 'dw_save_travel_notes_' . $id );
    $reply_nonce  = wp_create_nonce( 'dw_reply_travel_request_' . $id );
    $status_nonce = wp_create_nonce( 'dw_update_travel_status_' . $id );

    $tiers = array(
        'concierge' => 'Concierge Access',
        'elite'     => 'Elite Concierge',
        'premier'   => 'Premier Partner',
        'platinum'  => 'Platinum Partner',
    );

    $status_labels = array(
        'new'         => 'New',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    );

   // $dates = trim( ( $req->depart_date ?: '' ) . ' → ' . ( $req->return_date ?: '' ) );

    echo '<div class="wrap">';
    echo '<h1>Travel Concierge Request Details</h1>';
    echo '<p><a class="button" href="' . esc_url( $back_url ) . '">&larr; Back to list</a></p>';

    // A) Snapshot header
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin:0 0 6px;">' . esc_html( $req->full_name ) . '</h2>';
    echo '<p style="margin:0;">';
    echo '<strong>Email:</strong> <a href="mailto:' . esc_attr( $req->email ) . '">' . esc_html( $req->email ) . '</a> &nbsp; | &nbsp; ';
    echo '<strong>Phone:</strong> ' . esc_html( $req->phone ?: '—' ) . ' &nbsp; | &nbsp; ';
    echo '<strong>Code:</strong> <code>' . esc_html( $req->referral_code ?: '—' ) . '</code> &nbsp; | &nbsp; ';
    echo '<strong>Tier:</strong> ' . esc_html( ucfirst( $req->member_tier_at_request ?: '—' ) );
    echo '</p>';
    echo '<p style="margin:6px 0 0; color:#666;">';
    echo '<strong>Status:</strong> ' . esc_html( $status_labels[ $req->status ?? 'new' ] ?? 'New' );
    if ( ! empty( $req->status_updated_at ) ) {
        echo ' <em style="color:#888;">(updated ' . esc_html( $req->status_updated_at ) . ')</em>';
    }
    echo '</p>';
    echo '</div>';

    // B) Full read-only details
    echo '<table class="widefat striped" style="max-width:1100px; margin-bottom:14px;">';
    echo '<tbody>';

    $fields = array(
        'id'                => 'ID',
        'created_at'        => 'Submitted',
        'trip_purpose'      => 'Trip Purpose',
        'departure_city'    => 'Departure City / Airport',
        'destination'       => 'Destination(s)',
        'depart_date'       => 'Departure Date',
        'return_date'       => 'Return Date',
        'travelers'         => 'Number of Travelers',
        'services'          => 'Services Requested',
        'budget'            => 'Budget',
        'flexible_dates'    => 'Flexible Dates?',
        'flexible_airports' => 'Flexible Airports?',
        'notes'             => 'Notes / Preferences',
        'referral_code'     => 'Referral Code',
        'member_id'         => 'Linked Member ID',
        'member_tier_at_request' => 'Tier at Request',
        'ip_address'        => 'IP Address',
        'user_agent'        => 'User Agent',
        'admin_notes'       => 'Admin Notes',
        'admin_notes_updated_at' => 'Admin Notes Updated',
        'status'            => 'Status',
    );

    foreach ( $fields as $key => $label ) {
        $val = isset( $req->$key ) ? $req->$key : '';
        if ( $val === '' || $val === null ) $val = '—';

        if ( $key === 'flexible_dates' || $key === 'flexible_airports' ) {
            $val = ( (int) $val ) ? 'Yes' : 'No';
        }

        if ( is_string( $val ) ) {
            $val = nl2br( esc_html( $val ) );
        }

        echo '<tr>';
        echo '<th style="width:220px;">' . esc_html( $label ) . '</th>';
        echo '<td>' . $val . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // C) Update Tier tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Update Tier</h2>';
    echo '<form method="post" action="' . esc_url( $post_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_travel_tier">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $tier_nonce ) . '">';
    echo '<select name="tier">';
    foreach ( $tiers as $slug => $tlabel ) {
        $selected = selected( $req->member_tier_at_request, $slug, false );
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $tlabel ) . '</option>';
    }
    echo '</select> ';
    echo '<label style="margin-left:10px;">
            <input type="checkbox" name="also_update_member" value="1">
            Also update linked member tier
          </label> ';
    echo '<button class="button button-primary">Save Tier</button>';
    echo '</form>';
    echo '</div>';

    // D) Update Referral Code tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Update Referral Code</h2>';
    echo '<form method="post" action="' . esc_url( $post_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_travel_code">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $code_nonce ) . '">';
    echo '<input type="text" name="referral_code" value="' . esc_attr( $req->referral_code ) . '" style="width:260px; text-transform:uppercase;"> ';
    echo '<button class="button">Save Code</button>';
    echo '</form>';
    echo '</div>';

    // E) Admin notes tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Internal Admin Notes</h2>';
    echo '<form method="post" action="' . esc_url( $post_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_save_travel_admin_notes">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $notes_nonce ) . '">';
    echo '<textarea name="admin_notes" rows="5" style="width:100%;">' . esc_textarea( $req->admin_notes ?? '' ) . '</textarea>';
    echo '<p><button class="button button-primary">Save Notes</button></p>';
    echo '</form>';
    echo '</div>';

    // F) Reply tool
    echo '<div class="card" style="max-width:1100px; padding:16px 18px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Reply to Traveler</h2>';
    if ( isset($_GET['dw_email_sent']) ) {
        echo '<p style="color:green;"><strong>Sent!</strong> Your email was sent.</p>';
    }
    echo '<form method="post" action="' . esc_url( $post_url ) . '">';
    echo '<input type="hidden" name="action" value="dw_reply_travel_request">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $reply_nonce ) . '">';
    echo '<p><input type="text" name="subject" placeholder="Subject" style="width:100%; max-width:700px;"></p>';
    echo '<p><textarea name="message" rows="6" placeholder="Write your message..." style="width:100%;"></textarea></p>';
    echo '<p><button class="button button-primary">Send Email</button></p>';
    echo '</form>';
    echo '</div>';

    // G) Quick actions row (status)
    echo '<div class="card" style="max-width:1100px; padding:12px 14px; margin-bottom:14px;">';
    echo '<h2 style="margin-top:0;">Quick Actions</h2>';
    echo '<form method="post" action="' . esc_url( $post_url ) . '" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
    echo '<input type="hidden" name="action" value="dw_update_travel_status">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $status_nonce ) . '">';
    echo '<select name="status">';
    foreach ( $status_labels as $slug => $slabel ) {
        $selected = selected( $req->status, $slug, false );
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $slabel ) . '</option>';
    }
    echo '</select>';
    echo '<button class="button button-primary">Update Status</button>';
    echo '</form>';
    echo '</div>';

    echo '</div>';
}


    public function render_admin_travel_requests() {
        global $wpdb;

        $table    = $wpdb->prefix . 'dw_travel_requests';
        $per_page = 20;
        $paged    = $this->admin_get_paged();
        $offset   = ( $paged - 1 ) * $per_page;
        $search   = $this->admin_get_search();

        $action = $this->admin_get_action();
        $id     = $this->admin_get_id();

   // ======================
//  DETAIL VIEW
// ======================
if ( $action === 'view' && $id ) {
    $this->render_admin_travel_request_detail( $id );
    return;
}

        $where  = "1=1";
        $params = array();
        if ( $search ) {
            $where .= " AND (full_name LIKE %s OR email LIKE %s OR referral_code LIKE %s OR destination LIKE %s)";
            $like   = '%' . $wpdb->esc_like( $search ) . '%';
            $params = array( $like, $like, $like, $like );
        }

        $total = (int) ( $params
            ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) )
            : $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" )
        );

        $query = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $rows  = $params
            ? $wpdb->get_results( $wpdb->prepare( $query, array_merge( $params, array( $per_page, $offset ) ) ) )
            : $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

        echo '<div class="wrap"><h1>Travel Concierge Requests</h1>';
            if ( isset( $_GET['dw_deleted'] ) && $_GET['dw_deleted'] === '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Travel request deleted.</p></div>';
    }


        echo '<form method="get" style="margin:10px 0;">
                <input type="hidden" name="page" value="dw-travel-requests" />
                <input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search name, email, code, destination" />
                <button class="button">Search</button>
              </form>';

     echo '<table class="widefat striped">';
     echo '<thead><tr>
        <th>ID</th>
        <th>Date</th>
        <th>Name</th>
        <th>Email</th>
        <th>Destination</th>
        <th>Dates</th>
        <th>Services</th>
        <th>Referral Code</th>
        <th>Tier</th>
        <th>Actions</th>
      </tr></thead><tbody>';


        if ( $rows ) {
       foreach ( $rows as $r ) {

        $view_url = wp_nonce_url(
            admin_url( 'admin.php?page=dw-travel-requests&action=view&id=' . $r->id ),
            'dw_view_travel_' . $r->id
        );

        $delete_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=dw_delete_travel_request&id=' . $r->id ),
            'dw_delete_travel_request_' . $r->id
        );

        $dates = trim( ( $r->depart_date ?: '' ) . ' → ' . ( $r->return_date ?: '' ) );

        echo '<tr>
                <td><a href="' . esc_url( $view_url ) . '">' . esc_html( $r->id ) . '</a></td>
                <td>' . esc_html( $r->created_at ) . '</td>
                <td>' . esc_html( $r->full_name ) . '</td>
                <td>' . esc_html( $r->email ) . '</td>
                <td>' . esc_html( $r->destination ) . '</td>
                <td>' . esc_html( $dates ) . '</td>
                <td>' . esc_html( $r->services ) . '</td>
                <td><code>' . esc_html( $r->referral_code ) . '</code></td>
                <td>' . esc_html( ucfirst( $r->member_tier_at_request ) ) . '</td>
                <td>
                    <a class="button button-small" href="' . esc_url( $view_url ) . '">View</a>
                    <a class="button button-small" href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Are you sure you want to delete this travel request? This cannot be undone.\');">Delete</a>
                </td>
            </tr>';
    }
} else {
    echo '<tr><td colspan="10">No travel requests found.</td></tr>';
}

        echo '</tbody></table>';

        $this->admin_pagination( $total, $per_page, $paged, 'dw-travel-requests' );
        echo '</div>';
    }
    
/**
 * Admin: save internal notes for business request.
 * Hook: admin_post_dw_save_business_admin_notes
 */
public function admin_save_business_notes() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die( 'Invalid request ID.' );

    $nonce_action = 'dw_save_business_notes_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $admin_notes = isset($_POST['admin_notes'])
        ? sanitize_textarea_field( wp_unslash($_POST['admin_notes']) )
        : '';

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $wpdb->update(
        $table,
        array(
            'admin_notes'            => $admin_notes,
            'admin_notes_updated_at' => current_time('mysql'),
        ),
        array( 'id' => $id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $id ),
        'dw_view_business_' . $id
    );
    $view_url = add_query_arg( 'dw_updated', 'notes', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: reply to business requestor.
 * Hook: admin_post_dw_reply_business_request
 */
public function admin_reply_business_request() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die( 'Invalid request ID.' );

    $nonce_action = 'dw_reply_business_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $subject = isset($_POST['subject']) ? sanitize_text_field( wp_unslash($_POST['subject']) ) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field( wp_unslash($_POST['message']) ) : '';

    if ( ! $subject || ! $message ) wp_die( 'Subject and message are required.' );

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $req = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id )
    );
    if ( ! $req || empty( $req->email ) ) {
        wp_die( 'Request not found or missing email.' );
    }

        $settings = $this->get_settings();
    $headers  = $this->get_email_headers();

    $signature = ! empty( $settings['email_signature'] )
        ? $settings['email_signature']
        : "With gratitude,\n" . ( $settings['brand_name'] ?? 'Divine Wealth Solutions' );

    $body  = '<p>Hi ' . esc_html( $req->full_name ) . ',</p>';
    $body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';
    $body .= '<p style="margin-top:18px; white-space:pre-line;">' . nl2br( esc_html( $signature ) ) . '</p>';

    wp_mail(
        sanitize_email( $req->email ),
        $subject,
        $this->build_email_html( $subject, $body ),
        $headers
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $id ),
        'dw_view_business_' . $id
    );
    $view_url = add_query_arg( 'dw_email_sent', '1', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: update business request status.
 * Hook: admin_post_dw_update_business_status
 */
public function admin_update_business_status() {
    $this->require_manage_options();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $id ) wp_die( 'Invalid request ID.' );

    $nonce_action = 'dw_update_business_status_' . $id;
    if (
        empty($_POST['_wpnonce']) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $nonce_action )
    ) {
        wp_die( 'Security check failed.' );
    }

    $status = isset($_POST['status']) ? sanitize_key( wp_unslash($_POST['status']) ) : '';
    $allowed = array( 'new', 'in_progress', 'completed' );
    if ( ! in_array( $status, $allowed, true ) ) {
        wp_die( 'Invalid status.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $wpdb->update(
        $table,
        array(
            'status'            => $status,
            'status_updated_at' => current_time('mysql'),
        ),
        array( 'id' => $id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $id ),
        'dw_view_business_' . $id
    );
    $view_url = add_query_arg( 'dw_updated', 'status', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: update Business Perks admin notes.
 * Hook: admin_post_dw_update_business_notes
 */
public function admin_update_business_notes() {
    $this->require_manage_options();

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid request ID.' );
    }

    $nonce_action = 'dw_update_business_notes_' . $id;
    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
            $nonce_action
        )
    ) {
        wp_die( 'Security check failed.' );
    }

    $admin_notes = isset( $_POST['admin_notes'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ) )
        : '';

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $wpdb->update(
        $table,
        array(
            'admin_notes'            => $admin_notes,
            'admin_notes_updated_at' => current_time( 'mysql' ),
        ),
        array( 'id' => $id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $id ),
        'dw_view_business_' . $id
    );
    $view_url = add_query_arg( 'dw_updated', 'notes', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: reply to Business Perks request via email.
 * Hook: admin_post_dw_reply_business
 */
public function admin_reply_business() {
    $this->require_manage_options();

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid request ID.' );
    }

    $nonce_action = 'dw_reply_business_' . $id;
    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
            $nonce_action
        )
    ) {
        wp_die( 'Security check failed.' );
    }

    $subject = isset( $_POST['subject'] )
        ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )
        : '';

    $message = isset( $_POST['message'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )
        : '';

    if ( ! $subject || ! $message ) {
        wp_die( 'Subject and message are required.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id )
    );

    if ( ! $row || empty( $row->email ) ) {
        wp_die( 'Request not found or missing email.' );
    }

    $headers = $this->get_email_headers();

    $body  = '<p>Hi ' . esc_html( $row->full_name ) . ',</p>';
    $body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';
    $body .= '<p style="margin-top:18px;">With gratitude,<br>Divine Wealth Solutions</p>';

    wp_mail(
        sanitize_email( $row->email ),
        $subject,
        $this->build_email_html( $subject, $body ),
        $headers
    );

    $view_url = wp_nonce_url(
        admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $id ),
        'dw_view_business_' . $id
    );
    $view_url = add_query_arg( 'dw_email_sent', '1', $view_url );

    wp_safe_redirect( $view_url );
    exit;
}

/**
 * Admin: export all Business Perks requests as CSV.
 * Hook: admin_post_dw_export_business_perks
 */
public function admin_export_business_perks() {
    $this->require_manage_options();

    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
            'dw_export_business_perks'
        )
    ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );

    // CSV headers
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=business-perks-requests-' . date( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );

    // Header row
    fputcsv( $output, array(
        'ID',
        'Created At',
        'Full Name',
        'Email',
        'Phone',
        'Business Name',
        'Perk Type',
        'Details',
        'Notes',
        'Referral Code',
        'Referral Type',
        'Member ID',
        'Tier at Request',
        'Status',
        'Admin Notes',
        'Admin Notes Updated',
        'IP Address',
        'User Agent',
    ) );

    if ( $rows ) {
        foreach ( $rows as $row ) {
            fputcsv( $output, array(
                $row['id'] ?? '',
                $row['created_at'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['business_name'] ?? '',
                $row['perk_type'] ?? '',
                $row['details'] ?? '',
                $row['notes'] ?? '',
                $row['referral_code'] ?? '',
                $row['referral_type'] ?? '',
                $row['member_id'] ?? '',
                $row['member_tier_at_request'] ?? '',
                $row['status'] ?? '',
                $row['admin_notes'] ?? '',
                $row['admin_notes_updated_at'] ?? '',
                $row['ip_address'] ?? '',
                $row['user_agent'] ?? '',
            ) );
        }
    }

    fclose( $output );
    exit;
}

/**
 * Admin: delete a Business Perks request.
 * Hook: admin_post_dw_delete_business_request
 */
public function admin_delete_business_request() {
    $this->require_manage_options();

    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( ! $id ) {
        wp_die( 'Invalid request ID.' );
    }

    $nonce_action = 'dw_delete_business_request_' . $id;
    if (
        empty( $_GET['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
            $nonce_action
        )
    ) {
        wp_die( 'Security check failed.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    $wpdb->delete(
        $table,
        array( 'id' => $id ),
        array( '%d' )
    );

    $redirect = admin_url( 'admin.php?page=dw-business-requests' );
    $redirect = add_query_arg( 'dw_deleted', '1', $redirect );

    wp_safe_redirect( $redirect );
    exit;
}

private function render_admin_business_request_detail( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'dw_business_perks_requests';

    // Nonce security
    $nonce_action = 'dw_view_business_' . $id;
    if (
        isset( $_GET['_wpnonce'] ) &&
        ! wp_verify_nonce(
            sanitize_text_field( $_GET['_wpnonce'] ),
            $nonce_action
        )
    ) {
        echo '<div class="wrap"><h1>Invalid request.</h1></div>';
        return;
    }

    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id )
    );

    if ( ! $row ) {
        echo '<div class="wrap"><h1>Business Perk Request</h1><p>Request not found.</p></div>';
        return;
    }

    $back_url = admin_url( 'admin.php?page=dw-business-requests' );

    $status_nonce = wp_create_nonce( 'dw_update_business_status_' . $id );
    $notes_nonce  = wp_create_nonce( 'dw_update_business_notes_' . $id );
    $reply_nonce  = wp_create_nonce( 'dw_reply_business_' . $id );

    $status_labels = array(
        'new'         => 'New',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    );

    echo '<div class="wrap">';
    echo '<h1>Business Perk Request Details</h1>';
    echo '<p><a class="button" href="' . esc_url( $back_url ) . '">&larr; Back to list</a></p>';

    // Card: Snapshot
    echo '<div class="card" style="max-width:1100px; padding:18px; margin-bottom:14px;">';
    echo '<h2 style="margin:0 0 8px;">' . esc_html( $row->full_name ) . '</h2>';
    echo '<p style="margin:0;">';
    echo '<strong>Email:</strong> ' . esc_html( $row->email ) . ' &nbsp;|&nbsp; ';
    echo '<strong>Business:</strong> ' . esc_html( $row->business_name ?: '—' ) . ' &nbsp;|&nbsp; ';
    echo '<strong>Perk:</strong> ' . esc_html( $row->perk_type ) . ' &nbsp;|&nbsp; ';
    echo '<strong>Code:</strong> <code>' . esc_html( $row->referral_code ) . '</code>';
    echo '</p>';
    echo '</div>';

    // Table of fields
    echo '<table class="widefat striped" style="max-width:1100px; margin-bottom:14px;">';
    echo '<tbody>';

    $fields = array(
        'id'                      => 'ID',
        'created_at'              => 'Submitted',
        'referral_type'           => 'Referral Type',
        'member_tier_at_request'  => 'Tier at Request',
        'perk_type'               => 'Perk Type',
        'service_requested'       => 'Service Requested',
        'project_details'         => 'Project Details',
        'status'                  => 'Status',
        'admin_notes'             => 'Admin Notes',
        'admin_notes_updated_at'  => 'Admin Notes Updated',
        'ip_address'              => 'IP Address',
        'user_agent'              => 'User Agent'
    );

    foreach ( $fields as $key => $label ) {
        $val = isset( $row->$key ) ? $row->$key : '—';
        if ( is_string( $val ) ) {
            $val = nl2br( esc_html( $val ) );
        }

        echo '<tr>';
        echo '<th style="width:240px;">' . esc_html( $label ) . '</th>';
        echo '<td>' . $val . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Status Editor
    echo '<div class="card" style="max-width:1100px; padding:18px; margin-bottom:14px;">';
    echo '<h2>Update Status</h2>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_business_status">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $status_nonce ) . '">';

    echo '<select name="status">';
    foreach ( $status_labels as $slug => $lbl ) {
        $selected = selected( $row->status, $slug, false );
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $lbl ) . '</option>';
    }
    echo '</select>';

    echo ' <button class="button button-primary">Save Status</button>';
    echo '</form>';
    echo '</div>';

    // Admin Notes
    echo '<div class="card" style="max-width:1100px; padding:18px; margin-bottom:14px;">';
    echo '<h2>Internal Admin Notes</h2>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="dw_update_business_notes">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $notes_nonce ) . '">';
    echo '<textarea name="admin_notes" rows="5" style="width:100%;">' . esc_textarea( $row->admin_notes ?? '' ) . '</textarea>';
    echo '<p><button class="button button-primary">Save Notes</button></p>';
    echo '</form>';
    echo '</div>';

    // Reply Email Tool
    echo '<div class="card" style="max-width:1100px; padding:18px;">';
    echo '<h2>Email Reply</h2>';

    if ( isset($_GET['dw_email_sent']) ) {
        echo '<p style="color:green;"><strong>Sent!</strong> Email has been sent.</p>';
    }

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="dw_reply_business_request">';
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $reply_nonce ) . '">';
    echo '<p><input type="text" name="subject" placeholder="Subject" style="width:100%; max-width:700px;"></p>';
    echo '<p><textarea name="message" rows="6" placeholder="Write your message..." style="width:100%;"></textarea></p>';
    echo '<p><button class="button button-primary">Send Email</button></p>';
    echo '</form>';
    echo '</div>';

    echo '</div>';
}

   public function render_admin_business_requests() {
    global $wpdb;

    $table    = $wpdb->prefix . 'dw_business_perks_requests';
    $per_page = 20;
    $paged    = $this->admin_get_paged();
    $offset   = ( $paged - 1 ) * $per_page;
    $search   = $this->admin_get_search();

    // Ensure table exists
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
    if ( ! $exists ) {
        echo '<div class="wrap"><h1>Business Perks Requests</h1><p>Table not found yet. Submit one Business Perks form once, then refresh.</p></div>';
        return;
    }

    $action = $this->admin_get_action();
    $id     = $this->admin_get_id();

    // View page
    if ( $action === 'view' && $id ) {
        $this->render_admin_business_request_detail( $id );
        return;
    }

    // Filters
    $where  = "1=1";
    $params = array();
    if ( $search ) {
        $where .= " AND (full_name LIKE %s OR email LIKE %s OR referral_code LIKE %s OR business_name LIKE %s)";
        $like   = '%' . $wpdb->esc_like( $search ) . '%';
        $params = array( $like, $like, $like, $like );
    }

    // Count
    $total = (int) (
        $params
            ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) )
            : $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" )
    );

    // Fetch rows
    $query = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $rows  = $params
        ? $wpdb->get_results( $wpdb->prepare( $query, array_merge( $params, array( $per_page, $offset ) ) ) )
        : $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

    echo '<div class="wrap"><h1>Business Perks Requests</h1>';

    // Search form
    echo '<form method="get" style="margin:10px 0;">
            <input type="hidden" name="page" value="dw-business-requests" />
            <input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search name, email, code, business" />
            <button class="button">Search</button>
          </form>';

    // Export button
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 18px 0;">
            <input type="hidden" name="action" value="dw_export_business_perks" />
            <input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'dw_export_business_perks' ) ) . '" />
            <button class="button">Export All to CSV</button>
          </form>';

    if ( isset( $_GET['dw_deleted'] ) && $_GET['dw_deleted'] === '1' ) {
        echo '<div class="notice notice-success"><p>Business Perks request deleted.</p></div>';
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>
            <th>ID</th>
            <th>Date</th>
            <th>Name</th>
            <th>Business</th>
            <th>Email</th>
            <th>Perk Type</th>
            <th>Referral Code</th>
            <th>Referral Type</th>
            <th>Tier</th>
            <th>Status</th>
            <th>Actions</th>
          </tr></thead><tbody>';

    $status_labels = array(
        'new'         => 'New',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    );

    if ( $rows ) {
        foreach ( $rows as $r ) {

            $view_url = wp_nonce_url(
                admin_url( 'admin.php?page=dw-business-requests&action=view&id=' . $r->id ),
                'dw_view_business_' . $r->id
            );

            $delete_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=dw_delete_business_request&id=' . $r->id ),
                'dw_delete_business_request_' . $r->id
            );

            echo '<tr>
                    <td><a href="' . esc_url( $view_url ) . '">' . esc_html( $r->id ) . '</a></td>
                    <td>' . esc_html( $r->created_at ) . '</td>
                    <td>' . esc_html( $r->full_name ) . '</td>
                    <td>' . esc_html( $r->business_name ) . '</td>
                    <td>' . esc_html( $r->email ) . '</td>
                    <td>' . esc_html( $r->perk_type ) . '</td>
                    <td><code>' . esc_html( $r->referral_code ) . '</code></td>
                    <td>' . esc_html( ucfirst( $r->referral_type ?: '' ) ) . '</td>
                    <td>' . esc_html( ucfirst( $r->member_tier_at_request ) ) . '</td>
                    <td>' . esc_html( $status_labels[ $r->status ?? 'new' ] ?? 'New' ) . '</td>
                    <td>
                        <a class="button button-small" href="' . esc_url( $view_url ) . '">View</a>
                        <a class="button button-small" href="' . esc_url( $delete_url ) . '"
                           onclick="return confirm(\'Are you sure you want to delete this Business Perks request?\');">
                           Delete
                        </a>
                    </td>
                  </tr>';
        }
    } else {
        echo '<tr><td colspan="11">No business requests found.</td></tr>';
    }

    echo '</tbody></table>';

    $this->admin_pagination( $total, $per_page, $paged, 'dw-business-requests' );
    echo '</div>';
}
    public function render_admin_referral_logs() {
        global $wpdb;

        $table_logs    = $wpdb->prefix . 'dw_referral_logs';

        // Make sure table exists
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_logs ) );
        if ( ! $exists ) {
            echo '<div class="wrap"><h1>Referral Logs</h1><p>Referral log table not found yet. After updating the plugin, deactivate and reactivate it once to create the new table.</p></div>';
            return;
        }

        $per_page = 20;
        $paged    = $this->admin_get_paged();
        $offset   = ( $paged - 1 ) * $per_page;
        $search   = $this->admin_get_search();

        $where  = "1=1";
        $params = array();

        if ( $search ) {
            $where .= " AND (client_name LIKE %s OR client_email LIKE %s OR referral_code LIKE %s)";
            $like   = '%' . $wpdb->esc_like( $search ) . '%';
            $params = array( $like, $like, $like );
        }

        $total = (int) ( $params
            ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_logs} WHERE {$where}", $params ) )
            : $wpdb->get_var( "SELECT COUNT(*) FROM {$table_logs}" )
        );

        $query = "SELECT * FROM {$table_logs} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $rows  = $params
            ? $wpdb->get_results( $wpdb->prepare( $query, array_merge( $params, array( $per_page, $offset ) ) ) )
            : $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

        echo '<div class="wrap"><h1>Referral Logs</h1>';

        // Flash messages
        if ( isset( $_GET['dw_log_added'] ) && $_GET['dw_log_added'] === '1' ) {
            echo '<div class="notice notice-success"><p>Referral log saved and member totals updated.</p></div>';
        }
        if ( isset( $_GET['dw_error'] ) && $_GET['dw_error'] === 'no_member' ) {
            echo '<div class="notice notice-error"><p>No Rewards member found for that referral code.</p></div>';
        }
        if ( isset( $_GET['dw_deleted'] ) && $_GET['dw_deleted'] === '1' ) {
         echo '<div class="notice notice-success"><p>Referral log deleted.</p></div>';
        }

        // Add referral form
        $action_url = admin_url( 'admin-post.php' );
        $nonce      = wp_create_nonce( 'dw_add_referral_log' );

        echo '<h2>Add Closed Referral</h2>';
        echo '<form method="post" action="' . esc_url( $action_url ) . '" style="max-width:800px; margin:15px 0 25px; padding:15px; background:#fff; border:1px solid #ccd0d4; border-radius:4px;">';
        echo '<input type="hidden" name="action" value="dw_add_referral_log" />';
        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';

        echo '<table class="form-table"><tbody>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_referral_code">Referral Code</label></th>';
        echo '<td><input type="text" id="dw_log_referral_code" name="referral_code" class="regular-text" required /> ';
        echo '<p class="description">Paste the Rewards referral code that earned this commission.</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_client_name">Client Name</label></th>';
        echo '<td><input type="text" id="dw_log_client_name" name="client_name" class="regular-text" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_client_email">Client Email</label></th>';
        echo '<td><input type="email" id="dw_log_client_email" name="client_email" class="regular-text" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_service_type">Service Type</label></th>';
        echo '<td>';
        echo '<select id="dw_log_service_type" name="service_type">';
        echo '<option value="">Select one</option>';
        echo '<option value="tax_prep">Tax Prep</option>';
        echo '<option value="credit">Credit</option>';
        echo '<option value="funding">Business Funding</option>';
        echo '<option value="travel">Travel</option>';
        echo '<option value="business_perk">Business Perk</option>';
        echo '<option value="other">Other</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_amount">Revenue Amount</label></th>';
        echo '<td><input type="number" step="0.01" min="0" id="dw_log_amount" name="amount" class="regular-text" /> ';
        echo '<p class="description">Total revenue associated with this referral (for your own tracking).</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="dw_log_notes">Internal Notes</label></th>';
        echo '<td><textarea id="dw_log_notes" name="notes" rows="3" class="large-text"></textarea></td>';
        echo '</tr>';

        echo '</tbody></table>';

        echo '<p><button type="submit" class="button button-primary">Save Referral Log</button></p>';
        echo '</form>';

        // Search form
        echo '<h2>Recent Referral Activity</h2>';
        echo '<form method="get" style="margin:10px 0;">';
        echo '<input type="hidden" name="page" value="dw-referral-logs" />';
        echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search client, email, or referral code" />';
        echo ' <button class="button">Search</button>';
        echo '</form>';

        // Table of logs
       echo '<table class="widefat striped">';
        echo '<thead><tr>
        <th>Date</th>
        <th>Referral Code</th>
        <th>Client</th>
        <th>Service</th>
        <th>Amount</th>
        <th>Notes</th>
        <th>Actions</th>
        </tr></thead><tbody>';


        if ( $rows ) {
            foreach ( $rows as $row ) {

        // Build delete URL with nonce
        $delete_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=dw_delete_referral_log&id=' . $row->id ),
            'dw_delete_referral_log_' . $row->id
        );

        echo '<tr>';
        echo '<td>' . esc_html( $row->created_at ) . '</td>';
        echo '<td><code>' . esc_html( $row->referral_code ) . '</code></td>';

        $client = trim(
            ( $row->client_name ?: '' ) .
            ( $row->client_email ? ' (' . $row->client_email . ')' : '' )
        );
        echo '<td>' . ( $client ? esc_html( $client ) : '—' ) . '</td>';

        echo '<td>' . esc_html(
            $row->service_type
                ? ucfirst( str_replace( '_', ' ', $row->service_type ) )
                : '—'
        ) . '</td>';

        echo '<td>$' . esc_html( number_format( (float) $row->amount, 2 ) ) . '</td>';
        echo '<td>' . ( $row->notes ? nl2br( esc_html( $row->notes ) ) : '—' ) . '</td>';

        // Actions column
        echo '<td>';
        echo '<a href="' . esc_url( $delete_url ) . '" class="submitdelete" ';
        echo 'onclick="return confirm(\'Delete this referral log? This cannot be undone.\');">Delete</a>';
        echo '</td>';

        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7">No referral logs yet.</td></tr>';
}
}
   
public function render_admin_payout_requests() {
    global $wpdb;

$action = $this->admin_get_action();
$id     = $this->admin_get_id();

if ( $action === 'view' && $id ) {
    $this->render_admin_payout_request_detail( $id );
    return;
}

    // Use the direct table name; this is the table you confirmed has data.
    $table    = $wpdb->prefix . 'dw_payout_requests';
    $per_page = 20;
    $paged    = $this->admin_get_paged();
    $offset   = ( $paged - 1 ) * $per_page;
    $search   = $this->admin_get_search();

    // 1) Make sure the table actually exists
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        )
    );

    if ( ! $exists ) {
        echo '<div class="wrap"><h1>Payout Requests</h1><p>Table not found yet. Once the first payout request is stored, refresh this page.</p></div>';
        return;
    }

    // 2) Build WHERE + params (simple text search)
    $where  = '1=1';
    $params = array();

    if ( $search ) {
        $like   = '%' . $wpdb->esc_like( $search ) . '%';
        $where .= " AND (rewards_code LIKE %s OR payout_method LIKE %s)";
        $params = array( $like, $like );
    }

    // 3) Count total rows
    if ( $params ) {
        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE {$where}",
                $params
            )
        );
    } else {
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    // 4) Fetch rows (no join for now, keep it simple and reliable)
    $sql = "SELECT *
            FROM {$table}
            WHERE {$where}
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d";

    if ( $params ) {
        $query_params = array_merge( $params, array( $per_page, $offset ) );
        $rows         = $wpdb->get_results(
            $wpdb->prepare( $sql, $query_params )
        );
    } else {
        $rows = $wpdb->get_results(
            $wpdb->prepare( $sql, $per_page, $offset )
        );
    }

    $status_labels = array(
        'new'       => 'New',
        'pending'   => 'Pending Verification',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    );

    echo '<div class="wrap"><h1>Payout Requests</h1>';

    // Search form
    echo '<form method="get" style="margin:10px 0;">
            <input type="hidden" name="page" value="dw-payout-requests" />
            <input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Search by rewards code or method" />
            <button class="button">Search</button>
          </form>';

    echo '<table class="widefat striped">';
    echo '<thead><tr>
            <th>ID</th>
            <th>Date</th>
            <th>Rewards Code</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Method</th>
            <th>Status</th>
          </tr></thead><tbody>';

    if ( $rows ) {
        foreach ( $rows as $r ) {
            $view_url = wp_nonce_url(
                admin_url( 'admin.php?page=dw-payout-requests&action=view&id=' . $r->id ),
                'dw_view_payout_' . $r->id
            );

            echo '<tr>
                    <td><a href="' . esc_url( $view_url ) . '">' . esc_html( $r->id ) . '</a></td>
                    <td>' . esc_html( $r->created_at ) . '</td>
                    <td><code>' . esc_html( $r->rewards_code ) . '</code></td>
                    <td>$' . esc_html( number_format( (float) $r->amount_requested, 2 ) ) . '</td>
                    <td>' . esc_html( ucfirst( $r->request_type ) ) . '</td>
                    <td>' . esc_html( ucfirst( $r->payout_method ) ) . '</td>
                    <td>' . esc_html( $status_labels[ $r->status ] ?? $r->status ) . '</td>
                  </tr>';
        }
    } else {
        echo '<tr><td colspan="7">No payout requests found.</td></tr>';
    }

    echo '</tbody></table>';

    $this->admin_pagination( $total, $per_page, $paged, 'dw-payout-requests' );
    echo '</div>';
}




private function render_admin_payout_request_detail( $id ) {
    global $wpdb;

    // Use the actual table name you confirmed:
    $table   = $wpdb->prefix . 'dw_payout_requests';
    $members = $wpdb->prefix . 'dw_reward_members';

    // On the initial GET of the page, verify the "view" nonce.
    if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
        check_admin_referer( 'dw_view_payout_' . $id );
    }

    // Always fetch the row by the ID we're passed in the URL.
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT p.*, m.full_name, m.email
             FROM {$table} p
             LEFT JOIN {$members} m ON p.member_id = m.id
             WHERE p.id = %d
             LIMIT 1",
            $id
        )
    );

    if ( ! $row ) {
        echo '<div class="wrap"><h1>Payout Request</h1><p>Request not found.</p></div>';
        return;
    }

    $status_options = array(
        'new'       => 'New',
        'pending'   => 'Pending Verification',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    );

    // -------------------------------------------------
    // Handle POST updates (status + admin notes)
    // -------------------------------------------------
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['dw_update_payout'] ) ) {
        // Check the UPDATE nonce (different from the view nonce)
        check_admin_referer( 'dw_update_payout_' . $row->id );

        $new_status = isset( $_POST['status'] )
            ? sanitize_text_field( wp_unslash( $_POST['status'] ) )
            : $row->status;

        $notes = isset( $_POST['admin_notes'] )
            ? wp_kses_post( wp_unslash( $_POST['admin_notes'] ) )
            : $row->admin_notes;

        $paid_at = $row->paid_at;

        // When set to completed for the first time, stamp paid_at
        if ( $new_status === 'completed' && empty( $paid_at ) ) {
            $paid_at = current_time( 'mysql' );
        } elseif ( $new_status !== 'completed' ) {
            // If you change out of completed, clear the paid_at
            $paid_at = null;
        }

        $wpdb->update(
            $table,
            array(
                'status'      => $new_status,
                'admin_notes' => $notes,
                'paid_at'     => $paid_at,
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'id' => $row->id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        // After saving, redirect back to the same detail page
        // with a fresh "view" nonce and ?updated=1 flag.
        $detail_url = wp_nonce_url(
            admin_url( 'admin.php?page=dw-payout-requests&action=view&id=' . $row->id ),
            'dw_view_payout_' . $row->id
        );
        $detail_url = add_query_arg( 'updated', '1', $detail_url );
        wp_safe_redirect( $detail_url );
        exit;
    }

    echo '<div class="wrap"><h1>Payout Request #' . esc_html( $row->id ) . '</h1>';

    // Show success notice after redirect
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Payout request updated.</p></div>';
    }

    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Member</th>
            <td><?php echo esc_html( $row->full_name ?: '—' ); ?></td>
        </tr>
        <tr>
            <th scope="row">Email</th>
            <td><?php echo esc_html( $row->email ?: '—' ); ?></td>
        </tr>
        <tr>
            <th scope="row">Rewards Code</th>
            <td><code><?php echo esc_html( $row->rewards_code ); ?></code></td>
        </tr>
        <tr>
            <th scope="row">Amount Requested</th>
            <td>$<?php echo esc_html( number_format( (float) $row->amount_requested, 2 ) ); ?></td>
        </tr>
        <tr>
            <th scope="row">Request Type</th>
            <td><?php echo esc_html( ucfirst( $row->request_type ) ); ?></td>
        </tr>
        <tr>
            <th scope="row">Payout Method</th>
            <td><?php echo esc_html( ucfirst( $row->payout_method ) ); ?></td>
        </tr>
        <tr>
            <th scope="row">Payout Details</th>
            <td><?php echo nl2br( esc_html( $row->payout_details ) ); ?></td>
        </tr>
        <tr>
            <th scope="row">Created</th>
            <td><?php echo esc_html( $row->created_at ); ?></td>
        </tr>
        <tr>
            <th scope="row">Paid At</th>
            <td><?php echo $row->paid_at ? esc_html( $row->paid_at ) : '—'; ?></td>
        </tr>
        <tr>
            <th scope="row">Current Status</th>
            <td><?php echo esc_html( $status_options[ $row->status ] ?? $row->status ); ?></td>
        </tr>
    </table>

    <h2>Update Status</h2>
    <form method="post">
        <?php wp_nonce_field( 'dw_update_payout_' . $row->id ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="dw_payout_status">Status</label></th>
                <td>
                    <select id="dw_payout_status" name="status">
                        <?php foreach ( $status_options as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $row->status, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="dw_payout_notes">Admin Notes</label></th>
                <td>
                    <textarea id="dw_payout_notes"
                              name="admin_notes"
                              rows="5"
                              class="large-text"><?php echo esc_textarea( $row->admin_notes ); ?></textarea>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="dw_update_payout" class="button button-primary">
                Save Changes
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=dw-payout-requests' ) ); ?>" class="button">
                Back to list
            </a>
        </p>
    </form>
    <?php

    echo '</div>';
}





    public function admin_add_referral_log() {
        $this->require_manage_options();

        if (
            empty( $_POST['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'dw_add_referral_log' )
        ) {
            wp_die( 'Security check failed.' );
        }

        $referral_code = isset( $_POST['referral_code'] )
            ? strtoupper( sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) )
            : '';

        if ( ! $referral_code ) {
            wp_die( 'Referral code is required.' );
        }

        $client_name  = isset( $_POST['client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['client_name'] ) ) : '';
        $client_email = isset( $_POST['client_email'] ) ? sanitize_email( wp_unslash( $_POST['client_email'] ) ) : '';
        $service_type = isset( $_POST['service_type'] ) ? sanitize_key( wp_unslash( $_POST['service_type'] ) ) : '';
        $amount       = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $notes        = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

        global $wpdb;
        $members_table = $wpdb->prefix . 'dw_rewards_members';
        $logs_table    = $wpdb->prefix . 'dw_referral_logs';

        // Find member by referral code
        $member = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$members_table} WHERE referral_code = %s LIMIT 1",
                $referral_code
            )
        );

        $redirect = admin_url( 'admin.php?page=dw-referral-logs' );

        if ( ! $member ) {
            $redirect = add_query_arg( 'dw_error', 'no_member', $redirect );
            wp_safe_redirect( $redirect );
            exit;
        }

        // Insert log
        $wpdb->insert(
            $logs_table,
            array(
                'created_at'    => current_time( 'mysql' ),
                'member_id'     => (int) $member->id,
                'referral_code' => $referral_code,
                'client_name'   => $client_name,
                'client_email'  => $client_email,
                'service_type'  => $service_type,
                'amount'        => $amount,
                'notes'         => $notes,
                'source'        => 'manual',
            ),
            array(
                '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s',
            )
        );

        // Update member totals
        $wpdb->update(
            $members_table,
            array(
                'total_referrals' => (int) $member->total_referrals + 1,
                'total_revenue'   => (float) $member->total_revenue + $amount,
            ),
            array( 'id' => (int) $member->id ),
            array( '%d', '%f' ),
            array( '%d' )
        );

        $redirect = add_query_arg( 'dw_log_added', '1', $redirect );
        wp_safe_redirect( $redirect );
        exit;
    }

    public function admin_delete_referral_log() {
        $this->require_manage_options();

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_die( 'Invalid referral log ID.' );
        }

        if (
            empty( $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dw_delete_referral_log_' . $id )
        ) {
            wp_die( 'Security check failed.' );
        }

        global $wpdb;
        $logs_table = $wpdb->prefix . 'dw_referral_logs';

        $wpdb->delete(
            $logs_table,
            array( 'id' => $id ),
            array( '%d' )
        );

        $redirect = admin_url( 'admin.php?page=dw-referral-logs' );
        $redirect = add_query_arg( 'dw_deleted', '1', $redirect );

        wp_safe_redirect( $redirect );
        exit;
    }


} // ✅ THIS is the missing class closing brace you needed.

new Divine_Wealth_Forms();
register_activation_hook( __FILE__, array( 'Divine_Wealth_Forms', 'activate' ) );

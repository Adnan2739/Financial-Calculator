<?php
/**
 * Plugin Name: Financial Calculator
 * Description: Custom financial calculator plugin with asset financing fields, modern UI, and log management.
 * Version: 2.3.0
 * Author: Muhammad Adnan
 * Text Domain: fc-calculator
 * Domain Path: /languages
 */



if (!defined('ABSPATH')) exit;

class FC_Updated_Plugin {
    private static $instance = null;
    private $table;
    private $option_key = 'fc_updated_settings';

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'fc_calc_logs';

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_fc_send_result', array($this, 'ajax_send'));
        add_action('wp_ajax_nopriv_fc_send_result', array($this, 'ajax_send'));
        add_action('wp_ajax_fc_delete_log', array($this, 'ajax_delete_log'));
        register_activation_hook(__FILE__, array($this, 'on_activate'));
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'fc-calculator',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function on_activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) DEFAULT '',
            email VARCHAR(191) DEFAULT '',
            data LONGTEXT,
            ip VARCHAR(45) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $defaults = array(
            'from_email' => get_option('admin_email'),
            'interest_percent' => 15,
            'asset_types' => 'Saloon Vehicle,Commercial Vehicle,Plant & Machinery,Office Equipment',
            'asset_models' => 'Brand New,Used',
            'down_payment_options' => '0,10,20,30,40,50',
            'tenor_years_options' => '1,2,3,4,5'
        );
        add_option($this->option_key, $defaults);
    }

    public function register_settings() {
        register_setting($this->option_key, $this->option_key, array($this, 'sanitize_settings'));
        add_settings_section('fc_main_section', __('Calculator Settings', 'fc-calculator'), null, 'fc_calc_settings');

        add_settings_field('from_email', __('From Email', 'fc-calculator'), array($this, 'field_from_email'), 'fc_calc_settings', 'fc_main_section');
        add_settings_field('interest_percent', __('Interest Rate (%)', 'fc-calculator'), array($this, 'field_interest_percent'), 'fc_calc_settings', 'fc_main_section');

        // Dropdown options fields
        add_settings_field('asset_types', __('Asset Types (comma separated)', 'fc-calculator'), array($this, 'field_asset_types'), 'fc_calc_settings', 'fc_main_section');
        add_settings_field('asset_models', __('Asset Models (comma separated)', 'fc-calculator'), array($this, 'field_asset_models'), 'fc_calc_settings', 'fc_main_section');
        add_settings_field('down_payment_options', __('Down Payment Options (%)', 'fc-calculator'), array($this, 'field_down_payment_options'), 'fc_calc_settings', 'fc_main_section');
        add_settings_field('tenor_years_options', __('Tenor Years Options', 'fc-calculator'), array($this, 'field_tenor_years_options'), 'fc_calc_settings', 'fc_main_section');
    }

    public function sanitize_settings($v) {
        $out = array();
        $out['from_email'] = sanitize_email($v['from_email'] ?? '');
        $out['interest_percent'] = floatval($v['interest_percent'] ?? 0);
        if ($out['interest_percent'] < 10) $out['interest_percent'] = 10;
        if ($out['interest_percent'] > 50) $out['interest_percent'] = 50;

        // Sanitize dropdowns
        $out['asset_types'] = sanitize_text_field($v['asset_types'] ?? '');
        $out['asset_models'] = sanitize_text_field($v['asset_models'] ?? '');
        $out['down_payment_options'] = sanitize_text_field($v['down_payment_options'] ?? '');
        $out['tenor_years_options'] = sanitize_text_field($v['tenor_years_options'] ?? '');

        return $out;
    }

    // Fields
    public function field_from_email() {
        $opts = get_option($this->option_key);
        $val = $opts['from_email'] ?? get_option('admin_email');
        echo "<input type='email' name='{$this->option_key}[from_email]' value='".esc_attr($val)."' style='width:320px;' />";
    }

    public function field_interest_percent() {
        $opts = get_option($this->option_key);
        $val = $opts['interest_percent'] ?? 15;
        echo "<input type='number' step='0.1' min='10' max='50' name='{$this->option_key}[interest_percent]' value='".esc_attr($val)."' />";
    }

    public function field_asset_types() {
        $opts = get_option($this->option_key);
        $val = $opts['asset_types'] ?? '';
        echo "<input type='text' name='{$this->option_key}[asset_types]' value='".esc_attr($val)."' style='width:400px;' />";
        echo "<p class='description'>" . __('Comma separated asset types, e.g., Saloon Vehicle,Commercial Vehicle', 'fc-calculator') . "</p>";
    }

    public function field_asset_models() {
        $opts = get_option($this->option_key);
        $val = $opts['asset_models'] ?? '';
        echo "<input type='text' name='{$this->option_key}[asset_models]' value='".esc_attr($val)."' style='width:400px;' />";
        echo "<p class='description'>" . __('Comma separated asset models, e.g., Brand New,Used', 'fc-calculator') . "</p>";
    }

    public function field_down_payment_options() {
        $opts = get_option($this->option_key);
        $val = $opts['down_payment_options'] ?? '';
        echo "<input type='text' name='{$this->option_key}[down_payment_options]' value='".esc_attr($val)."' style='width:400px;' />";
        echo "<p class='description'>" . __('Comma separated numbers, e.g., 0,10,20,30', 'fc-calculator') . "</p>";
    }

    public function field_tenor_years_options() {
        $opts = get_option($this->option_key);
        $val = $opts['tenor_years_options'] ?? '';
        echo "<input type='text' name='{$this->option_key}[tenor_years_options]' value='".esc_attr($val)."' style='width:400px;' />";
        echo "<p class='description'>" . __('Comma separated years, e.g., 1,2,3,4,5', 'fc-calculator') . "</p>";
    }

    // Admin Menu
    public function admin_menu() {
        add_menu_page(__('Financial Calculator', 'fc-calculator'), __('Financial Calculator', 'fc-calculator'), 'manage_options', 'fc_calc_main', array($this, 'admin_page_dashboard'), 'dashicons-calculator', 60);
        add_submenu_page('fc_calc_main', __('Settings', 'fc-calculator'), __('Settings', 'fc-calculator'), 'manage_options', 'fc_calc_settings', array($this, 'admin_page_settings'));
        add_submenu_page('fc_calc_main', __('Logs', 'fc-calculator'), __('Logs', 'fc-calculator'), 'manage_options', 'fc_calc_logs', array($this, 'admin_page_logs'));
    }

    public function admin_page_dashboard() {
        echo '<div class="wrap"><h1>' . __('Financial Calculator', 'fc-calculator') . '</h1><p>' . sprintf(__('Use shortcode %s to display calculator.', 'fc-calculator'), '<code>[fc_calculator]</code>') . '</p></div>';
    }

    public function admin_page_settings() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>' . __('Calculator Settings', 'fc-calculator') . '</h1><form method="post" action="options.php">';
        settings_fields($this->option_key);
        do_settings_sections('fc_calc_settings');
        submit_button();
        echo '</form></div>';
    }

    public function admin_page_logs() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT 200");

        echo '<div class="wrap"><h1>' . __('Calculator Logs', 'fc-calculator') . '</h1>';
        echo '<table class="widefat fixed striped"><thead><tr><th>' . __('ID', 'fc-calculator') . '</th><th>' . __('Name', 'fc-calculator') . '</th><th>' . __('Email', 'fc-calculator') . '</th><th>' . __('IP', 'fc-calculator') . '</th><th>' . __('Created', 'fc-calculator') . '</th><th>' . __('Data', 'fc-calculator') . '</th><th>' . __('Action', 'fc-calculator') . '</th></tr></thead><tbody>';
        if ($rows) {
            foreach ($rows as $r) {
                $data_arr = maybe_unserialize($r->data);
                $data_html = '';
                if (is_array($data_arr) && isset($data_arr['raw'])) {
                    $data_html = nl2br(esc_html($data_arr['raw']));
                } else {
                    $data_html = esc_html($r->data);
                }
                echo "<tr id='log-row-{$r->id}'>
                    <td>{$r->id}</td>
                    <td>".esc_html($r->name)."</td>
                    <td>".esc_html($r->email)."</td>
                    <td>".esc_html($r->ip)."</td>
                    <td>".esc_html($r->created_at)."</td>
                    <td><div style='max-height:150px; overflow-y:auto; font-family: monospace; white-space: pre-wrap;'>".$data_html."</div></td>
                    <td><button class='button button-danger fc-delete-log' data-id='{$r->id}'>" . __('Delete', 'fc-calculator') . "</button></td>
                </tr>";
            }
        } else {
            echo '<tr><td colspan="7">' . __('No logs yet.', 'fc-calculator') . '</td></tr>';
        }
        echo '</tbody></table></div>';

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $('.fc-delete-log').on('click', function(){
                if(!confirm('<?php echo esc_js(__('Are you sure you want to delete this log?', 'fc-calculator')); ?>')) return;
                var btn = $(this);
                var id = btn.data('id');
                btn.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'fc-calculator')); ?>');
                $.post(ajaxurl, {
                    action: 'fc_delete_log',
                    nonce: '<?php echo wp_create_nonce('fc_delete_log_nonce'); ?>',
                    id: id
                }, function(res){
                    if(res.success){
                        $('#log-row-'+id).fadeOut(300, function(){ $(this).remove(); });
                    } else {
                        alert(res.data || '<?php echo esc_js(__('Delete failed.', 'fc-calculator')); ?>');
                        btn.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'fc-calculator')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_delete_log() {
        check_ajax_referer('fc_delete_log_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'fc-calculator'));
        }

        global $wpdb;
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $deleted = $wpdb->delete($this->table, array('id' => $id), array('%d'));
            if ($deleted) {
                wp_send_json_success(__('Deleted', 'fc-calculator'));
            } else {
                wp_send_json_error(__('Could not delete record.', 'fc-calculator'));
            }
        } else {
            wp_send_json_error(__('Invalid ID.', 'fc-calculator'));
        }
    }

    public function enqueue_assets() {
        $opts = get_option($this->option_key);

        wp_enqueue_style('fc-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '2.3.0');

        wp_enqueue_script('fc-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '2.3.0', true);

        // Get current language (PolyLang compatible)
        $current_lang = $this->get_current_language();

        $cfg = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fc_calc_nonce'),
            'ajaxAction' => 'fc_send_result',
            'interestPercent' => floatval($opts['interest_percent'] ?? 15),
            'assetTypes' => $opts['asset_types'] ?? '',
            'assetModels' => $opts['asset_models'] ?? '',
            'downPaymentOptions' => $opts['down_payment_options'] ?? '',
            'tenorYearsOptions' => $opts['tenor_years_options'] ?? '',
            'currentLang' => $current_lang,
            'isRTL' => is_rtl(),
            // Translations for JavaScript
            'i18n' => array(
                'typeOfAsset' => __('Type of Asset', 'fc-calculator'),
                'assetModel' => __('Asset Model', 'fc-calculator'),
                'valueOfAsset' => __('Value of Asset (Rs)', 'fc-calculator'),
                'downPayment' => __('Down Payment (%)', 'fc-calculator'),
                'tenor' => __('Tenor (Years)', 'fc-calculator'),
                'netLease' => __('Net Lease / Finance Amount (Rs)', 'fc-calculator'),
                'installmentAmount' => __('Installment Amount (Monthly) (Rs)', 'fc-calculator'),
                'termsAgree' => __('I agree to the terms and conditions', 'fc-calculator'),
                'calculate' => __('Calculate', 'fc-calculator'),
                'result' => __('Result', 'fc-calculator'),
                'resultDesc' => __('Your calculation results are displayed below', 'fc-calculator'),
                'interest' => __('Interest', 'fc-calculator'),
                'downPaymentRs' => __('Down Payment (Rs)', 'fc-calculator'),
                'tenorLabel' => __('Tenor', 'fc-calculator'),
                'monthlyInstallment' => __('Monthly Installment', 'fc-calculator'),
                'totalLoanAmount' => __('Total Loan Amount', 'fc-calculator'),
                'amountPaid' => __('Amount Paid', 'fc-calculator'),
                'emailResult' => __('Email this Result', 'fc-calculator'),
                'emailYourResults' => __('Email Your Results', 'fc-calculator'),
                'yourName' => __('Your Name', 'fc-calculator'),
                'enterName' => __('Enter your name', 'fc-calculator'),
                'yourEmail' => __('Your Email', 'fc-calculator'),
                'enterEmail' => __('Enter your email', 'fc-calculator'),
                'sendEmail' => __('Send Email', 'fc-calculator'),
                'fieldRequired' => __('This field is required', 'fc-calculator'),
                'agreeTerms' => __('Please agree to the terms', 'fc-calculator'),
                'emailSentSuccess' => __('Email sent successfully!', 'fc-calculator'),
                'serverError' => __('Server error', 'fc-calculator'),
                'ajaxError' => __('AJAX error - see console', 'fc-calculator'),
                'year' => __('yr', 'fc-calculator'),
                'years' => __('yrs', 'fc-calculator'),
                'rs' => __('Rs', 'fc-calculator')
            )
        ];

        wp_localize_script('fc-script', 'FinCalcConfig', $cfg);
    }

    /**
     * Get current language (PolyLang compatible)
     */
    private function get_current_language() {
        // Check if PolyLang is active
        if (function_exists('pll_current_language')) {
            return pll_current_language();
        }
        
        // Fallback to WordPress locale
        $locale = get_locale();
        return substr($locale, 0, 2); // Return language code (e.g., 'en', 'ur')
    }

    public function register_shortcodes() {
        add_shortcode('fc_calculator', array($this, 'render_calculator'));
    }

    public function render_calculator() {
        return '<div id="fin-calc-root"></div>';
    }

    // public function ajax_send() {
    //     check_ajax_referer('fc_calc_nonce', 'nonce');
    //     $name = sanitize_text_field($_POST['name'] ?? '');
    //     $email = sanitize_email($_POST['email'] ?? '');
    //     $raw = wp_unslash($_POST['raw_summary'] ?? '');

    //     $opts = get_option($this->option_key);
    //     $from = $opts['from_email'] ?? get_option('admin_email');
    //     $sitename = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    //     $to = get_option('admin_email');
    //     $subject = sprintf(__('New submission from %s', 'fc-calculator'), $name);
    //     $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: ' . $sitename . ' <' . $from . '>'];
    //     wp_mail($to, $subject, $raw, $headers);

    //     global $wpdb;
    //     $wpdb->insert($this->table, [
    //         'name' => $name,
    //         'email' => $email,
    //         'data' => maybe_serialize(['raw' => $raw]),
    //         'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    //     ]);

    //     wp_send_json_success(['message' => __('Your results have been sent successfully.', 'fc-calculator')]);
    // }

    public function ajax_send() {

    // 🔐 Security Check
    check_ajax_referer('fc_calc_nonce', 'nonce');

    // 📥 Data from form
    $name   = sanitize_text_field($_POST['name'] ?? '');
    $email  = sanitize_email($_POST['email'] ?? '');

    $loan_amount     = floatval($_POST['loan_amount'] ?? 0);
    $term_years      = intval($_POST['term_years'] ?? 0);
    $monthly_payment = floatval($_POST['monthly_payment'] ?? 0);
    $total_price     = floatval($_POST['total_price'] ?? 0);

    if (empty($name) || empty($email)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    // ⚙ Plugin settings
    $opts       = get_option($this->option_key);
    $from       = $opts['from_email'] ?? get_option('admin_email');
    $admin_mail = get_option('admin_email');
    $site_url   = get_site_url();

    // 📧 Email basics
    $subject = 'OLP Finance – Loan Calculation Result';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: OLP Finance <' . $from . '>',
    ];

    /* ===============================
       ✉️ EMAIL TEMPLATE (HTML)
    =============================== */
    ob_start(); ?>
    
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="max-width:600px;margin:auto;background:#ffffff;padding:25px;border-radius:6px;">

            <!-- LOGO -->
            <div style="text-align:center;margin-bottom:20px;">
                <img src="https://blue3.genetechz.com/OLP-Finance/wp-content/uploads/2025/09/OLP-Finance-logo.svg" 
                     alt="OLP Finance" 
                     style="max-width:180px;">
            </div>

            <h2 style="text-align:center;color:#0a2c5d;">
                Rent Calculator Summary
            </h2>

            <p>Dear <strong><?php echo esc_html($name); ?></strong>,</p>
            <p>Below are your Rent calculation details:</p>

            <!-- DATA TABLE -->
            <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse;">
                <tr style="background:#f0f4f8;">
                    <td><strong>Net Lease Amount</strong></td>
                    <td>Rs <?php echo number_format($loan_amount, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Tenor</strong></td>
                    <td><?php echo esc_html($term_years); ?> Years</td>
                </tr>
                <tr style="background:#f0f4f8;">
                    <td><strong>Monthly Installment</strong></td>
                    <td>Rs <?php echo number_format($monthly_payment, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Total Payable Amount</strong></td>
                    <td>Rs <?php echo number_format($total_price, 2); ?></td>
                </tr>
            </table>

            <!-- FOOTER -->
            <div style="margin-top:30px;font-size:12px;color:#666;text-align:center;">
                <p>
                    Copyright © OLP Financial Services Pakistan Limited.  
                    All Rights Reserved.
                </p>
                <p>
                    <a href="<?php echo esc_url($site_url); ?>" style="color:#0a2c5d;text-decoration:none;">
                        OLP Financial Services Pakistan Limited
                    </a>
                </p>
            </div>

        </div>
    </div>

    <?php
    $message = ob_get_clean();

    /* ===============================
       📤 SEND EMAILS
    =============================== */

    // Admin Email
    wp_mail($admin_mail, $subject, $message, $headers);

    // User Email
    wp_mail($email, $subject, $message, $headers);

    /* ===============================
       🗄 SAVE LOG
    =============================== */
    global $wpdb;
    $wpdb->insert($this->table, [
        'name'  => $name,
        'email' => $email,
        'data'  => maybe_serialize([
            'loan_amount'     => $loan_amount,
            'tenor_years'     => $term_years,
            'monthly_payment' => $monthly_payment,
            'total_price'     => $total_price,
        ]),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    // ✅ Success Response
    wp_send_json_success([
        'message' => 'Email successfully sent to Admin and User.'
    ]);
}

}

FC_Updated_Plugin::instance();
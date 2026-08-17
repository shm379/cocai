<?php
/**
 * Plugin Name: GameCity to CoCAI SSO & CRM Bridge
 * Plugin URI: https://cocai.nabuxai.com/
 * Description: افزونه اتصال یکپارچه کاربران و CRM گیم سیتی به سامانه هوش مصنوعی CoCAI با همگام‌سازی تگ بازی، کیف پول و سطوح کاربری.
 * Version: 1.0.0
 * Author: GameCity & CoCAI Dev Team
 * Text Domain: gamecity-cocai-sso
 */

if (! defined('ABSPATH')) {
    exit;
}

class GameCity_CoCAI_SSO {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('init', [$this, 'handle_sso_endpoint']);
        add_shortcode('gamecity_cocai_sso_btn', [$this, 'render_sso_button']);
    }

    public function add_admin_menu() {
        add_options_page(
            'تنظیمات CoCAI SSO',
            'اتصال CoCAI گیم سیتی',
            'manage_options',
            'gamecity-cocai-sso',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting('gamecity_cocai_settings', 'cocai_app_url');
        register_setting('gamecity_cocai_settings', 'cocai_secret_key');
        register_setting('gamecity_cocai_settings', 'cocai_client_id');
    }

    public function render_admin_page() {
        ?>
        <div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif;">
            <h1>🛡️ تنظیمات اتصال یکپارچه گیم سیتی به CoCAI AI</h1>
            <p>این افزونه اطلاعات احراز هویت و داده‌های CRM کاربران را به سامانه هوش مصنوعی کلش منتقل می‌کند.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('gamecity_cocai_settings');
                do_settings_sections('gamecity_cocai_settings');
                $app_url = get_option('cocai_app_url', 'https://cocai.nabuxai.com');
                $secret_key = get_option('cocai_secret_key', 'gamecity_secret_key_2026');
                $client_id = get_option('cocai_client_id', 'gamecity_main');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">آدرس سامانه CoCAI:</th>
                        <td><input type="url" name="cocai_app_url" value="<?php echo esc_attr($app_url); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">کلید امنیتی اشتراکی (Secret Key):</th>
                        <td><input type="text" name="cocai_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">شناسه کلاینت (Client ID):</th>
                        <td><input type="text" name="cocai_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
            <hr />
            <h3>لینک مستقیم ورود به CoCAI از گیم سیتی:</h3>
            <code><?php echo esc_url(home_url('/?gamecity_sso_action=auth')); ?></code>
        </div>
        <?php
    }

    /**
     * مدیریت اندپوینت ورود یکپارچه SSO
     */
    public function handle_sso_endpoint() {
        if (isset($_GET['gamecity_sso_action']) && $_GET['gamecity_sso_action'] === 'auth') {
            if (! is_user_logged_in()) {
                // اگر لاگین نیست، ابتدا به فرم لاگین وردپرس بفرست
                auth_redirect();
                exit;
            }

            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;

            // دریافت اطلاعات تکمیلی ووکامرس و CRM
            $mobile = get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'digits_phone', true) ?: get_user_meta($user_id, 'mobile', true);
            $player_tag = get_user_meta($user_id, 'coc_player_tag', true) ?: get_user_meta($user_id, 'player_tag', true);
            $wallet = (int) get_user_meta($user_id, '_wallet_balance', true);
            $vip_level = get_user_meta($user_id, 'vip_tier', true) ?: (user_can($user_id, 'administrator') ? 'diamond' : 'vip');

            $payload = [
                'gamecity_id' => (string) $user_id,
                'email' => $current_user->user_email,
                'name' => $current_user->display_name,
                'mobile' => $mobile,
                'wallet_balance' => $wallet,
                'crm_tier' => $vip_level,
                'player_tag' => $player_tag,
                'timestamp' => time(),
            ];

            $token = base64_encode(json_encode($payload));
            $secret = get_option('cocai_secret_key', 'gamecity_secret_key_2026');
            $signature = hash_hmac('sha256', $token, $secret);

            $cocai_url = rtrim(get_option('cocai_app_url', 'https://cocai.nabuxai.com'), '/');
            $callback_url = $cocai_url . '/auth/gamecity/callback?' . http_build_query([
                'token' => $token,
                'signature' => $signature,
            ]);

            wp_redirect($callback_url);
            exit;
        }
    }

    public function render_sso_button($atts) {
        $url = esc_url(home_url('/?gamecity_sso_action=auth'));
        return '<a href="' . $url . '" style="display:inline-block;padding:10px 20px;background:#f59e0b;color:#000;font-weight:bold;border-radius:12px;text-decoration:none;">🤖 ورود به هوش مصنوعی CoCAI</a>';
    }
}

GameCity_CoCAI_SSO::get_instance();

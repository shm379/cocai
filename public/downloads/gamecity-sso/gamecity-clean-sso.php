<?php
/**
 * Plugin Name: GameCity to CoCAI Clean SSO
 * Description: لاگین سریع و ایمن کاربران وردپرس گیم سیتی به سامانه CoCAI با استفاده از WordPress REST API و توکن اختصاصی
 * Version: 1.1.0
 * Author: CoCAI Team
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    if (isset($_GET['gamecity_sso_action']) && $_GET['gamecity_sso_action'] === 'auth') {
        if (! is_user_logged_in()) {
            auth_redirect();
            exit;
        }

        $user = wp_get_current_user();
        $secret = defined('COCAI_SSO_SECRET') ? COCAI_SSO_SECRET : 'gamecity_secret_2026';

        // ساخت توکن ساده و امن با مشخصات استاندارد کاربر وردپرس
        $tokenPayload = [
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'time' => time(),
        ];

        $token = base64_encode(json_encode($tokenPayload));
        $signature = hash_hmac('sha256', $token, $secret);

        $cocaiUrl = defined('COCAI_APP_URL') ? COCAI_APP_URL : 'https://cocai.nabuxai.com';
        $redirectUrl = rtrim($cocaiUrl, '/') . '/auth/gamecity/callback?' . http_build_query([
            'token' => $token,
            'signature' => $signature,
        ]);

        wp_redirect($redirectUrl);
        exit;
    }
});

<?php
/**
 * Plugin Name: WooCommerce Cleanup Command
 * Description: Commande WP-CLI `wp clean woo` pour supprimer produits, commandes, clients, médias, taxonomies WooCommerce, avec backup.
 */

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('clean woo', function () {
        global $wpdb;

        // 🔐 Sauvegarde de la base
        $backup_dir = WP_CONTENT_DIR . '/backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $backup_file = "$backup_dir/backup_before_woo_cleanup_$timestamp.sql";

        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD;
        $db_host = DB_HOST;

        WP_CLI::log("💾 Sauvegarde de la base dans : $backup_file");

        $cmd = sprintf(
            'mysqldump -h%s -u%s %s %s > %s',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            $db_pass ? "-p" . escapeshellarg($db_pass) : "",
            escapeshellarg($db_name),
            escapeshellarg($backup_file)
        );

        system($cmd, $retval);
        if ($retval !== 0) {
            WP_CLI::warning("⚠️ La sauvegarde a échoué. Veuillez vérifier `mysqldump`.");
        } else {
            WP_CLI::success("✅ Sauvegarde réussie !");
        }

        // 📦 Plugins à désactiver
        $plugins_to_toggle = [
            'woocommerce/woocommerce.php',
            'wordpress-seo/wp-seo.php',
            'handsome_toaster/handsome_toaster.php',
            'wp-lister-ebay/wp-lister-ebay.php',
            'wt-smart-coupons-for-woocommerce/wt-smart-coupons-for-woocommerce.php',
        ];

        WP_CLI::log("🔧 Désactivation des plugins...");
        foreach ($plugins_to_toggle as $plugin) {
            if (is_plugin_active($plugin)) {
                deactivate_plugins($plugin, true);
                WP_CLI::log("➡️ Plugin désactivé : $plugin");
            }
        }

        // Suppression commandes
        WP_CLI::log("🧾 Suppression des commandes...");
        $orders = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('shop_order', 'shop_order_refund')");
        foreach ($orders as $id) wp_delete_post($id, true);

        // Suppression clients
        WP_CLI::log("👥 Suppression des clients...");
        $customers = get_users(['role' => 'customer']);
        foreach ($customers as $user) wp_delete_user($user->ID);

        // Suppression produits
        WP_CLI::log("🗑 Suppression des produits...");
        do {
            $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product', 'product_variation') LIMIT 200");
            foreach ($ids as $id) wp_delete_post($id, true);
            WP_CLI::log("➖ " . count($ids) . " produits supprimés...");
        } while (!empty($ids));

        // Suppression taxonomies
        WP_CLI::log("🧼 Suppression des taxonomies...");
        $taxonomies = get_taxonomies(['object_type' => ['product']], 'names');
        foreach ($taxonomies as $tax) {
            $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
            foreach ($terms as $term) wp_delete_term($term->term_id, $tax);
        }

        // Attributs personnalisés
        WP_CLI::log("🧽 Suppression des attributs...");
        $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");

        // Médias inutilisés
        WP_CLI::log("🖼 Suppression des médias inutilisés...");
        $media_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'");
        $keep_ids = array_merge(
            $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page')"),
            $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'")
        );
        $to_delete = array_diff($media_ids, array_unique($keep_ids));
        foreach ($to_delete as $id) wp_delete_attachment($id, true);

        // Métadonnées orphelines
        WP_CLI::log("🗃 Nettoyage des métadonnées orphelines...");
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");

        // Réactivation plugins
        WP_CLI::log("🔁 Réactivation des plugins...");
        foreach ($plugins_to_toggle as $plugin) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                activate_plugin($plugin, '', false, true);
                WP_CLI::log("✅ Plugin réactivé : $plugin");
            }
        }

        WP_CLI::success("🎉 Nettoyage WooCommerce complet terminé !");
    });
}

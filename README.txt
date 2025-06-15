=== WooCommerce Cleanup Command ===
Contributors: [votre_nom_ou_profil_github]
Tags: WooCommerce, wp-cli, nettoyage, réinitialisation, produits, commandes
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Commande WP-CLI pour nettoyer complètement une boutique WooCommerce locale : produits, commandes, clients, médias non utilisés, tout en sauvegardant la base.

== Description ==

Cette commande WP-CLI `wp clean woo` permet de nettoyer une boutique WooCommerce locale en supprimant :

- Tous les produits (`product`, `product_variation`)
- Toutes les commandes (`shop_order`, `shop_order_refund`)
- Tous les clients (utilisateurs avec le rôle `customer`)
- Toutes les taxonomies liées à WooCommerce
- Tous les attributs produits personnalisés
- Tous les médias non utilisés (sauf ceux utilisés dans des pages ou comme miniatures)
- Toutes les métadonnées orphelines

**Elle effectue également une sauvegarde complète de la base de données avant toute suppression.**

== Installation ==

1. Créez le dossier `mu-plugins` si nécessaire :
   `wp-content/mu-plugins/`

2. Placez le fichier `woo-clean-command.php` dans ce dossier.

== Utilisation ==

Depuis un terminal à la racine de WordPress :


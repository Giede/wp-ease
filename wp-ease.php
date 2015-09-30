<?php
/**
 * Plugin Name: WP-Ease // ixcellent
 * Plugin URI: http://ixcellent.de/
 * Description: Easing the work with WordPress
 * Version: 0.1
 * Author: Guido Brand
 * Author URI: http://ixcellent.de/
 * Text Domain: ixc-ease
 * Domain Path: /languages
 * Network: true
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

/**
 *	Copyright (C) 2012-2015 ixcellent (email: info@ixcellent.de)
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Remove unused header information and links
add_action('init', 'removeheadlinks');
function removeheadlinks() {
    remove_action( 'wp_head', 'rsd_link');
    remove_action( 'wp_head', 'wp_generator');
    remove_action( 'wp_head', 'index_rel_link');
    remove_action( 'wp_head', 'wlwmanifest_link');
    remove_action( 'wp_head', 'feed_links',  2 );
    remove_action( 'wp_head', 'feed_links_extra',  3 );
    remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
    remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
}

// Disable update emails
add_filter( 'auto_core_update_send_email', '__return_false' );

// Disable automatic updates
add_filter( 'automatic_updater_disabled', '__return_true' );

// Disable XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

//Disable phoning home some plugins like E.T.
//HINT: [BackWPup](https://marketpress.de/product/backwpup-pro/) WILL NOT WORK WITH THE FOLLOWING OPTION!!!!!!!!!
/*add_filter( 'pre_http_request', '__return_true', 100 );*/

// change mail name and mail from to your WordPress settings.
add_filter('wp_mail_from', 'new_mail_from');
add_filter('wp_mail_from_name', 'new_mail_from_name');

$email = get_bloginfo('admin_email');
$emailfrom = get_bloginfo('name') . ('description');

function new_mail_from($old) {
    return $email;
}

function new_mail_from_name($old) {
    return $emailfrom;
}

class WP_HTML_Compression {
    protected $compress_css = true;
    protected $compress_js = true;
    protected $info_comment = true;
    protected $remove_comments = true;

    protected $html;
    public function __construct($html) {
        if (!empty($html)) {
            $this->parseHTML($html);
        }
    }
    public function __toString() {
        return $this->html;
    }
    protected function bottomComment($raw, $compressed) {
        $raw = strlen($raw);
        $compressed = strlen($compressed);
        $savings = ($raw-$compressed) / $raw * 100;
        $savings = round($savings, 2);
    }
    protected function minifyHTML($html) {
        $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $overriding = false;
        $raw_tag = false;
        $html = '';
        foreach ($matches as $token) {
            $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
            $content = $token[0];
            if (is_null($tag)) {
                if ( !empty($token['script']) ) {
                    $strip = $this->compress_js;
                }
                else if ( !empty($token['style']) ) {
                    $strip = $this->compress_css;
                }
                else if ($content == '<!--wp-html-compression no compression-->') {
                    $overriding = !$overriding;
                    continue;
                }
                else if ($this->remove_comments) {
                    if (!$overriding && $raw_tag != 'textarea') {
                        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
                    }
                }
            }
            else {
                if ($tag == 'pre' || $tag == 'textarea') {
                    $raw_tag = $tag;
                }
                else if ($tag == '/pre' || $tag == '/textarea') {
                    $raw_tag = false;
                }
                else {
                    if ($raw_tag || $overriding) {
                        $strip = false;
                    }
                    else {
                        $strip = true;
                        $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
                        $content = str_replace(' />', '/>', $content);
                    }
                }
            }
            if ($strip) {
                $content = $this->removeWhiteSpace($content);
            }
            $html .= $content;
        }
        return $html;
    }
    public function parseHTML($html) {
        $this->html = $this->minifyHTML($html);
        if ($this->info_comment) {
            $this->html .= "\n" . $this->bottomComment($html, $this->html);
        }
    }
    protected function removeWhiteSpace($str) {
        $str = str_replace("\t", ' ', $str);
        $str = str_replace("\n",  '', $str);
        $str = str_replace("\r",  '', $str);
        while (stristr($str, '  ')) {
            $str = str_replace('  ', ' ', $str);
        }
        return $str;
    }
}
function wp_html_compression_finish($html) {
    return new WP_HTML_Compression($html);
}
function wp_html_compression_start() {
    ob_start('wp_html_compression_finish');
}
add_action('get_header', 'wp_html_compression_start');

/* USEFUL SHORTCODES*/

//Use antispambot and create shortcode to hide mail-adresses within content
function hide_email_shortcode( $atts , $content = null ) {
    if ( ! is_email( $content ) ) {
        return;
    }

    return '<a href="mailto:' . antispambot( $content ) . '">' . antispambot( $content ) . '</a>';
}
add_shortcode( 'email', 'hide_email_shortcode' );

?>
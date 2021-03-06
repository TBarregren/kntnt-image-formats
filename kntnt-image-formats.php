<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Kntnt Image Formats
 * Plugin URI:        https://www.kntnt.com/
 * Description:       Provides a set of image formats including 'thumbnail', 'medium', 'medium_large', and 'large'.
 * Version:           1.0.0
 * Author:            Thomas Barregren
 * Author URI:        https://www.kntnt.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Kntnt\Image_Formats;

defined( 'ABSPATH' ) && new Plugin;

class Plugin {

    private static $built_in_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large' ];

    private static function default_image_formats() {
        return [
            'extra_small' => [
                'name' => __( 'Extra small', 'kntnt-bb-child-theme' ),
                'width' => 180,
                'height' => 9999,
                'crop' => false,
            ],
            'thumbnail' => [
                'name' => __( 'Extra small (crop)', 'kntnt-bb-child-theme' ),
                'width' => 180,
                'height' => 180,
                'crop' => true,
            ],
            'small' => [
                'name' => __( 'Small', 'kntnt-bb-child-theme' ),
                'width' => 300,
                'height' => 9999,
                'crop' => false,
            ],
            'small_crop' => [
                'name' => __( 'Small (crop)', 'kntnt-bb-child-theme' ),
                'width' => 300,
                'height' => 200,
                'crop' => true,
            ],
            'medium' => [
                'name' => __( 'Medium', 'kntnt-bb-child-theme' ),
                'width' => 600,
                'height' => 9999,
                'crop' => false,
            ],
            'medium_large' => [
                'name' => __( 'Medium (crop)', 'kntnt-bb-child-theme' ),
                'width' => 600,
                'height' => 400,
                'crop' => true,
            ],
            'large' => [
                'name' => __( 'Large', 'kntnt-bb-child-theme' ),
                'width' => 1060,
                'height' => 9999,
                'crop' => false,
            ],
            'extra_large' => [
                'name' => __( 'Extra large', 'kntnt-bb-child-theme' ),
                'width' => 1920,
                'height' => 9999,
                'crop' => false,
            ],
            'small_banner' => [
                'name' => __( 'Small banner', 'kntnt-bb-child-theme' ),
                'width' => 1920,
                'height' => 300,
                'crop' => true,
            ],
            'medium_banner' => [
                'name' => __( 'Medium banner', 'kntnt-bb-child-theme' ),
                'width' => 1920,
                'height' => 600,
                'crop' => true,
            ],
            'large_banner' => [
                'name' => __( 'Large banner', 'kntnt-bb-child-theme' ),
                'width' => 1920,
                'height' => 1200,
                'crop' => true,
            ],
        ];
    }

    private $names = [];

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'run' ] );
    }

    public function run() {

        $this->setup_image_formats();

        add_filter( 'image_size_names_choose', [ $this, 'update_ui' ], 9999 );
        add_filter( 'image_resize_dimensions', [ $this, 'crop_with_bleed' ], 10, 6 );

        add_filter( 'all_admin_notices', [ $this, 'media_options' ], 10, 1 );
        add_action( 'in_admin_footer', 'ob_end_flush', 10, 0 );

    }

    public function media_options() {
        ob_start( function ( $content ) {
            // TODO: Output a table with the image sizes read only.
            $start = '<h2 class="title">' . __( 'Image sizes' ) . '</h2>';
            $stop = '<h2 class="title">';
            return preg_replace( "`$start.*(?=$stop)`s", '', $content, 1 );
        } );
    }

    public function setup_image_formats() {
        $default_image_formats = apply_filters( 'kntnt-image-formats', self::default_image_formats() );
        foreach ( $default_image_formats as $slug => $format ) {
            $this->set_image_size( $slug, $format['width'], $format['height'], $format['crop'], $format['name'] );
        }
    }

    public function update_ui( $sizes ) {

        // Remove all previously defined images sizes that is overridden by ImageSizeBuilder.
        $sizes = array_diff_key( $sizes, $this->names );

        // Remove all images sizes with an empty name.
        $names = array_filter( $this->names );

        // Return all image sizes defined by this class and the leftovers.
        return array_merge( $names, $sizes );

    }

    public function crop_with_bleed( $payload, $src_w, $src_h, $dst_w, $dst_h, $crop ) {

        if ( ! $crop ) {
            return null;
        }

        $scale_factor = max( $dst_w / $src_w, $dst_h / $src_h );

        $crop_w = round( $dst_w / $scale_factor );
        $crop_h = round( $dst_h / $scale_factor );

        $src_x = floor( ( $src_w - $crop_w ) / 2 );
        $src_y = floor( ( $src_h - $crop_h ) / 2 );

        return [ 0, 0, (int) $src_x, (int) $src_y, (int) $dst_w, (int) $dst_h, (int) $crop_w, (int) $crop_h ];

    }

    private function set_image_size( $slug, $width, $height, $crop, $name ) {

        // Store the name in order
        $this->names[ $slug ] = $name;

        // Update the image size
        add_image_size( $slug, $width, $height, $crop );

        // Update the options for the built-in image sizes
        if ( in_array( $slug, self::$built_in_sizes ) ) {
            update_option( $slug . '_size_w', $width );
            update_option( $slug . '_size_h', $height );
            if ( $slug == 'thumbnail' ) {
                update_option( $slug . '_size_crop', $crop );
            }
        }

    }

}

<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

// FOR GUTENBERG BLOCK EDITOR
// CODE BEING USING FROM BSF CUSTOM FONT PLUGIN.

function uaf_block_editor_custom_fonts(){
	// Clean up any previously written physical UAF fonts in theme.json to restore it to standard.
	if ( function_exists( 'wp_theme_has_theme_json' ) && wp_theme_has_theme_json() ) {
		$theme_json_file = get_stylesheet_directory() . '/theme.json';
		if ( file_exists( $theme_json_file ) ) {
			$theme_json_raw = json_decode( file_get_contents( $theme_json_file ), true );
			if ( isset( $theme_json_raw['settings']['typography']['fontFamilies'] ) ) {
				$original_count = count( $theme_json_raw['settings']['typography']['fontFamilies'] );
				$theme_json_raw['settings']['typography']['fontFamilies'] = array_filter(
					$theme_json_raw['settings']['typography']['fontFamilies'],
					function($font) {
						return !isset($font['isUAF']);
					}
				);
				$theme_json_raw['settings']['typography']['fontFamilies'] = array_values(
					$theme_json_raw['settings']['typography']['fontFamilies']
				);
				if ( count( $theme_json_raw['settings']['typography']['fontFamilies'] ) !== $original_count ) {
					$theme_json = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );
					file_put_contents( $theme_json_file, $theme_json_string );
				}
			}
		}
	}
}

function uaf_inject_fonts_in_theme_json( $theme_json ) {
	$fontsData = uaf_group_fontdata_by_fontname(uaf_get_uploaded_font_data());
	if ( empty($fontsData) ) {
		return $theme_json;
	}

	$uaf_upload_path = uaf_path_details();
	$uaf_upload_url  = $uaf_upload_path['url'];
	if (is_ssl()){
		$uaf_upload_url = preg_replace('#^https?:#', 'https:', $uaf_upload_path['url']);
	}

	$uaf_fonts = array();
	foreach ($fontsData as $fontName => $fontData) {
		$font_faces = array();
		foreach ($fontData as $fontVariationKey => $fontVariationData) {
			$font_faces[] = array(
				'fontFamily'  => $fontName,
				'fontStretch' => '',
				'fontStyle'   => isset($fontVariationData['font_style']) ? $fontVariationData['font_style'] : 'normal',
				'fontWeight'  => isset($fontVariationData['font_weight']) ? $fontVariationData['font_weight'] : '400',
				'src'         => esc_url($uaf_upload_url . $fontVariationData['font_path']) . '.woff2'
			);
		}

		$uaf_fonts[] = array(
			'fontFamily' => $fontName,
			'slug'       => $fontName,
			'fontFace'   => $font_faces,
			'isUAF'      => true,
		);
	}

	if ( ! empty( $uaf_fonts ) ) {
		$theme_json->update_with( array(
			'version'  => 2,
			'settings' => array(
				'typography' => array(
					'fontFamilies' => $uaf_fonts,
				),
			),
		) );
	}

	return $theme_json;
}
add_filter( 'wp_theme_json_data_theme', 'uaf_inject_fonts_in_theme_json' );
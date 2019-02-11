<?php
    function replace_token_url( $var ) {
        $out = $var;
        if( is_string( $var ) ) {
            $out = str_replace( "@RT_SITE_URL@", get_bloginfo( "wpurl" ), $var );
        }
        return $out;
    }

    function filter_token_url( $value, $oldvalue ) {
        if( is_array( $value ) ) {
            return multidimensionalArrayMap( "replace_token_url", $value );
        }
        else if( is_string( $value ) )
            return replace_token_url( $value );
        else
            return $value;
    }

    function multidimensionalArrayMap( $func, $arr ) {
        $newArr = array();
        foreach( $arr as $key => $value ) {
            $newArr[ $key ] = ( is_array( $value ) ? multidimensionalArrayMap( $func, $value ) : $func( $value ) );
        }
        return $newArr;
    }

    
        add_filter( 'pre_update_option_posts_per_page', 'filter_token_url', 10, 2 );

        update_option( 'posts_per_page', '5' );

        remove_filter( 'pre_update_option_posts_per_page', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_selection', 'filter_token_url', 10, 2 );

        update_option( 'selection', 'custom' );

        remove_filter( 'pre_update_option_selection', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_permalink_structure', 'filter_token_url', 10, 2 );

        update_option( 'permalink_structure', '/%postname%/' );

        remove_filter( 'pre_update_option_permalink_structure', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_blogdescription', 'filter_token_url', 10, 2 );

        update_option( 'blogdescription', 'Just another WordPress site' );

        remove_filter( 'pre_update_option_blogdescription', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_page_for_posts', 'filter_token_url', 10, 2 );

        update_option( 'page_for_posts', '128' );

        remove_filter( 'pre_update_option_page_for_posts', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_page_on_front', 'filter_token_url', 10, 2 );

        update_option( 'page_on_front', '126' );

        remove_filter( 'pre_update_option_page_on_front', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_show_on_front', 'filter_token_url', 10, 2 );

        update_option( 'show_on_front', 'page' );

        remove_filter( 'pre_update_option_show_on_front', 'filter_token_url', 10, 2 );

        add_filter( 'pre_update_option_sidebars_widgets', 'filter_token_url', 10, 2 );

        update_option( 'sidebars_widgets', array (
  'wp_inactive_widgets' => 
  array (
  ),
  'sidebar' => 
  array (
  ),
  'array_version' => 3,
) );

        remove_filter( 'pre_update_option_sidebars_widgets', 'filter_token_url', 10, 2 );

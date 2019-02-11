<?php
/**
 * Custom install for Gantry 5 RocketLauncher.
 */

// Version check for PHP <5.4.0.
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    wp_die( 'Gantry 5 requires PHP 5.4+. Your PHP version : ' . PHP_VERSION );
}

function wp_install_defaults() {
    global $wpdb, $wp_rewrite, $table_prefix;

    define( 'WP_LOAD_IMPORTERS', true );

    $theme_name = 'g5_helium';

    require_once __DIR__ . '/install/rockettheme/src/wordpress-importer.php';

    $sample_data = __DIR__ . '/install/rockettheme/sample/data.xml';
    $sample_widgets = __DIR__ . '/install/rockettheme/sample/data.php';
    $sample_sql = __DIR__ . '/install/rockettheme/sample/data.sql';

    // Remove KSES filtering so our sample content wouldn't get modified
    kses_remove_filters();

    // Import sample data.
    if( file_exists( $sample_data ) ) {
        $sample_data  = rt_replace_url( $sample_data );
        $importer = new WP_Import();
        $importer->import( $sample_data );
    }

    // Re-add all of the KSES filters
    kses_init_filters();

    // Activate all the needed plugins.
    activate_plugin( 'gantry5/gantry5.php' );
    activate_plugin( 'wp_rokbox/rokbox.php' );
    activate_plugin( 'wp_rokajaxsearch/rokajaxsearch.php' );

    // Change the theme.
    switch_theme( $theme_name );
    delete_option( 'theme_switched' );

    // Check if data.php file exists and replace the token with current site url
    if( file_exists( $sample_widgets ) ) {
        delete_option( 'sidebars_widgets' );
        $sample_widgets  = rt_replace_url( $sample_widgets );
        include_once( $sample_widgets );
    }

    if( file_exists( $sample_sql ) ) {
        $sample_sql  = rt_replace_url_sql( $sample_sql );
        rt_import_sql( $sample_sql );
    }

    $wp_rewrite->init();
    $wp_rewrite->flush_rules();
}

add_filter( 'kses_allowed_protocols', function( $protocols ) {
    $streams = [
        'gantry-cache',
        'gantry-themes',
        'gantry-theme',
        'gantry-assets',
        'gantry-media',
        'gantry-engines',
        'gantry-engine',
        'gantry-layouts',
        'gantry-particles',
        'gantry-blueprints',
        'gantry-config',
        'wp-includes',
        'wp-content',
    ];

    $protocols = array_merge( $protocols, $streams );
    return $protocols;
} );

/**
 * Change the tokens in the XML
 */
function rt_replace_url( $file ) {
    $xml = file_get_contents( $file );
    $xml = preg_replace( "/\@RT_SITE_URL\@/", get_bloginfo('wpurl'), $xml );

    $upload_path = trim( get_option( 'upload_path' ) );

    if( empty( $upload_path ) ) {
        $dir = WP_CONTENT_DIR . '/uploads';
    } else {
        $dir = $upload_path;
        if( 'wp-content/uploads' == $upload_path ) {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif ( 0 !== strpos( $dir, ABSPATH ) ) {
            // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
            $dir = path_join( ABSPATH, $dir );
        }
    }

    $temp   = tempnam( $dir, 'rt_import' );
    $handle = fopen( $temp, 'w' );
    fwrite( $handle, $xml );
    $file = $temp;
    fclose( $handle );

    return $file;
}

/**
 * Change the tokens in the SQL
 */
function rt_replace_url_sql( $file ) {
    $sql = file_get_contents( $file );
    $wp_url = get_bloginfo( 'wpurl' );
    $wp_url = str_replace( '/', '\\\/', $wp_url );
    $sql = preg_replace( "/\@RT_SITE_URL\@/", $wp_url, $sql );

    $upload_path = trim( get_option( 'upload_path' ) );

    if( empty( $upload_path ) ) {
        $dir = WP_CONTENT_DIR . '/uploads';
    } else {
        $dir = $upload_path;
        if( 'wp-content/uploads' == $upload_path ) {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif ( 0 !== strpos( $dir, ABSPATH ) ) {
            // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
            $dir = path_join( ABSPATH, $dir );
        }
    }

    $temp   = tempnam( $dir, 'rt_import_sql' );
    $handle = fopen( $temp, 'w' );
    fwrite( $handle, $sql );
    $file = $temp;
    fclose( $handle );

    return $file;
}

/**
 * Import the SQL file with proper prefix
 */
function rt_import_sql( $file ) {
    global $wpdb;

    $buffer  = file_get_contents( $file );
    $queries = rt_split_sql( $buffer );

    foreach ( $queries as $query ) {
        if( trim( $query ) != '' ) {
            $sql = rt_replace_prefix( $query, $wpdb->prefix );
            $wpdb->query( $sql );
        }
    }
}

/**
 * Split the SQL file
 */
function rt_split_sql( $sql ) {
    $start   = 0;
    $open    = false;
    $char    = '';
    $end     = strlen( $sql );
    $queries = array();

    for( $i = 0; $i < $end; $i++ ) {
        $current = substr( $sql, $i, 1 );
        if( ( $current == '"' || $current == '\'' ) ) {
            $n = 2;

            while( substr( $sql, $i - $n + 1, 1 ) == '\\' && $n < $i ) {
                $n++;
            }

            if( $n % 2 == 0 ) {
                if( $open ) {
                    if( $current == $char ) {
                        $open = false;
                        $char = '';
                    }
                } else {
                    $open = true;
                    $char = $current;
                }
            }
        }

        if( ( $current == ';' && !$open ) || $i == $end - 1 ) {
            $queries[] = substr( $sql, $start, ( $i - $start + 1 ) );
            $start     = $i + 1;
        }
    }

    return $queries;
}

/**
 * This function replaces a string identifier <var>$prefix</var> with the string held is the
 * <var>tablePrefix</var> class variable.
 *
 * @param   string  $sql         The SQL statement to prepare.
 * @param   string  $old_prefix  The common table prefix.
 *
 * @return  string  The processed SQL statement.
 *
 * @since   11.1
 */
function rt_replace_prefix( $sql, $new_prefix, $old_prefix = '#__' ) {
    // Initialize variables.
    $escaped   = false;
    $startPos  = 0;
    $quoteChar = '';
    $literal   = '';

    $sql = trim( $sql );
    $n   = strlen( $sql );

    while( $startPos < $n ) {
        $ip = strpos( $sql, $old_prefix, $startPos );
        if ( $ip === false ) {
            break;
        }

        $j = strpos( $sql, "'", $startPos );
        $k = strpos( $sql, '"', $startPos );
        if( ( $k !== false ) && ( ( $k < $j ) || ( $j === false ) ) ) {
            $quoteChar = '"';
            $j         = $k;
        } else {
            $quoteChar = "'";
        }

        if ( $j === false ) {
            $j = $n;
        }

        $literal .= str_replace( $old_prefix, $new_prefix, substr( $sql, $startPos, $j - $startPos ) );
        $startPos = $j;

        $j = $startPos + 1;

        if ( $j >= $n ) {
            break;
        }

        // quote comes first, find end of quote
        while( true ) {
            $k       = strpos( $sql, $quoteChar, $j );
            $escaped = false;
            if ($k === false ) {
                break;
            }
            $l = $k - 1;
            while( $l >= 0 && $sql{$l} == '\\' ) {
                $l--;
                $escaped = !$escaped;
            }
            if( $escaped ) {
                $j = $k + 1;
                continue;
            }
            break;
        }
        if( $k === false ) {
            // error in the query - no end quote; ignore it
            break;
        }
        $literal .= substr( $sql, $startPos, $k - $startPos + 1 );
        $startPos = $k + 1;
    }

    if( $startPos < $n ) {
        $literal .= substr( $sql, $startPos, $n - $startPos );
    }

    return $literal;
}

<?php

/**
 * Plugin Name: ImportPress
 * Plugin URI: https://importpress.com
 * Description: Import Google Docs into WordPress Without Copy & Paste
 * Version: 1.1.1
 * Author: ImportPress
 * Author URI: https://importpress.com/contact
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: importpress
 */

require_once( 'config.php' );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

register_activation_hook( __FILE__, 'importpress_activate' );
register_uninstall_hook( __FILE__, 'importpress_uninstall' );

add_action( 'admin_notices', 'importpress_admin_notices' );

add_action( 'admin_menu', 'importpress_settings_menu' );
add_filter( 'plugin_action_links', 'importpress_action_links', 10, 5 );

function importpress_activate() {
  set_transient( 'importpress_activate_notice', true, 5 );
}

function importpress_uninstall() {
  delete_option( 'importpress_api_key' );
  delete_option( 'importpress_connect_secret' );

  delete_option( 'importpress_last_import' );
  delete_option( 'importpress_last_connect' );
}

function importpress_admin_notices() {
	if ( get_transient( 'importpress_activate_notice' ) ) {
    $url = 'options-general.php?page=importpress_settings';
    $link = '<a href="' . $url . '">' . __( 'Settings' , 'importpress' ) . '</a>';
    
    echo '
      <div class="notice notice-warning">
        <p>Welcome! Don\'t forget to connect your WordPress site to ImportPress in '. $link .'</p>
      </div>
    ';

    delete_transient( 'importpress_activate_notice' );
  }
}

function importpress_settings_menu() {
  $page_title = 'ImportPress Settings';
  $menu_title = 'ImportPress';
  $capability = 'manage_options';
  $slug = 'importpress_settings';
  $callback = 'importpress_settings_content';

  add_options_page( $page_title, $menu_title, $capability, $slug, $callback );
}

function importpress_action_links( $actions, $plugin_file ) {
  static $plugin;

  if ( !isset( $plugin ) ) {
    $plugin = plugin_basename( __FILE__ );
  }

  if ( $plugin !== $plugin_file ) {
    return $actions;
  }

  $url = 'options-general.php?page=importpress_settings';
  $link = '<a href="' . $url . '">' . __( 'Settings' , 'importpress' ) . '</a>';

  $actions = array_merge( array( 'settings' => $link ), $actions );

  return $actions;
} 

function importpress_connect_url() {
  $params = array(
    'domain' => parse_url( get_site_url() )['host'],
    'siteUrl' => get_site_url(),
    'adminUrl' => rtrim( get_admin_url(), '/' ),
    'restUrl' => rtrim( get_rest_url(), '/' ),
    'secret' => get_option( 'importpress_connect_secret' ),
  );

  $query_string = http_build_query( $params );

  return IMPORTPRESS_APP_BASE_URL . IMPORTPRESS_APP_CONNECT_PATH . '?' . $query_string;
}

function importpress_settings_content() {
  $expiry = time() + IMPORTPRESS_CONNECT_SECRET_TIMEOUT;
  $secret = wp_generate_password( IMPORTPRESS_CONNECT_SECRET_LENGTH, false ) . ':' . $expiry;

  update_option( 'importpress_connect_secret', $secret );

  $account_url = IMPORTPRESS_APP_BASE_URL . IMPORTPRESS_APP_ACCOUNT_PATH;
  $team_url = IMPORTPRESS_APP_BASE_URL . IMPORTPRESS_APP_TEAM_PATH;
  $sites_url = IMPORTPRESS_APP_BASE_URL . IMPORTPRESS_APP_SITES_PATH;
  $imports_url = IMPORTPRESS_APP_BASE_URL . sprintf( IMPORTPRESS_APP_IMPORTS_PATH, get_option( 'importpress_site_id' ) );

  $datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

  $last_import_at = get_option( 'importpress_last_import', false );
  $last_import = $last_import_at ? date( $datetime_format, $last_import_at ) : 'Never';

  $last_connect = date( $datetime_format, get_option( 'importpress_last_connect' ) );

  $connected = (bool) get_option( 'importpress_api_key', false );

?>
    <div class="wrap">
      <?php if ( $connected ) { ?>
        <h1>ImportPress</h1>
      <?php } else { ?>
        <h1>Connect Your Site to ImportPress</h1>
      <?php } ?>

      <?php if ( !$connected ) { ?>
        <br/>

        <p>Almost there! Complete your ImportPress setup by connecting your WordPress site below.</p>
        <br/>

        <p><a class="button-primary" href="<?php echo importpress_connect_url(); ?>">Connect</a></p>
        <br/>

        <p>Once your site is connected, you can import Google Docs into WordPress posts and pages. If you don’t already have an ImportPress account, sign in using your Google Account and provide access to your Google Docs.</p>
      <?php } else { ?>
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row" style="color: #666">Status</th>
              <td>
                Connected&nbsp;&nbsp;&nbsp;&nbsp;
                <a class="button-primary" href="<?php echo importpress_connect_url(); ?>">Reconnect</a>
              </td>
            </tr>
            <tr>
              <th scope="row" style="color: #666">Last Import</th>
              <td><?php echo $last_import ?></td>
            </tr>
            <tr>
              <th scope="row" style="color: #666">Last Connect</th>
              <td><?php echo $last_connect ?></td>
            </tr>
          </tbody>
        </table>

        <br/>
        <h2>Actions</h2>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row" style="color: #666">View Imports</th>
              <td><a class="button" href="<?php echo $imports_url; ?>">Open</a></td>
            </tr>
            <tr>
              <th scope="row" style="color: #666">View Sites</th>
              <td><a class="button" href="<?php echo $sites_url; ?>">Open</a></td>
            </tr>
            <tr>
              <th scope="row" style="color: #666">View Account</th>
              <td><a class="button" href="<?php echo $account_url; ?>">Open</a></td>
            </tr>
            <tr>
              <th scope="row" style="color: #666">View Team</th>
              <td><a class="button" href="<?php echo $team_url; ?>">Open</a></td>
            </tr>
          </tbody>
        </table>
      <?php } ?>

        <br/>
        <p>If you have issues or need assistance at any time, please contact support at <a href="mailto:support@importpress.com">support@importpress.com</a> and we'd be pleased to asssit you.</p>
        <br/>
    </div>
  <?php
}

add_action( 'rest_api_init', function() {
  register_rest_route( 'importpress/v1', 'documents', [
    'methods'  => 'POST',
    'callback' => 'importpress_api_post_document',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'documents', [
    'methods'  => 'PUT',
    'callback' => 'importpress_api_put_document',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'images', [
    'methods'  => 'POST',
    'callback' => 'importpress_api_post_image',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'images/(?P<id>\d+)', [
    'methods'  => 'DELETE',
    'callback' => 'importpress_api_delete_image',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'authors', [
    'methods'  => 'GET',
    'callback' => 'importpress_api_get_authors',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'categories', [
    'methods'  => 'GET',
    'callback' => 'importpress_api_get_categories',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'ping', [
    'methods'  => 'GET',
    'callback' => 'importpress_api_get_ping',
    'permission_callback' => function( $request ) {
      return importpress_api_authorize( $request );
    },
  ] );

  register_rest_route( 'importpress/v1', 'connect', [
    'methods'  => 'POST',
    'callback' => 'importpress_api_post_connect',
    'permission_callback' => '__return_true',
  ] );
});

function importpress_api_authorize( $request ) {
  $token = $request->get_header( 'X-Ip-Authorization' );

  if ( $token !== get_option( 'importpress_api_key' ) ) {
    return false;
  }

  return true;
}

function importpress_api_post_document( $request ) {
  $params = $request->get_json_params();

  $type = $params['type'];
  $status = $params['status'];
  $author_id = $params['authorId'];
  $category_ids = $params['categoryIds'];
  $title = $params['title'];
  $content = $params['content'];
  $image_ids = $params['imageIds'];

  if ( !isset( $type ) || !isset( $status ) || !isset( $author_id ) || !isset( $category_ids ) || !isset( $title ) || !isset( $content ) || !isset( $image_ids ) ) {
    return new WP_Error( 400, 'Must include required params' );
  }

  wp_set_current_user( $author_id );

  $post = [
    'post_type' => $type,
    'post_title' => wp_strip_all_tags( $title ),
    'post_status' => $status,
    'post_author' => $author_id,
    'post_category' => $category_ids,
    'post_content' => $content,
  ];

  $post_id = wp_insert_post( $post, true );

  if ( is_wp_error( $post_id ) ) {
    return new WP_Error( 500, 'Could not insert post' );
  }

  foreach ( $image_ids as $image_id ) {
    $image = array(
      'ID' => $image_id,
      'post_parent' => $post_id
    );

    $error = wp_update_post( $image, true );

    if ( is_wp_error( $error ) ) {
      return new WP_Error( 500, 'Could not update post' );
    }
  }

  update_option( 'importpress_last_import', time() );

  return [
    'id' => $post_id,
  ];
}

function importpress_api_put_document( $request ) {
  $params = $request->get_json_params();

  $post_id = $params['postId'];
  $content = $params['content'];
  $image_ids = $params['imageIds'];

  if ( !isset( $post_id ) || !isset( $content ) || !isset( $image_ids ) ) {
    return new WP_Error( 400, 'Must include required params' );
  }

  $post = [
    'ID' => $post_id,
    'post_content' => $content,
  ];

  $error = wp_update_post( $post, true );

  if ( is_wp_error( $error ) ) {
    return new WP_Error( 500, 'Could not update post' );
  }

  foreach ( $image_ids as $image_id ) {
    $image = array(
      'ID' => $image_id,
      'post_parent' => $post_id
    );

    $error = wp_update_post( $image, true );

    if ( is_wp_error( $error ) ) {
      return new WP_Error( 500, 'Could not update image parent post' );
    }
  }

  update_option( 'importpress_last_import', time() );

  return true;
}

function importpress_api_post_image( $request ) {
  $params = $request->get_json_params();

  $author_id = $params['authorId'];
  $url = $params['url'];
  $crop = $params['crop'];
  $dimensions = $params['dimensions'];

  if ( !isset( $author_id ) || !isset( $url ) ) {
    return new WP_Error( 400, 'Must include required params' );
  }

  wp_set_current_user( $author_id );

  $image = wp_tempnam();

  $response = wp_safe_remote_get( $url, array(
    'stream' => true,
    'filename' => $image,
    'timeout' => IMPORTPRESS_IMAGE_DOWNLOAD_TIMEOUT,
  ) );

  if ( is_wp_error( $response ) ) {
    wp_delete_file( $image );
    return new WP_Error( 500, 'Could not download image' );
  }
   
  $response_code = wp_remote_retrieve_response_code( $response );
   
  if ( 200 != $response_code ) {
    return new WP_Error( 500, 'Could not download image' );
  }
  
  $content_disposition = wp_remote_retrieve_header( $response, 'content-disposition' );
  
  preg_match( '/filename="(.*)"/', $content_disposition, $matches );
  $dest_filename = $matches[1];
  
  if ( !isset( $dest_filename ) ) {
    return new WP_Error( 500, 'Could not get downloaded image file name' );
  }

  $edit = wp_get_image_editor( $image );

  if ( is_wp_error( $edit ) ) {
    wp_delete_file( $image );
    return new WP_Error( 500, 'Could not initialize image editor' );
  }

  $size = $edit->get_size();

  $width = $size['width'];
  $height = $size['height'];

  $r_width = $dimensions['width'];
  $r_height = $dimensions['height'];

  if ( !empty( $crop ) ) {
    $top = $height * ( isset( $crop['top'] ) ? $crop['top'] : 0 );
    $bottom = $height * ( isset( $crop['bottom'] ) ? $crop['bottom'] : 0 );
    $left = $width * ( isset( $crop['left'] ) ? $crop['left'] : 0 );
    $right = $width * ( isset( $crop['right'] ) ? $crop['right'] : 0 );

    $c_width = $width - $left - $right;
    $c_height = $height - $top - $bottom;

    $edit->crop( $left, $top, $c_width, $c_height );
  }

  if ($width !== $r_width || $height !== $r_height) {
    $edit->resize( $r_width, $r_height );
  }

  $edit->set_quality( 100 );

  $result = $edit->save( $image );
  wp_delete_file( $image );

  if ( is_wp_error( $result ) ) {
    return new WP_Error( 500, 'Could not save edited image' );
  }

  $dest_image = $result['path'];

  $file = [
    'name' => $dest_filename,
    'tmp_name' => $dest_image,
  ];

  $id = media_handle_sideload( $file, 0 );
  wp_delete_file( $dest_image );

  if ( is_wp_error( $id ) ) {
    return new WP_Error( 500, 'Could not sideload image' );
  }

  $url = wp_get_attachment_url( $id );

  return [
    'id' => $id,
    'url' => $url,
  ];
}

function importpress_api_delete_image( $request ) {
  $id = $request->get_param( 'id' );
  $result = wp_delete_attachment( $id );

  if ( empty($result) ) {
    return new WP_Error( 500, 'Could not delete image' );
  }

  return new WP_REST_Response( null, 201 );
}

function importpress_api_get_authors() {
  $users = get_users( 'who=authors' );
  $results = array();

  foreach( $users as $user ) {
    array_push( $results, array(
      'id' => $user->ID,
      'name' => $user->display_name,
    ) );
  }

  return $results;
}

function importpress_api_get_categories() {
  $categories = get_categories( array(
    'hide_empty' => false,
  ) );

  $results = array();

  foreach( $categories as $category ) {
    array_push( $results, array(
      'id' => $category->term_id,
      'name' => $category->name,
    ) );
  }

  return $results;
}

function importpress_api_get_ping() {
  return array(
    'pluginVersion' => IMPORTPRESS_VERSION,
    'wordpressVersion' => get_bloginfo( 'version' ),
    'phpVersion' => phpversion(),
  );
}

function importpress_api_post_connect( $request ) {
  $params = $request->get_json_params();

  $site_id = $params['siteId'];
  $secret = $params['secret'];
  $api_key = $request->get_header( 'X-Ip-Authorization' );

  if ( !isset( $site_id ) || !isset( $secret ) || !isset( $api_key ) ) {
    return new WP_Error( 400, 'Must include required params' );
  }

  if ( $secret !== get_option( 'importpress_connect_secret' ) ) {
    return new WP_Error( 401, 'Not Authorized' );
  }

  $expiry = (int) explode( ':', $secret )[1];

  if ( time() > $expiry ) {
    delete_option( 'importpress_connect_secret' );
    return new WP_Error( 403, 'Forbidden' );
  }

  update_option( 'importpress_site_id', $site_id );
  update_option( 'importpress_api_key', $api_key );
  update_option( 'importpress_last_connect', time() );

  delete_option( 'importpress_connect_secret' );

  return true;
}

?>

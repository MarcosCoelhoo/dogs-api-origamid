<?php 
  
  function photo_data($post) {
    $post_meta = get_post_meta($post->ID);
    $src = wp_get_attachment_image_src($post_meta['img'][0], 'large')[0];
    $user = get_userdata($post->post_author);
    $total_comments = get_comments_number($post->ID);

    return [
      'id' => $post->ID,
      'title' => $post->post_title,
      'date' => $post->post_date,
      'post_content' => $post->post_content,
      'src' => $src,
      'price' => $post_meta['price'][0],
      'acess' => $post_meta['acess'][0],
      'total_comments' =>  $total_comments,
    ];
      
  }

  // PUXAR APENAS UMA FOTO

  function api_photo_get($request) {
    $post_id = $request['id'];
    $post = get_post($post_id);

    if (!isset($post) || empty($post_id)) {
      $response = new WP_Error('error', 'Post não encontrado', ['status' => 404]);
      return rest_ensure_response($response);
    };
    
    $photo = photo_data($post);

    $photo['acess'] = (int) $photo['acess'] + 1;
    update_post_meta($post_id, 'acess', $photo['acess']);

    $comments = get_comments([
      'post_id' => $post_id,
      'order' => 'ASC',
    ]);

    $response = [
      'photo' => $photo,
      'comments' => $comments,
    ];

    return rest_ensure_response($response);
  };


  function register_api_photo_get() {
    register_rest_route('api', '/photo/(?P<id>[0-9]+)', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => 'api_photo_get',
    ]);
  };

  add_action('rest_api_init', 'register_api_photo_get');  

  // PUXAR TODAS AS FOTOS

  function api_photos_get($request) {
    $_total = sanitize_text_field($request['_total']) ?: 6;
    $_page = sanitize_text_field($request['_page']) ?: 1;
    $_user = sanitize_text_field($request['_user']) ?: 0;
    
    if (!is_numeric($_user)) {
      $user = get_user_by('login', $_user);

      if (!$user) {
        $response = new WP_Error('error', 'Usuário não encontrado', ['status' => 404]);
        return rest_ensure_response($response);
      }

      $_user = $user->ID;
    };

    $args = [
      'post_type' => 'post',
      'author' => $_user,
      'post_per_page' => $_total,
      'pages' => $_page,
    ];

    $query = new WP_Query($args);
    $posts = $query->posts;

    $photos = [];

    if ($posts) {
      foreach ($posts as $post) {
        $photos[] = photo_data($post);
      };
    };

    return rest_ensure_response($photos);
  };

  function register_api_photos_get() {
    register_rest_route('api', '/photo', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => 'api_photos_get',
    ]);
  };


  add_action('rest_api_init', 'register_api_photos_get');  

?>
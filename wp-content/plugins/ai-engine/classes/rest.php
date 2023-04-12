<?php

class Meow_MWAI_Rest
{
	private $core = null;
	private $namespace = 'ai-engine/v1';

	public function __construct( $core ) {
		$this->core = $core;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		try {
			register_rest_route( $this->namespace, '/update_option', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_update_option' )
			) );
			register_rest_route( $this->namespace, '/all_settings', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_all_settings' ),
			) );
			register_rest_route( $this->namespace, '/make_completions', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'make_completions' ),
			) );
			register_rest_route( $this->namespace, '/make_images', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'make_images' ),
			) );
			register_rest_route( $this->namespace, '/make_titles', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'make_titles' ),
			) );
			register_rest_route( $this->namespace, '/make_excerpts', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'make_excerpts' ),
			) );
			register_rest_route( $this->namespace, '/update_post_title', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'update_post_title' ),
			) );
			register_rest_route( $this->namespace, '/update_post_excerpt', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'update_post_excerpt' ),
			) );
			register_rest_route( $this->namespace, '/create_post', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'create_post' ),
			) );
			register_rest_route( $this->namespace, '/create_image', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'create_image' ),
			) );
			register_rest_route( $this->namespace, '/openai_files', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_files_get' ),
			) );
			register_rest_route( $this->namespace, '/openai_files', array(
				'methods' => 'DELETE',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_files_delete' ),
			) );
			register_rest_route( $this->namespace, '/openai_files', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_files_upload' ),
			) );
			register_rest_route( $this->namespace, '/openai_files_download', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_files_download' ),
			) );
			register_rest_route( $this->namespace, '/openai_files_finetune', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_files_finetune' ),
			) );
			register_rest_route( $this->namespace, '/openai_deleted_finetunes', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_deleted_finetunes_get' ),
			) );
			register_rest_route( $this->namespace, '/openai_finetunes', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_finetunes_get' ),
			) );
			register_rest_route( $this->namespace, '/openai_finetunes', array(
				'methods' => 'DELETE',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_finetunes_delete' ),
			) );
			register_rest_route( $this->namespace, '/openai_finetunes_cancel', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_finetunes_cancel' ),
			) );
			register_rest_route( $this->namespace, '/openai_incidents', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'openai_incidents' ),
			) );
			register_rest_route( $this->namespace, '/count_posts', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'count_posts' ),
			) );
			register_rest_route( $this->namespace, '/post_types', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'post_types' ),
			) );
			register_rest_route( $this->namespace, '/post_content', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'post_content' ),
			) );
			register_rest_route( $this->namespace, '/templates', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'templates_get' ),
			) );
			register_rest_route( $this->namespace, '/templates', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'templates_save' ),
			) );
			register_rest_route( $this->namespace, '/logs', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'get_logs' ),
			) );
			register_rest_route( $this->namespace, '/moderate', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'moderate' ),
			) );
			register_rest_route( $this->namespace, '/vectors', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'get_vectors' ),
			) );
			register_rest_route( $this->namespace, '/vector', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'add_vector' ),
			) );
			register_rest_route( $this->namespace, '/vectors_ref', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'get_vectors_ref' ),
			) );
			register_rest_route( $this->namespace, '/vector', array(
				'methods' => 'PUT',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'modify_vector' ),
			) );
			register_rest_route( $this->namespace, '/vectors', array(
				'methods' => 'DELETE',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'delete_vectors' ),
			) );
			register_rest_route( $this->namespace, '/transcribe', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'transcribe' ),
			) );
		}
		catch ( Exception $e ) {
			var_dump( $e );
		}
	}

	function rest_all_settings() {
		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->core->get_all_options()
		], 200 );
	}

	function rest_update_option( $request ) {
		try {
			$params = $request->get_json_params();
			$value = $params['options'];
			$options = $this->core->update_options( $value );
			$success = !!$options;
			$message = __( $success ? 'OK' : "Could not update options.", 'ai-engine' );
			return new WP_REST_Response([ 'success' => $success, 'message' => $message, 'options' => $options ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function createValidationResult( $result = true, $message = null) {
		$message = $message ? $message : __( 'OK', 'ai-engine' );
		return [ 'result' => $result, 'message' => $message ];
	}

	function validate_updated_option( $option_name ) {
		$option_checkbox = get_option( 'mwai_option_checkbox', false );
		$option_text = get_option( 'mwai_option_text', 'Default' );
		if ( $option_checkbox === '' )
			update_option( 'mwai_option_checkbox', false );
		if ( $option_text === '' )
			update_option( 'mwai_option_text', 'Default' );
		return $this->createValidationResult();
	}

	function make_completions( $request ) {
		try {
			$params = $request->get_json_params();
			$prompt = $params['prompt'];
			$query = new Meow_MWAI_QueryText( $prompt );
			$query->injectParams( $params );
			$answer = $this->core->ai->run( $query );
			return new WP_REST_Response([ 'success' => true, 'data' => $answer->result, 'usage' => $answer->usage ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function make_images( $request ) {
		try {
			$params = $request->get_json_params();
			$prompt = $params['prompt'];
			$query = new Meow_MWAI_QueryImage( $prompt );
			$query->injectParams( $params );
			$answer = $this->core->ai->run( $query );
			return new WP_REST_Response([ 'success' => true, 'data' => $answer->results, 'usage' => $answer->usage ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function make_titles( $request ) {
		try {
			$params = $request->get_json_params();
			$postId = intval( $params['postId'] );
			$text = $this->core->getCleanPostContent( $postId );
			if ( empty( $text ) ) {
				return new WP_REST_Response([ 'success' => false, 'message' => "No text found for this post." ], 500 );
			}
			$language = $this->core->get_post_language( $postId );
			$prompt = "Using the same original language ($language), create a short but SEO-friendly title for this text: " . $text;
			$query = new Meow_MWAI_QueryText( $prompt, 128 );
			$query->setMaxResults( 5 );
			$query->setEnv( 'admin-tools' );
			$answer = $this->core->ai->run( $query );
			return new WP_REST_Response([ 'success' => true, 'data' => $answer->results ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function make_excerpts( $request ) {
		try {
			$params = $request->get_json_params();
			$postId = intval( $params['postId'] );
			$text = $this->core->getCleanPostContent( $postId );
			if ( empty( $text ) ) {
				return new WP_REST_Response([ 'success' => false, 'message' => "No text found for this post." ], 500 );
			}
			$language = $this->core->get_post_language( $postId );
			$prompt = "Using the same original language ($language), create a SEO-friendly introduction to this text, 120 to 170 characters max, no URLs: " . $text;
			$query = new Meow_MWAI_QueryText( $prompt, 512 );
			$query->setMaxResults( 5 );
			$query->setEnv( 'admin-tools' );
			$answer = $this->core->ai->run( $query );
			return new WP_REST_Response([ 'success' => true, 'data' => $answer->results ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function update_post_title( $request ) {
		try {
			$params = $request->get_json_params();
			$title = sanitize_text_field( $params['title'] );
			$postId = intval( $params['postId'] );
			$post = get_post( $postId );
			if ( !$post ) {
				throw new Exception( 'There is no post with this ID.' );
			}
			$post->post_title = $title;
			//$post->post_name = sanitize_title( $title );
			wp_update_post( $post );
			return new WP_REST_Response([ 'success' => true, 'message' => "Title updated." ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function update_post_excerpt( $request ) {
		try {
			$params = $request->get_json_params();
			$excerpt = sanitize_text_field( $params['excerpt'] );
			$postId = intval( $params['postId'] );
			$post = get_post( $postId );
			if ( !$post ) {
				throw new Exception( 'There is no post with this ID.' );
			}
			$post->post_excerpt = $excerpt;
			wp_update_post( $post );
			return new WP_REST_Response([ 'success' => true, 'message' => "Excerpt updated." ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function create_post( $request ) {
		try {
			$params = $request->get_json_params();
			$title = sanitize_text_field( $params['title'] );
			$content = sanitize_textarea_field( $params['content'] );
			$excerpt = sanitize_text_field( $params['excerpt'] );
			$postType = sanitize_text_field( $params['postType'] );
			$post = new stdClass();
			$post->post_title = $title;
			$post->post_excerpt = $excerpt;
			$post->post_content = $content;
			$post->post_status = 'draft';
			$post->post_type = isset( $postType ) ? $postType : 'post';
			$post->post_content = $this->core->markdown_to_html( $post->post_content );
			$postId = wp_insert_post( $post );
			return new WP_REST_Response([ 'success' => true, 'postId' => $postId ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function image_download( $url ) {
		$response = wp_remote_get( $url );
		$output = wp_remote_retrieve_body( $response );
		return $output;
	}

	function create_image( $request ) {
		try {
			$params = $request->get_json_params();
			$title = sanitize_text_field( $params['title'] );
			$caption = sanitize_text_field( $params['caption'] );
			$alt = sanitize_text_field( $params['alt'] );
			$description = sanitize_text_field( $params['description'] );
			$url = $params['url'];
			$filename = sanitize_text_field( $params['filename'] );
			$image_data = $this->image_download( $url );
			if ( !$image_data ) {
				throw new Exception( 'Could not download the image.' );
			}
			$upload_dir = wp_upload_dir();
			if ( empty( $filename ) ) {
				$filename = basename( $url );
			}
			$wp_filetype = wp_check_filetype( $filename );
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			}
			else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}
			
			// Make sure the file is unique, if not, add a number to the end of the file before the extension
			$i = 1;
			$parts = pathinfo( $file );
			while ( file_exists( $file ) ) {
				$file = $parts['dirname'] . '/' . $parts['filename'] . '-' . $i . '.' . $parts['extension'];
				$i++;
			}

			// Write the file
			file_put_contents( $file, $image_data );
			$attachment = [
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => $title,
				'post_content' => $description,
				'post_excerpt' => $caption,
				'post_status' => 'inherit'
			];
			// Register the file as a Media Library attachment
			$attachmentId = wp_insert_attachment( $attachment, $file );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attachment_data = wp_generate_attachment_metadata( $attachmentId, $file );
			wp_update_attachment_metadata( $attachmentId, $attachment_data );
			update_post_meta( $attachmentId, '_wp_attachment_image_alt', $alt );
			return new WP_REST_Response([ 'success' => true, 'attachmentId' => $attachmentId ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_files_get() {
		try {
			//$params = $request->get_json_params();
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$files = $openai->listFiles();
			return new WP_REST_Response([ 'success' => true, 'files' => $files ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_deleted_finetunes_get() {
		try {
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$finetunes = $openai->listDeletedFineTunes();
			return new WP_REST_Response([ 'success' => true, 'finetunes' => $finetunes ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_finetunes_get() {
		try {
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$finetunes = $openai->listFineTunes();
			return new WP_REST_Response([ 'success' => true, 'finetunes' => $finetunes ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_files_upload( $request ) {
		try {
			$params = $request->get_json_params();
			$filename = sanitize_text_field( $params['filename'] );
			$data = $params['data'];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$file = $openai->uploadFile( $filename, $data );
			return new WP_REST_Response([ 'success' => true, 'file' => $file ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_files_delete( $request ) {
		try {
			$params = $request->get_json_params();
			$fileId = $params['fileId'];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$openai->deleteFile( $fileId );
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_finetunes_cancel( $request ) {
		try {
			$params = $request->get_json_params();
			$finetuneId = $params['finetuneId'];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$openai->cancelFineTune( $finetuneId );
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_finetunes_delete( $request ) {
		try {
			$params = $request->get_json_params();
			$modelId = $params['modelId'];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$openai->deleteFineTune( $modelId );
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_files_download( $request ) {
		try {
			$params = $request->get_json_params();
			$fileId = $params['fileId'];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$data = $openai->downloadFile( $fileId );
			return new WP_REST_Response([ 'success' => true, 'data' => $data ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_files_finetune( $request ) {
		try {
			$params = $request->get_json_params();
			$fileId = $params['fileId'];
			$model = $params['model'];
			$suffix = $params['suffix'];
			$hyperparams = [
				"nEpochs" => $params['nEpochs'],
				"batchSize" => $params['batchSize']
			];
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$finetune = $openai->fineTuneFile( $fileId, $model, $suffix, $hyperparams );
			return new WP_REST_Response([ 'success' => true, 'finetune' => $finetune ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function openai_incidents() {
		try {
			$transient = get_transient( 'mwai_openai_incidents' );
			if ( $transient ) {
				return new WP_REST_Response([ 'success' => true, 'incidents' => $transient ], 200 );
			}
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$incidents = $openai->getIncidents();
			set_transient( 'mwai_openai_incidents', $incidents, 60 * 10 );
			return new WP_REST_Response([ 'success' => true, 'incidents' => $incidents ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function count_posts( $request ) {
		try {
			$params = $request->get_query_params();
			$postType = $params['postType'];
			$count = wp_count_posts( $postType );
			return new WP_REST_Response([ 'success' => true, 'count' => $count ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function post_content( $request ) {
		try {
			$params = $request->get_query_params();
			$offset = (int)$params['offset'];
			$postType = $params['postType'];
			$postId = (int)$params['postId'];
			$post = null;
			if ( !empty( $postId ) ) {
				$post = get_post( $postId );
				if ( $post->post_status !== 'publish' && $post->post_status !== 'future' && $post->post_status !== 'draft' ) {
					$post = null;
				}
			}
			else {
				$posts = get_posts( [
					'posts_per_page' => 1,
					'post_type' => $postType,
					'offset' => $offset,
					'post_status' => 'publish'
				] );
				$post = count( $posts ) === 0 ? null : $posts[0];
			}
			if ( !$post ) {
				return new WP_REST_Response([ 'success' => false, 'message' => 'Post not found' ], 404 );
			}
			$cleanPost = $this->core->getCleanPost( $post );
			return new WP_REST_Response([ 'success' => true, 'content' => $cleanPost['content'],
				'checksum' => $cleanPost['checksum'], 'language' => $cleanPost['language'], 'excerpt' => $cleanPost['excerpt'],
				'postId' => $cleanPost['postId'], 'title' => $cleanPost['title'], 'url' => $cleanPost['url'] ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function templates_get( $request ) {
		try {
			$params = $request->get_query_params();
			$category = $params['category'];
			$templates = [];
			$templates_option = get_option( 'mwai_templates', [] );
			if ( !is_array( $templates_option ) ) {
				update_option( 'mwai_templates', [] );
			}
			$categories = array_column( $templates_option, 'category' );
			$index = array_search( $category, $categories );
			$templates = [];
			if ( $index !== false ) {
				$templates = $templates_option[$index]['templates'];
			}
			return new WP_REST_Response([ 'success' => true, 'templates' => $templates ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function templates_save( $request ) {
		try {
			$params = $request->get_json_params();
			$category = $params['category'];
			$templates = $params['templates'];
			$templates_option = get_option( 'mwai_templates', [] );
			$categories = array_column( $templates_option, 'category' );
			$index = array_search( $category, $categories );
			if ( $index !== false && $index >= 0 ) {
				$templates_option[$index]['templates'] = $templates;
			}
			else {
				$group = [ 'category' => $category, 'templates' => $templates ];
				$templates_option[] = $group;
			}

			update_option( 'mwai_templates', $templates_option );
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function get_logs( $request ) {
		try {
			$params = $request->get_json_params();
			$offset = $params['offset'];
			$limit = $params['limit'];
			$filters = $params['filters'];
			$sort = $params['sort'];
			$logs = apply_filters( 'mwai_stats_logs', [], $offset, $limit, $filters, $sort );
			return new WP_REST_Response([ 'success' => true, 'total' => $logs['total'], 'logs' => $logs['rows'] ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function moderate( $request ) {
		try {
			$params = $request->get_json_params();
			$text = $params['text'];
			if ( !$text ) {
				return new WP_REST_Response([ 'success' => false, 'message' => 'Text not found.' ], 404 );
			}
			$openai = new Meow_MWAI_OpenAI( $this->core );
			$results = $openai->moderate( $text );
			return new WP_REST_Response([ 'success' => true, 'results' => $results ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}

	}

	function get_vectors( $request ) {
		try {
			$params = $request->get_json_params();
			$offset = $params['offset'];
			$limit = $params['limit'];
			$filters = $params['filters'];
			$sort = $params['sort'];
			$vectors = apply_filters( 'mwai_embeddings_vectors', [], $offset, $limit, $filters, $sort );
			return new WP_REST_Response([ 'success' => true, 'total' => $vectors['total'], 'vectors' => $vectors['rows'] ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function add_vector( $request ) {
		try {
			$params = $request->get_json_params();
			$vector = $params['vector'];
			$success = apply_filters( 'mwai_embeddings_vectors_add', false, $vector );
			return new WP_REST_Response([ 'success' => $success, 'vector' => $vector ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function get_vectors_ref( $request ) {
		try {
			$params = $request->get_json_params();
			$refId = $params['refId'];
			$vectors = apply_filters( 'mwai_embeddings_vectors_ref', false, $refId );
			return new WP_REST_Response([ 'success' => true, 'vectors' => $vectors ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function modify_vector( $request ) {
		try {
			$params = $request->get_json_params();
			$vector = $params['vector'];
			$success = apply_filters( 'mwai_embeddings_vectors_update', false, $vector );
			return new WP_REST_Response([ 'success' => $success, 'vector' => $vector ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function delete_vectors( $request ) {
		try {
			$params = $request->get_json_params();
			$ids = $params['ids'];
			$success = apply_filters( 'mwai_embeddings_vectors_delete', false, $ids );
			return new WP_REST_Response([ 'success' => $success ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}
	
	function transcribe( $request ) {
		try {
			$params = $request->get_json_params();
			$query = new Meow_MWAI_QueryTranscribe();
			$query->injectParams( $params );
			$query->setEnv('admin-tools');
			$answer = $this->core->ai->run( $query );
			return new WP_REST_Response([ 'success' => true, 'data' => $answer->result ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function post_types() {
		try {
			$postTypes = $this->core->getPostTypes();
			return new WP_REST_Response([ 'success' => true, 'postTypes' => $postTypes ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}
}

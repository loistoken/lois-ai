<?php

class MeowPro_MWAI_ContentAware {
  private $core = null;

  function __construct( $core ) {
    $this->core = $core;
    add_filter( 'mwai_chatbot_params', array( $this, 'chatbot_params' ) );
  }

  function chatbot_params( $params ) {
    if ( !isset( $params['content_aware'] ) ) {
      return $params;
    }
    $content = '';
    $post = get_post();
    if ( !empty( $post ) ) {
      $resolved_content = $post->post_content;
      if ( $this->core->get_option( 'resolve_shortcodes' ) ) {
        try {
          $resolved_content = preg_replace( '/\[mwai.*?\]/', '', $resolved_content );
          $resolved_content = @do_shortcode( $resolved_content );
        }
        catch ( Exception $e ) {
          error_log( $e->getMessage() );
        }
      }
      $postContent = $this->core->cleanText( $resolved_content );
      $content .= $postContent;
    }
    // If WooCommerce, get the Product Description
    if ( class_exists( 'WooCommerce' ) ) {
      if ( is_product() ) {
        global $product;
        $shortDescription = $this->core->cleanText( $product->get_short_description() );
        if ( !empty( $shortDescription ) ) {
          $content .= $shortDescription;
        }
      }
    }
    $content = $this->core->cleanSentences( $content, 1024 );
    $content = apply_filters( 'mwai_contentaware_content', $content, $post );
    $params['context'] = str_replace( '{CONTENT}', $content, $params['context'] );
    return $params;
  }
}

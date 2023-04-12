<?php

const MWAI_DEFAULT_NAMESPACE = 'mwai';

class MeowPro_MWAI_Embeddings {
  private $core = null;
  private $wpdb = null;
  private $db_check = false;
  private $table_vectors = null;
  private $namespace = 'ai-engine/v1/pinecone/';
  private $pcApiKey = null;
  private $pcServer = null;
  private $pcIndex = null;
  private $pcHost = null;
  private $pcNamespace = null;
  private $options = [];

  function __construct() {
    global $wpdb, $mwai_core;
    $this->core = $mwai_core;
    $this->wpdb = $wpdb;
    $this->options = $this->core->get_option( 'pinecone' );
    $this->pcServer = $this->options['server'];
    $this->pcApiKey = $this->options['apikey'];
    $this->pcIndex = $this->options['index'];
    $this->pcNamespace = isset( $this->options['namespace'] ) ? $this->options['namespace'] : MWAI_DEFAULT_NAMESPACE;
    $this->pcHost = $this->pinecone_get_host( $this->pcIndex );
    $this->table_vectors = $wpdb->prefix . 'mwai_vectors';
    add_filter( "mwai_context_search", array( $this, 'context_search' ), 10, 2 );
    add_filter( "mwai_embeddings_vectors", array( $this, 'vectors_query' ), 10, 5 );
    add_filter( "mwai_embeddings_vectors_add", array( $this, 'vectors_add' ), 10, 2 );
    add_filter( "mwai_embeddings_vectors_ref", array( $this, 'vectors_get_ref' ), 10, 2 );
    add_filter( "mwai_embeddings_vectors_update", array( $this, 'vectors_update' ), 10, 2 );
    add_filter( "mwai_embeddings_vectors_delete", array( $this, 'vectors_delete' ), 10, 2 );
    add_filter( "mwai_embeddings_vectors_search", array( $this, 'vectors_search' ), 10, 4 );
    add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
  }

  function rest_api_init() {
		try {
      register_rest_route( $this->namespace, '/add_index', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'pinecone_add_index' )
			));
      register_rest_route( $this->namespace, '/delete_index', array(
        'methods' => 'DELETE',
        'permission_callback' => array( $this->core, 'can_access_settings' ),
        'callback' => array( $this, 'pinecone_delete_index' )
      ));
			register_rest_route( $this->namespace, '/list_indexes', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'pinecone_list_indexes' )
			));
    }
    catch ( Exception $e ) {
      var_dump( $e );
    }
  }

  function pinecone_get_host( $indexName ) {
    $host = null;
    if ( !empty( $this->options['indexes'] ) ) {
      foreach ( $this->options['indexes'] as $i ) {
        if ( $i['name'] === $indexName ) {
          $host = $i['host'];
          break;
        }
      }
    }
    return $host;
  }

  function run( $method, $url, $query = null, $json = true, $isAbsoluteUrl = false )
  {
    $headers = "accept: application/json, charset=utf-8\r\ncontent-type: application/json\r\n" . 
      "Api-Key: " . $this->pcApiKey . "\r\n";
    $body = $query ? json_encode( $query ) : null;
    $url = $isAbsoluteUrl ? $url : "https://controller." . $this->pcServer . ".pinecone.io" . $url;
    $options = [
      "headers" => $headers,
      "method" => $method,
      "timeout" => 120,
      "body" => $body,
      "sslverify" => false
    ];

    try {
      $response = wp_remote_request( $url, $options );
      if ( is_wp_error( $response ) ) {
        throw new Exception( $response->get_error_message() );
      }
      $response = wp_remote_retrieve_body( $response );
      $data = $response === "" ? true : ( $json ? json_decode( $response, true ) : $response );
      if ( !is_array( $data ) && empty( $data ) && is_string( $response ) ) {
        throw new Exception( $response );
      }
      return $data;
    }
    catch ( Exception $e ) {
      error_log( $e->getMessage() );
      throw new Exception( 'Error while calling PineCone: ' . $e->getMessage() );
    }
    return [];
  }

  function context_search( $query, $embeddingsIndex = null ) {
    $queryEmbed = new Meow_MWAI_QueryEmbed( $query );
    $answer = $this->core->ai->run( $queryEmbed );
    if ( empty( $answer->result ) ) {
      return null;
    }    
    $embeds = apply_filters( 'mwai_embeddings_vectors_search', [], $answer->result, $embeddingsIndex );
    if ( empty( $embeds ) ) {
      return null;
    }
    $bestEmbed = $embeds[0];
    if ( $bestEmbed['score'] < 0.75 ) {
      return null;
    }
    $data = $this->vector_get( $bestEmbed['id'] );
    if ( !empty( $data ) ) {
      $data['score'] = $bestEmbed['score'];
    }
    return $data;
  }

  #region Pinecone 
  function pinecone_add_index( $request ) {
    try {
      $params = $request->get_json_params();
      $name = $params['name'];
      $podType = $params['podType'];
      $dimension = 1536;
      $metric = 'cosine';
      $index = $this->run( 'POST', '/databases', [
        'name' => $name,
        'metric' => $metric,
        'dimension' => $dimension,
        'pod_type' => "{$podType}.x1"
      ], true );
      if ( !empty( $index ) ) {
        return $this->pinecone_list_indexes();
      }
    }
    catch ( Exception $e ) {
      return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 200 );
    }
  }

  function pinecone_delete_index( $request ) {
    try {
      $params = $request->get_json_params();
      $name = $params['name'];
      $index = $this->run( 'DELETE', "/databases/{$name}", null, true );
      if ( !empty( $index ) ) {
        $this->vectors_delete_all( false, $name, false );
        return $this->pinecone_list_indexes();
      }
    }
    catch ( Exception $e ) {
      return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 200 );
    }
  }

  function pinecone_list_indexes() {
    try {
      $indexesIds = $this->run( 'GET', '/databases', null, true );
      $indexes = [];
      foreach ( $indexesIds as $indexId ) {
        $index = $this->run( 'GET', "/databases/{$indexId}", null, true );
        $indexes[] = [
          'name' => $index['database']['name'],
          'metric' => $index['database']['metric'],
          'dimension' => $index['database']['dimension'],
          'host' => $index['status']['host'],
          'ready' => $index['status']['ready']
        ];
      }
      return new WP_REST_Response([ 'success' => true, 'indexes' => $indexes ], 200 );
    }
    catch ( Exception $e ) {
      return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 200 );
    }
  }

  function pinecode_delete( $ids, $deleteAll = false, $namespace = null ) {
    if ( empty( $namespace ) ) {
      $namespace = $this->pcNamespace;
    }
    $res = $this->run( 'POST', "https://{$this->pcHost}/vectors/delete", [
      'ids' => $deleteAll ? null : $ids,
      'deleteAll' => $deleteAll,
      'namespace' => $namespace
    ], true, true );
    return $res;
  }

  function pinecone_upsert( $vector, $namespace = null ) {
    if ( empty( $namespace ) ) {
      $namespace = $this->pcNamespace;
    }
    $res = $this->run( 'POST', "https://{$this->pcHost}/vectors/upsert", [
      'vectors' => [
        'id' => (string)$vector['id'],
        'values' => $vector['embedding'],
        'metadata' => [
          'type' => $vector['type'],
          'title' => $vector['title']
        ]
      ],
      'namespace' => $namespace
    ], true, true );
    return isset( $res['upsertedCount'] ) && $res['upsertedCount'] > 0;
  }

  function pinecone_search( $vector, $indexName = null, $namespace = null ) {
    if ( empty( $namespace ) ) {
      $namespace = $this->pcNamespace;
    }
    $indexName = !empty( $indexName ) ? $indexName : $this->pcIndex;
    $host = $this->pinecone_get_host( $indexName );
    $res = $this->run( 'POST', "https://{$host}/query", [
      'topK' => 10,
      'vector' => $vector,
      'namespace' => $namespace
    ], true, true );
    return isset( $res['matches'] ) ? $res['matches'] : [];
  }
  #endregion

  #region Vectors DB Queries

  function vectors_search( $vectors, $queryVectors, $indexName = null, $namespace = null ) {
    if ( empty( $namespace ) ) {
      $namespace = $this->pcNamespace;
    }
    if ( !empty( $vectors ) ) { return $vectors; }
    if ( !$this->check_db() ) { return false; }
    $res = $this->pinecone_search( $queryVectors, $indexName, $namespace );
    return $res;
  }

  function vectors_delete( $success, $ids ) {
    if ( $success ) { return $success; }
    if ( !$this->check_db() ) { return false; }
    $this->pinecode_delete( $ids, false );
    foreach ( $ids as $id ) {
      $this->wpdb->delete( $this->table_vectors, [ 'id' => $id ], array( '%d' ) );
    }
    return true;
  }

  function vectors_delete_all( $success, $index, $syncPineCone = true ) {
    if ( $success ) { return $success; }
    if ( !$this->check_db() ) { return false; }
    if ( $syncPineCone ) { $this->pinecode_delete( null, true ); }
    $this->wpdb->delete( $this->table_vectors, [ 'dbIndex' => $index ], array( '%s' ) );
    return true;
  }

  function vectors_add( $success, $vector = [] ) {
    if ( $success ) { return $success; }
    if ( !$this->check_db() ) { return false; }
    $success = $this->wpdb->insert( $this->table_vectors, 
      [
        'type' => $vector['type'],
        'title' => $vector['title'],
        'content' => $vector['content'],
        'refId' => !empty( $vector['refId'] ) ? $vector['refId'] : null,
        'refChecksum' => !empty( $vector['refChecksum'] ) ? $vector['refChecksum'] : null,
        'dbIndex' => !empty( $vector['dbIndex'] ) ? $vector['dbIndex'] : $this->pcIndex,
        'dbNS' => $this->pcNamespace,
        'status' => "processing"
      ],
      array( '%s', '%s', '%s', '%s', '%s' )
    );

    if ( $success ) {
      $vector['id'] = $this->wpdb->insert_id;
      // Create embedding
      $queryEmbed = new Meow_MWAI_QueryEmbed( $vector['content'] );
      $queryEmbed->setEnv('admin-tools');
      $answer = $this->core->ai->run( $queryEmbed );
      $vector['embedding'] = $answer->result;
      if ( $this->pinecone_upsert( $vector ) ) {
        $this->wpdb->update( $this->table_vectors, [ 'status' => "ok" ],
          [ 'id' => $vector['id'] ], array( '%s' ), array( '%d' )
        );
      }
      else {
        $this->wpdb->update( $this->table_vectors, [ 'status' => "error" ],
          [ 'id' => $vector['id'] ], array( '%s' ), array( '%d' )
        );
      }
      return true;
    }
    else {
      return false;
    }
  }

  function vectors_get_ref( $vectors, $refId ) {
    if ( !empty( $vectors ) ) { return $vectors; }
    if ( !$this->check_db() ) { return false; }
    $vectors = $this->wpdb->get_results( $this->wpdb->prepare(
      "SELECT * FROM {$this->table_vectors} WHERE refId = %d", $refId ), ARRAY_A );
    return $vectors;
  }

  function vectors_update( $success, $vector = [] ) {
    if ( $success ) { return $success; }
    if ( !$this->check_db() ) { return false; }
    if ( empty( $vector['id'] ) ) { throw new Exception( "Missing ID" ); }
    $originalVector = $this->vector_get( $vector['id'] );
    if ( !$originalVector ) { throw new Exception( "Vector not found" ); }
    $newContent = $originalVector['content'] !== $vector['content'];

    // Update the vector
    $this->wpdb->update( $this->table_vectors, [
        'type' => $vector['type'],
        'title' => $vector['title'],
        'content' => $vector['content'],
        'refId' => !empty( $vector['refId'] ) ? $vector['refId'] : null,
        'refChecksum' => !empty( $vector['refChecksum'] ) ? $vector['refChecksum'] : null,
        'dbIndex' => $vector['dbIndex'],
        'status' => $newContent ? "processing" : "ok"
      ],
      [ 'id' => $vector['id'] ],
      array( '%s', '%s', '%s', '%s', '%s' ),
      array( '%d' )
    );

    if ( $originalVector['content'] !== $vector['content'] ) {
      // Delete the original vector
      $this->pinecode_delete( $originalVector['id'] );
      // Create embedding
      $queryEmbed = new Meow_MWAI_QueryEmbed( $vector['content'] );
      $queryEmbed->setEnv('admin-tools');
      $answer = $this->core->ai->run( $queryEmbed );
      $vector['embedding'] = $answer->result;
      if ( $this->pinecone_upsert( $vector ) ) {
        $this->wpdb->update( $this->table_vectors, [ 'status' => "ok" ],
          [ 'id' => $vector['id'] ], array( '%s' ), array( '%d' )
        );
      }
      else {
        $this->wpdb->update( $this->table_vectors, [ 'status' => "error" ],
          [ 'id' => $vector['id'] ], array( '%s' ), array( '%d' )
        );
      }
    }

    return true;
  }

  function vector_get( $id ) {
    if ( !$this->check_db() ) {
      return null;
    }
    $vector = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $this->table_vectors WHERE id = %d", $id ), ARRAY_A );
    return $vector;
  }

  function vectors_query( $vectors = [], $offset = 0, $limit = null, $filters = null, $sort = null ) {
    if ( !$this->check_db() ) {
      return $vectors;
    }

    // Is AI Search
    $isAiSearch = !empty( $filters['aiSearch'] );
    $matchedVectors = [];
    if ( $isAiSearch ) {
      $query = $filters['aiSearch'];
      $queryEmbed = new Meow_MWAI_QueryEmbed( $query );
      $queryEmbed->setEnv('admin-tools');
      //$queryEmbed->injectParams( $params );
			$answer = $this->core->ai->run( $queryEmbed );
      $matchedVectors = apply_filters( 'mwai_embeddings_vectors_search', $vectors,
        $answer->result, isset( $filters['index'] ) ? $filters['index'] : null );
    }

    $offset = !empty( $offset ) ? intval( $offset ) : 0;
    $limit = !empty( $limit ) ? intval( $limit ) : 100;
    $filters = !empty( $filters ) ? $filters : [];
    $sort = !empty( $sort ) ? $sort : [ "accessor" => "created", "by" => "desc" ];
    $query = "SELECT * FROM $this->table_vectors";

    // Filters
    $where = array();
    if ( isset( $filters['type'] ) ) {
      $where[] = "type = '" . esc_sql( $filters['type'] ) . "'";
    }
    if ( isset( $filters['index'] ) ) {
      $where[] = "dbIndex = '" . esc_sql( $filters['index'] ) . "'";
    }
    if ( $isAiSearch ) {
      $ids = [];
      foreach ( $matchedVectors as $vector ) {
        $ids[] = $vector['id'];
      }
      $where[] = "id IN (" . implode( ",", $ids ) . ")";
    }
    if ( count( $where ) > 0 ) {
      $query .= " WHERE " . implode( " AND ", $where );
    }

    // Count based on this query
    $vectors['total'] = $this->wpdb->get_var( "SELECT COUNT(*) FROM ($query) AS t" );

    // Order by
    if ( !$isAiSearch ) {
      $query .= " ORDER BY " . esc_sql( $sort['accessor'] ) . " " . esc_sql( $sort['by'] );
    }

    // Limits
    if ( !$isAiSearch && $limit > 0 ) {
      $query .= " LIMIT $offset, $limit";
    }

    $vectors['rows'] = $this->wpdb->get_results( $query, ARRAY_A );

    if ( $isAiSearch ) {
      foreach ( $vectors['rows'] as $key => $vectorRow ) {
        $vectorId = $vectorRow['id'];
        $queryVector = null;
        foreach ( $matchedVectors as $vector ) {
          if ( (string)$vector['id'] === (string)$vectorId ) {
            $queryVector = $vector;
            break;
          }
        }
        if ( !empty( $queryVector ) ) {
          $vectors['rows'][$key]['score'] = $queryVector['score'];
        }
      }
    }

    return $vectors;
  }

  #endregion

  #region Vectors DB

  function create_db() {
    $charset_collate = $this->wpdb->get_charset_collate();
    $sqlVectors = "CREATE TABLE $this->table_vectors (
      id BIGINT(20) NOT NULL AUTO_INCREMENT,
      type VARCHAR(32) NULL,
      title VARCHAR(255) NULL,
      content TEXT NULL,
      behavior VARCHAR(32) DEFAULT 'context' NOT NULL,
      status VARCHAR(32) NULL,
      dbIndex VARCHAR(64) NOT NULL,
      dbNS VARCHAR(64) NOT NULL,
      refId BIGINT(20) NULL,
      refChecksum VARCHAR(64) NULL,
      created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
      updated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sqlVectors );
  }

  function check_db() {
    if ( $this->db_check ) {
      return true;
    }
    $this->db_check = !( strtolower( 
      $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_vectors'" ) ) != strtolower( $this->table_vectors )
    );
    if ( !$this->db_check ) {
      $this->create_db();
      $this->db_check = !( strtolower( 
        $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_vectors'" ) ) != strtolower( $this->table_vectors )
      );
    }

    // TODO: REMOVE THIS AFTER MAY 2023
    // Make sure the column "refChecksum" exists in the $this->table_vectors table
    $this->db_check = $this->db_check && $this->wpdb->get_var( "SHOW COLUMNS FROM $this->table_vectors LIKE 'dbNS'" );
    if ( !$this->db_check ) {
      // Create the column "refChecksum" for checksum
      $this->wpdb->query( "ALTER TABLE $this->table_vectors ADD COLUMN refChecksum VARCHAR(64) NULL" );
      $this->wpdb->query( "UPDATE $this->table_vectors SET refChecksum = 'N/A'" );
      // Rename the column "refIndex" to "indexName"
      $this->wpdb->query( "ALTER TABLE $this->table_vectors CHANGE COLUMN refIndex dbIndex VARCHAR(64) NOT NULL" );
      // Create the column "dbNS" for namespace
      $this->wpdb->query( "ALTER TABLE $this->table_vectors ADD COLUMN dbNS VARCHAR(64) NOT NULL" );
      $this->wpdb->query( "UPDATE $this->table_vectors SET dbNS = 'mwai'" );
      $this->db_check = true;
    }

    return $this->db_check;
  }

  #endregion
}

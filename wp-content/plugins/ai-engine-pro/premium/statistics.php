<?php

class MeowPro_MWAI_Statistics {
  private $core = null;
  private $wpdb = null;
  private $db_check = false;
  private $table_logs = null;
  private $table_logmeta = null;
  private $apiRef = null;

  public function __construct() {
    global $wpdb, $mwai_core;
    $this->core = $mwai_core;
    $this->wpdb = $wpdb;
    $this->apiRef = $this->core->get_option('openai_apikey');
    $this->table_logs = $wpdb->prefix . 'mwai_logs';
    $this->table_logmeta = $wpdb->prefix . 'mwai_logmeta';
    add_action( "mwai_stats_log", array( $this, 'log' ), 10, 1 );
    add_filter( "mwai_stats_query", array( $this, 'query' ), 10, 1 );
    add_filter( "mwai_stats_logs", array( $this, 'logs_query' ), 10, 5 );
    add_shortcode( 'mwai_stats_current', array( $this, 'shortcode_current' ) );
    add_shortcode( 'mwai_stats', array( $this, 'shortcode_current' ) );

    // The log all should probably be an option
    add_filter( 'mwai_ai_reply', function ( $reply, $query ) {
      global $mwai_stats;
      $mwai_stats->addCasually( $query, $reply, [
        //'tags' => ['chatbot', 'index'],
      ]);
      return $reply;
    }, 10, 2 );

    // We need a cookie to track the session
    if ( !isset( $_COOKIE['mwai_session_id'] ) ) {
      @setcookie( 'mwai_session_id', uniqid(), 0 );
    }

    $limits = $this->core->get_option( 'limits' );
    if ( isset( $limits['enabled'] ) && $limits['enabled'] ) {
      add_filter( 'mwai_ai_allowed', array( $this, 'check_limits' ), 1, 3 );
    }
  }

  function check_limits( $allowed, $query, $limits ) {
    global $mwai_stats;
    if ( empty( $mwai_stats ) ){
      return $allowed;
    }
  
    // If there is no limit, or if it is admin or editor and they are ignored, then return $allowed.
    $hasLimits = $limits && $limits['enabled'];
    if ( !$hasLimits ) {
      return $allowed;
    }

    // Get Target
    $userId = $this->core->get_user_id();
    $target = $userId ? 'users' : 'guests';

    // Check Ignored Users
    if ( $target === 'users' ) {
      $ignoredUsers = $limits['users']['ignoredUsers'];
      $isAdministrator = current_user_can( 'manage_options' );
      if ( $isAdministrator && strpos( $ignoredUsers, 'administrator' ) !== false ) {
        return $allowed;
      }
      $isEditor = current_user_can( 'edit_posts' );
      if ( $isEditor && strpos( $ignoredUsers, 'editor' ) !== false ) {
        return $allowed;
      }
    }

    // If it's disabled (with credits === 0)
    $credits = apply_filters( 'mwai_stats_credits', $limits[$target]['credits'], $userId );
    if ( $credits === 0 ) {
      return $limits[$target]['overLimitMessage'];
    }
  
    // Let's query the Statistics Module
    $stats = $this->query();
    if ( $stats['overLimit'] ) {
      return $limits[$target]['overLimitMessage'];
    }
    return $allowed;
  }

  function addCasually( Meow_MWAI_Query $query, Meow_MWAI_Answer $answer, $overrides ) {
    $type = null;
    $units = 0;
    if ( is_a( $query, 'Meow_MWAI_QueryText' ) || is_a( $query, 'Meow_MWAI_QueryEmbed' ) ) {
      $type = 'tokens';
      $units = $answer->getTotalTokens();
    }
    else if ( is_a( $query, 'Meow_MWAI_QueryImage' ) ) {
      $type = 'images';
      $units = $query->maxResults;
    }
    else {
      return;
    }
    $stats = [ 
      'env' => $query->env,
      'session' => $query->session,
      'mode' => $query->mode,
      'model' => $query->model,
      'apiRef' => $query->apiKey,
      'units' => $units,
      'type' => $type,
    ];
    $stats = array_merge( $stats, $overrides );
    if ( empty( $stats['price'] ) ) {
      $openai = new Meow_MWAI_OpenAI( $this->core );
      $stats['price'] = $openai->getPrice( $query, $answer );
    }
    return $this->add( $stats );
  }

  function buildTagsForDb( $tags ) {
    if ( is_array( $tags ) ) {
      $tags = implode( '|', $tags );
    }
    if ( !empty( $tags ) ) {
      $tags .= '|';
    }
    else {
      $tags = null;
    }
    return $tags;
  }

  // Query Usage
  function query( $timeFrame = null, $isAbsolute = null, $userId = null, $ipAddress = null, $apiRef = null ) {

    // Validate apiRef, target, userId and ipAddress
    if ( $apiRef === null ) {
      $apiRef = $this->apiRef;
    }
    $target = 'guests';
    if ( $userId === null && $ipAddress === null ) {
      $userId = $this->core->get_user_id();
      if ( $userId ) {
        $target = 'users';
      }
      else {
        $ipAddress =  $this->core->get_ip_address();
        if ( $ipAddress === null ) {
          error_log( "AI Engine: There should be an userId or an ipAddress." );
          return null;
        }
      }
    }
    $limitsOption = $this->core->get_option('limits');
    $hasLimits = $limitsOption && isset( $limitsOption['enabled'] ) && $limitsOption['enabled'];
    $limits = $limitsOption[$target];
    if ( $timeFrame === null ) {
      $timeFrame = $limits['timeFrame'];
    }
    if ( $isAbsolute === null ) {
      $isAbsolute = $limits['isAbsolute'];
    }

    if ( $timeFrame !== 'hour' && $timeFrame !== 'day' && $timeFrame !== 'week'
      && $timeFrame !== 'month' && $timeFrame !== 'year' ) {
      error_log( "AI Engine: TimeFrame should be hour, day, week, month, or year." );
      return null;
    }

    // Create the SQL query
    $this->check_db();
    $prefix = esc_sql( $this->wpdb->prefix );
    $sql = "SELECT COUNT(*) AS queries, SUM(units) AS units, SUM(price) AS price FROM {$prefix}mwai_logs WHERE ";
    
    // Condition: UserId ot IpAddress
    if ( $target === 'users' ) {
      $sql .= "userId = " . esc_sql( $userId ) . "";
    }
    else {
      $sql .= "ip = '" . esc_sql( $ipAddress ) . "'";
    }

    // Condition ApiRef
    if ( $apiRef ) {
      $sql .= " AND apiRef = '" . esc_sql( $apiRef ) . "'";
    }
    
    // Condition: Time Frame (Relative or Absolute)
    if ( $timeFrame === 'hour' ) {
      if ( $isAbsolute ) {
        $now = date( 'Y-m-d H:i:s' );
        $sql .= " AND HOUR(time) = HOUR(\"$now\")";
      }
      else {
        $oneHourAgo = date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );
        $sql .= " AND time >= \"$oneHourAgo\"";
      }
    }
    else if ( $timeFrame === 'day' ) {
      if ( $isAbsolute ) {
        $now = date( 'Y-m-d H:i:s' );
        $sql .= " AND DAY(time) = DAY(\"$now\")";
      }
      else {
        $oneDayAgo = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
        $sql .= " AND time >= \"$oneDayAgo\"";
      }
    }
    else if ( $timeFrame === 'week' ) {
      if ( $isAbsolute ) {
        $now = date( 'Y-m-d H:i:s' );
        $sql .= " AND WEEK(time) = WEEK(\"$now\")";
      }
      else {
        $oneWeekAgo = date( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
        $sql .= " AND time >= \"$oneWeekAgo\"";
      }
    }
    else if ( $timeFrame === 'month' ) {
      if ( $isAbsolute ) {
        $now = date( 'Y-m-d H:i:s' );
        $sql .= " AND MONTH(time) = MONTH(\"$now\")";
      }
      else {
        $oneMonthAgo = date( 'Y-m-d H:i:s', strtotime( '-1 month' ) );
        $sql .= " AND time >= \"$oneMonthAgo\"";
      }
    }
    else if ( $timeFrame === 'year' ) {
      if ( $isAbsolute ) {
        $now = date( 'Y-m-d H:i:s' );
        $sql .= " AND YEAR(time) = YEAR(\"$now\")";
      }
      else {
        $oneYearAgo = date( 'Y-m-d H:i:s', strtotime( '-1 year' ) );
        $sql .= " AND time >= \"$oneYearAgo\"";
      }
    }

    // Process the results
    $results = $this->wpdb->get_results( $sql );
    if ( count( $results ) === 0 ) {
      return null;
    }
    $result = $results[0];
    $stats = [];
    $stats['userId'] = $userId;
    $stats['ipAddress'] = $ipAddress;
    $stats['queries'] = intVal( $result->queries );
    $stats['units'] = intVal( $result->units );
    $stats['price'] = round( floatVal( $result->price ), 2 );
    
    // Give a chance to the dev to override the credits
    $credits = apply_filters( 'mwai_stats_credits',  $limits['credits'], $userId );

    $stats['queriesLimit'] = intVal( $hasLimits && $limits['creditType'] === "queries" ? $credits : 0 );
    $stats['unitsLimit'] = intVal( $hasLimits && $limits['creditType'] === "units" ? $credits : 0 );
    $stats['priceLimit'] = floatVal( $hasLimits && $limits['creditType'] === "price" ? $credits : 0 );

    // Check if the limits are exceeded
    $stats['overLimit'] = false;
    if ( $hasLimits ) {
      if ( $limits['creditType'] === "queries" ) {
        $stats['overLimit'] = $stats['queries'] > $credits;
        $stats['usagePercentage'] = $stats['queriesLimit'] > 0 ? round( $stats['queries'] / $stats['queriesLimit'] * 100, 2 ) : 0;
      }
      else if ( $limits['creditType'] === "units" ) {
        $stats['overLimit'] = $stats['units'] > $credits;
        $stats['usagePercentage'] = $stats['unitsLimit'] > 0 ? round( $stats['units'] / $stats['unitsLimit'] * 100, 2 ) : 0;
      }
      else if ( $limits['creditType'] === "price" ) {
        $stats['overLimit'] = $stats['price'] > $credits;
        $stats['usagePercentage'] = $stats['priceLimit'] > 0 ? round( $stats['price'] / $stats['priceLimit'] * 100, 2 ) : 0;
      }
    }

    return $stats;
  }

  function shortcode_current( $atts ) {
    $display = isset( $atts['display'] ) ? $atts['display'] : 'debug';
    if ( $display === 'debug' ) {
      $display = 'stats';
    }
    else if ( $display === 'usage' ) {
      $display = 'usagebar';
    }

    $showWho = filter_var( isset( $atts['display_who'] ) ?
      $atts['display_who'] : true, FILTER_VALIDATE_BOOLEAN );
    $showQueries = filter_var( isset( $atts['display_queries'] ) ?
      $atts['display_queries'] : true, FILTER_VALIDATE_BOOLEAN );
    $showUnits = filter_var( isset( $atts['display_units'] ) ?
      $atts['display_units'] : true, FILTER_VALIDATE_BOOLEAN );
    $showPrice = filter_var( isset( $atts['display_price'] ) ?
      $atts['display_price'] : true, FILTER_VALIDATE_BOOLEAN );
    $showUsage = filter_var( isset( $atts['display_usage'] ) ?
      $atts['display_usage'] : true, FILTER_VALIDATE_BOOLEAN );
    $showCoins = filter_var( isset( $atts['display_coins'] ) ?
      $atts['display_coins'] : true, FILTER_VALIDATE_BOOLEAN );

    $stats = $this->query();

    if ( $display === "usagebar" ) {
      $percent = isset( $stats['usagePercentage'] ) ? $stats['usagePercentage'] : 0;
      $cssPercent = $percent > 100 ? 100 : $percent;
      $output = '<div class="mwai-statistics mwai-statistics-usage">';
      $output .= '<div class="mwai-statistics-bar-container">';
      $output .= '<div class="mwai-statistics-bar" style="width: ' . $cssPercent . '%;"></div>';
      $output .= '</div>';
      $output .= '<div class="mwai-statistics-bar-text">' . $percent . '%</div>';
      $output .= '</div>';
      $css = file_get_contents( MWAI_PATH . '/premium/stats-chatgpt.css' );
      $output .= "<style>" . $css . "</style>";
      return $output;
    }
    else if ( $display === "stats" ) {
      if ( $stats === null ) {
        return "No stats available.";
      }

      $output = '<div class="mwai-statistics mwai-statistics-debug">';

      if ( $showWho ) {
        if ( !empty( $stats['userId'] ) ) {
          $output .= "<div>User ID: {$stats['userId']}</div>";
        }
        if ( !empty( $stats['ipAddress'] ) ) {
          $output .= "<div>IP Address: {$stats['ipAddress']}</div>";
        }
      }

      if ( $showQueries ) {
        $output .= "<div>Queries: {$stats['queries']}" . 
          ( !empty( $stats['queriesLimit'] ) ? " / {$stats['queriesLimit']}" : "" ) . "</div>";
      }
        
      if ( $showUnits ) {
        $output .= "<div>Units: {$stats['units']}" . 
          ( !empty( $stats['unitsLimit'] ) ? " / {$stats['unitsLimit']}" : "" ) . "</div>";
        $output .= "<small>Note: Units are Tokens and Images Count.</small>";
      }

      if ( $showPrice ) {
        $output .= "<div>Price: {$stats['price']}$" . 
          ( !empty( $stats['priceLimit'] ) ? " / {$stats['priceLimit']}$" : "" ) . "</div>";
      }

      if ( $showCoins ) {
        $coins = apply_filters( 'mwai_stats_coins', $stats['price'], $stats, $atts );
        $coinsLimit = apply_filters( 'mwai_stats_coins_limit', $stats['priceLimit'], $stats, $atts );
        $output .= "<div>Coins: {$coins}" . 
          ( !empty( $coinsLimit ) ? " / {$coinsLimit}" : "" ) . "</div>";
      }

      if ( $showUsage && isset( $stats['usagePercentage'] ) ) {
        $output .= "<div>Usage: {$stats['usagePercentage']}% " . 
          ( $stats['overLimit'] ? '<span class="mwai-over">(OVER LIMIT)</span>' :
            '<span class="mwai-ok">(OK)</span>' ) . "</div>";
      }

      $output .= '</div>';
      return $output;
    }
  }

  function validate_data( $data ) {
    // env: Could be "textwriter", "chatbot", "imagesbot", or anything else
    $data['time'] = date( 'Y-m-d H:i:s' );
    $data['userId'] = $this->core->get_user_id( $data );
    $data['session'] = isset( $data['session'] ) ? (string)$data['session'] : null;
    $data['ip'] = $this->core->get_ip_address( $data );
    $data['model'] = isset( $data['model'] ) ? (string)$data['model'] : null;
    $data['mode'] = isset( $data['mode'] ) ? (string)$data['mode'] : null;
    $data['units'] = isset( $data['units'] ) ? intval( $data['units'] ) : 0;
    $data['type'] = isset( $data['type'] ) ? (string)$data['type'] : null;
    $data['price'] = isset( $data['price'] ) ? floatval( $data['price'] ) : 0;
    $data['env'] = isset( $data['env'] )? (string)$data['env'] : null;
    $data['apiRef'] = isset( $data['apiRef'] ) ? (string)$data['apiRef'] : null;
    $data['tags'] = $this->buildTagsForDb( isset( $data['tags'] ) ? $data['tags'] : null );
    return $data;
  }

  function add( $data ) {
    $this->check_db();
    $data = $this->validate_data( $data );
    $this->wpdb->insert( $this->table_logs, $data );
  }

  function check_db() {
    if ( $this->db_check ) {
      return true;
    }
    $this->db_check = !( strtolower( 
      $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_logs'" ) ) != strtolower( $this->table_logs )
    );
    if ( !$this->db_check ) {
      $this->create_db();
      $this->db_check = !( strtolower( 
        $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_logs'" ) ) != strtolower( $this->table_logs )
      );
    }

    // TODO: REMOVE THIS AFTER MAY 2023
    // Make sure the column "apiRef" exists in the $this->table_logs table
    $this->db_check = $this->db_check && $this->wpdb->get_var( "SHOW COLUMNS FROM $this->table_logs LIKE 'apiRef'" );
    if ( !$this->db_check ) {
      $this->wpdb->query( "ALTER TABLE $this->table_logs ADD COLUMN apiRef VARCHAR(128) NULL" );
      $this->wpdb->query( "UPDATE $this->table_logs SET apiRef = '$this->apiRef'" );
      $this->db_check = true;
    }

    return $this->db_check;
  }

  function create_db() {
    $charset_collate = $this->wpdb->get_charset_collate();

    $sqlLogs = "CREATE TABLE $this->table_logs (
      id BIGINT(20) NOT NULL AUTO_INCREMENT,
      userId BIGINT(20) NULL,
      ip VARCHAR(64) NULL,
      session VARCHAR(64) NULL,
      model VARCHAR(64) NULL,
      mode VARCHAR(64) NULL,
      units INT(11) NOT NULL DEFAULT 0,
      type VARCHAR(64) NULL,
      price FLOAT NOT NULL DEFAULT 0,
      env VARCHAR(64) NULL,
      tags VARCHAR(128) NULL,
      apiRef VARCHAR(128) NULL,
      time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    $sqlLogMeta = "CREATE TABLE $this->table_logmeta (
      meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
      log_id BIGINT(20) NOT NULL,
      meta_key varchar(255) NULL,
      meta_value longtext NULL,
      PRIMARY KEY  (meta_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sqlLogs );
    dbDelta( $sqlLogMeta );
  }

  function remove_db() {
    $sql = "DROP TABLE IF EXISTS $this->table_logs, $this->table_logmeta;";
    $this->wpdb->query( $sql );
  }

  function logs_query( $logs = [], $offset = 0, $limit = null, $filters = null, $sort = null ) {
    $offset = !empty( $offset ) ? intval( $offset ) : 0;
    $limit = !empty( $limit ) ? intval( $limit ) : 100;
    $filters = !empty( $filters ) ? $filters : [];
    $sort = !empty( $sort ) ? $sort : [ "accessor" => "time", "by" => "desc" ];
    $query = "SELECT * FROM $this->table_logs";

    // Filters
    $where = array();
    $where[] = "apiRef = '$this->apiRef'";
    if ( isset( $filters['userId'] ) ) {
      $where[] = "userId = " . intval( $filters['userId'] );
    }
    if ( isset( $filters['ip'] ) ) {
      $where[] = "ip = '" . esc_sql( $filters['ip'] ) . "'";
    }
    if ( isset( $filters['session'] ) ) {
      $where[] = "session = '" . esc_sql( $filters['session'] ) . "'";
    }
    if ( isset( $filters['model'] ) ) {
      $where[] = "model = '" . esc_sql( $filters['model'] ) . "'";
    }
    if ( isset( $filters['mode'] ) ) {
      $where[] = "mode = '" . esc_sql( $filters['mode'] ) . "'";
    }
    if ( isset( $filters['type'] ) ) {
      $where[] = "type = '" . esc_sql( $filters['type'] ) . "'";
    }
    if ( isset( $filters['env'] ) ) {
      $where[] = "env = '" . esc_sql( $filters['env'] ) . "'";
    }
    if ( isset( $filters['tags'] ) ) {
      $where[] = "tags LIKE '%" . esc_sql( $filters['tags'] ) . "%'";
    }
    if ( isset( $filters['from'] ) ) {
      $where[] = "time >= '" . esc_sql( $filters['from'] ) . "'";
    }
    if ( isset( $filters['to'] ) ) {
      $where[] = "time <= '" . esc_sql( $filters['to'] ) . "'";
    }
    if ( count( $where ) > 0 ) {
      $query .= " WHERE " . implode( " AND ", $where );
    }

    // Count based on this query
    $logs['total'] = $this->wpdb->get_var( "SELECT COUNT(*) FROM ($query) AS t" );

    // Order by
    $query .= " ORDER BY " . esc_sql( $sort['accessor'] ) . " " . esc_sql( $sort['by'] );

    // Limits
    if ( $limit > 0 ) {
      $query .= " LIMIT $offset, $limit";
    }

    $logs['rows'] = $this->wpdb->get_results( $query, ARRAY_A );
    return $logs;
  }
}
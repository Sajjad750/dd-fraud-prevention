<?php
/**
 * Creates the listing page for the plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 * @package Dd_Fraud_Prevention
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
 
class Listings_Table extends WP_List_Table {

  /**
	 * Initialize the API key table list.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			)
		);
	}

  public function get_entry_type()
  {
    $type = str_replace('dd_fraud_', "", $_GET['page']);
    
    return $type;
  }

  /**
   * Get list columns.
   *
   * @return array
   */
  public function get_columns() {
    $screen = get_current_screen();
    $type = $this->get_entry_type();
    $table_columns = array(
      'entry'       => __( $type, 'dd-fraud' ),
      'flag'        => __( 'Flag', 'dd-fraud' ),
      'notes'       => __( 'Notes', 'dd-fraud' ),
      'created_at'  => __( 'Created At', 'dd-fraud' ),
    );

    return $table_columns;	
  }

  public function column_default( $item, $column_name ) {		
    $type = $this->get_entry_type();
    switch ( $column_name ) {			
      case $type:
      case 'flag':
      case 'notes':
      case 'ID':
        return $item[$column_name];
      default:
        return $item[$column_name];
    }
  }

  //Query, filter data, handle sorting, pagination, and any other data-manipulation required prior to rendering
  public function prepare_items() {
	  
    $this->_column_headers = $this->get_column_info();	

    // check and process any actions such as bulk actions.
		$this->handle_table_actions();

    // fetch the table data
    $data = $this->fetch_table_data();
    
    // start by assigning your data to the items variable
    $this->items = $data['results'];

    // code to handle pagination
    $entries_per_page = $this->get_items_per_page( 'entries_per_page' );

    // set the pagination arguments		
    $this->set_pagination_args( array (
      'total_items' => $data['count'],
      'per_page'    => $entries_per_page,
      'total_pages' => ceil( $data['count']/$entries_per_page )
    ) );  
  }
  

  public function fetch_table_data() {

    $type = $this->get_entry_type();
    $flag = ( isset($_REQUEST['flag']) ? $_REQUEST['flag'] : 'all');
    
    global $wpdb;

    $table_name = $wpdb->prefix . 'dd_fraud_' . $type;		
    $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'id';
    $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'ASC';

		$current_page = $this->get_pagenum();
    $entries_per_page = $this->get_items_per_page( 'entries_per_page' );

		if ( $current_page > 1) {
			$offset = $entries_per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

    $search = '';

		if ( ! empty( $_REQUEST['s'] ) ) 
    {
			$search = "AND $type LIKE '%" . esc_sql( $wpdb->esc_like( wc_clean( wp_unslash( $_REQUEST['s'] ) ) ) ) . "%' ";
		}

    $sql = "SELECT ID, $type, flag, notes, created_at FROM $table_name WHERE ";
    if ($flag !== "all")
    {
      $sql .= $wpdb->prepare("flag = %s AND", $flag);
    }
    $sql .= " 1 = 1 {$search} ORDER BY $orderby $order";
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d;", $entries_per_page, $offset);

    $query_results = $wpdb->get_results( $sql, ARRAY_A  );

    $count_sql = "SELECT COUNT(id) FROM $table_name WHERE ";
    if ($flag !== "all")
    {
      $count_sql .= $wpdb->prepare("flag = %s AND", $flag);
    }

    $count_sql .= " 1 = 1 {$search};";
    $count = $wpdb->get_var($count_sql);

    $data = array( 'results' => $query_results, 'count' => $count );
    
    return $data;
  }

  public function handle_table_actions()
  {
    $the_table_action = $this->current_action();
		
		if ( 'delete_entry' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'delete_dd_entry' ) ) {
				$this->invalid_nonce_redirect();
			}
			else {
        $deleted = $this->delete_entry( absint( $_REQUEST['id']), $_REQUEST['type'] );
        $url = wp_get_referer();
        wp_redirect( add_query_arg( 'deleted', $deleted, $url ) );
        exit();
			}
		}

    if ( 'edit_entry' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'edit_dd_entry' ) ) {
				$this->invalid_nonce_redirect();
			}
			else {
				$this->page_view_edit_entry( absint( $_REQUEST['id']), $_REQUEST['type'] );
				exit();
			}
		}
  }

  public function no_items() 
  {
    _e( 'No entries found.', 'dd-fraud' );
  }
  
  protected function get_sortable_columns() 
  {
    $type = $this->get_entry_type();
    $sortable_columns = array (
        'entry' => $type,
        'flag' => 'flag',
        'created_at' => 'created_at',
      );
    return $sortable_columns;
  }

  protected function column_entry( $item )
  {
    $type = $this->get_entry_type();

    $admin_page_url =  admin_url( 'admin.php?page=dd_fraud_' . $type );

    $query_args_edit_entry = array(
			'page'		=>  wp_unslash( $_REQUEST['page'] ),
			'action'	=> 'edit_entry',
      'type'    => $type,
			'id'		=> absint( $item['ID']),
      '_wpnonce'	=> wp_create_nonce( 'edit_dd_entry' ),
		);

    $edit_entry_link = esc_url( add_query_arg( $query_args_edit_entry, $admin_page_url ) );

    $actions['edit_entry'] = '<a href="' . $edit_entry_link . '">' . __( 'Edit Entry', 'dd-fraud') . '</a>';	

    $query_args_delete_entry = array(
			'page'		=>  wp_unslash( $_REQUEST['page'] ),
			'action'	=> 'delete_entry',
      'type'    => $type,
			'id'		=> absint( $item['ID']),
			'_wpnonce'	=> wp_create_nonce( 'delete_dd_entry' ),
		);

    $delete_entry_link = esc_url( add_query_arg( $query_args_delete_entry, $admin_page_url ) );
    $actions['delete_entry'] = '<span class="trash"><a href="' . $delete_entry_link . '">' . __( 'Delete', 'dd-fraud') . '</a></span>';	

    $row_value = sanitize_text_field($item[$type]);
		return $row_value . $this->row_actions( $actions );
  }

  protected function column_notes ( $item )
  {
    $row_value = stripslashes($item['notes']);
    return $row_value;
  }

  private function delete_entry($id, $type)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'dd_fraud_' . $type;
		$delete = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

		return $delete;
  }

  public function invalid_nonce_redirect() {
		wp_die( __( 'Invalid Nonce', 'dd-fraud' ),
				__( 'Error', 'dd-fraud' ),
				array( 
						'response' 	=> 403, 
						'back_link' =>  esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ) , admin_url( 'admin.php?page=dd_fraud_' . $_REQUEST['type'] ) ) ),
					)
		);
  }

	private function page_view_edit_entry( $id, $type ) {
		
		$entry = $this->get_entry($id, $type);
		include_once( 'views/add-entry.php' );
	}

  private function get_entry( $id, $type )
  {
    global $wpdb;

    $table = $wpdb->prefix . 'dd_fraud_' . $type;
    $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $result = $wpdb->get_row($sql, ARRAY_A);
    return $result;
  }

  protected function get_views()
  {
    global $wpdb;

    $views = array();
    $current = ( !empty($_REQUEST['flag']) ? $_REQUEST['flag'] : 'all');

    $type = $this->get_entry_type();
    $table = $wpdb->prefix . 'dd_fraud_' . $type;
    $blocked_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE flag = 'blocked';" );
    $review_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE flag = 'review';" );
    $verified_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE flag = 'verified';" );
    $total = $blocked_count + $review_count + $verified_count;

    //All link
    $class = ($current == 'all' ? ' class="current"' :'');
    $all_url = remove_query_arg('flag');
    $views['all'] = "<a href='{$all_url }' {$class} >All <span class='count'>($total)</span></a>";

    //Blocked link
    $blocked_url = add_query_arg('flag','blocked');
    $class = ($current == 'blocked' ? ' class="current"' :'');
    $views['blocked'] = "<a href='{$blocked_url}' {$class} >Blocked <span class='count'>($blocked_count)</span></a>";

    //Review link
    $review_url = add_query_arg('flag','review');
    $class = ($current == 'review' ? ' class="current"' :'');
    $views['review'] = "<a href='{$review_url}' {$class} >Review Required <span class='count'>($review_count)</span></a>";

    //Verified link
    $verified_url = add_query_arg('flag','verified');
    $class = ($current == 'verified' ? ' class="current"' :'');
    $views['verified'] = "<a href='{$verified_url}' {$class} >Verified <span class='count'>($verified_count)</span></a>";

    return $views;
  }
}
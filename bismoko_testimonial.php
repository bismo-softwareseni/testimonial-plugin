<?php
    /*
        Plugin Name: Bismoko Widyatno Testimonial
        Description: Display testimonial form
        Version: 1.0
        Author: Bismoko Widyatno
    */
    
    //-- create WP List Table
    if ( !class_exists( 'WP_List_Table' ) ) {
	   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    
    //-- testimonials table using WP_List_Table
    class Bismo_Testimonial_Table extends WP_List_Table {
        function __construct() {
            global $status, $page;

            parent::__construct( array(
                'singular' => 'testimonial',
                'plural' => 'testimonials',
            ) );
        }
        
        function column_default($item, $column_name) {
            return $item[ $column_name ];
        }
        
        //-- checkbox column
        function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="id[]" value="%s" />',
                $item['testimonial_id']
            );
        }
        
        //-- delete testimonial column
        function column_delete_testimonial( $item ) {
            return sprintf(
                '<a href="?page=%s&action=%s&id=%s">Delete</a>',
                $_REQUEST['page'],
                'delete',
                $item['testimonial_id']
            );
        }
        
        function get_columns() {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'testimonial_name' => __( 'Name', 'bismoko_testimonial' ),
                'testimonial_email' => __( 'E-Mail', 'bismoko_testimonial' ),
                'testimonial_phone' => __( 'Phone Number', 'bismoko_testimonial' ),
                'testimonial_content' => __( 'Testimonial', 'bismoko_testimonial' ),
                'delete_testimonial' => __( 'Action', 'bismoko_testimonial' )
            );
            return $columns;
        }
        
        function get_bulk_actions() {
            $actions = array(
                'delete' => 'Delete'
            );
            return $actions;
        }
        
        function get_sortable_columns() {
            $sortable_columns = array(
                'testimonial_name' => array('testimonial_name', true),
                'testimonial_email' => array('testimonial_email', true),
                'testimonial_phone' => array('testimonial_phone', true)
            );
            
            return $sortable_columns;
        }
        
        function process_bulk_action() {
            global $wpdb;
            $table_name = 'wp_bismoko_testimonial';

            if ( 'delete' === $this->current_action() ) {
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) $ids = implode(',', $ids);

                if (!empty($ids)) {
                    $wpdb->query("DELETE FROM $table_name WHERE testimonial_id IN($ids)");
                }
            }
            
            
        }
        
        function prepare_items() {
            global $wpdb;
            $table_name = 'wp_bismoko_testimonial';

            $per_page = 10; // constant, how much records will be shown per page

            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();

            // here we configure table headers, defined in our methods
            $this->_column_headers = array($columns, $hidden, $sortable);

            // [OPTIONAL] process bulk action if any
            $this->process_bulk_action();

            // will be used in pagination settings
            $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

            // prepare query params, as usual current page, order by and order direction
            $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
            $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'testimonial_name';
            $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

            // [REQUIRED] define $items array
            // notice that last argument is ARRAY_A, so we will retrieve array
            $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

            // [REQUIRED] configure pagination
            $this->set_pagination_args(array(
                'total_items' => $total_items, // total items defined above
                'per_page' => $per_page, // per page constant defined at top of method
                'total_pages' => ceil($total_items / $per_page) // calculate pages count
            ));
        }
    }
    //-- end testimonial table using WP_List_Table 

    //-- testimonials widget class
    class Bismo_Testimonial_Widget extends WP_Widget {
        public function __construct() {
            $widget_ops = array( 
                'classname' => 'Bismo_Testimonial_Widget',
                'description' => esc_html__( 'Bismoko Widyatno Testimonial widget', 'bismoko_testimonial' )
            );
            parent::__construct( 'Bismo_Testimonial_Widget', 'Bismoko Testimonial Widget', $widget_ops );
        }
        
        //-- create form
        function form( $instance ) {
            //-- check value
            if( $instance ) {
                $widget_title = esc_attr( $instance[ 'widget_title' ] );
            } else {
                $widget_title = '';
            }
?>
            <!-- widget title -->
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>">
                  <?php echo esc_html_e( 'Widget Title :', 'bismoko_testimonial' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>" />
            </p>
            <!-- end widget title -->
<?php
        }
        
        //-- widget update
        function update( $new_instance, $old_instance ) {
              $instance = $old_instance;

              //-- set new value for the fields
              $instance[ 'widget_title' ] = strip_tags( $new_instance[ 'widget_title' ] );

              return $instance;
        }
        
        //-- widget display
        function widget( $args, $instance ) {
            extract( $args );

            //-- get widget options
            $widget_title = apply_filters( 'widget_title', $instance[ 'widget_title' ] );
            
            echo $before_widget;
            
?>
            <!-- widget testimonial container -->
            <div class="widget-testimonial-container">
                <!-- widget title -->
                <?php
                  if( $widget_title ) {
                    echo $before_title . esc_html( $widget_title ) . $after_title;
                  }
                ?>
                <!-- end widget title -->
                
                <!-- get testimonial data -->
                <?php
                    global $wpdb;
                    $testimonials = $wpdb->get_results( "SELECT * FROM wp_bismoko_testimonial order by RAND() limit 1" );
            
                    foreach( $testimonials as $testimonial ) {
                ?>
                
                <!-- name -->
                <p class="testimonial-label">Name :</p>
                <h6 class="testimonial-name"><?php echo esc_html( $testimonial->testimonial_name ); ?></h6>
                
                <!-- email -->
                <p class="testimonial-label">Email :</p>
                <h6 class="testimonial-email"><?php echo esc_html( $testimonial->testimonial_email ); ?></h6>
                
                <!-- phone -->
                <p class="testimonial-label">Phone :</p>
                <h6 class="testimonial-phone"><?php echo esc_html( $testimonial->testimonial_phone ); ?></h6>
                
                <!-- testimonial -->
                <p class="testimonial-label">Testimonial :</p>
                <h6 class="testimonial-content"><?php echo esc_html( $testimonial->testimonial_content ); ?></h6>
                
                <?php
                    }
                ?>
                <!-- end get testimonial data -->
            </div>
            <!-- end widget testimonial container -->
<?php
            echo $after_widget;
        }
        //-- end widget display
    
    }
    //-- end testimonial widget class

    //-- html code for testimonial form
    function bismo_testimonial_form() {
        echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="POST">';
        
        //-- name
        echo '<p>Name (required)</p>';
        echo '<input required type="text" name="testimonial-name" id="testimonial-name" class="bismo-form-text" value="" style="width:100%;" />';
        
        //-- email
        echo '<p for="testimonial-email">Email (required)</p>';
        echo '<input required type="email" name="testimonial-email" id="testimonial-email" class="bismo-form-text" style="width:100%;" value="" />';
        
        //-- phone number
        echo '<p for="testimonial-phone">Phone Number (required)</p>';
        echo '<input required type="text" name="testimonial-phone" id="testimonial-phone" class="bismo-form-text" style="width:100%;" value="" />';
        
        //-- testimonial text
        echo '<p for="testimonial-content">Testimonial (required)</p>';
        echo '<textarea required name="testimonial-content" id="testimonial-content" style="width:100%;" class="bismo-form-textarea"></textarea>';
        
        //-- submit button
        echo '<button type="submit" name="testimonial-submit" class="bismo-form-submit-button" style="margin-top:30px;">Submit</button>';
        
        echo '</form>';
    }

    //-- insert into database
    function bismo_testimonial_insert() {
        if( isset( $_POST[ 'testimonial-submit' ] ) ) {
            //-- sanitize input
            $testimonial_name       = sanitize_text_field( $_POST[ 'testimonial-name' ] );
            $testimonial_email      = sanitize_email( $_POST[ 'testimonial-email' ] );
            $testimonial_phone      = sanitize_text_field( $_POST[ 'testimonial-phone' ] );
            $testimonial_content    = esc_textarea( $_POST[ 'testimonial-content' ] );
            
            //-- insert into database
            global $wpdb;
            
            $wpdb->insert( 
                'wp_bismoko_testimonial',
                array(
                    'testimonial_name' => $testimonial_name,
                    'testimonial_email' => $testimonial_email,
                    'testimonial_phone' => $testimonial_phone,
                    'testimonial_content' => $testimonial_content
                )
            );
            
            //-- clear the cache
            $wpdb->flush();
        }
    }
    
    //-- testimonials table handlers
    function bismoko_testimonial_table_handlers() {
        global $wpdb;
        
        $testimonials_table = new Bismo_Testimonial_Table();
        $testimonials_table->prepare_items();
        
        //-- success delete message
        $message = '';
        if ('delete' === $testimonials_table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'bismoko_testimonial'), count($_REQUEST['id'])) . '</p></div>';
        }
        
?>

        <div class="wrap bismoko-testimonial-container">
            <h2>Manage Testimonials</h2>

            <?php
                echo $message;
            ?>
            
            <!-- testimonial table -->
            <form class="testimonial-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $testimonials_table->display() ?>
            </form>
            <!-- end testimonial table -->
        </div>


<?php
    }

    //-- creating admin menu
    function bismoko_testimonial_admin_menu() {
        add_menu_page( 'Manage Testimonials', 'Testimonials', 'manage_options', 'manage-testimonials.php', 'bismoko_testimonial_table_handlers', 'dashicons-editor-quote' );
    }

    //-- shortcode main function
    function bismoko_testimonial_shortcode() {
        ob_start();
        
        bismo_testimonial_insert();
        bismo_testimonial_form();
        
        return ob_get_clean();
    }

    //-- register shortcode
    add_shortcode( 'bismoko_testimonial', 'bismoko_testimonial_shortcode' );

    //-- register admin page
    add_action( 'admin_menu', 'bismoko_testimonial_admin_menu' );

    //-- register widget
    add_action( 'widgets_init', function() {
        register_widget( 'Bismo_Testimonial_Widget' );
    } );
?>
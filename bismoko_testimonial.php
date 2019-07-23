<?php
    /*
        Plugin Name: SoftwareSeni Testimonial
        Description: Display testimonial form
        Version: 2.0
        Author: Bismoko Widyatno
    */
    
    /**
     * --------------------------------------------------------------------------
     * Import require files
     * --------------------------------------------------------------------------
     **/ 
    
    //-- WP List Table ( for creating table list in the testimonial admin )
    if ( !class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }

    /* ----- end Import require files ----- */

    /**
     * --------------------------------------------------------------------------
     * Main class for this plugin. This class will handle most of the testimonial 
     * plugin logic
     * --------------------------------------------------------------------------
     **/
    class SS_Testimonial_Main {
        var $ss_testi_table_name = "ss_testimonial";

        function __construct() {
            global $wpdb;

            //-- check testimonial table exist or not, if not then create new table ( execute this only when activating the plugin )
            register_activation_hook( __FILE__, array( $this, 'ssTestiCheckCreateTestiTable' ) );

            //-- register testimonial shortcode
            add_shortcode( 'ss_testimonial', array( $this, 'ssTestiShortcodeCreate' ) );
            
            //-- register widget
            add_action( 'widgets_init', function() {
                register_widget( 'SS_Testimonial_Widget' );
            } );

            //-- register admin page
            add_action( 'admin_menu', array( $this, 'ssTestiCreateAdminMenu' ) );
        }

        //-- function for check and create testimonial table
        function ssTestiCheckCreateTestiTable() {
            global $wpdb;

            //-- check testimonial table exist or not, if not then create new table
            if( $wpdb->get_var("SHOW TABLES LIKE '$wpdb->prefix.$this->ss_testi_table_name'") != $wpdb->prefix.$this->ss_testi_table_name ) {
                $ss_testi_charset_collate = $wpdb->get_charset_collate();

                $ss_testi_sql = "CREATE TABLE " . $wpdb->prefix.$this->ss_testi_table_name . " (
                                        testimonial_id int(11) NOT NULL AUTO_INCREMENT,
                                        testimonial_name varchar(500) NOT NULL,
                                        testimonial_email varchar(500) NOT NULL,
                                        testimonial_phone varchar(500) NOT NULL,
                                        testimonial_content text NOT NULL,
                                        PRIMARY KEY  (testimonial_id)
                                    ) " . $ss_testi_charset_collate . ";";
            }
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $ss_testi_sql );
        }


        //-- function for displaying and storing testimonial input form
        function ssTestiFormDisplay() {
            if( isset( $_POST[ 'testimonial-submit' ] ) ) {
                //-- get post data
                $testimonial_inputs = array(
                    "testimonial_name" => "",
                    "testimonial_name_error" => "",
                    "testimonial_email" => "",
                    "testimonial_email_error" => "",
                    "testimonial_phone" => "",
                    "testimonial_phone_error" => "",
                    "testimonial_content" => "",
                    "testimonial_content_error" => ""
                );

                /* name */
                if( isset( $_POST[ 'testimonial-name' ] ) ) {
                    $testimonial_inputs[ 'testimonial_name' ] = sanitize_text_field( $_POST[ 'testimonial-name' ] );

                    //-- check form error
                    if( empty( $_POST[ 'testimonial-name' ] ) ) {
                        $testimonial_inputs[ 'testimonial_name_error' ] = "This form can't be empty";
                    } else {
                        if( preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $_POST[ 'testimonial-name' ] ) ) {
                            $testimonial_inputs[ 'testimonial_name_error' ] = "Special characters are not allowed";
                        }
                    }
                }

                /* email */
                if( isset( $_POST[ 'testimonial-email' ] ) ) {
                    $testimonial_inputs[ 'testimonial_email' ] = sanitize_email( $_POST[ 'testimonial-email' ] );

                    //-- check form error
                    if( empty( $_POST[ 'testimonial-email' ] ) ) {
                        $testimonial_inputs[ 'testimonial_email_error' ] = "This form can't be empty";
                    } else {
                        if( !is_email( $_POST[ 'testimonial-email' ] ) ) {
                            $testimonial_inputs[ 'testimonial_email_error' ] = "You have to input the correct email address";
                        }
                    }
                }

                /* phone */
                if( isset( $_POST[ 'testimonial-phone' ] ) ) {
                    $testimonial_inputs[ 'testimonial_phone' ] = sanitize_text_field( $_POST[ 'testimonial-phone' ] );

                    //-- check form error
                    if( empty( $_POST[ 'testimonial-phone' ] ) ) {
                        $testimonial_inputs[ 'testimonial_phone_error' ] = "This form can't be empty";
                    } else {
                        if( !is_numeric( $_POST[ 'testimonial-phone' ] ) ) {
                            $testimonial_inputs[ 'testimonial_phone_error' ] = "Only numeric value allowed";
                        }
                    }
                }

                /* content */
                if( isset( $_POST[ 'testimonial-content' ] ) ) {
                    $testimonial_inputs[ 'testimonial_content' ] = esc_textarea( $_POST[ 'testimonial-content' ] );

                    //-- check form error
                    if( empty( $_POST[ 'testimonial-content' ] ) ) {
                        $testimonial_inputs[ 'testimonial_content_error' ] = "This form can't be empty";
                    }
                }
                //-- end get post data
                

                //-- insert into database if there is no error
                if( empty( $testimonial_inputs[ 'testimonial_name_error' ] ) && empty( $testimonial_inputs[ 'testimonial_email_error' ] ) && empty( $testimonial_inputs[ 'testimonial_phone_error' ] ) && empty( $testimonial_inputs[ 'testimonial_content_error' ] ) ) {
                    global $wpdb;
                
                    $wpdb->insert( 
                        $wpdb->prefix.$this->ss_testi_table_name,
                        array(
                            'testimonial_name' => $testimonial_inputs[ 'testimonial_name' ],
                            'testimonial_email' => $testimonial_inputs[ 'testimonial_email' ],
                            'testimonial_phone' => $testimonial_inputs[ 'testimonial_phone' ],
                            'testimonial_content' => $testimonial_inputs[ 'testimonial_content' ]
                        )
                    );
                    
                    //-- clear the cache
                    $wpdb->flush();
                }
            }

            echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="POST">';
        
            //-- name
            echo '<p>Name (required)</p>';
            if( !empty( $testimonial_inputs[ 'testimonial_name_error' ] ) ) {
                echo '<p class="error-notif">' . $testimonial_inputs[ 'testimonial_name_error' ] . '</p>';
            }
            echo '<input required type="text" name="testimonial-name" id="testimonial-name" class="bismo-form-text" value="" style="width:100%;" />';
            
            //-- email
            echo '<p for="testimonial-email">Email (required)</p>';
            if( !empty( $testimonial_inputs[ 'testimonial_email_error' ] ) ) {
                echo '<p class="error-notif">' . $testimonial_inputs[ 'testimonial_email_error' ] . '</p>';
            }
            echo '<input required type="email" name="testimonial-email" id="testimonial-email" class="bismo-form-text" style="width:100%;" value="" />';
            
            //-- phone number
            echo '<p for="testimonial-phone">Phone Number (required)</p>';
            if( !empty( $testimonial_inputs[ 'testimonial_phone_error' ] ) ) {
                echo '<p class="error-notif">' . $testimonial_inputs[ 'testimonial_phone_error' ] . '</p>';
            }
            echo '<input required type="text" name="testimonial-phone" id="testimonial-phone" class="bismo-form-text" style="width:100%;" value="" />';
            
            //-- testimonial text
            echo '<p for="testimonial-content">Testimonial (required)</p>';
            if( !empty( $testimonial_inputs[ 'testimonial_content_error' ] ) ) {
                echo '<p class="error-notif">' . $testimonial_inputs[ 'testimonial_content_error' ] . '</p>';
            }
            echo '<textarea required name="testimonial-content" id="testimonial-content" style="width:100%;" class="bismo-form-textarea"></textarea>';
            
            //-- submit button
            echo '<button type="submit" name="testimonial-submit" class="bismo-form-submit-button" style="margin-top:30px;">Submit</button>';
            
            echo '</form>';
        }

        //-- function for creating testimonial shortcode
        function ssTestiShortcodeCreate() {
            ob_start();
        
            $this->ssTestiFormDisplay();
            
            return ob_get_clean();
        }

        //-- function for creating testimonial admin menu
        function ssTestiCreateAdminMenu() {
            add_menu_page( 'Manage Testimonials', 'Testimonials', 'manage_options', 'manage-testimonials.php', array( $this, 'ssTestiShowDataHandlers' ), 'dashicons-editor-quote' );
        }
        //-- end ssTestiCreateAdminMenu()

        //-- function for showing testimonials data on admin page
        function ssTestiShowDataHandlers() {
            global $wpdb;

            //-- create testimonial table (using WP List Table)
            $testimonials_table = new SS_Testimonial_Table();
            $testimonials_table->prepare_items();
            
            //-- success delete message
            $message = '';
            if ('delete' === $testimonials_table->current_action() && is_array( $_REQUEST['id'] ) ) {
                $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'ss_testimonial'), count($_REQUEST['id'])) . '</p></div>';
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
        //-- end ssTestiShowDataHandlers()
    }
    //-- end SS_Testimonial_Main




    /**
     * --------------------------------------------------------------------------
     * Class for creating testimonial widget
     * --------------------------------------------------------------------------
     **/
    class SS_Testimonial_Widget extends WP_Widget {
        public function __construct() {
            $widget_ops = array( 
                'classname' => 'SS_Testimonial_Widget',
                'description' => esc_html__( 'SoftwareSeni Testimonial widget', 'ss_testimonial' )
            );
            parent::__construct( 'SS_Testimonial_Widget', 'SoftwareSeni Testimonial Widget', $widget_ops );
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
                  <?php echo esc_html_e( 'Widget Title :', 'ss_testimonial' ); ?>
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

                    //-- create main testimonial class object
                    $ss_testimonial_main = new SS_Testimonial_Main();
                    
                    $testimonials = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix.$ss_testimonial_main->ss_testi_table_name . " order by RAND() limit 1" );
            
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
    //-- end SS_Testimonial_Widget class



    /**
     * --------------------------------------------------------------------------
     * Class for creating WP List Table for testimonial list in admin page
     * --------------------------------------------------------------------------
     **/
    class SS_Testimonial_Table extends WP_List_Table {
        var $ss_testimonial_main_class;

        function __construct() {
            global $status, $page;

            parent::__construct( array(
                'singular' => 'testimonial',
                'plural' => 'testimonials',
            ) );

            //-- create main testimonial class object
            $this->ss_testimonial_main_class = new SS_Testimonial_Main();
        }
        
        //-- default column
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
        
        //-- get testimonial data
        function get_columns() {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'testimonial_name' => __( 'Name', 'ss_testimonial' ),
                'testimonial_email' => __( 'E-Mail', 'ss_testimonial' ),
                'testimonial_phone' => __( 'Phone Number', 'ss_testimonial' ),
                'testimonial_content' => __( 'Testimonial', 'ss_testimonial' ),
                'delete_testimonial' => __( 'Action', 'ss_testimonial' )
            );
            return $columns;
        }
        
        //-- bulk checkbox action
        function get_bulk_actions() {
            $actions = array(
                'delete' => 'Delete'
            );
            return $actions;
        }
        
        //-- sortable column
        function get_sortable_columns() {
            $sortable_columns = array(
                'testimonial_name' => array('testimonial_name', true),
                'testimonial_email' => array('testimonial_email', true),
                'testimonial_phone' => array('testimonial_phone', true)
            );
            
            return $sortable_columns;
        }
        
        //-- bulk action handlers
        function process_bulk_action() {
            global $wpdb;

            $table_name = $wpdb->prefix.$this->ss_testimonial_main_class->ss_testi_table_name;

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
            $table_name = $wpdb->prefix.$this->ss_testimonial_main_class->ss_testi_table_name;

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
    //-- end SS_Testimonial_Table

    //-- run main class
    $ss_testimonial_main = new SS_Testimonial_Main();
?>
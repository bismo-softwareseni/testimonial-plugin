<?php
    /*
        Plugin Name: Bismoko Widyatno Testimonial
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
        var $ss_testi_table_prefix;
        var $ss_testi_table_name = "ss_testimonial";
        var $errorr;

        function __construct() {
            global $wpdb;

            //-- set testimonial table prefix
            $this->ss_testi_table_prefix = $wpdb->prefix;

            //-- check testimonial table exist or not, if not then create new table
            $this->ssTestiCheckCreateTestiTable();

            //-- register admin page
            add_action( 'admin_menu', array( $this, 'ssTestiCreateAdminMenu' ) );
        }

        //-- function for check and create testimonial table
        function ssTestiCheckCreateTestiTable() {
            global $wpdb;

            //-- check testimonial table exist or not, if not then create new table
            if( $wpdb->get_var("SHOW TABLES LIKE '$this->ss_testi_table_prefix.$this->ss_testi_table_name'") != $this->ss_testi_table_prefix.$this->ss_testi_table_name ) {
                $ss_testi_charset_collate = $wpdb->get_charset_collate();

                $ss_testi_sql = "CREATE TABLE " . $this->ss_testi_table_prefix.$this->ss_testi_table_name . " (
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

        //-- function for storing testimonials into database
        function ssTestiFormSubmitHandler() {
            if( isset( $_POST[ 'testimonial-submit' ] ) ) {
                //-- sanitize input
                $testimonial_name       = sanitize_text_field( $_POST[ 'testimonial-name' ] );
                $testimonial_email      = sanitize_email( $_POST[ 'testimonial-email' ] );
                $testimonial_phone      = sanitize_text_field( $_POST[ 'testimonial-phone' ] );
                $testimonial_content    = esc_textarea( $_POST[ 'testimonial-content' ] );
                
                //-- insert into database
                global $wpdb;
                
                $wpdb->insert( 
                    $this->ss_testi_table_prefix.$this->ss_testi_table_name,
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

        //-- function for displaying

        //-- function for creating testimonial admin menu
        function ssTestiCreateAdminMenu() {
            add_menu_page( 'Manage Testimonials', 'Testimonials', 'manage_options', 'manage-testimonials.php', array( $this, 'ssTestiShowDataHandlers' ), 'dashicons-editor-quote' );
        }
        //-- end ssTestiCreateAdminMenu()

        //-- function for showing testimonials data on admin page
        function ssTestiShowDataHandlers() {
            $message = $this->errorr;
    ?>

        <div class="wrap bismoko-testimonial-container">
            <h2>Manage Testimonials</h2>

            <?php
                echo $message;
            ?>
            
            <!-- testimonial table -->
            <form class="testimonial-table" method="GET">
                <!--<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>-->
                <?php //$testimonials_table->display() ?>
            </form>
            <!-- end testimonial table -->
        </div>

    <?php
        }
        //-- end ssTestiShowDataHandlers()
    }


    //-- run main class
    $ss_testimonial_main = new SS_Testimonial_Main();
?>
<?php

class WPAM_Plugins_Loaded_Tasks {

    public function __construct() {

        if (is_admin()) {
            //Do admin side plugins_loades tasks
            $this->do_db_upgrade_task();
                    
        } else {
            //Do front-end plugins loaded tasks
            
        }
    }

    public function do_db_upgrade_task() {
        if (get_option('wpam_db_version') != WPAM_DB_VERSION) {
            global $wpdb;
            $dbInstaller = new WPAM_Data_DatabaseInstaller($wpdb);
            $dbInstaller->doDbInstall();
        }
    }

}
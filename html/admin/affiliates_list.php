<?php
//display My Affiliates menu
?>
    <div class="wrap">
    <h2><?php _e('My Affiliates', 'wpam');?></h2>
    <?php
    $status_array = array(
        'all_active' => __( 'All Active', 'wpam' ), 
        'all' => __( 'All (Including Closed)', 'wpam' ), 
        'active' => __( 'Active', 'wpam' ), 
        'applied' => __( 'Applied', 'wpam' ), 
        'approved' => __( 'Approved', 'wpam' ), 
        'confirmed' => __( 'Confirmed', 'wpam' ), 
        'declined' => __( 'Declined', 'wpam' ), 
        'blocked' => __( 'Blocked', 'wpam' ), 
        'inactive' => __( 'Inactive', 'wpam' )
    );
    $current_class = "";
    if(isset($_REQUEST['statusFilter'])) {
        $status_text = esc_sql($_REQUEST['statusFilter']);
        if(!empty($status_text)){
            $current_class = $status_text;
        }
    }
    ?>
    <ul class="subsubsub"> 
    <?php
    $count = 1;
    foreach($status_array as $key => $status){
        ?>
        <li><a href="admin.php?page=wpam-affiliates&statusFilter=<?php echo $key;?>"<?php echo ($current_class==$key)?' class="current"':'';?>><?php echo $status;?></a><?php echo ($count==9) ? '': ' |';?></li>
        <?php
        $count = $count+1;
    } 
    ?>
    </ul>
    <div id="poststuff"><div id="post-body">
    <?php        
    
    include_once(WPAM_BASE_DIRECTORY . '/classes/aff_list_affiliates_table.php');
    //Create an instance of our package class...
    $affiliates_list_table = new WPAM_List_Affiliates_Table();
    //Fetch, prepare, sort, and filter our data...
    $affiliates_list_table->prepare_items();
    ?>
    <!--        
    <style type="text/css">
        .column-affiliateId {width:6%;}
        .column-status {width:6%;}
        .column-balance {width:6%;}
        .column-earnings {width:6%;}
        .column-firstName {width:6%;}
        .column-lastName {width:6%;}
        .column-email {width:10%;}
        .column-companyName {width:10%;}
        .column-dateCreated {width:10%;}
        .column-websiteUrl {width:10%;}
    </style>
    -->
    <div class="wpam-click-throughs">

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="wpam-click-throughs-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $affiliates_list_table->display() ?>
        </form>

    </div>

    </div></div>
    </div>

<div class="wrap">    
    <h2><?php _e('Bigo ID List', 'dd-fraud'); ?></h2>
      <div id="bigo-id">			
          <div id="nds-post-body">		
            <form id="bigo_id" method="get">
              <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
              <?php
                $this->listings_table->views();
                $this->listings_table->search_box( __( 'Find', 'dd-fraud' ), 'dd-bigo-search');
                $this->listings_table->display(); 
              ?>
            </form>
          </div>			
      </div>
</div>
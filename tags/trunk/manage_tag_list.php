<?php
/* Affichage */
if ($mode == 'list') {
    $list = new list_show();
    $list->admin_list(
        $tagslist['tab'],
        count($tagslist['tab']),
        $tagslist['title'],
        'tag_label',
        'manage_tag_list_controller&mode=list',
        'tags','tag_label',
        true,
        $tagslist['page_name_up'],
        $tagslist['page_name_val'],
        $tagslist['page_name_ban'],
        $tagslist['page_name_del'],
        $tagslist['page_name_add'],
        $tagslist['label_add'],
        false,
        false,
        _ALL_TAGS,
        _TAG,
        $_SESSION['config']['businessappurl']
        . 'static.php?filename=manage_users_b.gif',
        true,
        true,
        false,
        true,
        $eventsList['what'],
        true,
        $eventsList['autoCompletionArray']
    );
} elseif ($mode == 'up' || $mode == 'add') {
    ?><h1><img src="<?php
    echo $_SESSION['config']['businessappurl'];
    ?>static.php?filename=manage_tags_b.gif" alt="" />
    <?php
        if ($mode == 'up') {
            echo _MODIFY_TAG;
        } elseif ($mode == 'add') {
            echo _ADD_TAG;
        }?>
    </h1>
    <div id="inner_content" class="clearfix" align="center">
        <br /><br />
    <?php
    if ($state == false) {
        echo '<br /><br /><br /><br />' . _THIS_EVENT . ' ' . _IS_UNKNOWN
        . '<br /><br /><br /><br />';
    } else {?>
    <form name="frmevent" id="frmevent" method="post" action="<?php
        echo $_SESSION['config']['businessappurl'] . 'index.php?display=true'
        . '&amp;module=tags&amp;page=manage_tag_list_controller&amp;mode='
        . $mode;?>" class="forms addforms">
        <input type="hidden" name="display" value="true" />
        <input type="hidden" name="admin" value="tags" />
        <input type="hidden" name="page" value="manage_tag_list_controler" />
        <input type="hidden" name="mode" value="<?php echo $mode;?>" />
        
        <input type="hidden" name="tag_label" id="tag_label" value="<?php echo $_SESSION['m_admin']['tag']['tag_label'];?>" />

        <input type="hidden" name="order" id="order" value="<?php
            echo $_REQUEST['order'];?>" />
        <input type="hidden" name="order_field" id="order_field" value="<?php
            echo $_REQUEST['order_field'];?>" />
        <input type="hidden" name="what" id="what" value="<?php
            echo $_REQUEST['what'];?>" />
        <input type="hidden" name="start" id="start" value="<?php
            echo $_REQUEST['start'];?>" />
       
		
		<p>
            <label for="label"><?php echo _NAME; ?> : </label>
            <input name="tag_label" type="text"  id="tag_label_id" value="<?php
                echo functions::show_str(
                    $_SESSION['m_admin']['tag']['tag_label']
                ); ?>"/>
        </p>
       
       
       <?php 
       if ($mode == 'up')
	   	{
		?>   		
	
		<p>
            <label for="label"><?php echo _COLL_ID; ?> : </label>
            <span><?php
                echo functions::show_str(
                    $_SESSION['m_admin']['tag']['tag_coll']
                ); ?></span>
        </p>
 		<?php
		}
	   else
	   	{
	   		$arrayColl = $_SESSION['m_admin']['tags']['coll_id'];
	   		?>
	   		  <p>
                <label for="collection"><?php  echo _COLLECTION;?> : </label>
                <select disabled name="collection" id="collection" >
                    <!--<option value="" ><?php  echo _CHOOSE_COLLECTION;?></option>-->
            <?php
            for ($i = 0; $i < count($arrayColl); $i ++) {
                ?>
                <option  value="<?php
                echo $arrayColl[$i]['id'];
                ?>" <?php
                if (isset($_SESSION['m_admin']['doctypes']['COLL_ID'])
                    && $_SESSION['m_admin']['doctypes']['COLL_ID'] == $arrayColl[$i]['id']
                ) {
                    echo 'selected="selected"';
                }
                ?> ><?php  echo $arrayColl[$i]['label'];?></option>
                <?php
            }
			
             ?>
             </select>
             </p>
        <?php
	   	}
		
		if ($mode == 'up')
	   	{
	  	
		?>
 		<p>
 	        <label for="label"><?php echo _NB_DOCS_FOR_THIS_TAG; ?> : </label>
            <span><?php
                echo functions::show_str(
                    $_SESSION['m_admin']['tag']['tag_count']
                ); ?></span>
        </p>
		<?php
		}
		
		?>
		
        <p class="buttons">
            <?php
        if ($mode == 'up') {?>
            <input class="button" type="submit" name="tag_submit" style="width:190px;" value=
			"<?php echo _MODIFY_TAG; ?>" />
            <?php
        } elseif ($mode == 'add') {?>
            <input type="submit" class="button"  name="tag_submit" value=
			"<?php echo _ADD; ?>" />
            <?php
        }
        ?>
        <input type="button" class="button"  name="cancel" value="<?php
         echo _CANCEL; ?>" onclick="javascript:window.location.href='<?php
         echo $_SESSION['config']['businessappurl'];
		 ?>index.php?page=manage_tag_list_controller&amp;mode=list&amp;module=tags'"/>
		
		<?php 
		if ($mode == 'up')
	   	{
	   		?>
			<hr/>
			<h3><?php echo _TAGOTHER_OPTIONS; ?></h3>
			<p>
	 	        <label for="label"><?php echo _TAG_FUSION_ACTIONLABEL; ?> : </label>
	            <select name="tagfusion" id="tagfusion">
        	    <?php
	            foreach ($_SESSION['tmp_all_tags'] as $tmp_selectvalue_tag) {
	                ?>
	                <option value="<?php
	                echo $tmp_selectvalue_tag['tag_label'].",".$tmp_selectvalue_tag['coll_id'];
	                 
	                ?> 
	                "><?php  echo $tmp_selectvalue_tag['tag_label']." ::".$tmp_selectvalue_tag['coll_id'];?></option>
	                <?php
	            }
			
             ?>
	         </select>
	       
	       <input type="button" class="button"  name="cancel" style="border-radius:8px;font-size:8px;" 
	       onclick = "tag_fusion('<?php echo $_SESSION['m_admin']['tag']['tag_label'].','
	       .$_SESSION['m_admin']['tag']['tag_coll']; ?>',
	        $('tagfusion').value, <?php echo $route_tag_fusion_tags;?>,'<?php 
	        echo _TAGFUSION_GOODRESULT; ?>' , '<?php
	        echo $_SESSION['config']['businessappurl'] . 'index.php?display=true'
        . '&amp;module=tags&amp;page=manage_tag_list_controller&amp;mode=list'
           ?>');"' value="<?php echo _TAGFUSION; ?> ">
	        
	       </p>   		
	   		<?php
		}	
			
		?>
		
	
	</p>


	


     </form >
<?php
    }
   ?></div><?php
   
  
}

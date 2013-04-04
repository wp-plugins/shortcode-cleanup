<?php
add_action( 'admin_menu', 'shortcode_cleanup_admin_menu' );

function shortcode_cleanup_admin_menu() {
	add_management_page( 'Shortcode Cleanup Settings', 'Shortcode Cleanup', 'manage_options', 'shortcode_cleanup_admin_page', 'shortcode_cleanup_admin_page' );
}

function shortcode_cleanup_admin_page() {
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}else{
	
	$messages=array();
	//Form Submit Handling //
	if(isset($_POST['sc_nonce'])){
		if (!wp_verify_nonce($_POST['sc_nonce'], 'sc-nonce') ){
			die('It seems as if you are trying to do something that is not allowed'); 
		}else{		
			if(isset($_POST['action'])){
				if(!isset($_POST['sc-posttype']) || !isset($_POST['sc-promise'])){				
					if(!isset($_POST['sc-posttype'])){
						$messages["You must select at least one post type.  Please try again.  "]= 'problem' ;	
					}
					if(!isset($_POST['sc-promise'])){
						$messages["You must verify that you have a backup.  Please try again.  "]= 'problem';
					}
				}else{
					$registered_tags=array();
					$unregistered_tags=array();
					$action = ""; 
					$args['post_args']=array('post_type'=>array_keys($_POST['sc-posttype']));
					if($_POST['action']=='Freeze Shortcodes'){
						$action = 'freeze';
						$registered_tags=($_POST['sc-freeze-tags']);			
					}elseif($_POST['action']=='Wrap Shortcodes'){
						$action = 'wrap';
						$registered_tags=($_POST['sc-wrap-tags']);
						if(isset($_POST['sc-unregistered-wrap'])){				
							foreach($_POST['sc-unregistered-wrap-tags'] as $key=>$tag){
								if(isset($_POST['sc-unregistered-wrap'][$key])){
									$unregistered_tags[]=$tag;
								}						
							}
						}
						$args['wrap_before'] = isset($_POST['sc-wrap-before']) ? $_POST['sc-wrap-before'] : "";
						$args['wrap_after'] = isset($_POST['sc-wrap-after']) ? $_POST['sc-wrap-after'] : "";
						
					}elseif($_POST['action']=='Replace Shortcodes'){
						$action = 'replace';
						$registered_tags=($_POST['sc-replace-tags']);
						if(isset($_POST['sc-unregistered-replace'])){				
							foreach($_POST['sc-unregistered-replace-tags'] as $key=>$tag){
								if(isset($_POST['sc-unregistered-replace'][$key])){
									$unregistered_tags[]=$tag;
								}						
							}
						}
						$args['replacement'] = isset($_POST['sc-replacement-text']) ? $_POST['sc-replacement-text'] : "";					
					}elseif($_POST['action']=='Delete Shortcodes'){
						$action = 'delete';
						$registered_tags=($_POST['sc-delete-tags']);
						
						if(isset($_POST['sc-unregistered-delete'])){				
							foreach($_POST['sc-unregistered-delete-tags'] as $key=>$tag){
								if(isset($_POST['sc-unregistered-delete'][$key])){
									$unregistered_tags[]=$tag;
								}						
							}				
						}
						$args['delete_what']=isset($_POST['sc-delete']) ? $_POST['sc-delete'] : "";  
					}
					$cleanup=new scShortcodeCleanup($action, $registered_tags, $unregistered_tags, $args);		
					$messages=array_merge($messages, $cleanup->messages);
				}
			}
		} 
	}
	
	
	//Admin Page Contents//
	
	global $shortcode_tags;
	
	$wordpress_shortcodes = array( '__return_false', 'img_caption_shortcode', 'gallery_shortcode' );
	$shortcodes= array_diff($shortcode_tags, $wordpress_shortcodes);
	$actions=array('replace'=>'Replaces selected shortcodes with the replacement text that you provide below.  ', 'wrap'=>'Wraps selected shortcodes in the text you provide below.  ', 'delete'=>'Deletes selected shortcodes, and optionally, the content they contain.  ', 'freeze'=>'Replaces selected shortcodes with thier current static output.  In most cases with shortcodes associated with plugins and themes the static output remains reliant on the CSS and JavaScript of the plugin/theme.  <br/><br/><div class="note" >A special note about nested shortcodes: Some nested shortcodes cannot function independently.  To avoid unexpected behavior (like unwanted deletions or error messages), make sure to select both the inner and the outer shortcode when freezing nested shortcodes. </div>' );
	$pagenonce=wp_create_nonce('sc-nonce'); 
	?>
	
	<style type="text/css" >
	h3.handle{
		padding:7px 10px;
		cursor:pointer!important;
	}
	.sc_form{
		margin:10px;
	}
	.sc_form textarea{
		width:600px;
		height:200px;	
	}
	table{
		width:100%;
	}
	.sc_td_checkbox_unregistered input[type=checkbox]{
		margin: 5px 5% 0 0;
	}
	.sc_td_checkbox_unregistered label{
		margin-left:5%;
	}
	.sc_td_checkbox_unregistered label.sc_unregistered_tag_label{
		margin-left:-13px;
	}
	.sc_td_checkbox_unregistered{
		padding-bottom:15px;
	}
	.red{
		color:red;
	}
	.sc-message.problem{
		background-color:#DF6565;
	}
	.sc-message.success{
		background-color:#2e9fd2;
	}
	.sc-message{
		border-radius: 3px;
	}
	.sc-message p{
		padding:25px;
		color:#fff;
		font-size:16px;
		line height:18px;
		text-shadow: rgba(0,0,0,0.25) 1px 1px 0;
	}
	.note{
		font-size:80%;	
	}
	</style>
	<script type="text/javascript" >
		jQuery(document).ready( function(){
			jQuery('.content-box').hide();
			jQuery('.handlediv, h3.handle').on("click", function(){ jQuery(this).siblings('.content-box').toggle();});
		});
		
		function unDisableSubmit(obj, submitbutton){
               submitbutton.disabled=true; 
               if (obj.checked){submitbutton.disabled=false;}
		}			
	</script>
	
	<div class="wrap">	
	<h2>Shortcode Cleanup Utility</h2>
	<?php foreach($messages as $message => $type){ ?>
		<div class="sc-message <?php echo $type ?>" ><?php echo '<p>'.$message.'</p>'; ?></div>
	<?php } ?>
	<?php foreach($actions as $action=>$description){ ?>
		<div class="sc_action_tab postbox" id="sc-action-tab-<?php echo $action ?>"  >
			<div class="handlediv" title="clicktotoggle" >
				<br/>
			</div>
			<h3 class="handle" ><?php echo ucfirst($action) ; ?></h3>
			<div class="content-box" >
				<form class="sc_form"  method="POST" action="">
					<input type="hidden" name="sc_nonce" value="<?php echo  $pagenonce; ?>" />
					<table id="sc_checktable" >
						<tr>
							<td>
							<h4><?php echo $description; ?> </h4>
							<h5><?php echo ucfirst($action); ?> Registered Shortcodes:</h5>

							</td>
						</tr>
						<tr>
							<td colspan="2" class="sc_td_checkbox_<?php echo $action ?>" >
								<table>
								<?php 
								$eye=0;
								 foreach($shortcodes as $shortcode=>$function){
									if($eye%4==0){ ?>
										<tr>
									<?php	}	?>
									<td>
										<label for="<?php echo $shortcode.$action ?>" ><input type="checkbox" id="<?php echo $shortcode.$action ?>" name="<?php echo 'sc-'.$action.'-tags' ?>[]" value="<?php echo $shortcode; ?>" />&nbsp;&nbsp;<?php echo $shortcode ?></label>
									</td>
									<?php if($eye%4==3){ ?>
										</tr>
									<?php	}
									$eye++;							
								}
								if($eye%4!==0){
									while($eye%4!==0){?>
										<td></td>
										<?php	$eye++;
									} ?>
									</tr>
								<?php } ?>
								</table>
							</td>
						</tr>
						<?php if(!($action=='freeze')){ ?>
						<tr>
							<td colspan="2" >
								<h6>The shortcodes listed above are the ones that are currently registered on your site.  Below, you can list and select shortcodes for cleanup that are not currently registered, for example shortcodes from a theme that has since been uninstalled.  </h6>
								<h5><?php echo ucfirst($action); ?> Unregistered Shortcodes:</h5>
								
							</td>
						</tr>
						<?php for($n=0; $n<5; $n++){ ?>
						<tr>
							<td class="sc_td_checkbox_unregistered"  colspan="2" >
								<input type="checkbox"  name="<?php echo 'sc-unregistered-'.$action.'['.$n.']' ; ?>" value="true" /><label class="sc_unregistered_tag_label" for="<?php echo 'sc-unregistered-'.$action.'-tag-'.$n ; ?>" >Tag Name:
								<input type="text" id="<?php echo 'sc-unregistered-'.$action.'-tag-'.$n ; ?>" name="<?php echo 'sc-unregistered-'.$action.'-tags['.$n.']' ; ?>" ></label>
							</td>
						</tr>
						<?php } ?>
						<?php } 
						if($action=='wrap'){
							?>	
							<tr>
								<td colspan="2" >
									<h5>Wrapper </h5>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<div><label for="sc-wrap-before" >Before Shortcode<br/><textarea id="sc-wrap-before" name="sc-wrap-before"></textarea></div>
									<div><label for="sc-wrap-after" >After Shortcode<br/><textarea id="sc-wrap-after" name="sc-wrap-after"></textarea></div>
								</td>
							</tr>
						<?php }elseif($action =='delete'){ ?>
							<tr>
								<td colspan="2">
									<input type="radio" name="sc-delete" checked="checked" value="tags_only">&nbsp;&nbsp;Shortcode tags only<br>
									<input type="radio" name="sc-delete" value="all">&nbsp;&nbsp;Shortcode tags and content contained in shortcode tags
								</td>
							</tr>
						<?php }elseif($action=='replace'){ ?>
							<tr>
								<td colspan="2">
									<h5>Replacement text/HTML:</h5>
									<textarea id="sc-replacement-text" name="sc-replacement-text" ></textarea>
								</td>
							</tr> 
						<?php } ?>
						<tr>
							<td>
								<h5 for="on">Perform action on the following post types: </h5>
								<?php
								$post_types=get_post_types(array(  'public'   => true ),'names'); 
								foreach ($post_types as $key=>$post_type ) { ?>
								 <label class="sc_post_type" for="<?php echo $post_type ?>" ><input type="checkbox" id="<?php echo $post_type ?>" name="sc-posttype[<?php echo $post_type ?>]" value="true" />&nbsp;&nbsp;<?php echo $post_type ?></label><br/>
								<?php }	?>								
							</td>
						</tr>
					</table>
					<p>
					<label for ="sc-promise" ><input type="checkbox"  name="sc-promise" id="sc-promise" value="true" onclick="unDisableSubmit(this,this.form['submit-<?php echo $action ; ?>']);"/>&nbsp;&nbsp;I understand that the changes this plugin makes are <strong class="red" >not reversible</strong> and I promise that I have a recent, complete backup of my site. </label> </p>
					<input type="Submit" disabled="disabled" name="action" id="submit-<?php echo $action ; ?>" value="<?php echo ucfirst($action); ?> Shortcodes" />
				</form>
			</div>
		</div>
	<?php } ?>
	</div>
	<?php 
	}
}
?>
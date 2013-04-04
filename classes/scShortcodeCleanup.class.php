<?php
if(!class_exists('scShortcodeCleanup')){
	class scShortcodeCleanup{

		private $action, $registered_tags, $unregistered_tags, $args, $post_args, $sc_query, $wrap_before, $wrap_after, $replacement, $delete_what;
		
		public $messages;
		
		function __construct($action, $registered_tags, $unregistered_tags, $args){	
			$this->messages=array();
			$this->args=$args;
			$this->action = $action;
			$this->registered_tags = is_array($registered_tags)? array_flip($registered_tags) : array();//registered tags is now in the format 'tag_name'=>'n' ;
			$this->unregistered_tags = is_array($unregistered_tags)? array_fill_keys(array_keys(array_flip($unregistered_tags)), create_function('',  'return "";')) : array();	//registered tags is now in the format 'tag_name'=>"lambda_xxxx" ;

			$this->wrap_before = isset( $this->args['wrap_before'] ) && is_string( $this->args['wrap_before'] ) ? $this->args['wrap_before'] : "";
			$this->wrap_after = isset( $this->args['wrap_after'] ) && is_string( $this->args['wrap_after'] ) ? $this->args['wrap_after'] : "";
			$this->delete_what = isset ($this->args['delete_what']) && is_string( $this->args['delete_what']) ? $this->args['delete_what'] : "";
			$this->replacement = isset( $this->args['replacement'] ) && is_string( $this->args['replacement'] ) ? $this->args['replacement'] : "";
			$this->post_args=array('posts_per_page' => '-1');
			$this->post_args = isset( $this->args['post_args'] ) ? array_merge($this->args['post_args'], $this->post_args) : $this->post_args;
			$this->sc_query=get_posts($this->post_args);

			if( $this->action=='wrap' ){ 
				$this->wrap_shortcodes();
			}elseif( $this->action=='freeze' ){ 
				$this->freeze_shortcodes();
			}elseif( $this->action=='replace' ){ 
				$this->replace_shortcodes();
			}elseif( $this->action=='delete' ){ 
				$this->delete_shortcodes();
			}
		}
		
		private function make_message($scts, $verb){
			$message = "Nothing Happened";
			$tags = ""; 
			$post_types="";
			foreach($scts as $tag => $function){
				$tags .= $tag . '<br/>';
			}
			foreach($this->post_args['post_type'] as $key=>$type){
				if(!(count($this->post_args['post_type'])==1)){			
					if(!($key==count($this->post_args['post_type'])-1)){
						$post_types .= $type . ', ';
					}else{
						$post_types .= 'or ' . $type ;
					}
				}else{
					$post_types .= $type ;
				}
			}
			if(!empty($tags)){
				$message = "The following shortcodes were {$verb} in posts with type {$post_types}:<br/>{$tags}";
			}
			$this->messages = array($message => 'success');
		}
		
		private function wrap_shortcodes(){
			global $shortcode_tags;
			$save_shortcode_tags=$shortcode_tags;
			$shortcode_tags=array_merge(array_intersect_key($shortcode_tags, $this->registered_tags ), $this->unregistered_tags); // reduce shortcode_tags to only the ones we selected plus the add-ons (this affects get_shortcode_regex)
			if(!empty($shortcode_tags)){
				$pattern = get_shortcode_regex();
				foreach($this->sc_query as $p){ 
					setup_postdata($p); 
					$upd_post = array();
					$upd_post['ID'] = $p->ID;
					$replacement = $this->wrap_before . '$0' . $this->wrap_after;
					$upd_post['post_content'] = preg_replace( "/$pattern/s", $replacement ,  $p->post_content);
					wp_update_post($upd_post); 
				}

			}
			$this->make_message($shortcode_tags, 'wrapped' );
			$shortcode_tags = $save_shortcode_tags; // put the global $shortcode_tags back the way we found it
		}
		
		private function replace_shortcodes(){
			global $shortcode_tags;
			$save_shortcode_tags=$shortcode_tags;
			$shortcode_tags=array_merge(array_intersect_key($shortcode_tags, $this->registered_tags ), $this->unregistered_tags); // reduce shortcode_tags to only the ones we selected plus the add-ons (this affects get_shortcode_regex)
			if(!empty($shortcode_tags)){
				$pattern = get_shortcode_regex();
				foreach($this->sc_query as $p){ 
					setup_postdata($p); 
					$upd_post = array();
					$upd_post['ID'] = $p->ID;
					$upd_post['post_content'] = preg_replace( "/$pattern/s", $this->replacement ,  $p->post_content);
					wp_update_post($upd_post); 
				}
			}
			$this->make_message($shortcode_tags, 'replaced' );
			$shortcode_tags=$save_shortcode_tags; // put the global $shortcode_tags back the way we found it
		
		}
		
		
		private function freeze_shortcodes(){
			global $shortcode_tags;
			$save_shortcode_tags=$shortcode_tags;
			$shortcode_tags=array_intersect_key($shortcode_tags, $this->registered_tags ); // reduce shortcode_tags to only the ones we selected(this affects do_shortcode)
			if(!empty($shortcode_tags)){
				foreach($this->sc_query as $p){ 
					setup_postdata($p); 
					$upd_post = array();
					$upd_post['ID'] = $p->ID;
					$upd_post['post_content'] = do_shortcode($p->post_content);
					wp_update_post($upd_post); 
				}
			}
			$this->make_message($shortcode_tags, 'frozen' );
			$shortcode_tags=$save_shortcode_tags; // put the global $shortcode_tags back the way we found it	
		}


		private function delete_shortcodes(){
			global $shortcode_tags;
			$save_shortcode_tags=$shortcode_tags;
			$shortcode_tags=array_merge(array_intersect_key($shortcode_tags, $this->registered_tags ), $this->unregistered_tags); // reduce shortcode_tags to only the ones we selected plus the add-ons (this affects strip_shortcodes)
			if(!empty($shortcode_tags)){
				if ($this->delete_what == 'all'){
					foreach($this->sc_query as $p){ 
						setup_postdata($p); 
						$upd_post = array();
						$upd_post['ID'] = $p->ID;
						$upd_post['post_content'] = strip_shortcodes($p->post_content); //replace each occurence of the shortcode(s) with an empty string
						wp_update_post($upd_post); 
					}
				}elseif ($this->delete_what == 'tags_only'){
					$pattern = get_shortcode_regex();
					foreach($this->sc_query as $p){ 
						setup_postdata($p); 
						$upd_post = array();
						$upd_post['ID'] = $p->ID;
						$upd_post['post_content'] = preg_replace( "/$pattern/s", "$5" ,  $p->post_content);//replace each occurence of the shortcode(s) with contents only
						wp_update_post($upd_post); 
					}			
				}
			}
			$this->make_message($shortcode_tags, 'deleted' );
			$shortcode_tags=$save_shortcode_tags; // put the global $shortcode_tags back the way we found it
		}		
	}	
} 
?>
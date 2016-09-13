<?php

/*
 * Controller name: MU
 * Controller description: Blog creation controller for JSON API
 * Controller Author: Quique Torras
 */

class JSON_API_MU_Controller {

	/* Creates a new blog calling wpmu_create_blog
	 * the wpmu_create_blog parameters are:
	 * $domain  The domain of the new blog.
	 * $path    The path of the new blog.
	 * $title   The title of the new blog.
	 * $user_id The user id of the user account who will be the blog admin. (you can use an email instead of the user_id. If so, a new user will be created)
	 * $username The Username if we go to create a new user
	 * $password The password if we go to create a new user
	 * $meta    Other meta information.
	 * $site_id The site_id of the blog to be created.
	 */
    public function create(){
   	    global $json_api;
		$charset = get_option('blog_charset');

		if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
			header("HTTP/1.1 403 Forbidden");
			header("Content-Type: application/json; charset=$charset", true);
			flush();
			$json_api->error("You are not authorized", 403);
		}

        $parameters['domain'] = sanitize_text_field($_REQUEST['domain']);
        $parameters['path'] = sanitize_text_field($_REQUEST['path']);
        $parameters['title'] = sanitize_text_field($_REQUEST['title']);
        $parameters['user_id'] = sanitize_text_field($_REQUEST['user_id']);
        $parameters['username'] = sanitize_text_field($_REQUEST['username']);
        $parameters['meta'] = sanitize_text_field($_REQUEST['meta']);
        $parameters['site_id'] = sanitize_text_field($_REQUEST['site_id']);
        $parameters['password'] = sanitize_text_field($_REQUEST['password']);

        if ('' == $parameters['site_id']) $parameters['site_id'] = 1;

		if ('' == $parameters['domain']) {
                        header("HTTP/1.1 400 Params error");
                        header("Content-Type: application/json; charset=$charset", true);
                        flush();
                        $json_api->error("You must include 'domain' var in your request. ", 400);
		}
		if ('' == $parameters['path']) {
                        header("HTTP/1.1 400 Params error");
                        header("Content-Type: application/json; charset=$charset", true);
                        flush();
                        $json_api->error("You must include 'path' var in your request. ", 400);
		}
		if ('' == $parameters['user_id']) {
                        header("HTTP/1.1 400 Params error");
                        header("Content-Type: application/json; charset=$charset", true);
                        flush();
                        $json_api->error("You must include 'user_id' var in your request. ", 400);
		}
		if ('' == $parameters['username']) {
			header("HTTP/1.1 400 Params error");
	        header("Content-Type: application/json; charset=$charset", true);
        	flush();
			$json_api->error("You must include 'username' var in your request. ", 400);
		}
        // if the user_id is the user's e-mail
           if (!is_int($parameters['user_id']) ) {
            if (!($user_id = get_user_id_from_string($parameters['user_id'])) ) {
                $error = wpmu_validate_user_signup(
                        $parameters['username'],
                        $parameters['user_id']
                );

                if ('' != $error['errors']->get_error_code()) {
                    header("HTTP/1.1 400 Bad params");
                    header("Content-Type: application/json; charset=$charset", true);
                        flush();
                $json_api->error($error['errors'], 400);
                }
                if ('' == $parameters['password']) {
                    $parameters['password'] = wp_generate_password();
                }
                $user_id = wpmu_create_user(
                        $parameters['username'],
                        $parameters['password'],
                        $parameters['user_id']
                );
            }
            // User found by email, set user_id param with user id
            $parameters['user_id'] = $user_id;
        }
        else {
            // Comprobar que existe el id
        }
        if ($this->findBlog($parameters['domain'], $parameters['path']) !== false) {
            header("HTTP/1.1 409 Site already exists");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("Site already exists ", 409);
        }

        $id_blog = wpmu_create_blog(
                $parameters['domain'],
                $parameters['path'],
                $parameters['title'],
                $parameters['user_id'],
                $parameters['meta'],
                $parameters['site_id']
        );
        return array('blog_id' => $id_blog, 'user_id' => $user_id, 'path' => $parameters['path'] );

    }
     public function setLDAPLogin() {
       $user_id = $_REQUEST['user_id'];
       if ('' != get_user_meta($user_id, 'ldap_login')) {
               update_user_meta($user_id, 'ldap_login', 'true');
       }
       else {
               add_user_meta($user_id, 'ldap_login', 'true');
       }
       return array();
   }


    public function getBlogId()
    {
	    global $json_api;

        $domain = sanitize_text_field( $_REQUEST['domain'] );
        $path = sanitize_text_field( $_REQUEST['path'] );

        if ('' == $domain || '' == $path) {
        	header("HTTP/1.1 400 Bad params");
			header("Content-Type: application/json; charset=$charset", true);
	        $json_api->error("You must include 'domain' and 'path' var in your request.", 400);
        }

        return array($this->findBlog($domain, $path));
    }

    private function findBlog($domain, $path)
    {
        global $wpdb;
        $domain_found = $wpdb->get_results($wpdb->prepare(
            "SELECT blog_id FROM wp_blogs WHERE domain = %s AND path = %s LIMIT 1",
            $domain,
            $path . '/'
        ));

        if ( !count($domain_found) ){
           return false;
        }
        return array('blog_id' => $domain_found[0]->blog_id);
    }

		/* Get all User's Blogs
		 * the findBlogs parameters are:
		 * $user_id The user id of the user account who will be the blog admin. (you can use an email instead of the user_id. If so, a new user will be created)
		 */
		private function findBlogs($user_id)
    {
        return get_blogs_of_user($user_id);
    }

     public function createUser(){
        global $json_api;
        $charset = get_option('blog_charset');

        if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
            header("HTTP/1.1 403 Forbidden");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You are not authorized", 403);
        }


        $parameters['user_id'] = sanitize_text_field($_REQUEST['user_id']);
        $parameters['username'] = sanitize_text_field($_REQUEST['username']);


        if ('' == $parameters['user_id']) {
            header("HTTP/1.1 400 Params error");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You must include 'user_id' var in your request. ", 400);
        }
        if ('' == $parameters['username']) {
            header("HTTP/1.1 400 Params error");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You must include 'username' var in your request. ", 400);
        }
        // if the user_id is the user's e-mail
        if (!is_int($parameters['user_id']) ) {
            // if user exist
            if ($user = get_user_by('email',$parameters['user_id']) ) {
                $user_id = $user->ID;
         // if user not exist
        } else {
            $error = wpmu_validate_user_signup(
                    $parameters['username'],
                    $parameters['user_id']
                );

            if ('' != $error['errors']->get_error_code()) {
                header("HTTP/1.1 400 Bad params");
                header("Content-Type: application/json; charset=$charset", true);
                flush();
                $json_api->error($error['errors']->get_error_code(), 400);
            }
            if ('' == $parameters['password']) {
                $parameters['password'] = wp_generate_password();
            }
            $user_id = wpmu_create_user(
                $parameters['username'],
                $parameters['password'],
                $parameters['user_id']
            );

        }

        return array('user_id' => $user_id);

        }

    }

    public function userEnroll(){
        global $json_api;
        $charset = get_option('blog_charset');

        if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
            header("HTTP/1.1 403 Forbidden");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You are not authorized", 403);
        }

        $parameters['blog_id'] = sanitize_text_field($_REQUEST['blog_id']);
        //User id number
        $parameters['id'] = sanitize_text_field($_REQUEST['id']);

        if ('' == $parameters['id']) {
            header("HTTP/1.1 400 Params error");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You must include 'id' var in your request. ", 400);
        }
        if ('' == $parameters['blog_id']) {
            header("HTTP/1.1 400 Params error");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You must include 'blog_id' var in your request. ", 400);
        }
        //Checks if the user is already a member of the blog
        if(is_user_member_of_blog($parameters['id'],$parameters['blog_id'])){
            header("HTTP/1.1 409 The user is already a member of the blog");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("The user is already a member of the blog", 409);
        }
        //Checks if the blog exists
        if(!get_blog_details($parameters['blog_id'])){
            header("HTTP/1.1 404 Not found");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("Blog not found", 404);
        }
        //Associates a user to a blog with 'Autor' role
        $enroll = add_user_to_blog($parameters['blog_id'],$parameters['id'],'author');

        //Checks if the user exists
        if(!$enroll){
            header("HTTP/1.1 400 Bad params");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            die;
        }

        return array();
    }

		/* Get all User's Blogs
		 * the getUserBlogs parameters are:
		 * @method GET/POST
		 * @param $user_id The user id of the user account who will be the blog admin. (you can use an email instead of the user_id. If so, a new user will be created)
		 * @return array of blogs
		 */
		public function getUserBlogs() {
			global $json_api;
			$charset = get_option('blog_charset');

			if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
					header("HTTP/1.1 403 Forbidden");
					header("Content-Type: application/json; charset=$charset", true);
					flush();
					$json_api->error("You are not authorized", 403);
			}

			$parameters['user_id'] = sanitize_text_field($_REQUEST['user_id']);

			if ($user = get_user_by('login',$parameters['user_id']) ) {
					$blogs = $this->findBlogs($user->ID);

					return array('blogs' => $blogs);
			} else {
					header("HTTP/1.1 404 User not found");
					header("Content-Type: application/json; charset=$charset", true);
					flush();
					$json_api->error("User not found ", 404);
			}
		}

		/* Check if user is member of the blog
		 * the checkUserInBlog parameters are:
		 * @method GET/POST
		 * @param $user_id The user id of the user account who will be the blog admin. (you can use an email instead of the user_id. If so, a new user will be created)
		 * @param $domain  The domain of the new blog.
		 * @param $path    The path of the new blog.
		 * @return array
		 */
		public function checkUserInBlog(){
			global $json_api;
			$charset = get_option('blog_charset');

			if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
					header("HTTP/1.1 403 Forbidden");
					header("Content-Type: application/json; charset=$charset", true);
					flush();
					$json_api->error("You are not authorized", 403);
			}

			$parameters['user_id'] = sanitize_text_field($_REQUEST['user_id']);
			$parameters['domain'] = sanitize_text_field($_REQUEST['domain']);
			$parameters['path'] = sanitize_text_field($_REQUEST['path']);

			// if user exist
			if ($user = get_user_by('login',$parameters['user_id']) ) {
					$user_id = $user->ID;
					//Searching blog
					$blog_id = $this->findBlog($parameters['domain'], $parameters['path']);

					if ($blog_id == false) {
							header("HTTP/1.1 404 Site not found");
							header("Content-Type: application/json; charset=$charset", true);
							flush();
							$json_api->error("Site not found ", 404);
					}

					//Checks if the user is already a member of the blog
					if(!is_user_member_of_blog($user_id, $blog_id)){
							header("HTTP/1.1 404 The user is not a member of the blog");
							header("Content-Type: application/json; charset=$charset", true);
							flush();
							$json_api->error("The user is not a member of the blog", 404);
					} else {
							return array(
								'user_id' => $user_id,
								'blog_id' => $blog_id,
								'domain' => $parameters['domain'],
								'path' => $parameters['path']
							);
					}
			} else {
					header("HTTP/1.1 404 User not found");
					header("Content-Type: application/json; charset=$charset", true);
					flush();
					$json_api->error("User not found ", 404);
			}
		}

    public function checkPath(){
        global $json_api;
        $charset = get_option('blog_charset');

        if (sanitize_text_field($_REQUEST['apikey']) != get_option('wp_mu_apikey')) {
            header("HTTP/1.1 403 Forbidden");
            header("Content-Type: application/json; charset=$charset", true);
            flush();
            $json_api->error("You are not authorized", 403);
        }

        $parameters['domain'] = sanitize_text_field($_REQUEST['domain']);
        $parameters['path'] = sanitize_text_field($_REQUEST['path']);


        if ('' == $parameters['domain']) {
                        header("HTTP/1.1 400 Params error");
                        header("Content-Type: application/json; charset=$charset", true);
                        flush();
                        $json_api->error("You must include 'domain' var in your request. ", 400);
        }
        if ('' == $parameters['path']) {
                        header("HTTP/1.1 400 Params error");
                        header("Content-Type: application/json; charset=$charset", true);
                        flush();
                        $json_api->error("You must include 'path' var in your request. ", 400);
        }
        //Checks if blog is active with domain and path parameters
        $addres = domain_exists($parameters['domain'],$parameters['path']);
        //If blog exists return 1 else return 0
        (!$addres) ? $active = 0 : $active = 1;

        return array('Active' => $active);
    }


}

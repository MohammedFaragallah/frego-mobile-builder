<?php

use JWTAuth\Auth;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       github.com/MohammedFaragallah
 * @since      1.0.0
 *
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Frego_Mobile_Builder
 * @subpackage Frego_Mobile_Builder/public
 * @author     Mohammed Faragallah <o0frego0o@hotmail.com>
 */
class Frego_Mobile_Builder_Public {








	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $token_schema = array(
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'Get Authentication Properties',
		'type'       => 'object',
		'properties' => array(
			'token' => array(
				'type'        => 'string',
				'description' => 'JWT token',
			),
		),
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function get_token_schema() {
		return $this->token_schema;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		   * This function is provided for demonstration purposes only.
		   *
		   * An instance of this class should be passed to the run() function
		   * defined in Frego_Mobile_Builder_Loader as all of the hooks are defined
		   * in that particular class.
		   *
		   * The Frego_Mobile_Builder_Loader will then create the relationship
		   * between the defined hooks and the functions defined in this
		   * class.
		   */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/frego-mobile-builder-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		 /**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Frego_Mobile_Builder_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Frego_Mobile_Builder_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/frego-mobile-builder-public.js', array( 'jquery' ), $this->version, false );
	}


	/**
	 * Registers a REST API route.
	 */
	public function add_api_routes() {
		$namespace = $this->plugin_name . '/v' . intval( $this->version );
		$review    = new WC_REST_Product_Reviews_Controller();
		$customer  = new WC_REST_Customers_Controller();
		$user      = new WP_REST_Users_Controller();

		register_rest_route(
			$namespace,
			'reviews',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $review, 'create_item' ),
					'permission_callback' => '__return_true',
					'args'                => array_merge(
						( array( $review, 'get_endpoint_args_for_item_schema' ) )(
							WP_REST_Server::CREATABLE
						),
						array(
							'product_id'     => array(
								'required'    => true,
								'description' => __(
									'Unique identifier for the product.',
									'woocommerce'
								),
								'type'        => 'integer',
							),
							'review'         => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __(
									'Review content.',
									'woocommerce'
								),
							),
							'reviewer'       => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __(
									'Name of the reviewer.',
									'woocommerce'
								),
							),
							'reviewer_email' => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __(
									'Email of the reviewer.',
									'woocommerce'
								),
							),
						)
					),
				),
				'schema' => array( $review, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'customers/(?P<id>[\\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $customer, 'update_item' ),
					'permission_callback' => array(
						$this,
						'update_item_permissions_check',
					),
					'args'                => ( array( $customer, 'get_endpoint_args_for_item_schema' ) )(
						WP_REST_Server::EDITABLE
					),
				),
				'schema' => array( $customer, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'login',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'login' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'Login name for the user.',
						),
						'password' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'Password for the user.',
						),
					),
				),
				'schema' => array( $this, 'get_token_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'facebook',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'login_facebook' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'token' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'Facebook token.',
						),
					),
				),
				'schema' => array( $this, 'get_token_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'google',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'login_google' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'token' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'Facebook token.',
						),
					),
				),
				'schema' => array( $this, 'get_token_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'register',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $user, 'create_item' ),
					'permission_callback' => function ( $request ) {
						$request->set_param( 'roles', array( 'customer' ) );

						return true;
					},
					'args'                => $user->get_endpoint_args_for_item_schema(
						WP_REST_Server::CREATABLE
					),
				),
				'schema' => array( $user, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'lost-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'retrieve_password' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'user_login' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'change-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'change_password' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'password_old' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => 'The plaintext old user password',
					),
					'password_new' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => 'The plaintext new user password',
					),
				),
			)
		);
	}


	/**
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function change_password( $request ) {
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return new WP_Error(
				'user_not_login',
				__( 'Please login first.', 'frego-mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$username     = $current_user->user_login;
		$password_old = $request->get_param( 'password_old' );
		$password_new = $request->get_param( 'password_new' );

		// try login with username and password
		$user = wp_authenticate( $username, $password_old );

		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();

			return new WP_Error(
				$error_code,
				$user->get_error_message( $error_code ),
				array(
					'status' => 403,
				)
			);
		}

		wp_set_password( $password_new, $current_user->ID );

		return $current_user->ID;
	}

	/**
	 * Lost password for user.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function retrieve_password( $request ) {
		$errors = new WP_Error();

		$user_login = $request->get_param( 'user_login' );

		if ( empty( $user_login ) || ! is_string( $user_login ) ) {
			$errors->add( 'empty_username', __( '<strong>Error</strong>: The email field is empty.' ) );
		} elseif ( strpos( $user_login, '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $user_login ) ) );
			if ( empty( $user_data ) ) {
				$errors->add( 'invalid_email', __( '<strong>Error</strong>: There is no account with that username or email address.' ) );
			}
		} else {
			$login     = trim( $user_login );
			$user_data = get_user_by( 'login', $login );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		if ( ! $user_data ) {
			$errors->add( 'invalidcombo', __( '<strong>Error</strong>: There is no account with that username or email address.' ) );

			return $errors;
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
		$key        = get_password_reset_key( $user_data );

		if ( is_wp_error( $key ) ) {
			return $key;
		}

		if ( is_multisite() ) {
			$site_name = get_network()->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */
			$site_name = wp_specialchars_decode(
				get_option( 'blogname' ),
				ENT_QUOTES
			);
		}

		$message =
			__(
				'Someone has requested a password reset for the following account:',
				'frego-mobile-builder'
			) . "\r\n\r\n";
		// translators: %s: site name
		$message .=
			sprintf( __( 'Site Name: %s', 'frego-mobile-builder' ), $site_name ) .
			"\r\n\r\n";
		// translators: %s: user login
		$message .=
			sprintf( __( 'Username: %s', 'frego-mobile-builder' ), $user_login ) .
			"\r\n\r\n";
		$message .=
			__(
				'If this was a mistake, just ignore this email and nothing will happen.',
				'frego-mobile-builder'
			) . "\r\n\r\n";
		$message .=
			__(
				'To reset your password, visit the following address:',
				'frego-mobile-builder'
			) . "\r\n\r\n";
		$message .=
			'<' .
			network_site_url(
				"wp-login.php?action=rp&key={$key}&login=" .
					rawurlencode( $user_login ),
				'login'
			) .
			">\r\n";

		// translators: Password reset notification email subject. %s: Site title
		$title = sprintf(
			__( '[%s] Password Reset', 'frego-mobile-builder' ),
			$site_name
		);

		/**
		 * Filters the subject of the password reset email.
		 *
		 * @param string  $title      default email title
		 * @param string  $user_login the username for the user
		 * @param WP_User $user_data  WP_User object
		 */
		$title = apply_filters(
			'retrieve_password_title',
			$title,
			$user_login,
			$user_data
		);

		/**
		 * Filters the message body of the password reset mail.
		 *
		 * If the filtered message is empty, the password reset email will not be sent.
		 *
		 * @param string  $message    default mail message
		 * @param string  $key        the activation key
		 * @param string  $user_login the username for the user
		 * @param WP_User $user_data  WP_User object
		 */
		$message = apply_filters(
			'retrieve_password_message',
			$message,
			$key,
			$user_login,
			$user_data
		);

		if (
			$message
			&& ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message )
		) {
			return new WP_Error(
				'send_email',
				__(
					'Possible reason: your host may have disabled the mail() function.',
					'frego-mobile-builder'
				),
				array(
					'status' => 403,
				)
			);
		}

		return true;
	}

	public function getUrlContent( $url ) {
		$parts  = parse_url( $url );
		$host   = $parts['host'];
		$ch     = curl_init();
		$header = array(
			'GET /1575051 HTTP/1.1',
			"Host: {$host}",
			'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language:en-US,en;q=0.8',
			'Cache-Control:max-age=0',
			'Connection:keep-alive',
			'Host:adfoc.us',
			'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
		);

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		$result = curl_exec( $ch );
		curl_close( $ch );

		return $result;
	}

	/**
	 * Login with google.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function login_google( $request ) {
		$idToken = $request->get_param( 'idToken' );

		$url  = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
		$data = array( 'idToken' => $idToken );

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header' => "application/json; charset=UTF-8\r\n",
				'method' => 'GET',
			),
		);

		$context = stream_context_create( $options );
		$json    = $this->getUrlContent( $url );
		$result  = json_decode( $json );

		if ( false === $result ) {
			$error = new WP_Error();
			$error->add(
				403,
				__( 'Get Firebase user info error!', 'frego-mobile-builder' ),
				array( 'status' => 400 )
			);

			return $error;
		}

		// Email not exist
		$email = $result->email;
		if ( ! $email ) {
			return new WP_Error(
				'email_not_exist',
				__( 'User not provider email', 'frego-mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'email', $email );

		// Return data if user exist in database
		if ( $user ) {
			$auth  = new Auth();
			$token = $auth->generate_token( $user );

			return array(
				'token' => $token,
			);
		}
		$user_id = wp_insert_user(
			array(
				'user_pass'     => wp_generate_password(),
				'user_login'    => $result->email,
				'user_nicename' => $result->name,
				'user_email'    => $result->email,
				'display_name'  => $result->name,
				'first_name'    => $result->given_name,
				'last_name'     => $result->family_name,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$error_code = $user && $user->get_error_code();

			return new WP_Error(
				$error_code,
				$user_id->get_error_message( $error_code ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'id', $user_id );

		$auth  = new Auth();
		$token = $auth->generate_token( $user );

		$data = array(
			'token' => $token,
		);

		add_user_meta( $user_id, 'mbd_login_method', 'google', true );
		add_user_meta( $user_id, 'mbd_avatar', $result->picture, true );

		return $data;
	}


	/**
	 * @param WP_REST_Request $request Request object.
	 */
	public function login_facebook( $request ) {
		$token = $request->get_param( 'token' );

		try {
			$fb = new \Facebook\Facebook(
				array(
					'app_id'                => FACEBOOK_APP_ID,
					'app_secret'            => FACEBOOK_APP_SECRET,
					'default_graph_version' => 'v2.10',
					// 'default_access_token' => '{access-token}', // optional
				)
			);

			// Get the \Facebook\GraphNodes\GraphUser object for the current user.
			// If you provided a 'default_access_token', the '{access-token}' is optional.
			$response = $fb->get(
				'/me?fields=id,first_name,last_name,name,picture,email',
				$token
			);
		} catch ( \Facebook\Exceptions\FacebookResponseException $e ) {
			// When Graph returns an error
			echo __( 'Graph returned an error: ', 'frego-mobile-builder' ) . $e->getMessage();
			exit;
		} catch ( \Facebook\Exceptions\FacebookSDKException $e ) {
			// When validation fails or other local issues
			echo __(
				'Facebook SDK returned an error: ',
				'frego-mobile-builder'
			) . $e->getMessage();
			exit;
		}

		$me = $response->getGraphUser();

		// Email not exist
		$email = $me->getEmail();
		if ( ! $email ) {
			return new WP_Error(
				'email_not_exist',
				__( 'User not provider email', 'frego-mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'email', $email );

		// Return data if user exist in database
		if ( $user ) {
			$auth  = new Auth();
			$token = $auth->generate_token( $user );

			return array(
				'token' => $token,
			);
		}
		// Will create new user
		$first_name  = $me->getFirstName();
		$last_name   = $me->getLastName();
		$picture     = $me->getPicture();
		$name        = $me->getName();
		$facebook_id = $me->getId();

		$user_id = wp_insert_user(
			array(
				'user_pass'     => wp_generate_password(),
				'user_login'    => $email,
				'user_nicename' => $name,
				'user_email'    => $email,
				'display_name'  => $name,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$error_code = $user->get_error_code();

			return new WP_Error(
				$error_code,
				$user_id->get_error_message( $error_code ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'id', $user_id );

		$auth  = new Auth();
		$token = $auth->generate_token( $user );

		$data = array(
			'token' => $token,
		);

		add_user_meta( $user_id, 'mbd_login_method', 'facebook', true );
		add_user_meta( $user_id, 'mbd_avatar', $picture, true );

		return $data;
	}

	/**
	 * Do login with email and password.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function login( $request ) {
		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );

		// try login with username and password
		$user = wp_authenticate( $username, $password );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Generate token
		$auth  = new Auth();
		$token = $auth->generate_token( $user );

		// Return data
		return array(
			'token' => $token,
		);
	}

	/**
	 * Check if a given request has access to read a customer.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$id = (int) $request['id'];

		if ( get_current_user_id() != $id ) {
			return new WP_Error(
				'frego_mobile_builder',
				__( 'Sorry, you cannot change info.', 'frego-mobile-builder' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}

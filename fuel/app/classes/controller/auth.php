<?php
/**
 * Authenication. For user login, register, logout, etc.
 **/
class Controller_Auth extends Controller
{
	/**
	 * Default page. Show login and register form.
	 **/
	public function action_index()
	{
		$view = View::forge('auth/home');
		$data = array(
			'header' => View::forge('header'),
			'navbar' => View::forge('navbar'),
			'page_title' => 'aniTrace',
			'loggedin' => false,
		);
		$view->set_global($data);
		return $view;
	}

	/**
	 * Identify user data and let user login.
	 **/
	public function action_login()
	{
		$username = Input::post('username');
		$password = Input::post('password');
		if( $username !== '')
		{
			try{
				$valid = Sentry::login($username, $password, false);
				if( $valid )
				{
					Response::redirect( Uri::create('anime/') );
				}
				else
				{
					$view = View::forge('alert');
					$data['page_title'] = '登入';
					$data['loggedin'] = false;
					$data['alert'] = array(
						'type' => '',
						'title' => '登入失敗',
						'text' => '請檢查您輸入的帳號、密碼，再重試一次。',
						'return' => Uri::create('auth/'),
					);
					$view->set_global($data);
					return $view;
				}
			}
			catch( SentryAuthException $e )
			{
				$data['page_title'] = '登入';
				$data['loggedin'] = false;
				if( strpos($e->getMessage(), 'does not exist') !== false )
				{
					$data['alert'] = array(
						'type' => '',
						'title' => '登入失敗',
						'text' => '請檢查您輸入的帳號、密碼，再重試一次。',
						'return' => Uri::create('auth/'),
					);
					$view = View::forge('alert');
					$view->set_global($data);
					return $view;
				}
				else
				{
					return $e->getMessage();
				}
			}
		}
		else
		{
			$view = View::forge('auth/home');
			$data = array(
				'header' => View::forge('header'),
				'navbar' => View::forge('navbar'),
				'page_title' => 'aniTrace',
				'loggedin' => false,
			);
			$view->set_global($data);
			return $view;
		}
	}

	/**
	 * User registration. Make sure user data is not conflict with exist
	 * data and add new user data into database.
	 **/
	public function action_register()
	{
		$username = Input::post('username');
		if( $username !== '' && $username !== null )
		{
			$validator = Validation::forge('user');
			$validator->add_field('username', 'User Name', 'required');
			$validator->add_field('email', 'Email', 'required|valid_email');
			$validator->add_field('password', 'Password', 'required');
			if( $validator->run() )
			{
				$data = array(
					'username' => $username,
					'email' => Input::post('email'),
					'password' => Input::post('password'),
				);
				try
				{
					$uid = Sentry::user()->create($data);
					if($uid) {
						$data['page_title'] = '註冊';
						$data['loggedin'] = false;
						$data['alert'] = array(
							'type' => 'success',
							'title' => '歡迎',
							'text' => '註冊成功，祝您使用愉快！',
							'return' => Uri::create('animate/')
						);
						$view = View::forge('alert');
						$view->set_global($data);
						return $view;
					}
					else
					{
						$data['page_title'] = '註冊';
						$data['loggedin'] = false;
						$data['alert'] = array(
							'type' => 'warning',
							'title' => '註冊失敗',
							'text' => '請檢查您輸入的帳號、密碼，再重試一次。',
							'return' => Uri::create('auth/')
						);
						$view = View::forge('alert');
						$view->set_global($data);
						return $view;
					}
				}
				catch( SentryUserException $e )
				{
					$data['page_title'] = '註冊';
					$data['loggedin'] = false;
					$data['alert'] = array(
						'type' => 'warning',
						'title' => '註冊失敗',
						'text' => $e->getMessage(),
						'return' => Uri::create('auth/')
					);
					$view = View::forge('alert');
					$view->set_global($data);
					return $view;
				}
			}
			else
			{
				$data['page_title'] = '註冊';
				$data['loggedin'] = false;
				$data['alert'] = array(
					'type' => 'warning',
					'title' => '註冊失敗',
					'text' => '請檢查帳號與電子郵件格式，再重試一次。',
					'return' => Uri::create('auth/')
				);
				$view = View::forge('alert');
				$view->set_global($data);
				return $view;
			}
		}
		else
		{
			$view = View::forge('auth/home');
			$data = array(
				'header' => View::forge('header'),
				'navbar' => View::forge('navbar'),
				'page_title' => 'aniTrace',
				'loggedin' => false,
			);
			$view->set_global($data);
			return $view;
		}
	}

	/**
	 * Logout loggined user.
	 **/
	public function action_logout()
	{
		Sentry::logout();
		Response::redirect( Uri::base() );
	}

	/**
	 * Check if username is conflict.
	 **/
	public function action_check_username($username = '')
	{
		if ($username !== '')
		{
			echo json_encode( array('found' => Sentry::user_exists($username)) );
		}
	}

	/**
	 * Check if user's email is conflict.
	 **/
	public function action_check_email($email='')
	{
		$result = DB::select('email')->from('users')->where('email', $email)->execute();
		if (count( $result->as_array() ) > 0)
		{
			$found = true;
		}
		else
		{
			$found = false;
		}
		echo json_encode( array('found'=> $found) );
	}
}

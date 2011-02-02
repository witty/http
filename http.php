<?php
/**
 * Http Class , used for fetch / post request url
 *
 * <pre>
 * $http = new Http();
 * $result = $http->execute('ifeng');
 * $info = $http->get_info();
 * echo $info['http_code'];
 * </pre>
 * 
 * @author lzyy http://blog.leezhong.com
 * @version 0.1.0
 */
class Http extends Witty_Base {

	protected $_config = array(
		'timeout' => 3, 
		'cookie_path' => '/tmp/cookie.txt',
		'use_cookie' => true, 
		'max_redirect' => 5, 
		'redirect' => true, 
		'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.9',
	);

	protected $_httpinfo;

	protected $_ch;

	protected $_method = 'GET';

	protected $_params = array();

	protected $_cookies = array();

	protected $_headers = array();

	protected $_username;

	protected $_password;

	protected $_referer;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!function_exists('curl_init'))
		{
			throw new Http_Exception('请先安装curl模块');
		}
	}

	/**
	 * Set Referer
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_referer('http://www.google.com');
	 * </pre>
	 *
	 * @param string $referer referer
	 * @return $this
	 */
	public function set_referer($referer)
	{
		$this->_referer = $referer;
		return $this;
	}

	/**
	 * Set Method
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_method('GET');
	 * </pre>
	 *
	 * @param string $method http方法，GET/POST/PUT/DELETE
	 * @return $this
	 */
	public function set_method($method)
	{
		$method = strtoupper($method);
		if (in_array($method, array('GET', 'POST', 'PUT', 'DELETE')))
		{
			$this->_method = $method;
		}
		else
		{
			throw new Http_Exception('无效的http方法:{method}', array('{method}' => $method));
		}
		return $this;
	}

	/**
	 * Set Cookie
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_cookies(array('foo' => 'bar'));
	 * </pre>
	 *
	 * @param array $cookies cookies
	 * @return $this
	 */
	public function set_cookies(array $cookies)
	{
		$this->_cookies = array_merge($this->_cookies, $cookies);
		return $this;
	}

	/**
	 * Set Param
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_params(array('foo' => 'bar'));
	 * </pre>
	 *
	 * @param array $params params
	 * @return $this
	 */
	public function set_params(array $params)
	{
		$this->_params = array_merge($this->_params, $params);
		return $this;
	}

	/**
	 * Set Header
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_headers(array('Content-type:text/html;charset=utf-8'));
	 * </pre>
	 *
	 * @param array $params params
	 * @return $this
	 */
	public function set_headers(array $headers)
	{
		$this->_headers = array_merge($this->_headers, $headers);
		return $this;
	}

	/**
	 * Set Basic Auth Info
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->set_auth('username', 'password');
	 * </pre>
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return $this
	 */
	public function set_auth($username, $password)
	{
		$this->_username = $username;
		$this->_password = $password;
		return $this;
	}

	/**
	 * get http execute info
	 *
	 * <pre>
	 * $http = new Http();
	 * $http->execute('ifeng');
	 * var_dump($http->get_info());
	 * </pre>
	 *
	 * param keys
	 *
	 * <ul>
	 * <li>url</li>
	 * <li>content_type</li>
	 * <li>http_code</li>
	 * <li>header_size</li>
	 * <li>request_size</li>
	 * <li>filetime</li>
	 * <li>ssl_verify_result</li>
	 * <li>redirect_count</li>
	 * <li>total_time</li>
	 * <li>namelookup_time</li>
	 * <li>connect_time</li>
	 * <li>pretransfer_time</li>
	 * <li>size_upload</li>
	 * <li>size_download</li>
	 * <li>speed_download</li>
	 * <li>speed_upload</li>
	 * <li>download_content_length</li>
	 * <li>upload_content_length</li>
	 * <li>starttransfer_time</li>
	 * <li>redirect_time</li>
	 * <li>certinfo</li>
	 * <li>request_header</li>
	 * </ul>
	 *
	 * @param string $key 
	 * @return mixed
	 */
	public function get_info($key = null)
	{
		return empty($key) ? $this->_httpinfo : $this->_httpinfo[$key];
	}

	/**
	 * execute http request , return result
	 *
	 * <pre>
	 * $http = new Http();
	 * echo $http->execute('http://www.google.com');
	 * </pre>
	 *
	 * @param string $url url
	 * @return string
	 */
	public function execute($url)
	{
		$this->_ch = curl_init();
		$this->_set_method();
		$this->_set_params($url);
		$this->_set_cookie();
		$this->_set_timeout();
		$this->_set_referer();
		$this->_set_cookiepath();
		$this->_set_auth();
		$this->_set_redirect();
		$this->_set_user_agent();
		$this->_set_url($url);
		$this->_set_header();
		$this->_set_misc();
		$result = curl_exec($this->_ch);
		$this->_httpinfo = curl_getinfo($this->_ch);
		curl_close($this->_ch);

		return $result;
	}

	protected function _set_url($url)
	{
		curl_setopt($this->_ch, CURLOPT_URL, $url);
	}

	protected function _set_misc()
	{
		curl_setopt($this->_ch, CURLINFO_HEADER_OUT, TRUE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
	}

	protected function _set_header()
	{
		if (!empty($this->_headers))
		{
			$header = array_merge(array('Accept-Encoding:identity; q=0.5, *;q=0'), $this->_headers);
			curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
		}
	}

	protected function _set_user_agent()
	{
		curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_config['user_agent']);
	}

	protected function _set_redirect()
	{
		if ($this->_config['redirect'])
		{
			curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($this->_ch, CURLOPT_MAXREDIRS, $this->_config['max_redirect']);
		}
	}

	protected function _set_auth()
	{
		if (!empty($this->_username) && !empty($this->_password))
		{
			curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_username.':'.$this->_password);
		}
	}

	protected function _set_cookiepath()
	{
		if ($this->_config['use_cookie'])
		{
			curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $this->_config['cookie_path']);
			curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->_config['cookie_path']);
		}
	}

	protected function _set_referer()
	{
		if (!empty($this->_referer))
		{
			curl_setopt($this->_ch, CURLOPT_REFERER, $this->_referer);
		}
	}

	protected function _set_timeout()
	{
		curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->_config['timeout']);
	}

	protected function _set_cookie()
	{
		if (!empty($this->_cookies))
		{
			$cookies = $this->_cookies;
			$cookie_str = '';
			foreach ($cookies as $key => $val)
			{
				$cookie_str .= $key.'='.$val.'; ';
			}
			curl_setopt($this->_ch, CURLOPT_COOKIE, rtrim($cookie_str, '; '));
		}
	}

	protected function _set_method()
	{
		curl_setopt ($this->_ch, CURLOPT_HTTPGET, TRUE); 
		curl_setopt ($this->_ch, CURLOPT_POST, FALSE); 
		if ($this->_method == 'POST')
		{
			curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		}
		elseif ($this->_method == 'PUT' || $this->_method == 'DELETE')
		{
			curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $this->_method); 
		}
	}

	protected function _set_params(& $url)
	{
		if ($this->_method == 'GET')
		{
			if (!empty($this->_params))
			{
				$url .= '?'.http_build_query($this->_params);
			}
		}
		else
		{
			$uploadFile = false;
			foreach ($this->_params as $param)
			{
				if (substr($param, 0, 1) == '@')
				{
					$uploadFile = true;
					break;
				}
			}
			if (!$uploadFile)
			{
				$this->_params = http_build_query($this->_params);
			}
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $this->_params); 
		}
	}
}

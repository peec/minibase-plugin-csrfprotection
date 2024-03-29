<?php
namespace Pkj\Minibase\Plugin\Csrf;

use Pkj\Minibase\Plugin\Csrf\Annotation\IgnoreCsrfProtection;

use Minibase\Mvc\View;
use Minibase\Http\Request;
use Minibase\Plugin\Plugin;

use Doctrine\Common\Annotations\AnnotationRegistry;


/**
 * This plugin secures against CSRF attacks.
 * 
 * Secures all HTTP requests except GET method.
 * 
 * Configuration:
 * 
 * - token_name: Default is "csrf_token"
 * - storage: "cookie" or "session" , if session is used, session must be started.
 * 
 * Usage:
 * 
 * 	$mb->initPlugins(array('Minibase\Plugin\Csrf\CsrfPlugin' => null));
 * 	// All $mb->on requests are protected from here.
 * 
 * Sometimes you don't want csrf protection, ie. REST APIS. Stopping the plugin:
 * 
 * 	$mb->get('Minibase\Plugin\Csrf\CsrfPlugin')->stop();
 * 	// And then start it again.
 * 	$mb->get('Minibase\Plugin\Csrf\CsrfPlugin')->start();
 * 	
 * 
 * 
 * @author peec
 *
 */
class CsrfPlugin extends Plugin {
	private $generatedToken;
	
	private $routeBefore;
	private $beforeRender;
	
	public function stop () {
		$this->mb->events->off("mb:call:execute", $this->routeBefore);
		$this->mb->events->off("before:render", $this->beforeRender);
	}
	
	public function start () {
		$that = $this;
		
		
		// Load custom annotations.
		AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Annotations.php');
		
		
		$this->routeBefore = function (Request $req, $annotations) {
			$this->setToken();
			
			$skip = $req->method === 'get';
			foreach($annotations as $annotation) {
				// Ignore CSRF protection.
				if ($annotation instanceof IgnoreCsrfProtection) {
					$skip = true;
				}
			}
			
			
			if (!$skip) {
				if (!isset($_REQUEST[$this->tokenName()]) || !$this->getServerToken() || $_REQUEST[$this->tokenName()] !== $this->getServerToken()) {
					$call = $this->mb->events->trigger("csrf:invalid", 
							array ($req), 
							function () use($req) {
								$response = function () use($req) {
									throw new CsrfInvalidTokenException("Invalid CSRF token for {$req->uri}, create custom event handler for csrf:invalid.");
								};
								return $response;
							})[0];
					
					return $call;
				}
			}
		};
		$this->beforeRender = function (View $view, &$args) use ($that) {
			// Assigns a view variable so it can be used in views.
			$args["csrfToken"] = $that->getServerToken();
			
			// Assigns a view variable (appends "Input" in the end so it can add hidden input field.
			$args["csrfTokenInput"] = '<input type="hidden" name="'.$that->tokenName().'" value="'.$that->getServerToken().'" />';
		};
		
			
		// Create / validate token before route.
		$this->mb->events->on("mb:call:execute", $this->routeBefore);
		// Add token var.
		$this->mb->events->on("before:render", $this->beforeRender);
		
		
	}
	public function tokenName () {
		return $this->cfg("token_name", "csrfToken");
	}
	
	/**
	 * Gets the server token, depending on "storage" config is cookie, then gets from cookie, else session.
	 */
	public function getServerToken () {
		$token = null;
		if ($this->cfg("storage", "cookie") === 'cookie') {
			$token = isset($_COOKIE[$this->tokenName()]) ? $_COOKIE[$this->tokenName()] : null;
		} else {
			$token = isset($_SESSION[$this->tokenName()]) ? $_SESSION[$this->tokenName()] : null;
		}
		return $token ?: $this->generatedToken;
	}
	
	/**
	 * Sets the token, depending on "storage" config is "cookie" it uses cookie , else session.
	 * @throws \Exception If cookie can not be set.
	 */
	public function setToken () {
		$storage = $this->cfg("storage", "cookie")  === "cookie" ? $_COOKIE : $_SESSION;
		if (!isset($storage[$this->tokenName()])) {
			$token = uniqid(rand(), true);
			
			if ($this->cfg("storage", "cookie") === 'cookie') {
				if (!setcookie($this->tokenName(), $token, time()+(60*60*24*30))){
					throw new \Exception ("Could not set CSRF token.");
				}
			} else {
				$_SESSION[$this->tokenName()] = $token;
			}
			
			$this->generatedToken = $token;
		}
	}
	
}
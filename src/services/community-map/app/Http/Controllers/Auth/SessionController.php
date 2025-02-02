<?php
/******************************************************************************\
|                                                                              |
|                             SessionController.php                            |
|                                                                              |
|******************************************************************************|
|                                                                              |
|        This is a controller for users' session information.                  |
|                                                                              |
|        Author(s): Abe Megahed                                                |
|                                                                              |
|        This file is subject to the terms and conditions defined in           |
|        'LICENSE.txt', which is part of this source code distribution.        |
|                                                                              |
|******************************************************************************|
|     Copyright (C) 2022, Data Science Institute, University of Wisconsin      |
\******************************************************************************/

namespace App\Http\Controllers\Auth;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use App\Models\UserAccounts\UserAccount;
use App\Http\Controllers\Controller;
use App\Utilities\Security\Password;

class SessionController extends Controller
{
	//
	// creating methods
	//
	
	/**
	 * Create a new session.
	 *
	 * @param Illuminate\Http\Request $request - the Http request object
	 * @return App\Models\Users\User
	 */
	public function postLogin(Request $request) {

		// get input parameters
		//
		$username = $request->input('username');
		$password = $request->input('password');

		// validate user account
		//
		$userAccount = UserAccount::where('username', '=', $username)->first();
		if ($userAccount) {
			if (Password::isValid($password, $userAccount->password)) {
				$user = $userAccount->user->toArray();

				// check for user associated with this account
				//
				if (!$userAccount->hasBeenVerified()) {
					return response("User email has not been verified.", 401);
				}
				if (!$userAccount->isEnabled()) {
					return response("User has not been approved.", 401);
				}

				// update last login
				//
				$userAccount->last_login = new DateTime();
				$userAccount->save();

				// create new session
				//
				$request->session()->regenerate();

				// set session info
				//
				session([
					'user_id' => $userAccount->id,
					'timestamp' => time()
				]);

				return $user;
			} else {
				return response("Incorrect username or password.", 401);
			}
		} else {
			return response("Incorrect username or password.", 401);
		}
	}

	/**
	 * Update a user's timestamps.
	 *
	 */
	public function putStart() {

		// find user by id
		//
		$userAccount = UserAccount::current();

		// update login dates
		//
		if ($userAccount) {
			$userAccount->updateLoginDates();
			$userAccount->updateLoginDates();
		}
		
		return $userAccount->ultimate_login_at;
	}

	//
	// querying methods
	//

	/**
	 * Get a session.
	 *
	 * @param $sessionId - the id of the session to get
	 * @return App\Models\Users\Auth\UserSession
	 */
	public function getIndex(string $sessionId) {
		if ($sessionId == 'current') {
			return UserSession::current();
		}
	}

	/**
	 * Get all sessions.
	 *
	 * @return App\Models\Users\Session[]
	 */
	public function getAll(Request $request) {

		// execute query
		//
		return Session::all();
	}

	/**
	 * Find if a session is valid.
	 *
	 * @param $sessionId - the id of the session to query
	 * @return bool
	 */
	public function isValid($sessionId) {
		return $_COOKIE != null;
	}

	//
	// deleting methods
	//

	/**
	 * Delete current session.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function postLogout() {

		// expire cookie
		//
		// setcookie(config('session.cookie'), "", time()-3600, '/');
		// unset($_COOKIE[Session::getId()]);

		// destroy session cookies
		//
		Session::flush();

		// return response
		//	
		return response("SESSION_DESTROYED");
	}
}

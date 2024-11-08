<?php
/******************************************************************************\
|                                                                              |
|                         PasswordResetController.php                          |
|                                                                              |
|******************************************************************************|
|                                                                              |
|        This is a controller for user account password reset information.     |
|                                                                              |
|        Author(s): Abe Megahed                                                |
|                                                                              |
|        This file is subject to the terms and conditions defined in           |
|        'LICENSE.txt', which is part of this source code distribution.        |
|                                                                              |
|******************************************************************************|
|     Copyright (C) 2022, Data Science Institute, University of Wisconsin      |
\******************************************************************************/

namespace App\Http\Controllers\UserAccounts;

use \DateTime;
use \DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\UserAccounts\PasswordReset;
use App\Models\UserAccounts\UserAccount;
use App\Http\Controllers\Controller;
use App\Utilities\Uuids\Guid;
use App\Utilities\Filters\DateFilters;
use App\Utilities\Filters\LimitFilter;

class PasswordResetController extends Controller
{
	//
	// creating methods
	//

	/**
	 * Create a new password reset.
	 *
	 * @param Illuminate\Http\Request $request - the Http request object
	 * @return object
	 */
	public function postCreate(Request $request) {

		// get parameters
		//
		$username = $request->input('username');
		$email = $request->input('email');

		// get user by username or email
		//
		if ($username) {
			$userAccount = UserAccount::where('username', '=', $username)->first();
		} else if ($email) {
			$userAccount = UserAccount::where('email', '=', $email)->first();
		}
		
		// user not found
		//
		if (!$userAccount) {
			return [
				'success' => true
			];
		}

		// create and send password reset
		//
		$passwordReset = new PasswordReset([
			'id' => Guid::create(),
			'user_id' => $userAccount->id
		]);
		$passwordReset->save();
		$passwordReset->send();

		return [
			'success' => true
		];
	}

	//
	// querying methods
	//

	/**
	 * Get a password reset.
	 *
	 * @param string $id - the id of the password reset to get
	 * @return App\Models\Users\Accounts\PasswordReset
	 */
	public function getIndex(string $id) {

		// find password reset by id
		//
		$passwordReset = PasswordReset::find($id);
		if (!$passwordReset) {
			return response("Password reset not found.", 404);
		}

		return $passwordReset;
	}

	/**
	 * Get all password resets.
	 *
	 * @return App\Models\UserAccounts\PasswordReset[]
	 */
	public function getAll(Request $request) {

		// create query
		//
		$query = PasswordReset::orderBy('created_at', 'DESC');

		// add filters
		//
		$query = DateFilters::applyTo($request, $query);
		$query = LimitFilter::applyTo($request, $query);

		// execute query
		//
		return $query->get();
	}

	/**
	 * Get a password reset by key.
	 *
	 * @param string $passwordResetKey - the key of the password reset to get
	 * @return App\Models\Users\Accounts\PasswordReset
	 */
	public function getByKey(string $key) {

		// find password reset by key
		//
		return PasswordReset::where('key', '=', $key)->first();
	}

	/**
	 * Get a password reset by id.
	 *
	 * @param string $id - the id of the password reset to get
	 * @return App\Models\Users\Accounts\PasswordReset
	 */
	public function getByIndex(string $id) {

		// find password reset by id
		//
		$passwordReset = PasswordReset::find($id);
		if (!$passwordReset) {
			return response("Password reset not found.", 401);
		}

		// check password reset
		//
		$time = new DateTime($passwordReset->created_at, new DateTimeZone('GMT'));
		if ((gmdate('U') - $time->getTimestamp()) > 1800) {
			return response("Password reset expired.", 401);
		}

		return $passwordReset;
	}

	//
	// updating methods
	//

	/**
	 * Update a password reset.
	 *
	 * @param Illuminate\Http\Request $request - the Http request object
	 * @return App\Models\Users\Accounts\PasswordReset
	 */
	public function updateByIndex(Request $request, string $id) {

		// get input parameters
		//
		$password = $request->input('password');

		// get models
		//
		$passwordReset = PasswordReset::find($id);
		$userAccount = UserAccount::find($passwordReset->user_id);

		// update password
		//
		$userAccount->modifyPassword($password);

		// destroy password reset if present
		//
		$passwordReset->delete();

		// notify user that password has changed
		//
		if (config('mail.enabled')) {
			$user = $userAccount->user;
			if ($user) {
				Mail::send('emails.password-changed', [
					'name' => $user->getFullName(),
					'app_name' => config('app.name'),
					'client_url' => config('app.client_url')
				], function($message) use ($user) {
					$message->to($user->getEmail(), $user->getFullName());
					$message->subject('Password Changed');
				});
			}
		}

		// return response
		//
		return response()->json([
			'success' => true
		]);
	}

	//
	// deleting methods
	//

	/**
	 * Delete a password reset.
	 *
	 * @param string $id - the id of the password reset to delete
	 * @return App\Models\Users\Accounts\PasswordReset
	 */
	public function deleteIndex(string $id) {

		// find password reset by id
		//
		$passwordReset = PasswordReset::find($id);
		if (!$passwordReset) {
			return response("Password reset not found.", 404);
		}

		// delete password reset
		//
		$passwordReset->delete();
		return $passwordReset;
	}
}

<?php

/**
 * Created by Jeffrey Cooper.
 * Date: April 26, 2017 11:00.00 pm
 * @author Jeffrey Cooper <jcooper37@cnm.edu>
 * @version 0.1.0
 *
 */

require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 3) . "/php/classes/autoload.php";
require_once dirname(__DIR__, 3) . "/php/lib/xsrf.php";
require_once("/etc/apache2/~jcooper37/encrypted-config.php");

use Edu\Cnm\DataDesign\{
	Profile,
	// testing only
	Favorite
};


/**
 * api for the Profile class
 *
 * @author Jeffrey Cooper <jcooper37@cnm.edu>
 **/

//verify the session, start if not active
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {
	//establish mySQL connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/~jcooper37/data-design.ini");

	// mock a session and assign a specific user to it.
	// only for testing purposes - not in the live code.
	//$_SESSION["profile"] = Profile::getProfileByProfileId($pdo, 732);

	//determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	//sanitize input
	$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
	$favProfileId = filter_input(INPUT_GET, "favProfileId", FILTER_VALIDATE_INT);
	$favProductId = filter_input(INPUT_GET, "favProductId", FILTER_VALIDATE_INT);

	//make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true || $id < 0)) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}


	// handle GET request - if id is present, that profile is returned, otherwise all profiles are returned
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();

		//get a specific profile or all profiles and update reply
		if(empty($id) === false) {
			$profile = Profile::getProfileByProfileId($pdo, $id);
			if($profile !== null) {
				$reply->data = $profile;
			}
		} else if(empty($profileEmail) === false) {
			$profiles = Profile::getProfileByProfileEmail($pdo, $profileEmail)->toArray();
			if($profiles !== null) {
				$reply->data = $profiles;
			}
		} else {
			$profiles = Profile::getAllProfiles($pdo)->toArray();
			if($profiles !== null) {
				$reply->data = $profiles;
			}
		}
	} else if($method === "PUT" || $method === "POST") {

		verifyXsrf();
		$requestContent = file_get_contents("php://input");
		// Retrieves the JSON package that the front end sent, and stores it in $requestContent. Here we are using file_get_contents("php://input") to get the request from the front end. file_get_contents() is a PHP function that reads a file into a string. The argument for the function, here, is "php://input". This is a read only stream that allows raw data to be read from the front end request which is, in this case, a JSON package.
		$requestObject = json_decode($requestContent);
		// This Line Then decodes the JSON package and stores that result in $requestObject

		//make sure profile email is available (required field)
		if(empty($requestObject->profileEmail) === true) {
			throw(new \InvalidArgumentException ("No content for Email.", 405));
		}


		//  !!!!make sure profileId is not null
		if(empty($requestObject->ProfileId) === true) {
			throw(new \InvalidArgumentException ("No Profile ID.", 405));
		}

		//perform the actual put or post
		if($method === "PUT") {

			//enforce that the end user has a XSRF token.
			verifyXsrf();


			// retrieve the profile to update
			$profile = Profile::getProfileByProfileId($pdo, $id);
			if($profile === null) {
				throw(new RuntimeException("Profile does not exist", 404));
			}

			//enforce the user is signed in and only trying to edit their own profile
			if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $profile->getProfileId()) {
				throw(new \InvalidArgumentException("You are not allowed to edit this profile", 403));
			}

			// update all attributes
			$profile->setProfileAtHandle($requestObject->profileAtHandle);
			$profile->setProfileEmail($requestObject->profileEmail);
			$profile->setProfilePhone($requestObject->profilePhone);
			$profile->update($pdo);

			// update reply
			$reply->message = "Profile updated OK";

		} else if($method === "POST") {

			//enforce that the end user has a XSRF token.
			verifyXsrf();

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to view profiles", 403));
			}

			// create new profile and insert into the database
			$profile = new Profile(null, $requestObject->ProfileId, $requestObject->profileEmail, null);
			$profile->insert($pdo);

			// update reply
			$reply->message = "Profile created OK";
		}

	} else if($method === "DELETE") {

		//enforce that the end user has a XSRF token.
		verifyXsrf();

		// retrieve the Profile to be deleted
		$profile = Profile::getProfileByProfileId($pdo, $id);
		if($profile === null) {
			throw(new RuntimeException("Profile does not exist", 404));
		}

		//enforce the user is signed in and only trying to edit their own profile
		if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $profile->getProfileId()) {
			throw(new \InvalidArgumentException("You are not allowed to delete this profile", 403));
		}

		// delete profile
		$profile->delete($pdo);
		// update reply
		$reply->message = "Profile deleted OK";
	} else {
		throw (new InvalidArgumentException("Invalid HTTP method request"));
	}
// update the $reply->status $reply->message
} catch(\Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}

header("Content-type: application/json");
if($reply->data === null) {
	unset($reply->data);
}

// encode and return reply to front end caller
echo json_encode($reply);
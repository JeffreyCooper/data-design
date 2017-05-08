<?php


require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 3) . "/php/classes/autoload.php";
require_once dirname(__DIR__, 3) . "/php/lib/xsrf.php";
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

use Edu\Cnm\DataDesign\{
	Favorite,
	// testing only
	Profile
};


/**
 * api for the Favorite class
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
	//establish the mySQL connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/ddcdata-design.ini");

	// mock a session and assign a specific user to it.
	// only for testing purposes - not in the live code.
	//$_SESSION["profile"] = Profile::getProfileByProfileId($pdo, 732);

	//determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	//sanitize input
	$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
	$favProfileId = filter_input(INPUT_GET, "favProfileId", FILTER_VALIDATE_INT);
	$favProductId = filter_input(INPUT_GET, "favProductId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	//make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true || $id < 0)) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}


	// handle GET request - if id is present, that favorite is returned, otherwise all favorites are returned
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();

		//get a specific favorite or all favorites and update reply
		if(empty($id) === false) {
			$favorite = Favorite::getFavoriteByFavoriteId($pdo, $id);
			if($favorite !== null) {
				$reply->data = $favorite;
			}
		} else if(empty($favoriteProfileId) === false) {
			$favorite = Favorite::getFavoriteByFavProfileId($pdo, $favProfileId)->toArray();
			if($favorite !== null) {
				$reply->data = $favorite;
			}
		} else {
			$favorites = Favorite::getAllFavorites($pdo)->toArray();
			if($favorites !== null) {
				$reply->data = $favorites;
			}
		}
	} else if($method === "PUT" || $method === "POST") {

		verifyXsrf();
		$requestContent = file_get_contents("php://input");
		// Retrieves the JSON package that the front end sent, and stores it in $requestContent. Here we are using file_get_contents("php://input") to get the request from the front end. file_get_contents() is a PHP function that reads a file into a string. The argument for the function, here, is "php://input". This is a read only stream that allows raw data to be read from the front end request which is, in this case, a JSON package.
		$requestObject = json_decode($requestContent);
		// This Line Then decodes the JSON package and stores that result in $requestObject

		//  make sure profileId is available
		if(empty($requestObject->favProfileId) === true) {
			throw(new \InvalidArgumentException ("No Profile ID.", 405));
		}

		//perform the actual put or post
		if($method === "PUT") {

			//enforce that the end user has a XSRF token.
			verifyXsrf();


			// retrieve the favorite to update
			$favorite = Favorite::getFavoriteByFavProfileId($pdo, $id);
			if($favorite === null) {
				throw(new RuntimeException("Favorite does not exist", 404));
			}

			//enforce the user is signed in and only trying to edit their own favorite
			if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $favorite->getFavProfileId()) {
				throw(new \InvalidArgumentException("You are not allowed to edit this favorite", 403));
			}

			// update reply
			$reply->message = "Favorite updated OK";

		} else if($method === "POST") {

			//enforce that the end user has a XSRF token.
			verifyXsrf();

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to post favorites", 403));
			}

			// create new favorite and insert into the database
			$favorite = new Favorite(null, $requestObject->favProfileId, $requestObject->favProductId, null);
			$favorite->insert($pdo);

			// update reply
			$reply->message = "Favorite created OK";
		}

	} else if($method === "DELETE") {

		//enforce that the end user has a XSRF token.
		verifyXsrf();

		// retrieve the Favorite to be deleted
		$favorite = Favorite::getFavoriteByFavProfileId($pdo, $id);
		if($favorite === null) {
			throw(new RuntimeException("Favorite does not exist", 404));
		}

		//enforce the user is signed in and only trying to edit their own favorite
		if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $favorite->getFavProfileId()) {
			throw(new \InvalidArgumentException("You are not allowed to delete this favorite", 403));
		}

		// delete favorite
		$favorite->delete($pdo);
		// update reply
		$reply->message = "Favorite deleted OK";
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
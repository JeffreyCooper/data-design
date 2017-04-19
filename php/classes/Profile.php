<?php

namespace Edu\Cnm\DataDesign;
require_once("autoload.php");
/**
 * Small Cross Section of a 'Product'.
 *
 * ***more description***
 *
 * @author Jeffrey Cooper <jcooper37@cnm.edu>
 * @version 0.1.0
 **/

/**
 * Class Product
 * @package Edu\Cnm\DataDesign
 * HELP - I don't know what the 'implements' means
 **/
class Product implements \JsonSerializable {
	use ValidateDate;
	/**
	 * ID for this Product; this is the primary key
	 * @var int $productID
	 **/
	private $productID;
	/**
	 * ID of the Profile that 'favorited' this Product; this is a foreign key
	 * @var int $productProfileID
	 **/
	private $productProfileID;
	/**
	 * actual textual title of this Product
	 * @var string $productTitle
	 **/
	private $productTitle;
	/**
	 * date and time this Product was issued, in a PHP DateTime object
	 * @var \DateTime $productDescription
	 **/
	private $productDescription;

	/**
	 * constructor for this Product
	 *
	 * @param int|null $newProductID ID of this Product or null if a new Product
	 * @param int $newProductProfileID ID of the Profile that purchased this Product
	 * @param string $newProductTitle string containing actual product data
	 * @param \DateTime|string|null $newProductDescription date and time Product was purchased or null if set to current date and time
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds (e.g., strings too long, negative integers)
	 * @throws \TypeError if data types violate type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php
	 **/
	public function __construct(?int $newProductID, int $newProductProfileID, string $newProductTitle, $newProductDescription = null) {
		try {
			$this->setProductID($newProductID);
			$this->setProductProfileID($newProductProfileID);
			$this->setProductTitle($newProductTitle);
			$this->setProductDescription($newProductDescription);
		} //determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}

	/**
	 * accessor method for product ID
	 *
	 * @return int|null value of product ID
	 **/
	public function getProductID(): ?int {
		return ($this->productID);
	}

	/**
	 * mutator method for product ID
	 *
	 * @param int|null $newProductID new value of product ID
	 * @throws \RangeException if $newProductID is not positive
	 * @throws \TypeError if $newProductID is not an integer
	 **/
	public function setProductID(?int $newProductID): void {
		//if product ID is null immediately return it
		if($newProductID === null) {
			$this->productID = null;
			return;
		}
		// verify the product ID is positive
		if($newProductID <= 0) {
			throw(new \RangeException("product ID is not positive"));
		}
		// convert and store the product ID
		$this->productID = $newProductID;
	}

	/**
	 * accessor method for product profile ID
	 *
	 * @return int value of product profile ID
	 **/
	public function getProductProfileID(): int {
		return ($this->productProfileID);
	}

	/**
	 * mutator method for product profile ID
	 *
	 * @param int $newProductProfileID new value of product profile ID
	 * @throws \RangeException if $newProfileID is not positive
	 * @throws \TypeError if $newProfileID is not an integer
	 **/
	public function setProductProfileID(int $newProductProfileID): void {
		// verify the profile ID is positive
		if($newProductProfileID <= 0) {
			throw(new \RangeException("product profile ID is not positive"));
		}
		// convert and store the profile ID
		$this->productProfileID = $newProductProfileID;
	}

	/**
	 * accessor method for product title
	 *
	 * @return string value of product title
	 **/
	public function getProductTitle(): string {
		return ($this->productTitle);
	}

	/**
	 * mutator method for product title
	 *
	 * @param string $newProductTitle new value of product title
	 * @throws \InvalidArgumentException if $newProductTitle is not a string or insecure
	 * @throws \RangeException if $newProductTitle is > 140 characters
	 * @throws \TypeError if $newProductTitle is not a string
	 **/
	public function setProductTitle(string $newProductTitle): void {
		// verify the product title is secure
		$newProductTitle = trim($newProductTitle);
		$newProductTitle = filter_var($newProductTitle, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newProductTitle) === true) {
			throw(new \InvalidArgumentException("product title is empty or insecure"));
		}
		// verify the product title will fit in the database
		if(strlen($newProductTitle) > 140) {
			throw(new \RangeException("product title too large"));
		}
		// store the product title
		$this->productTitle = $newProductTitle;
	}

	/**
	 * accessor method for product description
	 *
	 * @return \DateTime value of product description
	 **/
	public function getProductDescription(): \DateTime {
		return ($this->productDescription);
	}

	/**
	 * mutator method for product description
	 *
	 * @param \DateTime|string|null $newProductDescription product description as a DateTime object or string (or null to load the current time)
	 * @throws \InvalidArgumentException if $newProductDescription is not a valid object or string
	 * @throws \RangeException if $newProductDescription is a date that does not exist
	 **/
	public function setProductDescription($newProductDescription = null): void {
		// base case: if the date is null, use the current date and time
		if($newProductDescription === null) {
			$this->productDescription = new \DateTime();
			return;
		}
		// store the purchase date using the ValidateDescription trait
		try {
			$newProductDescription = self::validateDateTime($newProductDescription);
		} catch(\InvalidArgumentException | \RangeException $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		$this->productDescription = $newProductDescription;
	}

	/**
	 * inserts this Product into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo): void {
		// enforce the productID is null (i.e., don't insert a product that already exists)
		if($this->productID !== null) {
			throw(new \PDOException("not a new product"));
		}
		// create query template
		$query = "INSERT INTO product(productProfileID, productTitle, productDescription) VALUES(:productProfileID, :productTitle, :productDescription)";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holders in the template
		$formattedDate = $this->productDescription->format("Y-m-d H:i:s");
		$parameters = ["productProfileID" => $this->productProfileID, "productTitle" => $this->productTitle, "productDescription" => $formattedDate];
		$statement->execute($parameters);
		// update the null productID with what mySQL just gave us
		$this->productID = intval($pdo->lastInsertID());
	}

	/**
	 * deletes this Product from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function delete(\PDO $pdo): void {
		// enforce the productID is not null (i.e., don't delete a product that hasn't been inserted)
		if($this->productID === null) {
			throw(new \PDOException("unable to delete a product that does not exist"));
		}
		// create query template
		$query = "DELETE FROM product WHERE productID = :productID";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holder in the template
		$parameters = ["productID" => $this->productID];
		$statement->execute($parameters);
	}

	/**
	 * updates this Product in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo): void {
		// enforce the productID is not null (i.e., don't update a product that hasn't been inserted)
		if($this->productID === null) {
			throw(new \PDOException("unable to update a product that does not exist"));
		}
		// create query template
		$query = "UPDATE product SET productProfileID = :productProfileID, productTitle = :productTitle, productDescription = :productDescription WHERE productID = :productID";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holders in the template
		$formattedDate = $this->productDescription->format("Y-m-d H:i:s");
		$parameters = ["productProfileID" => $this->productProfileID, "productTitle" => $this->productTitle, "productDescription" => $formattedDate, "productID" => $this->productID];
		$statement->execute($parameters);
	}

	/**
	 * gets the Product by title
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $productTitle product title to search for
	 * @return \SplFixedArray SplFixedArray of Products found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProductByProductTitle(\PDO $pdo, string $productTitle) {
		// sanitize the description before searching
		$productTitle = trim($productTitle);
		$productTitle = filter_var($productTitle, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($productTitle) === true) {
			throw(new \PDOException("product title is invalid"));
		}
		// create query template
		$query = "SELECT productID, productProfileID, productTitle, productDescription FROM product WHERE productTitle LIKE :productTitle";
		$statement = $pdo->prepare($query);
		// bind the product title to the place holder in the template
		$productTitle = "%$productTitle%";
		$parameters = ["productTitle" => $productTitle];
		$statement->execute($parameters);
		// build an array of products
		$products = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$product = new Product($row["productID"], $row["productProfileID"], $row["productTitle"], $row["productDescription"]);
				$products[$products->key()] = $product;
				$products->next();
			} catch(\Exception $exception) {
				// if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return ($products);
	}

	/**
	 * gets the Product by productID
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param int $productID product ID to search for
	 * @return Product|null Product found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProductByProductID(\PDO $pdo, int $productID): ?Product {
		// sanitize the productID before searching
		if($productID <= 0) {
			throw(new \PDOException("product ID is not positive"));
		}
		// create query template
		$query = "SELECT productID, productProfileID, productTitle, productDescription FROM product WHERE productID = :productID";
		$statement = $pdo->prepare($query);
		// bind the product ID to the place holder in the template
		$parameters = ["productID" => $productID];
		$statement->execute($parameters);
		// grab the product from mySQL
		try {
			$product = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$product = new Product($row["productID"], $row["productProfileID"], $row["productTitle"], $row["productDescription"]);
			}
		} catch(\Exception $exception) {
			// if the row couldn't be converted, rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return ($product);
	}

	/**
	 * gets the Product by profile ID
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param int $productProfileID profile ID to search by
	 * @return \SplFixedArray SplFixedArray of Products found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProductByProductProfileID(\PDO $pdo, int $productProfileID): \SPLFixedArray {
		// sanitize the profile ID before searching
		if($productProfileID <= 0) {
			throw(new \RangeException("product profile ID must be positive"));
		}
		// create query template
		$query = "SELECT productID, productProfileID, productTitle, productDescription FROM product WHERE productProfileID = :productProfileID";
		$statement = $pdo->prepare($query);
		// bind the product profile ID to the place holder in the template
		$parameters = ["productProfileID" => $productProfileID];
		$statement->execute($parameters);
		// build an array of products
		$products = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$product = new Product($row["productID"], $row["productProfileID"], $row["productTitle"], $row["productDescription"]);
				$products[$products->key()] = $product;
				$products->next();
			} catch(\Exception $exception) {
				// if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return ($products);
	}

	/**
	 * gets all Products
	 *
	 * @param \PDO $pdo PDO connection object
	 * @return \SplFixedArray SplFixedArray of Products found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getAllProducts(\PDO $pdo): \SPLFixedArray {
		// create query template
		$query = "SELECT productID, productProfileID, productTitle, productDescription FROM product";
		$statement = $pdo->prepare($query);
		$statement->execute();
		// build an array of products
		$products = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$product = new Product($row["productID"], $row["productProfileID"], $row["productTitle"], $row["productDescription"]);
				$products[$products->key()] = $product;
				$products->next();
			} catch(\Exception $exception) {
				// if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return ($products);
	}

	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array resulting state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);
		//format the date so that the front end can consume it
		$fields["productDescription"] = round(floatval($this->productDescription->format("U.u")) * 1000);
		return ($fields);
	}
}
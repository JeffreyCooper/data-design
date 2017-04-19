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
	 * id for this Product; this is the primary key
	 * @var int $productId
	 **/
	private $productId;
	/**
	 * id of the Profile that 'favorited' this Product; this is a foreign key
	 * @var int $productProfileId
	 **/
	private $productProfileId;
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
	 * @param int|null $newProductId id of this Product or null if a new Product
	 * @param int $newProductProfileId id of the Profile that purchased this Product
	 * @param string $newProductTitle string containing actual product data
	 * @param \DateTime|string|null $newProductDescription date and time Product was purchased or null if set to current date and time
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds (e.g., strings too long, negative integers)
	 * @throws \TypeError if data types violate type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php
	 **/
	public function __construct(?int $newProductId, int $newProductProfileId, string $newProductTitle, $newProductDescription = null) {
		try {
			$this->setProductId($newProductId);
			$this->setProductProfileId($newProductProfileId);
			$this->setProductTitle($newProductTitle);
			$this->setProductDescription($newProductDescription);
		} //determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}

	/**
	 * accessor method for product id
	 *
	 * @return int|null value of product id
	 **/
	public function getProductId(): ?int {
		return ($this->productId);
	}

	/**
	 * mutator method for product id
	 *
	 * @param int|null $newProductId new value of product id
	 * @throws \RangeException if $newProductId is not positive
	 * @throws \TypeError if $newProductId is not an integer
	 **/
	public function setProductId(?int $newProductId): void {
		//if product id is null immediately return it
		if($newProductId === null) {
			$this->productId = null;
			return;
		}
		// verify the product id is positive
		if($newProductId <= 0) {
			throw(new \RangeException("product id is not positive"));
		}
		// convert and store the product id
		$this->productId = $newProductId;
	}

	/**
	 * accessor method for product profile id
	 *
	 * @return int value of product profile id
	 **/
	public function getProductProfileId(): int {
		return ($this->productProfileId);
	}

	/**
	 * mutator method for product profile id
	 *
	 * @param int $newProductProfileId new value of product profile id
	 * @throws \RangeException if $newProfileId is not positive
	 * @throws \TypeError if $newProfileId is not an integer
	 **/
	public function setProductProfileId(int $newProductProfileId): void {
		// verify the profile id is positive
		if($newProductProfileId <= 0) {
			throw(new \RangeException("product profile id is not positive"));
		}
		// convert and store the profile id
		$this->productProfileId = $newProductProfileId;
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
		// enforce the productId is null (i.e., don't insert a product that already exists)
		if($this->productId !== null) {
			throw(new \PDOException("not a new product"));
		}
		// create query template
		$query = "INSERT INTO product(productProfileId, productTitle, productDescription) VALUES(:productProfileId, :productTitle, :productDescription)";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holders in the template
		$formattedDate = $this->productDescription->format("Y-m-d H:i:s");
		$parameters = ["productProfileId" => $this->productProfileId, "productTitle" => $this->productTitle, "productDescription" => $formattedDate];
		$statement->execute($parameters);
		// update the null productId with what mySQL just gave us
		$this->productId = intval($pdo->lastInsertId());
	}

	/**
	 * deletes this Product from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function delete(\PDO $pdo): void {
		// enforce the productId is not null (i.e., don't delete a product that hasn't been inserted)
		if($this->productId === null) {
			throw(new \PDOException("unable to delete a product that does not exist"));
		}
		// create query template
		$query = "DELETE FROM product WHERE productId = :productId";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holder in the template
		$parameters = ["productId" => $this->productId];
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
		// enforce the productId is not null (i.e., don't update a product that hasn't been inserted)
		if($this->productId === null) {
			throw(new \PDOException("unable to update a product that does not exist"));
		}
		// create query template
		$query = "UPDATE product SET productProfileId = :productProfileId, productTitle = :productTitle, productDescription = :productDescription WHERE productId = :productId";
		$statement = $pdo->prepare($query);
		// bind the member variables to the place holders in the template
		$formattedDate = $this->productDescription->format("Y-m-d H:i:s");
		$parameters = ["productProfileId" => $this->productProfileId, "productTitle" => $this->productTitle, "productDescription" => $formattedDate, "productId" => $this->productId];
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
		$query = "SELECT productId, productProfileId, productTitle, productDescription FROM product WHERE productTitle LIKE :productTitle";
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
				$product = new Product($row["productId"], $row["productProfileId"], $row["productTitle"], $row["productDescription"]);
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
	 * gets the Product by productId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param int $productId product id to search for
	 * @return Product|null Product found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProductByProductId(\PDO $pdo, int $productId): ?Product {
		// sanitize the productId before searching
		if($productId <= 0) {
			throw(new \PDOException("product id is not positive"));
		}
		// create query template
		$query = "SELECT productId, productProfileId, productTitle, productDescription FROM product WHERE productId = :productId";
		$statement = $pdo->prepare($query);
		// bind the product id to the place holder in the template
		$parameters = ["productId" => $productId];
		$statement->execute($parameters);
		// grab the product from mySQL
		try {
			$product = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$product = new Product($row["productId"], $row["productProfileId"], $row["productTitle"], $row["productDescription"]);
			}
		} catch(\Exception $exception) {
			// if the row couldn't be converted, rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return ($product);
	}

	/**
	 * gets the Product by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param int $productProfileId profile id to search by
	 * @return \SplFixedArray SplFixedArray of Products found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProductByProductProfileId(\PDO $pdo, int $productProfileId): \SPLFixedArray {
		// sanitize the profile id before searching
		if($productProfileId <= 0) {
			throw(new \RangeException("product profile id must be positive"));
		}
		// create query template
		$query = "SELECT productId, productProfileId, productTitle, productDescription FROM product WHERE productProfileId = :productProfileId";
		$statement = $pdo->prepare($query);
		// bind the product profile id to the place holder in the template
		$parameters = ["productProfileId" => $productProfileId];
		$statement->execute($parameters);
		// build an array of products
		$products = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$product = new Product($row["productId"], $row["productProfileId"], $row["productTitle"], $row["productDescription"]);
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
		$query = "SELECT productId, productProfileId, productTitle, productDescription FROM product";
		$statement = $pdo->prepare($query);
		$statement->execute();
		// build an array of products
		$products = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$product = new Product($row["productId"], $row["productProfileId"], $row["productTitle"], $row["productDescription"]);
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
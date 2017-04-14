-- prepare disk to create tables
DROP TABLE IF EXISTS favorite;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS profile;
-- create the entity 'profile'
CREATE TABLE profile (
	profileId INT UNSIGNED AUTO_INCREMENT NOT NULL,
	profileActivationToken CHAR(32),
	profileAtHandle VARCHAR(32) NOT NULL,
	profileEmail VARCHAR(128) UNIQUE NOT NULL,
	profileHash	CHAR(128) NOT NULL,
	profilePhone VARCHAR(32),
	profileSalt CHAR(64) NOT NULL,
	-- require all emails to be unique
	UNIQUE(profileEmail),
	-- require all AtHandle to be unique
	UNIQUE(profileAtHandle),
	-- require all profileID to be unique by designating it as the Primary Key of this Entity
	PRIMARY KEY(profileId)
);
-- create the entity 'product'
CREATE TABLE product (
	productId INT UNSIGNED AUTO_INCREMENT NOT NULL,
	productProfileId INT UNSIGNED NOT NULL,
	productTitle VARCHAR(140) NOT NULL,
	productDescription DATETIME(6) NOT NULL,
	INDEX(productProfileId),
	FOREIGN KEY(productProfileId) REFERENCES profile(profileId),
	PRIMARY KEY(productId)
);
-- create the entity 'favorite'
CREATE TABLE favorite (
	favProfileId INT UNSIGNED NOT NULL,
	favProductId INT UNSIGNED NOT NULL,
	INDEX(favProfileId),
	INDEX(favProductId),
	FOREIGN KEY(favProfileId) REFERENCES profile(profileId),
	FOREIGN KEY(favProductId) REFERENCES product(productId),
	PRIMARY KEY(favProfileId, favProductId)
);

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Conceptual Model</title>
		<!-- Custom CSS -->
		<link rel="stylesheet" href="../css/data-design-style.css" type="text/css" />
		<!--		Custom JavaScript-->
		<script type="text-javascript" src="../js/data-design-script.js"></script>
	</head>
	<body>
		<header>
			<h1>Conceptual Model</h1>
		</header>
		<main>
			<h2>Entities & Attributes</h2>
			<h3>PROFILE</h3>
				<ul>
					<li>profileId (primary key)</li>
					<li>profileActivationToken (for account verification)</li>
					<li>profileAtHandle</li>
					<li>profileEmail</li>
					<li>profileHash (for account password)</li>
					<li>profilePhone</li>
					<li>profileSalt (for account password)</li>
				</ul>
			<h3>PRODUCT</h3>
				<ul>
					<li>productId (primary key)</li>
					<li>productProfileId (foreign key)</li>
					<li>productContent</li>
					<li>productDate</li>
				</ul>
			<h3>FAVORITE</h3>
				<ul>
					<li>favProfileId (foreign key)</li>
					<li>favProductId (foreign key)</li>
					<li>favDate</li>
					<li>Relations</li>
				</ul>
			<h3>Relations</h3>
				<ul>
					<li>1 Profile can favorite many Products - (1 to n)</li>
					<li>Many Profiles can favorite many Products - (m to n)</li>
					<li>Many Products can have many Favorites - (m to n)</li>
				</ul>
		</main>
	</body>
</html>



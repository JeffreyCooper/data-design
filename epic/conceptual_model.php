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
			<p>PROFILE

				profileId (primary key)
				profileActivationToken (for account verification)
				profileAtHandle
				profileEmail
				profileHash (for account password)
				profilePhone
				profileSalt (for account password)
				TWEET

				tweetId (primary key)
				tweetProfileId (foreign key)
				tweetContent
				tweetDate
				LIKE

				likeProfileId (foreign key)
				likeTweetId (foreign key)
				likeDate
				Relations

				One Profile can write many Tweets - (1 to n)
				Many Profiles can like many Tweets - (m to n)
				Many Tweets can have many Likes - (m to n)
			</p>
			<!--Add relevant image-->
			<img src="images/sandbox-normandy-beach.jpg" alt="Sandbox of Normandy Beach" />
			<p>Want a surprise? <a href="http://www.swminiart.com" target="_blank">Click!</a></p>
			<p>Want another surprise? <a href="about/index.php">Here you go!</a></p>

			<h3>A List of Things</h3>
			<ul>
				<li>Mom</li>
				<li>Dad</li>
				<li>Ju</li>
			</ul>
			<ol>
				<li>Jan</li>
				<li>Bob</li>
				<li>Josh</li>
				<li>Bradley</li>
			</ol>

			<h3>Cats &amp; Dogs</h3>
			<table>
				<tr>
					<th>Cats</th>
					<th>Dogs</th>
				</tr>
				<tr>
					<td>Tabby</td>
					<td>Weiner</td>
				</tr>
				<tr>
					<td>Tortoise</td>
					<td>Labrador</td>
				</tr>
				<tr>
					<td>Sheba</td>
					<td>Dori</td>
				</tr>
				<tr>
					<td>Meow</td>
					<td>Ruff</td>
				</tr>
			</table>
		</main>
	</body>
</html>



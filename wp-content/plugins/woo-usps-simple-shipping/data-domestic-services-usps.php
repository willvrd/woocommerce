<?php

/*  Domestic services. The names are displayed to the user
	name =>     Name of the service
	services => Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
*/

return array(

	//Priority Mail Express®
	'EXPRESS_MAIL' => array(
		'name'  => 'Priority Mail Express&#8482;',

		'services' => array(
			'2'  => "Priority Mail Express&#8482;, Hold for Pickup",
			'3'  => "Priority Mail Express&#8482;",
			'23' => "Priority Mail Express&#8482;, Sunday/Holiday",
		)
	),

	//Priority Mail®
	'PRIORITY_MAIL' => array(
		'name'  => "Priority Mail&#0174;",

		'services' => array(
			'1'  => "Priority Mail&#0174;",
			'33' => "Priority Mail&#0174;, Hold For Pickup",
			'18' => "Priority Mail&#0174; Keys and IDs",
			'47' => "Priority Mail&#0174; Regional Rate Box A",
			'48' => "Priority Mail&#0174; Regional Rate Box A, Hold For Pickup",
			'49' => "Priority Mail&#0174; Regional Rate Box B",
			'50' => "Priority Mail&#0174; Regional Rate Box B, Hold For Pickup",
		)
	),

	//First-Class Mail®
	'FIRST_CLASS' => array(
		'name'  => 'First-Class Mail&#0174;',

		'services' => array(
			'0A'  => "First-Class Mail&#0174; Postcards",
			'0B'  => "First-Class Mail&#0174; Letter",
			'0C'  => "First-Class Mail&#0174; Large Envelope",
			'0D'  => "First-Class Mail&#0174; Parcel",
			'12' => "First-Class&#8482; Postcard Stamped",
			'15' => "First-Class&#8482; Large Postcards",
			'19' => "First-Class&#8482; Keys and IDs",
			'61' => "First-Class&#8482; Package Service",
			'53' => "First-Class&#8482; Package Service, Hold For Pickup",
			'78' => "First-Class Mail&#0174; Metered Letter"
		)
	),

	//Standard Post™
	'STANDARD_POST' => array(
		'name'  => 'USPS Retail Ground&#8482;',

		'services' => array(
			'4'  => "USPS Retail Ground&#8482;"
		)
	),

	//Media Mail®
	'MEDIA_MAIL' => array(
		'name'  => 'Media Mail',

		'services' => array(
			'6'  => "Media Mail"
		)
	),

	//Library Mail
	'LIBRARY_MAIL' => array(
		'name'  => "Library Mail",

		'services' => array(
			'7'  => "Library Mail"
		)
	)
);